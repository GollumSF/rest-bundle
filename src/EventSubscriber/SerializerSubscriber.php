<?php
namespace GollumSF\RestBundle\EventSubscriber;

use Doctrine\Persistence\ManagerRegistry;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerAction;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerActionExtractorInterface;
use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Annotation\Validate;
use GollumSF\RestBundle\Exceptions\UnserializeValidateException;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerializeManagerInterface;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserializeManagerInterface;
use GollumSF\RestBundle\Metadata\Validate\MetadataValidateManagerInterface;
use GollumSF\RestBundle\Serializer\Transform\SerializerTransformInterface;
use GollumSF\RestBundle\Serializer\Transform\UnserializerTransformInterface;
use GollumSF\RestBundle\Traits\ManagerRegistryToManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SerializerSubscriber implements EventSubscriberInterface {
	
	use ManagerRegistryToManager;
	
	/** @var SerializerInterface */
	private $serializer;
	
	/** @var ControllerActionExtractorInterface */
	private $controllerActionExtractor;
	
	/** @var MetadataSerializeManagerInterface */
	private $metadataSerializeManager;
	
	/** @var MetadataUnserializeManagerInterface */
	private $metadataUnserializeManager;
	
	/** @var MetadataValidateManagerInterface */
	private $metadataValidateManager;
	
	/** @var ManagerRegistry */
	private $managerRegistry;
	
	/** @var ValidatorInterface */
	private $validator;
	
	public static function getSubscribedEvents() {
		return [
			KernelEvents::CONTROLLER_ARGUMENTS => [
				['onKernelControllerArguments', -10],
			],
			KernelEvents::VIEW => [
				['onKernelView', -10],
			],
			KernelEvents::EXCEPTION => [
				['onKernelValidateException', 257],
			],
		];
	}
	
	public function __construct(
		SerializerInterface $serializer,
		ControllerActionExtractorInterface $controllerActionExtractor,
		MetadataSerializeManagerInterface $metadataSerializeManager,
		MetadataUnserializeManagerInterface $metadataUnserializeManager,
		MetadataValidateManagerInterface $metadataValidateManager
	) {
		$this->serializer = $serializer;
		$this->controllerActionExtractor = $controllerActionExtractor;
		$this->metadataSerializeManager = $metadataSerializeManager;
		$this->metadataUnserializeManager = $metadataUnserializeManager;
		$this->metadataValidateManager = $metadataValidateManager;
	}

	public function setManagerRegistry(ManagerRegistry $managerRegistry): self {
		$this->managerRegistry = $managerRegistry;
		return $this;
	}

	public function setValidator(ValidatorInterface $validator): self {
		$this->validator = $validator;
		return $this;
	}
	
	public function onKernelControllerArguments(ControllerArgumentsEvent $event) {
		
		$request  = $event->getRequest();
		
		$controllerAction = $this->controllerActionExtractor->extractFromRequest($request);
		$metadata = $this->metadataUnserializeManager->getMetadata($controllerAction->getControllerClass(), $controllerAction->getActionMethod());
		
		if ($metadata) {
			
			$content = $request->getContent();
			$entity = $request->attributes->get($metadata->getName());
			
			$groups = array_merge(
				[ strtolower($request->getMethod()) ],
				$metadata->getGroups()
			);

			$class = $request->attributes->get(Unserialize::REQUEST_ATTRIBUTE_CLASS);
			if (!$class && $entity) {
				$class = get_class($entity);
			}

			if (!$class) {
				throw new \LogicException('Class not found on un serialize action');
			}
			
			if ($content) {
				$entity = $this->unserialize($content, $entity, $class, $groups);
			}

			if (!$entity) {
				throw new BadRequestHttpException('Bad parameter on request content');
			}
	
			$request->attributes->set($metadata->getName(), $entity);
			
			$this->validate($request, $entity);

			if ($metadata->isSave() && $this->isEntity($entity)) {
				$em = $this->getEntityManagerForClass($entity);
				$em->persist($entity);
				$em->flush();
			}
			
		}
	}
	
	public function onKernelView(ViewEvent $event) {
		
		$request  = $event->getRequest();
		
		$controllerAction = $this->controllerActionExtractor->extractFromRequest($request);
		$metadata = $this->metadataSerializeManager->getMetadata($controllerAction->getControllerClass(), $controllerAction->getActionMethod());
		
		/** @var Serialize $annotation */
		if ($metadata) {

			$data = $event->getControllerResult();
			$groups = array_merge([ 'get' ], $metadata->getGroups());

			$content = $this->serialize($data,'json', $groups);

			$headers = $metadata->getHeaders();
			$headers['Content-Type']   = 'application/json';
			$headers['Content-Length'] = mb_strlen($content, 'UTF-8');

			$event->setResponse(new Response($content, $metadata->getCode(), $headers));
		}
	}

	public function onKernelValidateException(ExceptionEvent $event) {
		
		$e = $event->getThrowable();
		if ($e instanceof UnserializeValidateException) {
			$rtn = [];

			foreach ($e->getConstraints() as $violation) {

				$prop = $violation->getPropertyPath();
				if (!$prop) {
					$prop = '_root_';
				}
				if (!array_key_exists($prop, $rtn)) {
					$rtn[$prop] = [];
				}
				$rtn[$prop][] = $violation->getMessage();
			}

			$content = $this->serializer->serialize($rtn, 'json');

			$headers = [
				'Content-Type'   => 'application/json',
				'Content-Length' => mb_strlen($content, 'UTF-8')
			];

			$event->setResponse(new Response($content, Response::HTTP_BAD_REQUEST, $headers));
		}
	}
	
	protected function unserialize(string $content, $entity, string $class, array $groups) {
		try {
			$format = 'json';
			$context = [
				'groups' => $groups,
			];
			if ($entity) {
				$context['object_to_populate'] = $entity;
			}
			
			if (!$this->serializer->supportsDecoding($format, $context)) {
				throw new NotEncodableValueException(sprintf('Deserialization for the format %s is not supported', $format));
			}

			$data = $this->serializer->decode($content, $format, $context);

			$entity = $this->serializer->denormalize($data, $class, $format, $context);

			if ($entity instanceof UnserializerTransformInterface) {
				$entity->unserializeTransform($data, $groups);
			}

			return $entity;

		} catch (MissingConstructorArgumentsException $e) {
			throw new BadRequestHttpException($e->getMessage());
		} catch (\UnexpectedValueException $e) {
			throw new BadRequestHttpException($e->getMessage());
		}
	}

	protected function validate(Request $request, $entity): void {
		
		$controllerAction = $this->controllerActionExtractor->extractFromRequest($request);
		$metadata = $this->metadataValidateManager->getMetadata($controllerAction->getControllerClass(), $controllerAction->getActionMethod());
		
		if ($metadata) {

			$groups = array_merge([
				strtolower($request->getMethod()) ],
				$metadata->getGroups()
			);
			
			if (!$this->validator) {
				throw new \LogicException(sprintf('%s service not declared.', ValidatorInterface::class));
			}

			$errors = $this->validator->validate($entity, null, $groups);
			if ($errors->count()) {
				throw new UnserializeValidateException($errors);
			}
		}
	}
	
	protected function serialize($data, string $format, array $groups) {
		
		$context = [
			'groups' =>  $groups,
		];
		
		if (!$this->serializer->supportsEncoding($format, $context)) {
			throw new NotEncodableValueException(sprintf('Serialization for the format %s is not supported', $format));
		}

		$json = $this->serializer->normalize($data, $format, $context);
		
		if ($data instanceof SerializerTransformInterface) {
			$json = $data->serializeTransform($json, $groups);
		}
		
		return $this->serializer->encode($json, $format, $context);
	}
}
