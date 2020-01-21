<?php

namespace Test\GollumSF\RestBundle\Annotation;

use GollumSF\RestBundle\Annotation\Serialize;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class SerializeTest extends TestCase
{
	
	public function provideConstruct() {
		return [
			[ [],  [], [], Response::HTTP_OK ],
			[ [
				'headers' => ['header_key' => 'header_value']
			],  ['header_key' => 'header_value'], [], Response::HTTP_OK ],
			[ [
				'groups' => 'group1'
			],  [], 'group1', Response::HTTP_OK ],
			[ [
				'groups' => [ 'group1' ]
			],  [], ['group1'], Response::HTTP_OK ],
			[ [
				'code' => Response::HTTP_INTERNAL_SERVER_ERROR
			],  [], [], Response::HTTP_INTERNAL_SERVER_ERROR ],
		];
	}

	/**
	 * @dataProvider provideConstruct
	 */
	public function testConstruct($param, $headers, $groups, $code) {
		$annotation = new Serialize($param);
		$this->assertEquals($annotation->headers, $headers);
		$this->assertEquals($annotation->groups, $groups);
		$this->assertEquals($annotation->code, $code);
	}
	
}