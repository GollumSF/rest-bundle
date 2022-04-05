<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Validate;

use GollumSF\RestBundle\Metadata\Validate\MetadataValidate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class MetadataValidateTest extends TestCase {
	
	public function provideConstruct() {
		return [
			[ [] ],
			[ [ 'group1' ] ],
		];
	}
	
	/**
	 * @dataProvider provideConstruct
	 */
	public function testConstruct($groups) {
		$annotation = new MetadataValidate($groups);
		$this->assertEquals($annotation->getGroups(), $groups);
	}
	
}
