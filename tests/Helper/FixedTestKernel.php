<?php
namespace Test\GollumSF\RestBundle\Helper;

use Nyholm\BundleTest\TestKernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Workaround for nyholm/symfony-bundle-test v3 injecting deprecated
 * 'annotations' config that SF 8+ doesn't recognize.
 */
class FixedTestKernel extends TestKernel {
	protected function buildContainer(): ContainerBuilder {
		$container = parent::buildContainer();
		$r = new \ReflectionProperty($container, 'extensionConfigs');
		$r->setAccessible(true);
		$all = $r->getValue($container);
		if (isset($all['framework'])) {
			foreach ($all['framework'] as $i => $config) {
				unset($all['framework'][$i]['annotations']);
			}
			$r->setValue($container, $all);
		}
		return $container;
	}
}
