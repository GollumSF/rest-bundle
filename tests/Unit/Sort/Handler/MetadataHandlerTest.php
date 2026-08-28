<?php
namespace Test\GollumSF\RestBundle\Unit\Sort\Handler;

use GollumSF\RestBundle\Metadata\Sort\MetadataSort;
use GollumSF\RestBundle\Metadata\Sort\MetadataSortable;
use GollumSF\RestBundle\Metadata\Sort\MetadataSortManagerInterface;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Sort\ApiSortContext;
use GollumSF\RestBundle\Sort\ApiSorterInterface;
use GollumSF\RestBundle\Sort\Handler\MetadataHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MetadataHandlerTest extends TestCase {

	private function createHandler(MetadataSort $metadata, array $sorters = []): MetadataHandler {
		$manager = $this->createMock(MetadataSortManagerInterface::class);
		$manager
			->method('getMetadata')
			->with('ENTITY', 'CONTROLLER', 'ACTION')
			->willReturn($metadata)
		;
		$locator = new ServiceLocator(array_map(
			static function ($sorter) { return static function () use ($sorter) { return $sorter; }; },
			$sorters
		));
		return new MetadataHandler($manager, $locator);
	}

	private function context(): ApiSortContext {
		return new ApiSortContext('ENTITY', 'CONTROLLER', 'ACTION');
	}

	public function testNoWhitelistLeavesTheKeyToTheNextHandler() {
		$handler = $this->createHandler(new MetadataSort());
		$this->assertNull($handler->getOrder($this->context(), 'title', null));
	}

	public static function provideDirection() {
		return [
			'direction asked for'      => [ Direction::DESC, null, Direction::DESC ],
			'default of the sortable'  => [ null, Direction::DESC, Direction::DESC ],
			'asked wins over default'  => [ Direction::ASC, Direction::DESC, Direction::ASC ],
			'ascending by default'     => [ null, null, Direction::ASC ],
		];
	}

	#[DataProvider('provideDirection')]
	public function testDirection($asked, $sortableDirection, $expected) {

		$handler = $this->createHandler(new MetadataSort([
			new MetadataSortable('author', 'author.name', null, $sortableDirection),
		]));

		$order = $handler->getOrder($this->context(), 'author', $asked);

		$this->assertEquals('author', $order->getKey());
		$this->assertEquals('author.name', $order->getPath());
		$this->assertEquals($expected, $order->getDirection());
		$this->assertNull($order->getSorter());
	}

	public function testAKeyOutsideTheWhitelistIsRejected() {

		$handler = $this->createHandler(new MetadataSort([
			new MetadataSortable('title', 'title'),
			new MetadataSortable('author', 'author.name'),
		]));

		$this->expectException(BadRequestHttpException::class);
		$this->expectExceptionMessage('Order "secret" is not sortable, expected one of: title, author');

		$handler->getOrder($this->context(), 'secret', null);
	}

	public function testSorterIsResolvedFromTheLocator() {

		$sorter = $this->createMock(ApiSorterInterface::class);
		$handler = $this->createHandler(
			new MetadataSort([ new MetadataSortable('popularity', null, 'SORTER_ID') ]),
			[ 'SORTER_ID' => $sorter ]
		);

		$order = $handler->getOrder($this->context(), 'popularity', Direction::DESC);

		$this->assertSame($sorter, $order->getSorter());
		$this->assertNull($order->getPath());
		$this->assertEquals(Direction::DESC, $order->getDirection());
	}

	public function testAnUnregisteredSorterIsAProgrammingError() {

		$handler = $this->createHandler(
			new MetadataSort([ new MetadataSortable('popularity', null, 'SORTER_ID') ])
		);

		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Sorter "SORTER_ID" not found, did you tag it with "gollumsf_rest.sorter"?');

		$handler->getOrder($this->context(), 'popularity', null);
	}
}
