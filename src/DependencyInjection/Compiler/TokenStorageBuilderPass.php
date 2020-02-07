<?php

namespace GollumSF\RestBundle\DependencyInjection\Compiler;


use GollumSF\RestBundle\EventSubscriber\ExceptionSubscriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TokenStorageBuilderPass implements CompilerPassInterface {
	
	public function process(ContainerBuilder $container) {
		if (!$container->hasDefinition(TokenStorageInterface::class) && !$container->hasAlias(TokenStorageInterface::class)) {
			return;
		}
		$container->getDefinition(ExceptionSubscriber::class      )->addMethodCall('setTokenStorage', [ new Reference(TokenStorageInterface::class) ]);
	}
}
