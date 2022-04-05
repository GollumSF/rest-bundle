<?php
namespace GollumSF\RestBundle\DependencyInjection;

use Doctrine\Persistence\ManagerRegistry;
use GollumSF\RestBundle\Configuration\ApiConfiguration;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\EventSubscriber\SerializerSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
		$config = $this->processConfiguration(new Configuration(), $configs);

		$container
			->register(ApiConfigurationInterface::class, ApiConfiguration::class)
			->addArgument($config['max_limit_item'])
			->addArgument($config['default_limit_item'])
			->addArgument($config['always_serialized_exception'])
		;
	}
}
