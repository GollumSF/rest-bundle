<?php

namespace GollumSF\RestBundle\DependencyInjection;

use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {

	public function getConfigTreeBuilder() {
		
		$treeBuilder = new TreeBuilder('gollum_sf_rest');

		$treeBuilder->getRootNode()->children()
			->integerNode('maxLimitItem')->defaultValue(ApiConfigurationInterface::DEFAULT_MAX_LIMIT_ITEM)->end()
			->integerNode('defaultLimitItem')->defaultValue(ApiConfigurationInterface::DEFAULT_DEFAULT_LIMIT_ITEM)->end()
		->end();

		return $treeBuilder;
	}
}