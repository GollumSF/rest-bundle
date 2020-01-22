<?php
namespace Test\GollumSF\RestBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
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

interface StubSerializer extends SerializerInterface, NormalizerInterface, EncoderInterface {
}

class SerializerSubscriberTest extends TestCase {

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
	
//	use AnnotationControllerReader;
//	
//	/**
//	 * @var SerializerInterface
//	 */
//	private $serializer;
//	/**
//	 * @var EntityManagerInterface
//	 */
//	private $em;
//	/**
//	 * @var ValidatorInterface
//	 */
//	private $validator;
//	
//	public static function getSubscribedEvents() {
//		return [
//			KernelEvents::CONTROLLER_ARGUMENTS => [
//				['onKernelControllerArguments', -1],
//			],
//			KernelEvents::VIEW => [
//				['onKernelView', -1],
//			],
//			KernelEvents::EXCEPTION => [
//				['onKernelException', 256],
//			],
//		];
//	}
//	
//	public function __construct(
//		SerializerInterface $serializer,
//		EntityManagerInterface $em,
//		ValidatorInterface $validator
//	) {
//		$this->serializer = $serializer;
//		$this->em = $em;
//		$this->validator = $validator;
//	}
//	
//	/**
//	 * @throws \Doctrine\ORM\ORMException
//	 * @throws \Doctrine\ORM\OptimisticLockException
//	 */
//	public function onKernelControllerArguments(ControllerArgumentsEvent $event) {
//		
//		if (!$event->isMasterRequest()) {
//			return;
//		}
//		
//		$request = $event->getRequest();
//		
//		/** @var Unserialize $annotation */
//		$annotation = $this->getAnnotation($request, Unserialize::class);
//		if ($annotation) {
//			
//			$content = $request->getContent();
//			$entity = $request->attributes->get($annotation->name);
//			
//			if (!is_array($annotation->groups)) {
//				$annotation->groups = [ $annotation->groups ];
//			}
//			
//			try {
//				$this->serializer->deserialize($content, get_class($entity), 'json', [
//					'groups' => array_merge([ strtolower($request->getMethod()) ], $annotation->groups),
//					'object_to_populate' => $entity,
//				]);
//			} catch (\UnexpectedValueException $e) {
//				throw new BadRequestHttpException($e->getMessage());
//			}
//			
//			if ($entity instanceof UnserializerTransformInterface) {
//				$entity->unserializeTransform(\json_decode($content), $annotation->groups);
//			}
//			
//			/** @var Validate $annotationValidate */
//			$annotationValidate = $this->getAnnotation($request, Validate::class);
//			if ($annotationValidate) {
//				
//				if (!is_array($annotationValidate->groups)) {
//					$annotationValidate->groups = [ $annotationValidate->groups ];
//				}
//				
//				$errors = $this->validator->validate($entity, null, $annotationValidate->groups);
//				if($errors->count()) {
//					throw new UnserializeValidateException($errors);
//				}
//			}
//			
//			if ($annotation->save && $this->isEntity($entity)) {
//				$this->em->persist($entity);
//				$this->em->flush();
//			}
//			
//		}
//	}
//
//	private function isEntity($class) {
//		if (is_object($class)) {
//			$class = ($class instanceof Proxy) ? get_parent_class($class) : get_class($class);
//		}
//		return ! $this->em->getMetadataFactory()->isTransient($class);
//	}
//	
	
	public function provideOnKernelView() {
		return [
			[ [ 'key' => 'value' ], 'group1'              , [ 'groups' => [ 'get', 'group1' ] ]          , 'normalize_data' ],
			[ 1                   , 'group1'              , [ 'groups' => [ 'get', 'group1' ] ]          , 'normalize_data' ],
			[ true                , 'group1'              , [ 'groups' => [ 'get', 'group1' ] ]          , 'normalize_data' ],
			[ null                , 'group1'              , [ 'groups' => [ 'get', 'group1' ] ]          , 'normalize_data' ],
			[ [ 'key' => 'value' ], [ 'group1', 'group2' ], [ 'groups' => [ 'get', 'group1', 'group2' ] ], 'normalize_data' ],
			[ [ 'key' => 'value' ], []                    , [ 'groups' => [ 'get' ] ]                    , 'normalize_data' ],
			[ new StubEntity(
				function ($content, array $groups) {
					$this->assertEquals($content, 'normalize_data');
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
