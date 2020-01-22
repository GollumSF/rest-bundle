<?php
namespace GollumSF\RestBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Annotation\Validate;
use GollumSF\RestBundle\Exceptions\UnserializeValidateException;
use GollumSF\RestBundle\Serializer\Transform\SerializerTransformInterface;
use GollumSF\RestBundle\Serializer\Transform\UnserializerTransformInterface;
use GollumSF\RestBundle\Traits\AnnotationControllerReader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SerializerSubscriber implements EventSubscriberInterface {
	
	use AnnotationControllerReader;
	
	/**
	 * @var SerializerInterface
	 */
	private $serializer;
	/**
	 * @var EntityManagerInterface
	 */
	private $em;
	/**
	 * @var ValidatorInterface
	 */
	private $validator;
	
	public static function getSubscribedEvents() {
		return [
			KernelEvents::CONTROLLER_ARGUMENTS => [
				['onKernelControllerArguments', -1],
			],
			KernelEvents::VIEW => [
				['onKernelView', -1],
			],
			KernelEvents::EXCEPTION => [
				['onKernelException', 256],
			],
		];
	}
	
	public function __construct(
		SerializerInterface $serializer,
		EntityManagerInterface $em,
		ValidatorInterface $validator
	) {
		$this->serializer = $serializer;
		$this->em = $em;
		$this->validator = $validator;
	}
	
	/**
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function onKernelControllerArguments(ControllerArgumentsEvent $event) {
		
		$request = $event->getRequest();
		
		/** @var Unserialize $annotation */
		$annotation = $this->getAnnotation($request, Unserialize::class);
		if ($annotation) {
			
			$content = $request->getContent();
			$entity = $request->attributes->get($annotation->name);
			
			$groups = $annotation->groups;
			if (!is_array($groups)) {
				$groups = [ $groups ];
			}
			$groups = array_merge([ strtolower($request->getMethod()) ], $groups);

			$this->unserialize($content, $entity, $groups);

			$this->validate($request, $entity);

			if ($annotation->save && $this->isEntity($entity)) {
				$this->em->persist($entity);
				$this->em->flush();
			}
			
		}
	}
	
	protected function unserialize(string $content, $entity, array $groups): void {
		try {
			$this->serializer->deserialize($content, get_class($entity), 'json', [
				'groups' => $groups,
				'object_to_populate' => $entity,
			]);
		} catch (\UnexpectedValueException $e) {
			throw new BadRequestHttpException($e->getMessage());
		}

		if ($entity instanceof UnserializerTransformInterface) {
			$entity->unserializeTransform(\json_decode($content), $groups);
		}
	}

	protected function validate(Request $request, $entity): void {
		/** @var Validate $annotationValidate */
		$annotationValidate = $this->getAnnotation($request, Validate::class);
		if ($annotationValidate) {

			if (!is_array($annotationValidate->groups)) {
				$annotationValidate->groups = [$annotationValidate->groups];
			}

			$errors = $this->validator->validate($entity, null, $annotationValidate->groups);
			if ($errors->count()) {
				throw new UnserializeValidateException($errors);
			}
		}
	}

	protected  function isEntity($class) {
		if (is_object($class)) {
			$class = ($class instanceof Proxy) ? get_parent_class($class) : get_class($class);
		}
		return ! $this->em->getMetadataFactory()->isTransient($class);
	}
	
	public function onKernelView(ViewEvent $event) {
		
		$request  = $event->getRequest();
		
		/** @var Serialize $annotation */
		$annotation = $this->getAnnotation($request, Serialize::class);
		if ($annotation) {
			
			if (!is_array($annotation->groups)) {
				$annotation->groups = [ $annotation->groups ];
			}
			
			$data = $event->getControllerResult();
			$groups = array_merge([ 'get' ], $annotation->groups);
			
			$content = $this->serialize($data,'json', $groups);
			
			$annotation->headers['Content-Type']   = 'application/json';
			$annotation->headers['Content-Length'] = mb_strlen($content, 'UTF-8');
			
			$event->setResponse(new Response($content, $annotation->code, $annotation->headers));
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
	
	public function onKernelException(ExceptionEvent $event) {
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
}
