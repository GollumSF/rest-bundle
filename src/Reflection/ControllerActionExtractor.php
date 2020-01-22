<?php

namespace GollumSF\RestBundle\Reflection;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ControllerActionExtractor implements ControllerActionExtractorInterface
{
	/** @var ContainerInterface  */
	private $container;
	
	public function __construct(
		ContainerInterface $container
	) {
		$this->container = $container;
	}

	public function extractFromString($controllerAction): ?ControllerAction {

		if (!$controllerAction) {
			return null;
		}
		if (\is_string($controllerAction)) {
			if (false !== strpos($controllerAction, '::')) {
				$controllerAction = explode('::', $controllerAction);
			} else {
				$controllerAction = [ $controllerAction ];
			}
		}
		
		if (is_array($controllerAction)) {
			if (count($controllerAction) === 1) {
				$controllerAction[1] = '__invoke';
			}
			
			if (isset($controllerAction[1])) {
				if (is_object($controllerAction[0])) {
					$controllerAction[0] = get_class($controllerAction[0]);
				}
				if ($this->container->has($controllerAction[0])) {
					$controllerAction[0] = get_class($this->container->get($controllerAction[0]));
				}
				if (class_exists($controllerAction[0])) {
					return new ControllerAction(
						$controllerAction[0],
						$controllerAction[1]
					);
				}
			}
		}
		
		return null;
	}
}