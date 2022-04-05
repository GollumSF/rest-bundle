<?php
namespace Test\GollumSF\RestBundle\Unit\Request\ParamConverter;

use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerAction;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerActionExtractorInterface;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserialize;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserializeManagerInterface;
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
	
	public function providerApplySuccess() {
		return [
			[  new MetadataUnserialize('NAME', [], false), 'BAD_NAME', null, false ],
			[  new MetadataUnserialize('NAME', [], false), 'NAME', 'value', false ],
		];
	}
	
	/**
	 * @dataProvider providerApplySuccess
	 */
	public function testApplySuccess($metadata, $configurationName, $requestValue, $result) {

		$serializer                 = $this->getMockForAbstractClass(SerializerInterface::class);
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$attributes = $this->getMockBuilder(ParameterBag::class)->disableOriginalConstructor()->getMock();

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request->attributes = $attributes;
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		
		$configuration = $this->getMockBuilder(ParamConverter::class)->disableOriginalConstructor()->getMock();
		
		$controllerActionExtractor
			->expects($this->once())
			->method('extractFromRequest')
			->with($request)
			->willReturn($controllerAction)
		;
		
		$metadataUnserializeManager
			->expects($this->once())
			->method('getMetadata')
			->with('CONTROLLER', 'ACTION')
			->willReturn($metadata)
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
			->expects($this->exactly($metadata->getName() === $configurationName ? 1 : 0))
			->method('get')
			->with($configurationName)
			->willReturn($requestValue)
		;
		
		$postRestParamConverter = new PostRestParamConverter(
			$serializer,
			$controllerActionExtractor,
			$metadataUnserializeManager
		);
		
		$this->assertEquals(
			$postRestParamConverter->apply($request, $configuration), $result
		);
	}

	public function testApplyDeserialize() {

		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		
		$metadata = new MetadataUnserialize('NAME', [], false);
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
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		
		$controllerActionExtractor
			->expects($this->once())
			->method('extractFromRequest')
			->with($request)
			->willReturn($controllerAction)
		;
		
		$metadataUnserializeManager
			->expects($this->once())
			->method('getMetadata')
			->with('CONTROLLER', 'ACTION')
			->willReturn($metadata)
		;

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
			->expects($this->once())
			->method('get')
			->with($configurationName)
			->willReturn(null)
		;
		
		$attributes
			->expects($this->exactly(2))
			->method('set')
			->withConsecutive(
				[ $configurationName, $entity ],
				[ Unserialize::REQUEST_ATTRIBUTE_CLASS, \stdClass::class ]
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

		$postRestParamConverter = new PostRestParamConverterTestIdentifier(
			$serializer,
			$controllerActionExtractor,
			$metadataUnserializeManager
		);

		$this->assertEquals(
			$postRestParamConverter->apply($request, $configuration), true
		);
	}

	public function testApplyDeserializeMissingConstructorArgumentsException() {

		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadata = new MetadataUnserialize('NAME', [], false);
		$configurationName = 'NAME';

		$attributes = $this->getMockBuilder(ParameterBag::class)
			->disableOriginalConstructor()
			->getMock()
		;

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()
			->getMock()
		;
		$request->attributes = $attributes;
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		
		$controllerActionExtractor
			->expects($this->once())
			->method('extractFromRequest')
			->with($request)
			->willReturn($controllerAction)
		;
		
		$metadataUnserializeManager
			->expects($this->once())
			->method('getMetadata')
			->with('CONTROLLER', 'ACTION')
			->willReturn($metadata)
		;

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
			->expects($this->once())
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
			->willThrowException(new MissingConstructorArgumentsException(''))
		;

		$this->expectException(BadRequestHttpException::class);
		$postRestParamConverter = new PostRestParamConverterTestIdentifier(
			$serializer,
			$controllerActionExtractor,
			$metadataUnserializeManager
		);
		$postRestParamConverter->apply($request, $configuration);
	}

	public function testApplyDeserializeUnexpectedValueException() {

		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadata = new MetadataUnserialize('NAME', [], false);
		$configurationName = 'NAME';

		$attributes = $this->getMockBuilder(ParameterBag::class)
			->disableOriginalConstructor()
			->getMock()
		;

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()
			->getMock()
		;
		$request->attributes = $attributes;
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		
		$controllerActionExtractor
			->expects($this->once())
			->method('extractFromRequest')
			->with($request)
			->willReturn($controllerAction)
		;
		
		$metadataUnserializeManager
			->expects($this->once())
			->method('getMetadata')
			->with('CONTROLLER', 'ACTION')
			->willReturn($metadata)
		;

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
			->expects($this->once())
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
		$postRestParamConverter = new PostRestParamConverterTestIdentifier(
			$serializer,
			$controllerActionExtractor,
			$metadataUnserializeManager
		);
		$postRestParamConverter->apply($request, $configuration);
	}
	
	public function testApplyDoctrineParamConverter() {

		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		
		$metadata = new MetadataUnserialize('NAME', [], false);
		$configurationName = 'NAME';
		
		$attributes = $this->getMockBuilder(ParameterBag::class)
			->disableOriginalConstructor()
			->getMock()
		;

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()
			->getMock()
		;
		$request->attributes = $attributes;
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		
		$controllerActionExtractor
			->expects($this->once())
			->method('extractFromRequest')
			->with($request)
			->willReturn($controllerAction)
		;
		
		$metadataUnserializeManager
			->expects($this->once())
			->method('getMetadata')
			->with('CONTROLLER', 'ACTION')
			->willReturn($metadata)
		;

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
			->expects($this->once())
			->method('get')
			->with($configurationName)
			->willReturn(null)
		;
		
		$attributes
			->expects($this->once())
			->method('set')
			->with(Unserialize::REQUEST_ATTRIBUTE_CLASS, \stdClass::class)
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
		
		$postRestParamConverter = new PostRestParamConverterTestIdentifier(
			$serializer,
			$controllerActionExtractor,
			$metadataUnserializeManager
		);
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
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$postRestParamConverter = new PostRestParamConverter(
			$serializer,
			$controllerActionExtractor,
			$metadataUnserializeManager
		);

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
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$postRestParamConverter = new PostRestParamConverter(
			$serializer,
			$controllerActionExtractor,
			$metadataUnserializeManager
		);
		
		$this->assertTrue(
			$postRestParamConverter->supports(
				$this->getMockBuilder(ParamConverter::class)
					->disableOriginalConstructor()
					->getMock()
			)
		);
	}
	
}
