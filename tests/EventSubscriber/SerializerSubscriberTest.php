<?php
namespace Test\GollumSF\RestBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\Proxy;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Annotation\Validate;
use GollumSF\RestBundle\EventSubscriber\SerializerSubscriber;
use GollumSF\RestBundle\Exceptions\UnserializeValidateException;
use GollumSF\RestBundle\Serializer\Transform\SerializerTransformInterface;
use GollumSF\RestBundle\Serializer\Transform\UnserializerTransformInterface;
use GollumSF\RestBundle\Traits\AnnotationControllerReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class SerializerSubscriberOnKernelViewTest extends SerializerSubscriber {
	
	private $annotation;
	
	public function __construct(
		SerializerInterface $serializer,
		EntityManagerInterface $em,
		ValidatorInterface $validator,
		Serialize $annotation
	) {
		parent::__construct($serializer, $em, $validator);
		$this->annotation = $annotation;
	}
	
	public function getAnnotation(Request $request, string $annotationClass) {
		return $this->annotation;
	}

}


class SerializerSubscriberOnKernelControllerArgumentsTest extends SerializerSubscriber {

	private $annotation;

	public function __construct(
		SerializerInterface $serializer,
		EntityManagerInterface $em,
		ValidatorInterface $validator,
		Unserialize $annotation
	) {
		parent::__construct($serializer, $em, $validator);
		$this->annotation = $annotation;
	}

	public function getAnnotation(Request $request, string $annotationClass) {
		return $this->annotation;
	}

	protected function validate(Request $request, $entity): void {
	}

	protected function isEntity($class) {
		return false;
	}

	protected function unserialize(string $content, $entity, array $groups): void {
	}
}

class SerializerSubscriberOnKernelControllerArgumentsTestSave extends SerializerSubscriberOnKernelControllerArgumentsTest {

	private $isEntity;

	public function __construct(
		SerializerInterface $serializer,
		EntityManagerInterface $em,
		ValidatorInterface $validator,
		bool $isEntity,
		bool $save
	) {
		parent::__construct($serializer, $em, $validator, new Unserialize([
			'name' => 'ENTITY_NAME',
			'save' => $save
		]));
		$this->isEntity = $isEntity;
	}
	
	protected function isEntity($class) {
		return $this->isEntity;
	}
}


class SerializerSubscriberValidateTest extends SerializerSubscriber {

	private $annotation;

	public function __construct(
		SerializerInterface $serializer,
		EntityManagerInterface $em,
		ValidatorInterface $validator,
		Validate $annotation
	) {
		parent::__construct($serializer, $em, $validator);
		$this->annotation = $annotation;
	}

