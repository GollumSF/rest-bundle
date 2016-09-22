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
	 * @var array
	 */
	protected $configurations;
	
	/**
	 * Constructor.
	 * @param Reader $reader
	 */
	public function __construct(Reader $reader, array $configurations) {
		$this->reader         = $reader;
		$this->configurations = $configurations;
	}
	
	public function onGSFCoreRouteCreated(RouteCreatedEvent $event) {
		$route = $event->getRoute();
		$this->setFormat($route);
	}
	
	protected function setFormat (Route $route) {
		if (!$this->configurations['overrideUrlExtension']) {
			return;
		}
		
		$controllerAction = explode('::', $route->getDefault('_controller'));
		
		$controller = new \ReflectionClass($controllerAction[0]);
		$action	    = $controller->getMethod($controllerAction[1]);
		$anno	    = $this->reader->getMethodAnnotation($action, Rest::class);
		if ($anno) {
			$route->setPath($route->getPath().'.{_format}');
			$route->setRequirement('_format', implode('|', $this->configurations['format']));
			$route->setDefault('_format', $this->configurations['defaultFormat']);
			$this->forceSchemes($route);
		}
	}
	
	protected function forceSchemes(Route $route) {
		$route->setSchemes($this->configurations['schemes']);
	}

}
