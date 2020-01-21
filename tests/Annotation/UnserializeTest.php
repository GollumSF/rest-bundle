<?php

namespace Test\GollumSF\RestBundle\Annotation;

use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Annotation\Unserialize;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class UnserializeTest extends TestCase
{
	
	public function provideConstruct() {
		return [
			[ [],  '', [], true ],
			[ [
				'value' => 'anno_name'
			],  'anno_name', [], Response::HTTP_OK ],
			[ [
				'name' => 'anno_name'
			],  'anno_name', [], Response::HTTP_OK ],
			[ [
				'groups' => 'group1'
			],  '', 'group1', true ],
			[ [
				'groups' => [ 'group1' ]
			],  '', ['group1'], true ],
			[ [
				'save' => false
			],  '', [], false],
		];
	}

	/**
	 * @dataProvider provideConstruct
	 */
	public function testConstruct($param, $name, $groups, $save) {
		$annotation = new Unserialize($param);
		$this->assertEquals($annotation->name, $name);
		$this->assertEquals($annotation->groups, $groups);
		$this->assertEquals($annotation->save, $save);
	}
	
}