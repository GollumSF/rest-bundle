<?php
namespace GollumSF\RestBundle\DependencyInjection;

use GollumSF\RestBundle\Configuration\ApiConfiguration;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerializeManagerInterface;
use GollumSF\RestBundle\Metadata\Sort\MetadataSortManagerInterface;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserializeManagerInterface;
use GollumSF\RestBundle\Metadata\Validate\MetadataValidateManagerInterface;
use GollumSF\RestBundle\Sort\ApiSorterInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class GollumSFRestExtension extends Extension
{
	public function load(array $configs, ContainerBuilder $container): void
	{
		$loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');

		$container
			->registerForAutoconfiguration(ApiSorterInterface::class)
			->addTag(ApiSorterInterface::TAG)
		;

		$config = $this->processConfiguration(new Configuration(), $configs);

		$container
			->register(ApiConfigurationInterface::class, ApiConfiguration::class)
			->addArgument($config['max_limit_item'])
			->addArgument($config['default_limit_item'])
			->addArgument($config['always_serialized_exception'])
		;

		$this->registerMetadataConfigHandlers($container, $config['metadata']);
	}

	/**
	 * Declared metadata takes precedence over the attributes: a lower priority is walked
	 * first, and a manager stops on the first handler answering. That is what lets an
	 * application override a class it cannot annotate.
	 */
	private function registerMetadataConfigHandlers(ContainerBuilder $container, array $metadata): void {

		$controllers = $metadata['controllers'];
		$entities = $metadata['entities'];

		$handlers = [
			\GollumSF\RestBundle\Metadata\Serialize\Handler\ConfigHandler::class   => [ MetadataSerializeManagerInterface::HANDLER_TAG, [ $controllers ] ],
			\GollumSF\RestBundle\Metadata\Unserialize\Handler\ConfigHandler::class => [ MetadataUnserializeManagerInterface::HANDLER_TAG, [ $controllers ] ],
			\GollumSF\RestBundle\Metadata\Validate\Handler\ConfigHandler::class    => [ MetadataValidateManagerInterface::HANDLER_TAG, [ $controllers ] ],
			\GollumSF\RestBundle\Metadata\Sort\Handler\ConfigHandler::class        => [ MetadataSortManagerInterface::HANDLER_TAG, [ $entities, $controllers ] ],
		];

		foreach ($handlers as $class => [ $tag, $arguments ]) {
			$definition = $container->register($class, $class);
			foreach ($arguments as $argument) {
				$definition->addArgument($argument);
			}
			$definition->addTag($tag, [ 'priority' => -10 ]);
		}
	}
}
