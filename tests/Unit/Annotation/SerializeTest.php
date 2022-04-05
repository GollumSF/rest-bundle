<?php

namespace Test\GollumSF\RestBundle\Unit\Annotation;

use GollumSF\RestBundle\Annotation\Serialize;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class SerializeTest extends TestCase
{
	
	public function provideConstructLegacy() {
		return [
			[ [],  [], [], Response::HTTP_OK ],
			[ [
				'headers' => ['header_key' => 'header_value']
			],  ['header_key' => 'header_value'], [], Response::HTTP_OK ],
			[ [
				'groups' => 'group1'
			],  [], ['group1'], Response::HTTP_OK ],
			[ [
				'groups' => [ 'group1' ]
			],  [], ['group1'], Response::HTTP_OK ],
			[ [
				'value' => 'group1'
			],  [], ['group1'], Response::HTTP_OK ],
			[ [
				'value' => [ 'group1' ]
			],  [], ['group1'], Response::HTTP_OK ],
			[ [
				'code' => Response::HTTP_INTERNAL_SERVER_ERROR
			],  [], [], Response::HTTP_INTERNAL_SERVER_ERROR ],
		];
	}

	/**
	 * @dataProvider provideConstructLegacy
	 */
	public function testConstructLegacy($param, $headers, $groups, $code) {
		$annotation = new Serialize($param);
		$this->assertEquals($annotation->getHeaders(), $headers);
		$this->assertEquals($annotation->getGroups(), $groups);
		$this->assertEquals($annotation->getCode(), $code);
	}
	
	public function provideConstruct() {
		return [
			[ [], [], Response::HTTP_OK, [] ],
			[ [ 'header_key' => 'header_value' ], [], Response::HTTP_OK, [] ],
			[ [], 'group1', Response::HTTP_OK, [ 'group1' ] ],
			[ [], [ 'group1' ], Response::HTTP_OK, [ 'group1' ] ],
			[ [], [], Response::HTTP_INTERNAL_SERVER_ERROR, [] ],
		];
	}
	
	/**
	 * @dataProvider provideConstruct
	 */
	public function testConstruct($headers, $groups, $code, $groupsResult) {
		$annotation = new Serialize($code, $groups, $headers);
		$this->assertEquals($annotation->getHeaders(), $headers);
		$this->assertEquals($annotation->getGroups(), $groupsResult);
		$this->assertEquals($annotation->getCode(), $code);
	}
	
}
