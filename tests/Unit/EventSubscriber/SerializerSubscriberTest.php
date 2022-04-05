<?php
namespace Test\GollumSF\RestBundle\Unit\EventSubscriber;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\Proxy;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerAction;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerActionExtractorInterface;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Annotation\Validate;
use GollumSF\RestBundle\EventSubscriber\SerializerSubscriber;
use GollumSF\RestBundle\Exceptions\UnserializeValidateException;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerialize;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerializeManagerInterface;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserialize;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserializeManagerInterface;
use GollumSF\RestBundle\Metadata\Validate\MetadataValidate;
use GollumSF\RestBundle\Metadata\Validate\MetadataValidateManagerInterface;
use GollumSF\RestBundle\Serializer\Transform\SerializerTransformInterface;
use GollumSF\RestBundle\Serializer\Transform\UnserializerTransformInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class SerializerSubscriberOnKernelControllerArgumentsTest extends SerializerSubscriber {
	
	public $groups;
	
	protected function validate(Request $request, $entity): void {
	}

	protected function isEntity($class): bool {
		return false;
	}

	protected function unserialize(string $content, $entity, string $class, array $groups) {
		$this->groups = $groups;
		return $entity;
	}
}

class SerializerSubscriberOnKernelControllerArgumentsTestSave extends SerializerSubscriberOnKernelControllerArgumentsTest {

	private $em;
	private $isEntity;

	public function __construct(
		SerializerInterface $serializer,
		ControllerActionExtractorInterface $controllerActionExtractor,
		MetadataSerializeManagerInterface $metadataSerializeManager,
		MetadataUnserializeManagerInterface $metadataUnserializeManager,
		MetadataValidateManagerInterface $metadataValidateManager,
		ObjectManager $em,
		bool $isEntity
	) {
		parent::__construct(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);
		$this->em = $em;
		$this->isEntity = $isEntity;
	}

	protected function isEntity($class): bool {
		return $this->isEntity;
	}

	protected function getEntityManagerForClass($entityOrClass): ?ObjectManager {
		return $this->em;
	}
}

class StubEntity implements SerializerTransformInterface, UnserializerTransformInterface{

	private $serializeCallback;
	private $unserializeCallback;
	
	public function __construct(
		$serializeCallback,
		$unserializeCallback
	) {
		$this->serializeCallback = $serializeCallback;
		$this->unserializeCallback = $unserializeCallback;
	}

	public function serializeTransform($data, array $groups) {
		return ($this->serializeCallback)($data, $groups);
	}

	/**
	 * @param $data
	 * @param string[] $groups
	 */
	public function unserializeTransform($data, array $groups): void {
		($this->unserializeCallback)($data, $groups);
	}
}

interface StubSerializer extends SerializerInterface, NormalizerInterface, EncoderInterface, DenormalizerInterface, DecoderInterface {
}

class StubProxy extends \stdClass implements Proxy {
	public function __load() {
	}
	public function __isInitialized() {
	}
}

class SerializerSubscriberTest extends TestCase {
	
	use ReflectionPropertyTrait;
	
	public function testGetSubscribedEvents() {
		$this->assertEquals(SerializerSubscriber::getSubscribedEvents(), [
			KernelEvents::CONTROLLER_ARGUMENTS => [
				['onKernelControllerArguments', -10],
			],
			KernelEvents::VIEW => [
				['onKernelView', -10],
			],
			KernelEvents::EXCEPTION => [
				['onKernelValidateException', 257],
			],
		]);
	}

	public function provideonKernelControllerArgumentsSuccess() {
		return [
			[ 'POST', [], [ 'post' ], \stdClass::class ],
			[ 'post', [], [ 'post' ], \stdClass::class ],
			[ 'patch', [], [ 'patch' ], \stdClass::class ],
			[ 'POST', [ 'group1' ], [ 'post', 'group1' ], \stdClass::class ],
			[ 'POST', [ 'group1', 'group2' ], [ 'post', 'group1', 'group2' ], \stdClass::class ],
			[ 'POST', [], [ 'post' ], null ],
			[ 'post', [], [ 'post' ], null ],
			[ 'patch', [], [ 'patch' ], null ],
			[ 'POST', [ 'group1' ], [ 'post', 'group1' ], null ],
			[ 'POST', [ 'group1', 'group2' ], [ 'post', 'group1', 'group2' ], null ],
		];
	}
	
