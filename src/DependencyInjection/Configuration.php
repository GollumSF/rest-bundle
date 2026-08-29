<?php

namespace GollumSF\RestBundle\DependencyInjection;

use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\Model\Direction;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Response;

class Configuration implements ConfigurationInterface {

	public function getConfigTreeBuilder(): TreeBuilder {
		
		$treeBuilder = new TreeBuilder('gollum_sf_rest');

		$treeBuilder->getRootNode()->children()
			->integerNode('max_limit_item')->defaultValue(ApiConfigurationInterface::DEFAULT_MAX_LIMIT_ITEM)->end()
			->integerNode('default_limit_item')->defaultValue(ApiConfigurationInterface::DEFAULT_DEFAULT_LIMIT_ITEM)->end()
			->booleanNode('always_serialized_exception')->defaultValue(ApiConfigurationInterface::DEFAULT_ALWAYS_SERIALIZED_EXCEPTION)->end()
			->append($this->getMetadataNode())
		->end();

		return $treeBuilder;
	}

	/**
	 * Declares what the #[Serialize], #[Unserialize], #[Validate] and #[ApiSortable]
	 * attributes declare, for the classes you cannot annotate.
	 */
	private function getMetadataNode(): ArrayNodeDefinition {

		$node = (new TreeBuilder('metadata'))->getRootNode();

		$node
			->addDefaultsIfNotSet()
			->children()

				->arrayNode('controllers')
					->info('Keyed by "Fully\\Qualified\\Controller::action".')
					->useAttributeAsKey('name')
					->arrayPrototype()
						->children()

							->arrayNode('serialize')
								->canBeUnset()
								->children()
									->integerNode('code')->defaultValue(Response::HTTP_OK)->end()
									->append($this->getGroupsNode([]))
									->arrayNode('headers')
										->normalizeKeys(false)
										->scalarPrototype()->end()
									->end()
								->end()
							->end()

							->arrayNode('unserialize')
								->canBeUnset()
								->children()
									->scalarNode('name')->defaultValue('')->end()
									->append($this->getGroupsNode([]))
									->booleanNode('save')->defaultTrue()->end()
									->scalarNode('type')
										->info('Target class. Defaults to the type of the controller parameter named after "name".')
										->defaultNull()
									->end()
								->end()
							->end()

							->arrayNode('validate')
								->canBeUnset()
								->children()
									->append($this->getGroupsNode([ 'Default' ]))
								->end()
							->end()

						->end()
					->end()
				->end()

				->arrayNode('entities')
					->info('Keyed by entity class.')
					->useAttributeAsKey('name')
					->arrayPrototype()
						->children()
							->arrayNode('sortable')
								->info('Sort keys usable through the "order" query parameter.')
								->useAttributeAsKey('name')
								->arrayPrototype()
									->beforeNormalization()->ifNull()->thenEmptyArray()->end()
									->children()
										->scalarNode('path')
											->info('Property path relative to the entity, dots crossing relations. Defaults to the key.')
											->defaultNull()
										->end()
										->scalarNode('sorter')
											->info('Class name of an ApiSorterInterface service.')
											->defaultNull()
										->end()
										->enumNode('direction')
											->info('Direction applied when the request asks for none.')
											->values([ Direction::ASC->value, Direction::DESC->value ])
											->defaultNull()
										->end()
									->end()
								->end()
							->end()
						->end()
					->end()
				->end()

			->end()
		;

		return $node;
	}

	/**
	 * Accepts a single group as well as a list of them, like the attributes do.
	 */
	private function getGroupsNode(array $default): ArrayNodeDefinition {
		$node = (new TreeBuilder('groups'))->getRootNode();
		$node
			->beforeNormalization()->ifString()->then(function ($value) { return [ $value ]; })->end()
			->scalarPrototype()->end()
			->defaultValue($default)
		;
		return $node;
	}
}
