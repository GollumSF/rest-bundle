<?php
namespace GollumSF\RestBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Annotation\Validate;
use GollumSF\RestBundle\Exceptions\UnserializeValidateException;
use GollumSF\RestBundle\Serializer\Transform\SerializerTransformInterface;
use GollumSF\RestBundle\Serializer\Transform\UnserializerTransformInterface;
use GollumSF\RestBundle\Traits\AnnotationControllerReader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
	 * @param FilterControllerEvent $event
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function onKernelControllerArguments(ControllerArgumentsEvent $event) {
		
		if (!$event->isMasterRequest()) {
			return;
		}
		
		$request = $event->getRequest();
		
		/** @var Unserialize $annotation */
		$annotation = $this->getAnnotation($request, Unserialize::class);
		if ($annotation) {
			
			$content = $request->getContent();
			$entity = $request->attributes->get($annotation->name);
			
			if (!is_array($annotation->groups)) {
				$annotation->groups = [ $annotation->groups ];
			}
			
			try {
				$this->serializer->deserialize($content, get_class($entity), 'json', [
					'groups' => array_merge([ strtolower($request->getMethod()) ], $annotation->groups),
					'object_to_populate' => $entity,
				]);
			} catch (NotEncodableValueException $e) {
				throw new BadRequestHttpException($e->getMessage());
			}
			
			if ($entity instanceof UnserializerTransformInterface) {
				$entity->unserializeTransform(\json_decode($content), $annotation->groups);
			}
			
			/** @var Validate $annotationValidate */
			$annotationValidate = $this->getAnnotation($request, Validate::class);
			if ($annotationValidate) {
				
				if (!is_array($annotationValidate->groups)) {
					$annotationValidate->groups = [ $annotationValidate->groups ];
				}
				
				$errors = $this->validator->validate($entity, null, $annotationValidate->groups);
				if($errors->count()) {
					throw new UnserializeValidateException($errors);
				}
			}
			
			if ($annotation->save) {
				$this->em->persist($entity);
				$this->em->flush($entity);
			}
			
		}
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
			
			$content = $this->serializer->serialize($data,'json', [
				'groups' =>  array_merge([ 'get' ], $annotation->groups),
			]);
			
			if ($data instanceof SerializerTransformInterface) {
				$content = $data->serialize($content, $annotation->groups);
			}
			
			$annotation->headers['Content-Type']   = 'application/json';
			$annotation->headers['Content-Length'] = mb_strlen($content, 'UTF-8');
			
			$event->setResponse(new Response($content, $annotation->code, $annotation->headers));
		}
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
			
			$event->setResponse(new JsonResponse($rtn, Response::HTTP_BAD_REQUEST));
		}
	}
}
