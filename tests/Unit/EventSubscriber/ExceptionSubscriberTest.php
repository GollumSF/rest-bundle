<?php
namespace ExceptionSubscriberTestGollumSF\RestBundle\Unit\EventSubscriber;

use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerAction;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerActionExtractorInterface;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\EventSubscriber\ExceptionSubscriber;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerialize;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerializeManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ExceptionSubscriberTestOnKernelException extends ExceptionSubscriber {
	public $isAuthenticated;
	public function isAuthenticated(): bool {
		return $this->isAuthenticated;
	}
}

class ExceptionSubscriberTest extends TestCase {

	use ReflectionPropertyTrait;
	
	public function testGetSubscribedEvents() {
		$this->assertEquals(ExceptionSubscriber::getSubscribedEvents(), [
			KernelEvents::EXCEPTION => [
				['onKernelException', 256],
			],
		]);
	}

	public function providerOnKernelException() {
		return [
			[ false, true, new \Exception('EXCEPTION_ERROR', 42), 'EXCEPTION_ERROR', 42, 500 ],
			[ false, false, new \Exception('EXCEPTION_ERROR', 21), 'EXCEPTION_ERROR', 21, 500 ],
			[ false, true, new NotFoundHttpException('NOT_FOUND'), 'NOT_FOUND', 0, 404 ],
			[ false, true, new UnauthorizedHttpException('', 'UNAUTHORIZED'), 'UNAUTHORIZED', 0, 401 ],
			[ false, true, new AccessDeniedHttpException('HTTP_DENIED'), 'HTTP_DENIED', 0, 401 ],
			[ false, true, new AccessDeniedException('DENIED'), 'DENIED', 403, 401 ],
		];
	}

	/**
	 * @dataProvider providerOnKernelException
	 */
	public function testOnKernelExceptionSuccess($isAuthenticated, $debug, $e, $message, $code, $statusCode) {
		$serializer                = $this->getMockForAbstractClass(SerializerInterface::class);
		$configuration             = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager  = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$kernel                    = $this->getMockForAbstractClass(KernelInterface::class);
		$request                   = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$metadata                  = $this->getMockBuilder(MetadataSerialize::class)->disableOriginalConstructor()->getMock();
		$controllerAction          = new ControllerAction('CONTROLLER', 'ACTION');
		
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

		$configuration
			->expects($this->once())
			->method('isAlwaysSerializedException')
			->willReturn(false)
		;

		$serializer
			->expects($this->once())
			->method('serialize')
			->willReturnCallback(function ($data, $format) use ($debug, $message, $code) {
				$this->assertEquals($format, 'json');
				$this->assertEquals($data['message'], $message);
				$this->assertEquals($data['code'], $code);
				if ($debug) {
					$this->assertArrayHasKey('stack', $data);
				} else {
					$this->assertArrayNotHasKey('stack', $data);
				}
				return 'serialized_data';
			})
		;

		$event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $e);

		$exceptionSubscriber = new ExceptionSubscriberTestOnKernelException(
			$serializer,
			$configuration,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$debug
		);
		$exceptionSubscriber->isAuthenticated = $isAuthenticated;

		$exceptionSubscriber->onKernelException($event);

		$response = $event->getResponse();

