<?php
namespace Test\GollumSF\RestBundle\Request\ParamConverter;

use GollumSF\RestBundle\Request\ParamConverter\PostRestParamConverter;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use GollumSF\RestBundle\Annotation\Unserialize;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class PostRestParamConverterTest extends TestCase {
	
	public function providerApply() {
		return [
			[  new Unserialize(['name' => 'NAME']), 'NAME', null, null, true ],
			[  new Unserialize(['name' => 'NAME']), 'BAD_NAME', null, null, false ],
			[  new Unserialize(['name' => 'NAME']), 'NAME', 'value', null, false ],
			[  new Unserialize(['name' => 'NAME']), 'NAME', null, 'value', false ],
		];
	}
	
	/**
	 * @dataProvider providerApply
	 */
	public function testApply($annotation, $configurationName, $requestId, $requestName, $result) {

		$attributes = $this->getMockBuilder(ParameterBag::class)
			->disableOriginalConstructor()
			->getMock()
		;

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()
			->getMock()
		;
		$request->attributes = $attributes;
			
		$configuration = $this->getMockBuilder(ParamConverter::class)
			->disableOriginalConstructor()
			->getMock()
		;

		$request
			->method('get')
			->willReturnCallback(function($name) use ($configurationName, $requestId, $requestName) {
				if ($name === 'id') {
					return $requestId;
				}
				if ($name === $configurationName) {
					return $requestName;
				}
				$this->assertTrue(false);
			})
		;

		$configuration
			->method('getName')
			->willReturn($configurationName)
		;
		$configuration
			->method('getClass')
			->willReturn(\stdClass::class)
		;


		$attributes
			->expects($this->once())
			->method('get')
			->with('_'.Unserialize::ALIAS_NAME)
			->willReturn($annotation)
		;
		$attributes
			->method('set')
			->willReturnCallback(function ($name, $value) use ($configurationName) {
				$this->assertEquals($name, $configurationName);
				$this->assertInstanceOf(\stdClass::class, $value);
			})
		;
		
		$postRestParamConverter = new PostRestParamConverter();
		
		$this->assertEquals(
			$postRestParamConverter->apply($request, $configuration), $result
		);
	}
	
	public function testSupports() {

		$postRestParamConverter = new PostRestParamConverter();
		
		$this->assertTrue(
			$postRestParamConverter->supports(
				$this->getMockBuilder(ParamConverter::class)
					->disableOriginalConstructor()
					->getMock()
			)
		);
	}
	
}