<?php
namespace GollumSF\RestBundle\DependencyInjection;

use GollumSF\RestBundle\Configuration\ApiConfiguration;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class GollumSFRestExtension extends Extension
{
	public function load(array $configs, ContainerBuilder $container)
	{
		$loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');
		$config = $this->processConfiguration(new Configuration(), $configs);

		$container
			->register(ApiConfigurationInterface::class, ApiConfiguration::class)
			->addArgument($config['maxLimitItem'])
			->addArgument($config['defaultLimitItem'])
		;
		
		
	}
}