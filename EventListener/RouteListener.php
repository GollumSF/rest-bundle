<?php

namespace GollumSF\RestBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use GollumSF\CoreBundle\Event\RouteCreatedEvent;
use GollumSF\RestBundle\Annotation\Rest;
use Symfony\Component\Routing\Route;

/**
 * RouteListener
 *
 * @author Damien Duboeuf <smeagolworms4@gmail.com>
 */
class RouteListener {
	
	/**
	 * @var Reader
	 */
	protected $reader;
	
	/**
	 * Constructor.
	 * @param Reader $reader
	 */
	public function __construct(Reader $reader) {
		$this->reader = $reader;
	}
	
	public function onGSFCoreRouteCreated(RouteCreatedEvent $event) {
		$route = $event->getRoute();
		$this->setFormat($route);
		$this->forceSheme($route);
	}
	
	protected function setFormat (Route $route) {
		$controllerAction = explode('::', $route->getDefault('_controller'));
		
		$controller = new \ReflectionClass($controllerAction[0]);
		$action	    = $controller->getMethod($controllerAction[1]);
		$anno	    = $this->reader->getMethodAnnotation($action, Rest::class);
		if ($anno) {
			$route->setPath($route->getPath().'.{_format}');
			$route->setRequirement('_format', implode('|', [ 'json', 'xml' ])); // TODO add option on bundle
			$route->setDefault('_format', 'json');
		}
	}
	
	protected function forceSheme (Route $route) {
		$route->setSchemes(['http', 'https']); // TODO add option on bundle
	}

}
