<?php
namespace GollumSF\RestBundle\EventSubscriber;

use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerActionExtractorInterface;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerializeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

class ExceptionSubscriber implements EventSubscriberInterface {

	/** @var SerializerInterface */
	private $serializer;

	/** @var ApiConfigurationInterface */
	private $apiConfiguration;

	/** @var ControllerActionExtractorInterface */
	private $controllerActionExtractor;

	/** @var MetadataSerializeManagerInterface */
	private $metadataSerializeManager;

	/** @var bool */
	private $debug;

	/** @var TokenStorageInterface */
	private $tokenStorage;

	public static function getSubscribedEvents() {
		return [
			KernelEvents::EXCEPTION => [
				['onKernelException', 256],
			],
		];
	}

	public function __construct(
		SerializerInterface $serializer,
		ApiConfigurationInterface $apiConfiguration,
		ControllerActionExtractorInterface $controllerActionExtractor,
		MetadataSerializeManagerInterface $metadataSerializeManager,
		bool $debug
	) {
		$this->serializer = $serializer;
		$this->apiConfiguration = $apiConfiguration;
		$this->controllerActionExtractor = $controllerActionExtractor;
		$this->metadataSerializeManager = $metadataSerializeManager;
		$this->debug = $debug;
	}

	public function setTokenStorage(TokenStorageInterface $tokenStorage) {
		$this->tokenStorage = $tokenStorage;
	}

	public function onKernelException(ExceptionEvent $event) {

		$controllerAction = $this->controllerActionExtractor->extractFromRequest($event->getRequest());
		$serialize = null;
		if ($controllerAction) {
			$serialize = $this->metadataSerializeManager->getMetadata($controllerAction->getControllerClass(), $controllerAction->getActionMethod());
		}

		if (
			$this->apiConfiguration->isAlwaysSerializedException() ||
			$serialize
		) {
			$code = Response::HTTP_INTERNAL_SERVER_ERROR;
			$e = $event->getThrowable();
			if (
				$e instanceof UnauthorizedHttpException ||
				$e instanceof AccessDeniedHttpException ||
				$e instanceof AccessDeniedException
			) {
				$code = Response::HTTP_UNAUTHORIZED;
				if ($this->isAuthenticated()) {
					$code = Response::HTTP_FORBIDDEN;
				}
			} else
			if (
				$e instanceof HttpException
			) {
				$code = $e->getStatusCode();
			}

			$json = $this->debug ? [
				'message' => $e->getMessage(),
				'code' => $e->getCode(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'stack' => array_map(function ($trace) {
					unset($trace['args']);
					return $trace;
				}, $e->getTrace()),
				'class' => get_class($e)
			] : [
				'message' => $e->getMessage(),
				'code' => $e->getCode(),
			];

			$content = $this->serialize($json, 'json');
			$headers = [
				'Content-Type'   => 'application/json',
				'Content-Length' => mb_strlen($content, 'UTF-8')
			];
			$event->setResponse(new Response($content, $code, $headers));
		}
	}


	protected function serialize($data, string $format) {
		return $this->serializer->serialize($data, $format);
	}

	protected function isAuthenticated(): bool {
		return $this->tokenStorage && $this->tokenStorage->getToken() && !!$this->tokenStorage->getToken()->getUser();
	}
}
