<?php

namespace Test\GollumSF\RestBundle\Unit\Annotation;

use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Annotation\Unserialize;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class UnserializeTest extends TestCase
{
	public function provideConstructLegacy() {
		return [
			[ [],  '', [], true ],
			[ [
				'name' => 'anno_name'
			],  'anno_name', [], Response::HTTP_OK ],
			[ [
				'value' => 'anno_name'
			],  'anno_name', [], Response::HTTP_OK ],
			[ [
				'groups' => 'group1'
			],  '', ['group1'], true ],
			[ [
				'groups' => [ 'group1' ]
			],  '', ['group1'], true ],
			[ [
				'save' => false
			],  '', [], false],
		];
	}
	
	/**
	 * @dataProvider provideConstructLegacy
	 */
	public function testConstructLegacy($param, $name, $groups, $save) {
		$annotation = new Unserialize($param);
		$this->assertEquals($annotation->getName(), $name);
		$this->assertEquals($annotation->getGroups(), $groups);
		$this->assertEquals($annotation->isSave(), $save);
		$this->assertEquals($annotation->getAliasName(), Unserialize::ALIAS_NAME);
		$this->assertFalse($annotation->allowArray());
	}
	
	public function provideConstruct() {
		return [
			[ '', [], true, [] ],
			[ 'anno_name', [], Response::HTTP_OK, [] ],
			[ '', 'group1', true, ['group1'] ],
			[ '', [ 'group1' ], true, [ 'group1' ] ],
			[ '', [], false, []],
		];
	}
	
	/**
	 * @dataProvider provideConstruct
	 */
	public function testConstruct($name, $groups, $save, $groupsResult) {
		$annotation = new Unserialize($name, $groups, $save);
		$this->assertEquals($annotation->getName(), $name);
		$this->assertEquals($annotation->getGroups(), $groupsResult);
		$this->assertEquals($annotation->isSave(), $save);
		$this->assertEquals($annotation->getAliasName(), Unserialize::ALIAS_NAME);
		$this->assertFalse($annotation->allowArray());
	}
	
}
