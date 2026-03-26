<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Validate;

use GollumSF\RestBundle\Metadata\Validate\MetadataValidate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\DataProvider;

class MetadataValidateTest extends TestCase {
	
	public static function provideConstruct() {
		return [
			[ [] ],
			[ [ 'group1' ] ],
		];
	}
	
	#[DataProvider('provideConstruct')]
	public function testConstruct($groups) {
		$annotation = new MetadataValidate($groups);
		$this->assertEquals($annotation->getGroups(), $groups);
	}
	
}
