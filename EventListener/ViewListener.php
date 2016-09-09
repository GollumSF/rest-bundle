<?php

namespace GollumSF\RestBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use GollumSF\RestBundle\Annotation\Rest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * ViewListener
 *
 * @author Damien Duboeuf <smeagolworms4@gmail.com>
 */
class ViewListener implements EventSubscriberInterface {

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var Reader
	 */
	protected $reader;
	
	/**
	 * @var Serializer
	 */
	private $serializer;

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container The service container instance
	 */
	public function __construct(ContainerInterface $container) {
		$this->container  = $container;
		$this->reader     = $container->get('annotation_reader');
		$this->serializer = $container->get('jms_serializer');
	}

	/**
	 * Renders the template and initializes a new response object with the
	 * rendered template content.
	 *
	 * @param GetResponseForControllerResultEvent $event
	 */
	public function onKernelView(GetResponseForControllerResultEvent $event) {

		$request  = $event->getRequest();
		list($controller, $action) = $this->getControllerAction($request);

		if (!$controller || !$action) {
			return;
		}

		/**
		 * @var $anno Rest
		 */
		$rClass  = new \reflectionClass($controller);
		$rMethod = $rClass->getMethod($action);
		$anno    = $this->reader->getMethodAnnotation($rMethod, Rest::class);

		if (!$anno) {
			return;
		}

		$code       = $anno->code;
		$data       = $event->getControllerResult();
		$serialized = $this->serialize ($data, $request->get('_format'));
		$header     = [
			'Content-type' => 'application/json; charset=utf-8',
			'Content-length' => mb_strlen($serialized),
		];

		$event->setResponse(new Response($serialized, $code, $header));
	}

	private function getControllerAction(Request $request) {
		$explode = explode('::', $request->attributes->get('_controller'));
		return [ $explode[0], isset($explode[1]) ? $explode[1] : null ];
	}

	protected function serialize ($data, $format) {
		return $this->serializer->serialize($data, $format);
	}

	public static function getSubscribedEvents() {
		return [
			KernelEvents::VIEW => 'onKernelView',
		];
	}
}