	public function getAnnotation(Request $request, string $annotationClass) {
		return $this->annotation;
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
				['onKernelControllerArguments', -1],
			],
			KernelEvents::VIEW => [
				['onKernelView', -1],
			],
			KernelEvents::EXCEPTION => [
				['onKernelException', 256],
			],
		]);
	}

	public function providerOnKernelControllerArguments() {
		return [
			[ 'POST', [], [ 'post' ] ],
			[ 'post', [], [ 'post' ] ],
			[ 'patch', [], [ 'patch' ] ],
			[ 'POST', 'group1', [ 'post', 'group1' ] ],
			[ 'POST', [ 'group1', 'group2' ], [ 'post', 'group1', 'group2' ] ],
		];
	}
	
	/**
	 * @dataProvider providerOnKernelControllerArguments
	 */
	public function testOnKernelControllerArguments($method, $groups, $groupResults) {

		$serializer = $this->getMockBuilder(StubSerializer::class        )->getMockForAbstractClass();
		$em         = $this->getMockBuilder(EntityManagerInterface::class)->getMockForAbstractClass();
		$validator  = $this->getMockBuilder(ValidatorInterface::class    )->getMockForAbstractClass();
		$kernel     = $this->getMockBuilder(KernelInterface::class       )->getMockForAbstractClass();

		$attributes = $this->getMockBuilder(ParameterBag::class)->disableOriginalConstructor()->getMock();
		$request    = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request->attributes = $attributes;
		
		$entity = new \stdClass();
		$controller = function () {};
		
		$annotation = new Unserialize([
			'name' => 'ENTITY_NAME',
			'groups' => $groups
		]);
			
		$event = new ControllerArgumentsEvent($kernel, $controller, [ 'ARGUMENTS' ], $request, HttpKernelInterface::MASTER_REQUEST);

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
			->expects($this->once())
			->method('get')
			->with('ENTITY_NAME')
			->willReturn($entity)
		;
		
		$serializerSubscriber = new SerializerSubscriberOnKernelControllerArgumentsTest(
			$serializer,
			$em,
			$validator,
			$annotation
		);
		
		$serializerSubscriber->onKernelControllerArguments($event);
	}
	
	public function providerOnKernelControllerArgumentsSave() {
		return [
			[true, true, true ],
			[true, false, false ],
			[false, true, false ],
			[false, false, false ]
		];
	}

	/**
	 * @dataProvider providerOnKernelControllerArgumentsSave
	 */
	public function testOnKernelControllerArgumentsSave($isEntity, $save, $called) {
		
		$serializer = $this->getMockBuilder(StubSerializer::class        )->getMockForAbstractClass();
		$em         = $this->getMockBuilder(EntityManagerInterface::class)->getMockForAbstractClass();
		$validator  = $this->getMockBuilder(ValidatorInterface::class    )->getMockForAbstractClass();
		$kernel     = $this->getMockBuilder(KernelInterface::class       )->getMockForAbstractClass();

		$attributes = $this->getMockBuilder(ParameterBag::class)->disableOriginalConstructor()->getMock();
		$request    = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request->attributes = $attributes;


		$entity = new \stdClass();
		$controller = function () {};
		
		$event = new ControllerArgumentsEvent($kernel, $controller, [ 'ARGUMENTS' ], $request, HttpKernelInterface::MASTER_REQUEST);

		$serializerSubscriber = new SerializerSubscriberOnKernelControllerArgumentsTestSave(
			$serializer,
			$em,
			$validator,
			$isEntity,
			$save
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
			->expects($this->once())
			->method('get')
			->with('ENTITY_NAME')
			->willReturn($entity)
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

	public function providerUnserialize() {
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
	 * @dataProvider providerUnserialize
	 */
	public function testUnserialize($entity, $class, $groups) {
		$serializer              = $this->getMockBuilder(StubSerializer::class        )->getMockForAbstractClass();
		$em                      = $this->getMockBuilder(EntityManagerInterface::class)->getMockForAbstractClass();
		$validator               = $this->getMockBuilder(ValidatorInterface::class    )->getMockForAbstractClass();

		$context = [
			'groups' => $groups,
			'object_to_populate' => $entity,
		];

		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$em,
			$validator
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
		;

		$this->reflectionCallMethod($serializerSubscriber, 'unserialize', [ 'CONTENT', $entity, $groups ]);
	}

	public function testUnserializeNotSupport() {
		$serializer              = $this->getMockBuilder(StubSerializer::class        )->getMockForAbstractClass();
		$em                      = $this->getMockBuilder(EntityManagerInterface::class)->getMockForAbstractClass();
		$validator               = $this->getMockBuilder(ValidatorInterface::class    )->getMockForAbstractClass();

		$entity = new \stdClass();
		$context = [
			'groups' => [],
			'object_to_populate' => $entity,
		];

		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$em,
			$validator
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

		$this->reflectionCallMethod($serializerSubscriber, 'unserialize', [ 'CONTENT', $entity, [] ]);
	}
	
	public function testUnserializeException() {
		$serializer              = $this->getMockBuilder(StubSerializer::class        )->getMockForAbstractClass();
		$em                      = $this->getMockBuilder(EntityManagerInterface::class)->getMockForAbstractClass();
		$validator               = $this->getMockBuilder(ValidatorInterface::class    )->getMockForAbstractClass();

		$entity = new \stdClass();
		$context = [
			'groups' => [],
			'object_to_populate' => $entity,
		];

		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$em,
			$validator
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
		
		$this->reflectionCallMethod($serializerSubscriber, 'unserialize', [ 'CONTENT', $entity, [] ]);
	}

	public function providerValidate() {
		return  [
			[ []                      , 'POST' , [ 'post' ] ],
			[ []                      , 'post' , [ 'post' ] ],
			[ []                      , 'patch', [ 'patch' ] ],
			[ 'groups1'               , 'post' , [ 'post', 'groups1' ] ],
			[ [ 'groups1', 'groups2' ], 'post' , [ 'post', 'groups1', 'groups2' ] ],
		];
	}

	/**
	 * @dataProvider providerValidate
	 */
	public function testValidate($groups, $method, $groupsFinal) {
		$serializer              = $this->getMockBuilder(SerializerInterface::class             )->getMockForAbstractClass();
		$em                      = $this->getMockBuilder(EntityManagerInterface::class          )->getMockForAbstractClass();
		$validator               = $this->getMockBuilder(ValidatorInterface::class              )->getMockForAbstractClass();
		$constraintViolationList = $this->getMockBuilder(ConstraintViolationListInterface::class)->getMockForAbstractClass();
		
		$request = $this->getMockBuilder(Request::class)->getMock();
		
		$entity = new \stdClass();
		
		$serializerSubscriber = new SerializerSubscriberValidateTest(
			$serializer,
			$em,
			$validator,
			new Validate([
				'value' => $groups
			])
		);

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
		$serializer              = $this->getMockBuilder(SerializerInterface::class             )->getMockForAbstractClass();
		$em                      = $this->getMockBuilder(EntityManagerInterface::class          )->getMockForAbstractClass();
		$validator               = $this->getMockBuilder(ValidatorInterface::class              )->getMockForAbstractClass();
		$constraintViolationList = $this->getMockBuilder(ConstraintViolationListInterface::class)->getMockForAbstractClass();

		$request = $this->getMockBuilder(Request::class)->getMock();

		$entity = new \stdClass();

		$serializerSubscriber = new SerializerSubscriberValidateTest(
			$serializer,
			$em,
			$validator,
			new Validate([])
		);

		$request
			->expects($this->once())
			->method('getMethod')
			->willReturn('get')
		;

		$validator
			->expects($this->once())
			->method('validate')
			->with($entity, null, [ 'get', 'Default' ])
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


	public function providerIsEntity() {
		return [
			[ new \stdClass(), \stdClass::class, true, false ],
			[ new StubProxy(), \stdClass::class, true, false ],
			[ new \stdClass(), \stdClass::class, false, true ],
		];
	}

	/**
	 * @dataProvider providerIsEntity
	 */
	public function testIsEntity($entity, $class, $transiantResult, $result) {
		$serializer      = $this->getMockBuilder(SerializerInterface::class     )->getMockForAbstractClass();
		$em              = $this->getMockBuilder(EntityManagerInterface::class  )->getMockForAbstractClass();
		$validator       = $this->getMockBuilder(ValidatorInterface::class      )->getMockForAbstractClass();
		$metadataFactory = $this->getMockBuilder(ClassMetadataFactory::class)->getMockForAbstractClass();
		
		$serializerSubscriber = new SerializerSubscriber(
			$serializer,
			$em,
			$validator
		);
		
		$em
			->method('getMetadataFactory')
			->willReturn($metadataFactory)
		;
		
		$metadataFactory
			->method('isTransient')
			->with($class)
			->willReturn($transiantResult)
		;

		
		$this->assertEquals(
			$this->reflectionCallMethod($serializerSubscriber, 'isEntity', [ $entity ]),
			$result
		);
	}
	
	
	public function provideOnKernelView() {
		return [
			[ [ 'key' => 'value' ], 'group1'              , [ 'groups' => [ 'get', 'group1' ] ]          , 'normalize_data' ],
			[ 1                   , 'group1'              , [ 'groups' => [ 'get', 'group1' ] ]          , 'normalize_data' ],
			[ true                , 'group1'              , [ 'groups' => [ 'get', 'group1' ] ]          , 'normalize_data' ],
			[ null                , 'group1'              , [ 'groups' => [ 'get', 'group1' ] ]          , 'normalize_data' ],
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
	public function testOnKernelView($controllerResult, $annoGroup, $serializeGroup, $result) {

		$serializer = $this->getMockBuilder(StubSerializer::class        )->getMockForAbstractClass();
		$em         = $this->getMockBuilder(EntityManagerInterface::class)->getMockForAbstractClass();
		$validator  = $this->getMockBuilder(ValidatorInterface::class    )->getMockForAbstractClass();
		$kernel     = $this->getMockBuilder(KernelInterface::class       )->getMockForAbstractClass();

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

		$event = new ViewEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $controllerResult);
		
		$annotation = new Serialize([
			'groups' => $annoGroup
		]);
		
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
		
		$serializerSubscriber = new SerializerSubscriberOnKernelViewTest(
			$serializer,
			$em,
			$validator,
			$annotation
		);

		$serializerSubscriber->onKernelView($event);
		
		$response = $event->getResponse();

		$this->assertEquals($response->headers->get('Content-Type'), 'application/json');
		$this->assertEquals($response->headers->get('Content-Length'), 12);
		$this->assertEquals($response->getContent(), 'encoded_data');
	}
	
	public function testOnKernelViewNotSuport() {
		$serializer = $this->getMockBuilder(StubSerializer::class        )->getMockForAbstractClass();
		$em         = $this->getMockBuilder(EntityManagerInterface::class)->getMockForAbstractClass();
		$validator  = $this->getMockBuilder(ValidatorInterface::class    )->getMockForAbstractClass();
		$kernel     = $this->getMockBuilder(KernelInterface::class       )->getMockForAbstractClass();

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

		$event = new ViewEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, 'data');

		$annotation = new Serialize([]);

		$serializerSubscriber = new SerializerSubscriberOnKernelViewTest(
			$serializer,
			$em,
			$validator,
			$annotation
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

		$serializer = $this->getMockBuilder(SerializerInterface::class   )->getMockForAbstractClass();
		$em         = $this->getMockBuilder(EntityManagerInterface::class)->getMockForAbstractClass();
		$validator  = $this->getMockBuilder(ValidatorInterface::class    )->getMockForAbstractClass();
		$kernel     = $this->getMockBuilder(KernelInterface::class       )->getMockForAbstractClass();
		
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
			$em,
			$validator
		);
		
		$serializerSubscriber->onKernelException($event);
		
		$response = $event->getResponse();

		$this->assertEquals($response->headers->get('Content-Type'), 'application/json');
		$this->assertEquals($response->headers->get('Content-Length'), 14);
		$this->assertEquals($response->getContent(), 'serialize_data');
	}
}
