<?php

namespace GollumSF\RestBundle\DependencyInjection\Compiler;

use GollumSF\RestBundle\Sort\ApiOrderResolverInterface;
use GollumSF\RestBundle\Sort\ApiSorterInterface;
use GollumSF\RestBundle\Sort\Handler\MetadataHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ApiOrderResolverPass implements CompilerPassInterface
{
	public function process(ContainerBuilder $container): void
	{
		if (!$container->has(ApiOrderResolverInterface::class)) {
			return;
		}

		$definition = $container->findDefinition(ApiOrderResolverInterface::class);

		$taggedServices = $container->findTaggedServiceIds(ApiOrderResolverInterface::HANDLER_TAG);
		uasort($taggedServices, function ($a, $b) {
			$aVal = isset($a[0]) && isset($a[0]['priority']) ? $a[0]['priority'] : 0;
			$bVal = isset($b[0]) && isset($b[0]['priority']) ? $b[0]['priority'] : 0;
			if ($aVal === $bVal) {
				return 0;
			}
			return ($aVal < $bVal) ? 1 : -1;
		});
		foreach ($taggedServices as $id => $tags) {
			$definition->addMethodCall('addHandler', [new Reference($id)]);
		}

		if ($container->has(MetadataHandler::class)) {
			$sorters = [];
			foreach ($container->findTaggedServiceIds(ApiSorterInterface::TAG) as $id => $tags) {
				$sorters[$id] = new Reference($id);
			}
			$container
				->findDefinition(MetadataHandler::class)
				->setArgument('$sorters', ServiceLocatorTagPass::register($container, $sorters))
			;
		}
	}
}
