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
			->expects($this->once())
			->method('getName')
			->willReturn($configurationName)
		;
		$configuration
			->expects($this->once())
			->method('getClass')
			->willReturn(\stdClass::class)
		;
		
		$attributes
			->expects($this->exactly($annotation->getName() === $configurationName ? 2 : 1))
			->method('get')
			->withConsecutive(
				[ '_'.Unserialize::ALIAS_NAME ],
				[ $configurationName ]
			)
			->willReturnOnConsecutiveCalls(
				$annotation,
				$requestValue
			)
		;
		
		$postRestParamConverter = new PostRestParamConverter($serializer);
		
		$this->assertEquals(
			$postRestParamConverter->apply($request, $configuration), $result
		);
	}

	public function testApplyDeserializeS() {

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
			->expects($this->once())
			->method('getName')
			->willReturn($configurationName)
		;
		$configuration
			->expects($this->once())
			->method('getClass')
			->willReturn(\stdClass::class)
		;

		$attributes
			->expects($this->exactly(2))
			->method('get')
			->withConsecutive(
				[ '_'.Unserialize::ALIAS_NAME ],
				[ $configurationName ]
			)
			->willReturnOnConsecutiveCalls(
				$annotation,
				null
			)
		;
		
		$attributes
			->expects($this->exactly(2))
			->method('set')
			->withConsecutive(
				[ $configurationName, $entity ],
				[ '_'.Unserialize::ALIAS_NAME.'_class', \stdClass::class ]
			)
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
			->expects($this->once())
			->method('getName')
			->willReturn($configurationName)
		;
		$configuration
			->expects($this->once())
			->method('getClass')
			->willReturn(\stdClass::class)
		;

		$attributes
			->expects($this->exactly(2))
			->method('get')
			->withConsecutive(
				[ '_'.Unserialize::ALIAS_NAME ],
				[ $configurationName  ]
			)
			->willReturnOnConsecutiveCalls(
				$annotation,
				null
			)
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
			->willThrowException(new MissingConstructorArgumentsException(''))
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
			->expects($this->once())
			->method('getName')
			->willReturn($configurationName)
		;
		$configuration
			->expects($this->once())
			->method('getClass')
			->willReturn(\stdClass::class)
		;
		
		$attributes
			->expects($this->exactly(2))
			->method('get')
			->withConsecutive(
				[ '_'.Unserialize::ALIAS_NAME ],
				[ $configurationName  ]
			)
			->willReturnOnConsecutiveCalls(
				$annotation,
				null
			)
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
			->expects($this->once())
			->method('getName')
			->willReturn($configurationName)
		;
		$configuration
			->expects($this->once())
			->method('getClass')
			->willReturn(\stdClass::class)
		;
		
		
		$attributes
			->expects($this->exactly(2))
			->method('get')
			->withConsecutive(
				[ '_'.Unserialize::ALIAS_NAME ],
				[ $configurationName  ]
			)
			->willReturnOnConsecutiveCalls(
				$annotation,
				null
			)
		;
		
		$attributes
			->expects($this->once())
			->method('set')
			->with('_'.Unserialize::ALIAS_NAME.'_class', \stdClass::class)
		;

		$doctrineParamConverter = $this->getMockBuilder(DoctrineParamConverter::class)->disableOriginalConstructor()->getMock();

		$doctrineParamConverter
			->expects($this->once())
			->method('supports')
			->with($configuration)
			->willReturn(true)
		;
		$doctrineParamConverter
			->expects($this->once())
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
		
		
		$at = -1;
		$hasAssert = [];
		$getAssert = [];
		
		$i = -1;
		if (isset($options['id']) && $options['id']) {
			if (!\is_array($options['id'])) {
				$hasAssert[++$i] = [ [ 'ID' ], $haseName ];
				if ($haseName) {
					$getAssert[++$i] = [ [ 'ID' ], $nameAtt ];
				} else {
					$hasAssert[++$i] = [ [ 'id' ], $hasId ];
					if ($hasId) {
						$getAssert[++$i] = [ [ 'id' ], $nameAtt ];
					}
				}
			}
		}
		if (!isset($options['id'])) {
			$hasAssert[++$i] = [ [ 'NAME' ], false ];
			$hasAssert[++$i] = [ [ 'id' ], $hasId ];
			if ($hasId) {
				$getAssert[++$i] = [ [ 'id' ], 42 ];
			}
		}
		
		$attributes
			->expects($this->exactly(count($hasAssert)))
			->method('has')
			->willReturnCallback(function($name) use (&$at, &$hasAssert) {
				$at++;
				$this->assertTrue(isset($hasAssert[$at]));
				$this->assertEquals($hasAssert[$at][0], [ $name ]);
				return $hasAssert[$at][1];
			})
		;
		$attributes
			->expects($this->exactly(count($getAssert)))
			->method('get')
			->willReturnCallback(function($name) use (&$at, &$getAssert) {
				$at++;
				$this->assertTrue(isset($getAssert[$at]));
				$this->assertEquals($getAssert[$at][0], [ $name ]);
				return $getAssert[$at][1];
			})
		;
		
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
