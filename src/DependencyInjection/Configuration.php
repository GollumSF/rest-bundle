<?php

namespace GollumSF\RestBundle\DependencyInjection;

use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {

	public function getConfigTreeBuilder() {
		
		$treeBuilder = new TreeBuilder('gollum_sf_rest');

		$treeBuilder->getRootNode()->children()
			->integerNode('max_limit_item')->defaultValue(ApiConfigurationInterface::DEFAULT_MAX_LIMIT_ITEM)->end()
			->integerNode('default_limit_item')->defaultValue(ApiConfigurationInterface::DEFAULT_DEFAULT_LIMIT_ITEM)->end()
			->booleanNode('always_serialized_exception')->defaultValue(ApiConfigurationInterface::DEFAULT_ALWAYS_SERIALIZED_EXCEPTION)->end()
		->end();

		return $treeBuilder;
	}
}