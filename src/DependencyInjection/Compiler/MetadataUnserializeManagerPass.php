<?php

namespace GollumSF\RestBundle\DependencyInjection\Compiler;

use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserializeManagerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MetadataUnserializeManagerPass implements CompilerPassInterface
{
	public function process(ContainerBuilder $container)
	{
		// always first check if the primary service is defined
		if (!$container->has(MetadataUnserializeManagerInterface::class)) {
			return;
		}

		$definition = $container->findDefinition(MetadataUnserializeManagerInterface::class);

		$taggedServices = $container->findTaggedServiceIds(MetadataUnserializeManagerInterface::HANDLER_TAG);
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
