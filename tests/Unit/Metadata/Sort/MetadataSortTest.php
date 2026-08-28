<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Sort;

use GollumSF\RestBundle\Metadata\Sort\MetadataSort;
use GollumSF\RestBundle\Metadata\Sort\MetadataSortable;
use PHPUnit\Framework\TestCase;

class MetadataSortTest extends TestCase {

	public function testEmpty() {
		$metadata = new MetadataSort();
		$this->assertTrue($metadata->isEmpty());
		$this->assertEquals([], $metadata->all());
		$this->assertEquals([], $metadata->getKeys());
		$this->assertFalse($metadata->has('title'));
		$this->assertNull($metadata->get('title'));
	}

	public function testSortables() {
		$title = new MetadataSortable('title', 'title');
		$author = new MetadataSortable('author', 'author.name');

		$metadata = new MetadataSort([ $title, $author ]);

		$this->assertFalse($metadata->isEmpty());
		$this->assertEquals([ 'title', 'author' ], $metadata->getKeys());
		$this->assertEquals([ 'title' => $title, 'author' => $author ], $metadata->all());
		$this->assertTrue($metadata->has('author'));
		$this->assertSame($author, $metadata->get('author'));
		$this->assertFalse($metadata->has('unknown'));
		$this->assertNull($metadata->get('unknown'));
	}

	public function testTheLastSortableWinsOnADuplicatedKey() {
		$first = new MetadataSortable('title', 'title');
		$second = new MetadataSortable('title', 'other');

		$metadata = new MetadataSort([ $first, $second ]);

		$this->assertEquals([ 'title' ], $metadata->getKeys());
		$this->assertSame($second, $metadata->get('title'));
	}
}
