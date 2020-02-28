<?php
namespace Test\GollumSF\RestBundle\Unit\Request\ParamConverter;

use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
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

class PostRestParamConverterTestIdentifier extends PostRestParamConverter {
	public $hasIdentifier = false;
	
	protected function hasIdentifier(Request $request, ParamConverter $configuration): bool {
		return $this->hasIdentifier;
	}
}

class PostRestParamConverterTest extends TestCase {
	
	use ReflectionPropertyTrait;
	
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
			->method('set')
			->with($configurationName, $entity)
		;
		$attributes
			->expects($this->at(3))
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

		$postRestParamConverter = new PostRestParamConverterTestIdentifier($serializer);

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
		$postRestParamConverter = new PostRestParamConverterTestIdentifier($serializer);
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
		$postRestParamConverter = new PostRestParamConverterTestIdentifier($serializer);
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
		
		$postRestParamConverter = new PostRestParamConverterTestIdentifier($serializer);
		$postRestParamConverter->setDoctrineParamConverter($doctrineParamConverter);
		$postRestParamConverter->hasIdentifier = true;
		
		$this->assertTrue(
			$postRestParamConverter->apply($request, $configuration)
		);
	}

	public function providerHasIdentifier() {
		return [
			[ [ 'id' => [ 'ID' ] ], false, null, false, true  ],
			[ [ 'id' => 'ID' ]    , true , null, false, false ],
			[ [ 'id' => 'ID' ]    , true , 42  , false, true  ],
			[ [ 'id' => 'ID' ]    , false, null, false, false ],
			[ []                  , false, null, true , true  ],
			[ []                  , false, null, false, false ],
		];
	}
	
	/**
	 * @dataProvider providerHasIdentifier
	 */
	public function testHasIdentifier($options, $haseName, $nameAtt, $hasId, $result) {
		
		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$postRestParamConverter = new PostRestParamConverter($serializer);

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
			->method('getName')
			->willReturn('NAME')
		;

		$configuration
			->method('getOptions')
			->willReturn($options)
		;
		
		$i = -1;
		if (isset($options['id']) && !is_array($options['id'])) {
			$attributes
				->expects($this->at(++$i))
				->method('has')
				->with('ID')
				->willReturn($haseName)
			;
			if ($haseName) {
				$attributes
					->expects($this->at(++$i))
					->method('get')
					->with('ID')
					->willReturn($nameAtt)
				;
			}
		}
		if (!isset($options['id']) ) {
			$attributes
				->expects($this->at(++$i))
				->method('has')
				->with('NAME')
				->willReturn(false)
			;
			$attributes
				->expects($this->at(++$i))
				->method('has')
				->with('id')
				->willReturn($hasId)
			;
			if ($hasId) {
				$attributes
					->expects($this->at(++$i))
					->method('get')
					->with('id')
					->willReturn(42)
				;
			}
		}


		$this->assertEquals(
			$this->reflectionCallMethod($postRestParamConverter, 'hasIdentifier', [$request, $configuration]), $result
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