<?php

namespace GollumSF\RestBundle\DependencyInjection\Compiler;

use GollumSF\RestBundle\Metadata\Sort\MetadataSortManagerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MetadataSortManagerPass implements CompilerPassInterface
{
	public function process(ContainerBuilder $container): void
	{
		// always first check if the primary service is defined
		if (!$container->has(MetadataSortManagerInterface::class)) {
			return;
		}

		$definition = $container->findDefinition(MetadataSortManagerInterface::class);

		$taggedServices = $container->findTaggedServiceIds(MetadataSortManagerInterface::HANDLER_TAG);
		uasort($taggedServices, function ($a, $b) {
			$aVal = isset($a[0]) && isset($a[0]['priority']) ? $a[0]['priority'] : 0;
			$bVal = isset($b[0]) && isset($b[0]['priority']) ? $b[0]['priority'] : 0;
			if ($aVal === $bVal) {
				return 0;
			}
			return ($aVal < $bVal) ? -1 : 1;
		});
		foreach ($taggedServices as $id => $tags) {
			$definition->addMethodCall('addHandler', [new Reference($id)]);
		}
	}
}
