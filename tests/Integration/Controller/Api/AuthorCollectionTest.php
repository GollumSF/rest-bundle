<?php

namespace Test\GollumSF\RestBundle\Integration\Controller\Api;

/**
 * Author carries a `books` collection, so ordering on `books.title` crosses a to-many
 * relation: the join it needs multiplies the rows, which would inflate the total and cut
 * the page short. The count is taken DISTINCT and the ordering goes through an aggregate.
 */
class AuthorCollectionTest extends AbstractControllerTestCase {

	/**
	 * 40 authors, 50 books: joining the collection yields 56 rows.
	 */
	public function testTheTotalCountsEntitiesNotRows() {

		$this->loadFixture();

		$client = $this->getClient();
		$client->request('GET', '/api/authors?limit=5&order=book:asc');
		$response = $client->getResponse();

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals(40, \json_decode($response->getContent(), true)['total']);
	}

	public static function provideFullPage() {
		return [
			'ascending on the collection'  => [ 'book:asc' ],
			'descending on the collection' => [ 'book:desc' ],
			'plain column'                 => [ 'name:asc' ],
		];
	}

	/**
	 * A page of 5 holds 5 distinct authors, whichever key it is ordered on.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideFullPage')]
	public function testAPageIsNotCutShort($order) {

		$this->loadFixture();

		$client = $this->getClient();
		$client->request('GET', '/api/authors?limit=5&order='.$order);
		$response = $client->getResponse();

		$this->assertEquals(200, $response->getStatusCode());

		$ids = array_column(\json_decode($response->getContent(), true)['data'], 'id');

		$this->assertCount(5, $ids);
		$this->assertEquals($ids, array_unique($ids));
	}

	/**
	 * Both directions really order, and on opposite ends.
	 */
	public function testTheCollectionOrderingIsApplied() {

		$this->loadFixture();

		$client = $this->getClient();

		$client->request('GET', '/api/authors?limit=40&order=book:asc');
		$ascending = array_column(\json_decode($client->getResponse()->getContent(), true)['data'], 'id');

		$client->request('GET', '/api/authors?limit=40&order=book:desc');
		$descending = array_column(\json_decode($client->getResponse()->getContent(), true)['data'], 'id');

		$this->assertCount(40, $ascending);
		$this->assertCount(40, $descending);
		$this->assertNotEquals($ascending, $descending);
		$this->assertEquals([], array_diff($ascending, $descending));
	}

	/**
	 * A query callback joining the collection must not inflate the total either.
	 */
	public function testTheTotalWithAQueryCallbackJoiningTheCollection() {

		$this->loadFixture();

		$repository = $this->getServiceContainer()
			->get('doctrine')
			->getRepository(\Test\GollumSF\RestBundle\ProjectTest\Entity\Author::class)
		;

		$list = $repository->apiFindByOrder(5, 0, new \GollumSF\RestBundle\Model\ApiOrderCollection(), function ($queryBuilder) {
			$queryBuilder->leftJoin('t.books', 'joined_books');
		});

		$this->assertEquals(40, $list->getTotal());
	}
}
