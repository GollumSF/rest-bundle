<?php

namespace GollumSF\RestBundle\DependencyInjection\Compiler;

use GollumSF\RestBundle\EventSubscriber\SerializerSubscriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorPass implements CompilerPassInterface {
	
	public function process(ContainerBuilder $container) {
		if (!$container->hasDefinition(ValidatorInterface::class) && !$container->hasAlias(ValidatorInterface::class)) {
			return;
		}
		$serializerSubscriber = $container->getDefinition(SerializerSubscriber::class);
		$serializerSubscriber->addMethodCall('setValidator', [ new Reference(ValidatorInterface::class) ]);
	}
}
