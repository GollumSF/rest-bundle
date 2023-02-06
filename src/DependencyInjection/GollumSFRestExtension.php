<?php
namespace GollumSF\RestBundle\DependencyInjection;

use GollumSF\RestBundle\Configuration\ApiConfiguration;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;

class GollumSFRestExtension extends Extension
{
	public function load(array $configs, ContainerBuilder $container)
	{
		$loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');
		if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
			// @codeCoverageIgnoreStart
			$loader->load('services_php8.yml');
			// @codeCoverageIgnoreEnd
		}
		if (version_compare(Kernel::VERSION, '6.2.0', '>=')) {
			// @codeCoverageIgnoreStart
			$loader->load('services_sf62.yml');
			// @codeCoverageIgnoreEnd
		}
		$config = $this->processConfiguration(new Configuration(), $configs);

		$container
			->register(ApiConfigurationInterface::class, ApiConfiguration::class)
			->addArgument($config['max_limit_item'])
			->addArgument($config['default_limit_item'])
			->addArgument($config['always_serialized_exception'])
		;
	}
}
