<?php
namespace Test\GollumSF\RestBundle\Unit\Sort;

use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Sort\ApiOrderParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApiOrderParserTest extends TestCase {

	public static function provideParse() {
		return [
			'single key'                     => [ 'title', null, [ [ 'title', null ] ] ],
			'explicit direction'             => [ 'title:desc', null, [ [ 'title', Direction::DESC ] ] ],
			'uppercase direction'            => [ 'title:DESC', null, [ [ 'title', Direction::DESC ] ] ],
			'deprecated direction parameter' => [ 'title', 'desc', [ [ 'title', Direction::DESC ] ] ],
			'explicit wins over deprecated'  => [ 'title:asc', 'desc', [ [ 'title', Direction::ASC ] ] ],
			'multi keys'                     => [ 'author:asc,id:desc', null, [ [ 'author', Direction::ASC ], [ 'id', Direction::DESC ] ] ],
			'deprecated applies to all'      => [ 'author,id', 'desc', [ [ 'author', Direction::DESC ], [ 'id', Direction::DESC ] ] ],
			'dotted path'                    => [ 'author.name:desc', null, [ [ 'author.name', Direction::DESC ] ] ],
			'spaces trimmed'                 => [ ' author , id : desc ', null, [ [ 'author', null ], [ 'id', Direction::DESC ] ] ],
			'empty items skipped'            => [ 'author,,id', null, [ [ 'author', null ], [ 'id', null ] ] ],
			'invalid direction ignored'      => [ 'title:sideways', null, [ [ 'title', null ] ] ],
			'invalid deprecated ignored'     => [ 'title', 'sideways', [ [ 'title', null ] ] ],
			'null order'                     => [ null, null, [] ],
			'empty order'                    => [ '', null, [] ],
			'only commas'                    => [ ',,', null, [] ],
		];
	}

	#[DataProvider('provideParse')]
	public function testParse($order, $direction, $expected) {
		$this->assertEquals($expected, ApiOrderParser::parse($order, $direction));
	}

	public static function provideParseDirection() {
		return [
			[ 'asc', Direction::ASC ],
			[ 'DESC', Direction::DESC ],
			[ ' desc ', Direction::DESC ],
			[ 'sideways', null ],
			[ '', null ],
			[ null, null ],
		];
	}

	#[DataProvider('provideParseDirection')]
	public function testParseDirection($value, $expected) {
		$this->assertEquals($expected, ApiOrderParser::parseDirection($value));
	}
}