	/**
	 * @dataProvider provideonKernelControllerArgumentsSuccess
	 */
	public function testonKernelControllerArgumentsSuccess($method, $groups, $groupResults, $class) {

		$serializer                 = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);
		$kernel                     = $this->getMockBuilder(KernelInterface::class)->getMockForAbstractClass();

		$attributes = $this->getMockBuilder(ParameterBag::class)->disableOriginalConstructor()->getMock();
		$request    = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request->attributes = $attributes;
		
		$entity = new \stdClass();
		$controller = function () {};
		
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		$metadata = new MetadataUnserialize('ENTITY_NAME', $groups, false);
		
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
		
		$event = new ControllerArgumentsEvent($kernel, $controller, [], $request, HttpKernelInterface::MASTER_REQUEST);

		$request
			->expects($this->once())
			->method('getContent')
			->willReturn('CONTENT')
		;
		$request
			->expects($this->once())
			->method('getMethod')
			->willReturn($method)
		;

		$attributes
			->expects($this->exactly(2))
			->method('get')
			->withConsecutive(
				[ 'ENTITY_NAME' ],
				[ Unserialize::REQUEST_ATTRIBUTE_CLASS ]
			)
			->willReturnOnConsecutiveCalls(
				$entity,
				$class
			)
		;
		
		$attributes
			->expects($this->once())
			->method('set')
			->with('ENTITY_NAME', $entity)
		;
		
		$serializerSubscriber = new SerializerSubscriberOnKernelControllerArgumentsTest(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);
		