		$this->assertEquals($response->headers->get('Content-Type'), 'application/json');
		$this->assertEquals($response->headers->get('Content-Length'), 15);
		$this->assertEquals($response->getStatusCode(), $statusCode);
		$this->assertEquals($response->getContent(), 'serialized_data');
	}


	/**
	 * @dataProvider providerOnKernelException
	 */
	public function testOnKernelExceptionNoSerialize($isAuthenticated, $debug, $e, $message, $code, $statusCode) {
		$serializer                = $this->getMockForAbstractClass(SerializerInterface::class);
		$configuration             = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager  = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$kernel                    = $this->getMockForAbstractClass(KernelInterface::class);
		$request                   = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$controllerAction          = new ControllerAction('CONTROLLER', 'ACTION');
		
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
			->willReturn(null)
		;

		$configuration
			->expects($this->once())
			->method('isAlwaysSerializedException')
			->willReturn(true)
		;

		$serializer
			->expects($this->once())
			->method('serialize')
			->willReturnCallback(function ($data, $format) use ($debug, $message, $code) {
				$this->assertEquals($format, 'json');
				$this->assertEquals($data['message'], $message);
				$this->assertEquals($data['code'], $code);
				if ($debug) {
					$this->assertArrayHasKey('stack', $data);
				} else {
					$this->assertArrayNotHasKey('stack', $data);
				}
				return 'serialized_data';
			})
		;

		$event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $e);

		$exceptionSubscriber = new ExceptionSubscriberTestOnKernelException(
			$serializer,
			$configuration,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$debug
		);
		$exceptionSubscriber->isAuthenticated = $isAuthenticated;

		$exceptionSubscriber->onKernelException($event);

		$response = $event->getResponse();

		$this->assertEquals($response->headers->get('Content-Type'), 'application/json');
		$this->assertEquals($response->headers->get('Content-Length'), 15);
		$this->assertEquals($response->getStatusCode(), $statusCode);
		$this->assertEquals($response->getContent(), 'serialized_data');
	}


	/**
	 * @dataProvider providerOnKernelException
	 */
	public function testOnKernelExceptionNoSerializeNoConfig($isAuthenticated, $debug, $e) {
		$serializer                = $this->getMockForAbstractClass(SerializerInterface::class);
		$configuration             = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager  = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$kernel                    = $this->getMockForAbstractClass(KernelInterface::class);
		$request                   = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$controllerAction          = new ControllerAction('CONTROLLER', 'ACTION');
		
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
			->willReturn(null)
		;

		$configuration
			->expects($this->once())
			->method('isAlwaysSerializedException')
			->willReturn(false)
		;

		$serializer
			->expects($this->never())
			->method('serialize')
		;

		$event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $e);

		$exceptionSubscriber = new ExceptionSubscriberTestOnKernelException(
			$serializer,
			$configuration,
			$controllerActionExtractor,
			$metadataSerializeManager,
			$debug
		);
		$exceptionSubscriber->isAuthenticated = $isAuthenticated;

		$exceptionSubscriber->onKernelException($event);

		$this->assertNull(
			$event->getResponse()
		);

	}

	public function testSerialize() {
		$serializer                = $this->getMockForAbstractClass(SerializerInterface::class);
		$configuration             = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager  = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);

		$serializer
			->expects($this->once())
			->method('serialize')
			->with([ 'DATA' ], 'json')
			->willReturn('serialized_data')
		;
		
		$exceptionSubscriber = new ExceptionSubscriber(
			$serializer,
			$configuration,
			$controllerActionExtractor,
			$metadataSerializeManager,
			true
		);

		$this->assertEquals(
			$this->reflectionCallMethod($exceptionSubscriber, 'serialize', [ [ 'DATA' ], 'json' ]), 'serialized_data'
		);
	}

	public function testIsAuthenticatedUserNoTokenStorage() {
		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$configuration = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$exceptionSubscriber = new ExceptionSubscriber(
			$serializer,
			$configuration,
			$controllerActionExtractor,
			$metadataSerializeManager,
			true
		);

		$this->assertFalse(
			$this->reflectionCallMethod($exceptionSubscriber, 'isAuthenticated')
		);
	}

	public function testIsAuthenticatedNoToken() {
		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$configuration = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$tokenStorage = $this->getMockForAbstractClass(TokenStorageInterface::class);

		$tokenStorage
			->expects($this->once())
			->method('getToken')
			->willReturn(null)
		;

		$exceptionSubscriber = new ExceptionSubscriber(
			$serializer,
			$configuration,
			$controllerActionExtractor,
			$metadataSerializeManager,
			true
		);
		$exceptionSubscriber->setTokenStorage($tokenStorage);

		$this->assertFalse(
			$this->reflectionCallMethod($exceptionSubscriber, 'isAuthenticated')
		);
	}

	public function testIsAuthenticatedNoAuthenticated() {
		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$configuration = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$tokenStorage = $this->getMockForAbstractClass(TokenStorageInterface::class);
		$token = $this->getMockForAbstractClass(TokenInterface::class);

		$tokenStorage
			->expects($this->exactly(2))
			->method('getToken')
			->willReturn($token)
		;

		$token
			->expects($this->once())
			->method('getUser')
			->willReturn(null)
		;

		$exceptionSubscriber = new ExceptionSubscriber(
			$serializer,
			$configuration,
			$controllerActionExtractor,
			$metadataSerializeManager,
			true
		);
		$exceptionSubscriber->setTokenStorage($tokenStorage);

		$this->assertFalse(
			$this->reflectionCallMethod($exceptionSubscriber, 'isAuthenticated')
		);
	}

	public function testIsAuthenticatedAuthenticated() {
		$serializer = $this->getMockForAbstractClass(SerializerInterface::class);
		$configuration = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$controllerActionExtractor = $this->getMockForAbstractClass(ControllerActionExtractorInterface::class);
		$metadataSerializeManager = $this->getMockForAbstractClass(MetadataSerializeManagerInterface::class);
		$tokenStorage = $this->getMockForAbstractClass(TokenStorageInterface::class);
		$token = $this->getMockForAbstractClass(TokenInterface::class);
		$user = $this->getMockForAbstractClass(UserInterface::class);

		$tokenStorage
			->expects($this->exactly(2))
			->method('getToken')
			->willReturn($token)
		;

		$token
			->expects($this->once())
			->method('getUser')
			->willReturn($user)
		;

		$exceptionSubscriber = new ExceptionSubscriber(
			$serializer,
			$configuration,
			$controllerActionExtractor,
			$metadataSerializeManager,
			true
		);
		$exceptionSubscriber->setTokenStorage($tokenStorage);

		$this->assertTrue(
			$this->reflectionCallMethod($exceptionSubscriber, 'isAuthenticated')
		);
	}
}
