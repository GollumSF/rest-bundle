<?php
namespace Test\GollumSF\RestBundle\Unit\Request\ParamConverter;

use GollumSF\RestBundle\Request\ParamConverter\PostRestParamConverter;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use GollumSF\RestBundle\Annotation\Unserialize;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\SerializerInterface;

class PostRestParamConverterTest extends TestCase {
	
	public function providerApply() {
		return [
			[  new Unserialize(['name' => 'NAME']), 'BAD_NAME', null, false ],
			[  new Unserialize(['name' => 'NAME']), 'NAME', 'value', false ],
		];
	}
	
	/**
	 * @dataProvider providerApply
	 */
	public function testApply($annotation, $configurationName, $requestValue, $result) {

		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
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

		$configuration
			->expects($this->at(0))
			->method('getName')
			->willReturn($configurationName)
		;
		$configuration
			->method('getClass')
			->willReturn(\stdClass::class)
		;

		$attributes
			->expects($this->at(0))
			->method('get')
			->with('_'.Unserialize::ALIAS_NAME)
			->willReturn($annotation)
		;
		
		if ($annotation->getName() === $configurationName) {
			$attributes
				->expects($this->at(1))
				->method('get')
				->with($configurationName)
				->willReturn($requestValue)
			;
		}
		
		$postRestParamConverter = new PostRestParamConverter($serializer);
		
		$this->assertEquals(
			$postRestParamConverter->apply($request, $configuration), $result
		);
	}

	public function testApplyDeserialize() {

		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$annotation = new Unserialize(['name' => 'NAME']);
		$configurationName = 'NAME';
		$entity = new \stdClass();

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

		$configuration
			->expects($this->at(0))
			->method('getName')
			->willReturn($configurationName)
		;
		$configuration
			->method('getClass')
			->willReturn(\stdClass::class)
		;

		$attributes
			->expects($this->at(0))
			->method('get')
			->with('_'.Unserialize::ALIAS_NAME)
			->willReturn($annotation)
		;
		$attributes
			->expects($this->at(1))
			->method('get')
			->with($configurationName)
			->willReturn(null)
		;
		$attributes
			->expects($this->at(2))
			->method('get')
			->with($configurationName)
			->willReturn(null)
		;
		$attributes
			->expects($this->at(3))
			->method('set')
			->with($configurationName, $entity)
		;
		$attributes
			->expects($this->at(4))
			->method('set')
			->with('_'.Unserialize::ALIAS_NAME.'_class')
		;

		$request
			->expects($this->once())
			->method('getContent')
			->willReturn(['CONTENT'])
		;
		$serializer
			->expects($this->once())
			->method('deserialize')
			->with(['CONTENT'], \stdClass::class, 'json',[
				'groups' => [],
			])
			->willReturn($entity)
		;

		$postRestParamConverter = new PostRestParamConverter($serializer);

		$this->assertEquals(
			$postRestParamConverter->apply($request, $configuration), true
		);
	}

	public function testApplyDeserializeMissingConstructorArgumentsException() {

		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$annotation = new Unserialize(['name' => 'NAME']);
		$configurationName = 'NAME';
		$entity = new \stdClass();

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

		$configuration
			->expects($this->at(0))
			->method('getName')
			->willReturn($configurationName)
		;
		$configuration
			->method('getClass')
			->willReturn(\stdClass::class)
		;

		$attributes
			->expects($this->at(0))
			->method('get')
			->with('_'.Unserialize::ALIAS_NAME)
			->willReturn($annotation)
		;
		$attributes
			->expects($this->at(1))
			->method('get')
			->with($configurationName)
			->willReturn(null)
		;
		$attributes
			->expects($this->at(2))
			->method('get')
			->with($configurationName)
			->willReturn(null)
		;

		$request
			->expects($this->once())
			->method('getContent')
			->willReturn(['CONTENT'])
		;
		$serializer
			->expects($this->once())
			->method('deserialize')
			->with(['CONTENT'], \stdClass::class, 'json',[
				'groups' => [],
			])
			->willThrowException(new MissingConstructorArgumentsException())
		;

		$this->expectException(BadRequestHttpException::class);
		$postRestParamConverter = new PostRestParamConverter($serializer);
		$postRestParamConverter->apply($request, $configuration);
	}