		$serializerSubscriber->onKernelControllerArguments($event);
		$this->assertEquals($serializerSubscriber->groups, $groupResults);
	}


	public function testonKernelControllerArgumentsNoClassNoEntity() {

		$serializer                 = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);
		$kernel                     = $this->getMockBuilder(KernelInterface::class)->getMockForAbstractClass();

		$attributes = $this->getMockBuilder(ParameterBag::class)->disableOriginalConstructor()->getMock();
		$request    = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request->attributes = $attributes;

		$controller = function () {};
		
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		$metadata = new MetadataUnserialize('ENTITY_NAME', [], false );
		
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

		$event = new ControllerArgumentsEvent($kernel, $controller, [], $request, HttpKernelInterface::MASTER_REQUEST);

		$request
			->expects($this->once())
			->method('getContent')
			->willReturn('CONTENT')
		;
		$request
			->expects($this->once())
			->method('getMethod')
			->willReturn('POST')
		;
		
		$attributes
			->expects($this->exactly(2))
			->method('get')
			->withConsecutive(
				[ 'ENTITY_NAME' ],
				[ Unserialize::REQUEST_ATTRIBUTE_CLASS ]
			)
			->willReturnOnConsecutiveCalls(
				null,
				null
			)
		;
		
		$serializerSubscriber = new SerializerSubscriberOnKernelControllerArgumentsTest(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);
		
		$this->expectException(\LogicException::class);

		$serializerSubscriber->onKernelControllerArguments($event);
	}


	public function testonKernelControllerArgumentsNoEntity() {

		$serializer                 = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);
		$kernel                     = $this->getMockBuilder(KernelInterface::class)->getMockForAbstractClass();

		$attributes = $this->getMockBuilder(ParameterBag::class)->disableOriginalConstructor()->getMock();
		$request    = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request->attributes = $attributes;

		$controller = function () {};
		
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		$metadata = new MetadataUnserialize('ENTITY_NAME', [], false );
		
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

		$event = new ControllerArgumentsEvent($kernel, $controller, [], $request, HttpKernelInterface::MASTER_REQUEST);

		$request
			->expects($this->once())
			->method('getContent')
			->willReturn(null)
		;
		$request
			->expects($this->once())
			->method('getMethod')
			->willReturn('POST')
		;
		
		$attributes
			->expects($this->exactly(2))
			->method('get')
			->withConsecutive(
				[ 'ENTITY_NAME' ],
				[ Unserialize::REQUEST_ATTRIBUTE_CLASS ]
			)
			->willReturnOnConsecutiveCalls(
				null,
				\stdClass::class
			)
		;

		$serializerSubscriber = new SerializerSubscriberOnKernelControllerArgumentsTest(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);

		$this->expectException(BadRequestHttpException::class);

		$serializerSubscriber->onKernelControllerArguments($event);
	}
	
	public function provideronKernelControllerArgumentsSave() {
		return [
			[true, true, true ],
			[true, false, false ],
			[false, true, false ],
			[false, false, false ]
		];
	}

	/**
	 * @dataProvider provideronKernelControllerArgumentsSave
	 */
	public function testonKernelControllerArgumentsSave($isEntity, $save, $called) {
		
		$serializer                 = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);
		$em                         = $this->getMockForAbstractClass(ObjectManager::class);
		$kernel                     = $this->getMockBuilder(KernelInterface::class)->getMockForAbstractClass();

		$attributes = $this->getMockBuilder(ParameterBag::class)->disableOriginalConstructor()->getMock();
		$request    = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request->attributes = $attributes;
		
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		$metadata = new MetadataUnserialize('ENTITY_NAME', [], $save );
		
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

		$entity = new \stdClass();
		$controller = function () {};

		$event = new ControllerArgumentsEvent($kernel, $controller, [], $request, HttpKernelInterface::MASTER_REQUEST);

		$serializerSubscriber = new SerializerSubscriberOnKernelControllerArgumentsTestSave(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager,
			$em,
			$isEntity
		);

		$request
			->expects($this->once())
			->method('getContent')
			->willReturn('CONTENT')
		;
		$request
			->expects($this->once())
			->method('getMethod')
			->willReturn('post')
		;
		
		$attributes
			->expects($this->exactly(2))
			->method('get')
			->withConsecutive(
				[ 'ENTITY_NAME' ],
				[ Unserialize::REQUEST_ATTRIBUTE_CLASS ]
			)
			->willReturnOnConsecutiveCalls(
				$entity,
				\stdClass::class
			)
		;
		
		$attributes
			->expects($this->once())
			->method('set')
			->with('ENTITY_NAME', $entity)
		;
		
		if ($called) {
			$em
				->expects($this->once())
				->method('persist')
				->with($entity)
			;
			$em
				->expects($this->once())
				->method('flush')
			;
		} else {
			$em
				->expects($this->never())
				->method('persist')
			;
			$em
				->expects($this->never())
				->method('flush')
			;
		}
		
		$serializerSubscriber->onKernelControllerArguments($event);
	}

	public function providerUnserializeSuccess() {
		return [
			[ new \stdClass(), \stdClass::class, []],
			[ new \stdClass(), \stdClass::class, [ 'group1' ]],
			[ new StubEntity(
				function ($data, array $groups) {
					$this->assertTrue(false);
				},
				function ($data, array $groups) {
					$this->assertEquals($data, ['Decode' => 'Data']);
					$this->assertEquals($groups, [ 'group1' ]);
				}
			), StubEntity::class, [ 'group1' ]],
		];
	}
	
	/**
	 * @dataProvider providerUnserializeSuccess
	 */
	public function testUnserializeSuccess($entity, $class, $groups) {
		$serializer                 = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);

		$context = [
			'groups' => $groups,
			'object_to_populate' => $entity,
		];

		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);

		$serializer
			->method('supportsDecoding')
			->with('json', $context)
			->willReturn(true)
		;

		$serializer
			->expects($this->once())
			->method('decode')
			->with('CONTENT', 'json', $context)
			->willReturn(['Decode' => 'Data'])
		;
		$serializer
			->expects($this->once())
			->method('denormalize')
			->with(['Decode' => 'Data'], $class, 'json', $context)
			->willReturn($entity)
		;

		$this->assertEquals(
			$this->reflectionCallMethod($serializerSubscriber, 'unserialize', [ 'CONTENT', $entity, $class, $groups ]), $entity
		);
	}

	public function testUnserializeNotSupport() {
		$serializer                 = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);

		$entity = new \stdClass();
		$context = [
			'groups' => [],
			'object_to_populate' => $entity,
		];

		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);

		$serializer
			->method('supportsDecoding')
			->with('json', $context)
			->willReturn(false)
		;

		$serializer
			->expects($this->never())
			->method('decode')
		;
		$serializer
			->expects($this->never())
			->method('denormalize')
		;

		$this->expectException(BadRequestHttpException::class);

		$this->reflectionCallMethod($serializerSubscriber, 'unserialize', [ 'CONTENT', $entity, \stdClass::class, [] ]);
	}

	public function testUnserializeExceptionUnexpectedValue() {
		$serializer                 = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);

		$entity = new \stdClass();
		$context = [
			'groups' => [],
			'object_to_populate' => $entity,
		];

		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);

		$serializer
			->method('supportsDecoding')
			->with('json', $context)
			->willReturn(true)
		;

		$serializer
			->expects($this->once())
			->method('decode')
			->with('CONTENT', 'json', $context)
			->willReturn(['Decode' => 'Data'])
		;
		$serializer
			->expects($this->once())
			->method('denormalize')
			->with(['Decode' => 'Data'], \stdClass::class, 'json', $context)
			->willThrowException(new \UnexpectedValueException())
		;

		$this->expectException(BadRequestHttpException::class);

		$this->reflectionCallMethod($serializerSubscriber, 'unserialize', [ 'CONTENT', $entity, \stdClass::class, [] ]);
	}

	public function testUnserializeExceptionMissingConstructorArguments() {
		$serializer                 = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);

		$entity = new \stdClass();
		$context = [
			'groups' => [],
			'object_to_populate' => $entity,
		];

		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);

		$serializer
			->method('supportsDecoding')
			->with('json', $context)
			->willReturn(true)
		;

		$serializer
			->expects($this->once())
			->method('decode')
			->with('CONTENT', 'json', $context)
			->willReturn(['Decode' => 'Data'])
		;
		$serializer
			->expects($this->once())
			->method('denormalize')
			->with(['Decode' => 'Data'], \stdClass::class, 'json', $context)
			->willThrowException(new MissingConstructorArgumentsException(''))
		;

		$this->expectException(BadRequestHttpException::class);

		$this->reflectionCallMethod($serializerSubscriber, 'unserialize', [ 'CONTENT', $entity, \stdClass::class, [] ]);
	}

	public function providerValidateSuccess() {
		return  [
			[ []                      , 'POST' , [ 'post' ] ],
			[ []                      , 'post' , [ 'post' ] ],
			[ []                      , 'patch', [ 'patch' ] ],
			[ [ 'groups1' ]           , 'post' , [ 'post', 'groups1' ] ],
			[ [ 'groups1', 'groups2' ], 'post' , [ 'post', 'groups1', 'groups2' ] ],
		];
	}

	/**
	 * @dataProvider providerValidateSuccess
	 */
	public function testValidateSuccess($groups, $method, $groupsFinal) {
		$serializer                 = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);
		$validator                  = $this->getMockBuilder(ValidatorInterface::class)->getMockForAbstractClass();
		$constraintViolationList    = $this->getMockBuilder(ConstraintViolationListInterface::class)->getMockForAbstractClass();
		
		$request = $this->getMockBuilder(Request::class)->getMock();
		$entity = new \stdClass();
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		$metadata = new MetadataValidate($groups);
		
		$controllerActionExtractor
			->expects($this->once())
			->method('extractFromRequest')
			->with($request)
			->willReturn($controllerAction)
		;
		
		$metadataValidateManager
			->expects($this->once())
			->method('getMetadata')
			->with('CONTROLLER', 'ACTION')
			->willReturn($metadata)
		;
		
		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);
		$serializerSubscriber->setValidator($validator);

		$request
			->expects($this->once())
			->method('getMethod')
			->willReturn($method)
		;
		
		$validator
			->expects($this->once())
			->method('validate')
			->with($entity, null, $groupsFinal)
			->willReturn($constraintViolationList)
		;

		$constraintViolationList
			->expects($this->once())
			->method('count')
			->willReturn(0)
		;
		
		$this->reflectionCallMethod($serializerSubscriber, 'validate', [ $request, $entity ]);
	}

	public function testValidateException() {
		$serializer                 = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);
		$validator                  = $this->getMockBuilder(ValidatorInterface::class)->getMockForAbstractClass();
		$constraintViolationList    = $this->getMockBuilder(ConstraintViolationListInterface::class)->getMockForAbstractClass();
		
		$request = $this->getMockBuilder(Request::class)->getMock();
		$entity = new \stdClass();
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		$metadata = new MetadataValidate([]);
		
		$controllerActionExtractor
			->expects($this->once())
			->method('extractFromRequest')
			->with($request)
			->willReturn($controllerAction)
		;
		
		$metadataValidateManager
			->expects($this->once())
			->method('getMetadata')
			->with('CONTROLLER', 'ACTION')
			->willReturn($metadata)
		;

		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);
		$serializerSubscriber->setValidator($validator);

		$request
			->expects($this->once())
			->method('getMethod')
			->willReturn('get')
		;

		$validator
			->expects($this->once())
			->method('validate')
			->with($entity, null, [ 'get' ])
			->willReturn($constraintViolationList)
		;

		$constraintViolationList
			->expects($this->once())
			->method('count')
			->willReturn(2)
		;
		
		$this->expectException(UnserializeValidateException::class);

		$this->reflectionCallMethod($serializerSubscriber, 'validate', [ $request, $entity ]);
	}
	
	public function testValidateNoService() {
		$serializer = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);

		$request = $this->getMockBuilder(Request::class)->getMock();
		$entity = new \stdClass();
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		$metadata = new MetadataValidate([ 'group1' ]);
		
		$controllerActionExtractor
			->expects($this->once())
			->method('extractFromRequest')
			->with($request)
			->willReturn($controllerAction)
		;
		
		$metadataValidateManager
			->expects($this->once())
			->method('getMetadata')
			->with('CONTROLLER', 'ACTION')
			->willReturn($metadata)
		;

		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);

		$this->expectException(\LogicException::class);

		$this->reflectionCallMethod($serializerSubscriber, 'validate', [ $request, $entity ]);
	}
	
	public function provideOnKernelView() {
		return [
			[ [ 'key' => 'value' ], [ 'group1' ]          , [ 'groups' => [ 'get', 'group1' ] ]          , 'normalize_data' ],
			[ 1                   , [ 'group1' ]          , [ 'groups' => [ 'get', 'group1' ] ]          , 'normalize_data' ],
			[ true                , [ 'group1' ]          , [ 'groups' => [ 'get', 'group1' ] ]          , 'normalize_data' ],
			[ null                , [ 'group1' ]          , [ 'groups' => [ 'get', 'group1' ] ]          , 'normalize_data' ],
			[ [ 'key' => 'value' ], [ 'group1', 'group2' ], [ 'groups' => [ 'get', 'group1', 'group2' ] ], 'normalize_data' ],
			[ [ 'key' => 'value' ], []                    , [ 'groups' => [ 'get' ] ]                    , 'normalize_data' ],
			[ new StubEntity(
				function ($data, array $groups) {
					$this->assertEquals($data, 'normalize_data');
					$this->assertEquals($groups, [ 'get' ]);
					return 'new_normalize_data';
				},
				function ($data, array $groups) {
					$this->assertTrue(false);
				}
			), [], [ 'groups' => [ 'get' ] ], 'new_normalize_data'],
		];
	}

	/**
	 * @dataProvider provideOnKernelView
	 */
	public function testOnKernelViewSuccess($controllerResult, $annoGroup, $serializeGroup, $result) {

		$serializer                 = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);
		$kernel                     = $this->getMockBuilder(KernelInterface::class)->getMockForAbstractClass();

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$event = new ViewEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $controllerResult);
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		$metadata = new MetadataSerialize(200, $annoGroup, []);
		
		$controllerActionExtractor
			->expects($this->once())
			->method('extractFromRequest')
			->with($request)
			->willReturn($controllerAction)
		;
		
		$metadataSerializeManager
			->expects($this->once())
			->method('getMetadata')
			->with('CONTROLLER', 'ACTION')
			->willReturn($metadata)
		;
		
		$serializer
			->method('supportsEncoding')
			->with('json', $serializeGroup)
			->willReturn(true)
		;

		$serializer
			->method('normalize')
			->with($controllerResult, 'json', $serializeGroup)
			->willReturn('normalize_data')
		;

		$serializer
			->method('encode')
			->with($result, 'json', $serializeGroup)
			->willReturn('encoded_data')
		;
		
		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);

		$serializerSubscriber->onKernelView($event);
		
		$response = $event->getResponse();

		$this->assertEquals($response->headers->get('Content-Type'), 'application/json');
		$this->assertEquals($response->headers->get('Content-Length'), 12);
		$this->assertEquals($response->getContent(), 'encoded_data');
	}
	
	public function testOnKernelViewNotSupport() {
		$serializer = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);
		$kernel                     = $this->getMockBuilder(KernelInterface::class)->getMockForAbstractClass();
		
		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$event = new ViewEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, 'data');
		$controllerAction = new ControllerAction('CONTROLLER', 'ACTION');
		$metadata = new MetadataSerialize(200, [], []);
		
		$controllerActionExtractor
			->expects($this->once())
			->method('extractFromRequest')
			->with($request)
			->willReturn($controllerAction)
		;
		
		$metadataSerializeManager
			->expects($this->once())
			->method('getMetadata')
			->with('CONTROLLER', 'ACTION')
			->willReturn($metadata)
		;

		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);
		
		$serializer
			->method('supportsEncoding')
			->with('json', [ 'groups' => [ 'get' ] ])
			->willReturn(false)
		;

		$this->expectException(NotEncodableValueException::class);
		$serializerSubscriber->onKernelView($event);
	}
	
	public function testOnKernelException() {

		$serializer                 = $this->getMockBuilder(StubSerializer::class)->getMockForAbstractClass();
		$controllerActionExtractor  = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager   = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$metadataUnserializeManager = $this->getMockForAbstractClass(MetadataUnserializeManagerInterface::class);
		$metadataValidateManager    = $this->getMockForAbstractClass(MetadataValidateManagerInterface::class);
		$kernel                     = $this->getMockBuilder(KernelInterface::class)->getMockForAbstractClass();
		
		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		
		$constraintViolation1 = $this->getMockBuilder(ConstraintViolationInterface::class)->getMockForAbstractClass();
		$constraintViolation2 = $this->getMockBuilder(ConstraintViolationInterface::class)->getMockForAbstractClass();
		$constraintViolation3 = $this->getMockBuilder(ConstraintViolationInterface::class)->getMockForAbstractClass();
		
		$constraintViolationList = new ConstraintViolationList();
		$constraintViolationList->add($constraintViolation1);
		$constraintViolationList->add($constraintViolation2);
		$constraintViolationList->add($constraintViolation3);
			
		$e = new UnserializeValidateException($constraintViolationList);

		$event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $e);

		$constraintViolation1->method('getPropertyPath')->willReturn('');
		$constraintViolation2->method('getPropertyPath')->willReturn('propName');
		$constraintViolation3->method('getPropertyPath')->willReturn('propName');
		
		$constraintViolation1->method('getMessage')->willReturn('message1');
		$constraintViolation2->method('getMessage')->willReturn('message2');
		$constraintViolation3->method('getMessage')->willReturn('message3');

		$serializer
			->method('serialize')
			->with([
				'_root_' => [ 'message1' ],
				'propName' => [ 'message2', 'message3' ]
			], 'json')
			->willReturn('serialize_data')
		;
		
		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$metadataUnserializeManager,
			$metadataValidateManager
		);
		
		$serializerSubscriber->onKernelValidateException($event);
		
		$response = $event->getResponse();

		$this->assertEquals($response->headers->get('Content-Type'), 'application/json');
		$this->assertEquals($response->headers->get('Content-Length'), 14);
		$this->assertEquals($response->getContent(), 'serialize_data');
	}
}
