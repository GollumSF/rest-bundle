<?php
namespace Test\GollumSF\RestBundle\Unit\Model;

use GollumSF\RestBundle\Model\Direction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DirectionTest extends TestCase {

	public static function provideIsValid() {
		return [
			[ 'ASC', true ],
			[ 'DESC', true ],
			[ 'asc', false ],
			[ 'BAD_DIRECTION', false ],
			[ '', false ],
		];
	}

	#[DataProvider('provideIsValid')]
	public function testIsValid($value, $expected) {
		$this->assertEquals($expected, Direction::isValid($value));
	}
}