	public function testApplyDeserializeUnexpectedValueException() {

		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$annotation = new Unserialize(['name' => 'NAME']);
		$configurationName = 'NAME';
		$entity = new \stdClass();

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

		$configuration
			->expects($this->at(0))
			->method('getName')
			->willReturn($configurationName)
		;
		$configuration
			->method('getClass')
			->willReturn(\stdClass::class)
		;

		$attributes
			->expects($this->at(0))
			->method('get')
			->with('_'.Unserialize::ALIAS_NAME)
			->willReturn($annotation)
		;
		$attributes
			->expects($this->at(1))
			->method('get')
			->with($configurationName)
			->willReturn(null)
		;
		$attributes
			->expects($this->at(2))
			->method('get')
			->with($configurationName)
			->willReturn(null)
		;

		$request
			->expects($this->once())
			->method('getContent')
			->willReturn(['CONTENT'])
		;
		$serializer
			->expects($this->once())
			->method('deserialize')
			->with(['CONTENT'], \stdClass::class, 'json',[
				'groups' => [],
			])
			->willThrowException(new \UnexpectedValueException())
		;

		$this->expectException(BadRequestHttpException::class);
		$postRestParamConverter = new PostRestParamConverter($serializer);
		$postRestParamConverter->apply($request, $configuration);
	}
	
	public function testApplyDoctrineParamConverter() {

		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$annotation = new Unserialize(['name' => 'NAME']);
		$configurationName = 'NAME';
		
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

		$configuration
			->expects($this->at(0))
			->method('getName')
			->willReturn($configurationName)
		;
		$configuration
			->expects($this->at(1))
			->method('getClass')
			->willReturn(\stdClass::class)
		;
		$configuration
			->expects($this->at(2))
			->method('isOptional')
			->willReturn(false)
		;
		$configuration
			->expects($this->at(3))
			->method('setIsOptional')
			->with(true)
		;
		$configuration
			->expects($this->at(4))
			->method('setIsOptional')
			->with(false)
		;

		$attributes
			->expects($this->at(0))
			->method('get')
			->with('_'.Unserialize::ALIAS_NAME)
			->willReturn($annotation)
		;

		$attributes
			->expects($this->at(1))
			->method('get')
			->with($configurationName)
			->willReturn(null)
		;
		$attributes
			->expects($this->at(2))
			->method('get')
			->with($configurationName)
			->willReturn(new \stdClass())
		;
		$attributes
			->expects($this->at(3))
			->method('set')
			->with('_'.Unserialize::ALIAS_NAME.'_class')
			->willReturn(\stdClass::class)
		;

		$doctrineParamConverter = $this->getMockBuilder(DoctrineParamConverter::class)->disableOriginalConstructor()->getMock();

		$doctrineParamConverter
			->expects($this->at(0))
			->method('supports')
			->with($configuration)
			->willReturn(true)
		;
		$doctrineParamConverter
			->expects($this->at(1))
			->method('apply')
			->with($request, $configuration)
		;
		
		$postRestParamConverter = new PostRestParamConverter($serializer);
		$postRestParamConverter->setDoctrineParamConverter($doctrineParamConverter);
		
		$this->assertTrue(
			$postRestParamConverter->apply($request, $configuration)
		);
	}
	
	public function testSupports() {

		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$postRestParamConverter = new PostRestParamConverter($serializer);
		
		$this->assertTrue(
			$postRestParamConverter->supports(
				$this->getMockBuilder(ParamConverter::class)
					->disableOriginalConstructor()
					->getMock()
			)
		);
	}
	
}