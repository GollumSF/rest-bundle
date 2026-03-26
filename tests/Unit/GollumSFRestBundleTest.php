<?php
namespace Test\GollumSF\RestBundle\Unit;

use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Configuration\ApiConfiguration;
use Doctrine\Persistence\ManagerRegistry;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\EventSubscriber\ExceptionSubscriber;
use GollumSF\RestBundle\EventSubscriber\SerializerSubscriber;
use GollumSF\RestBundle\GollumSFRestBundle;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerializeManager;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerializeManagerInterface;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserializeManager;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserializeManagerInterface;
use GollumSF\RestBundle\Metadata\Validate\MetadataValidateManager;
use GollumSF\RestBundle\Metadata\Validate\MetadataValidateManagerInterface;
use GollumSF\RestBundle\Search\ApiSearch;
use GollumSF\RestBundle\Search\ApiSearchInterface;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineIdDenormalizer;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineObjectDenormalizer;
use GollumSF\RestBundle\Serializer\Normalizer\RecursiveObjectNormalizer;
use Nyholm\BundleTest\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GollumSFRestBundleTest extends KernelTestCase {

	use ReflectionPropertyTrait;

	protected static function getKernelClass(): string {
		return TestKernel::class;
	}

	protected static function createKernel(array $options = []): KernelInterface {
		$kernel = new TestKernel('test', true);
		if (isset($options['config']) && is_callable($options['config'])) {
			$options['config']($kernel);
		}
		return $kernel;
	}

	private function bootTestKernel(array $configs = [], array $bundles = []): ContainerInterface {
		self::bootKernel(['config' => function (TestKernel $kernel) use ($configs, $bundles) {
			$kernel->addTestBundle(GollumSFRestBundle::class);
			$kernel->addTestBundle(\GollumSF\ControllerActionExtractorBundle\GollumSFControllerActionExtractorBundle::class);
			foreach ($bundles as $bundle) {
				$kernel->addTestBundle($bundle);
			}
			foreach ($configs as $config) {
				$kernel->addTestConfig($config);
			}
			$kernel->addTestCompilerPass(new class implements CompilerPassInterface {
				public function process(ContainerBuilder $container): void {
					foreach ($container->getDefinitions() as $id => $definition) {
						if (str_starts_with($id, 'GollumSF\\')) {
							$definition->setPublic(true);
						}
					}
					foreach ($container->getAliases() as $id => $alias) {
						if (str_starts_with($id, 'GollumSF\\')) {
							$alias->setPublic(true);
						}
					}
				}
			}, PassConfig::TYPE_BEFORE_REMOVING);
		}]);
		return self::$kernel->getContainer();
	}

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	public function testInitBundle() {
		$container = $this->bootTestKernel([__DIR__.'/Resources/config.yaml']);

		$this->assertInstanceOf(ApiSearchInterface::class       , $container->get(ApiSearchInterface::class));
		$this->assertInstanceOf(ApiConfigurationInterface::class, $container->get(ApiConfigurationInterface::class));
		$this->assertInstanceOf(ApiSearch::class       , $container->get(ApiSearchInterface::class));
		$this->assertInstanceOf(ApiConfiguration::class, $container->get(ApiConfigurationInterface::class));
		$this->assertInstanceOf(DoctrineIdDenormalizer::class    , $container->get(DoctrineIdDenormalizer::class));
		$this->assertInstanceOf(DoctrineObjectDenormalizer::class, $container->get(DoctrineObjectDenormalizer::class));
		$this->assertInstanceOf(RecursiveObjectNormalizer::class , $container->get(RecursiveObjectNormalizer::class));
		$this->assertInstanceOf(SerializerSubscriber::class      , $container->get(SerializerSubscriber::class));
		$this->assertInstanceOf(ExceptionSubscriber::class       , $container->get(ExceptionSubscriber::class));
		$this->assertInstanceOf(MetadataSerializeManager::class  , $container->get(MetadataSerializeManagerInterface::class));
		$this->assertInstanceOf(MetadataUnserializeManager::class, $container->get(MetadataUnserializeManagerInterface::class));
		$this->assertInstanceOf(MetadataValidateManager::class   , $container->get(MetadataValidateManagerInterface::class));

		$handlersSerialize = $this->reflectionGetValue($container->get(MetadataSerializeManagerInterface::class), 'handlers');
		$this->assertGreaterThanOrEqual(1, count($handlersSerialize));
		$this->assertInstanceOf(\GollumSF\RestBundle\Metadata\Serialize\Handler\AttributeHandler::class, $handlersSerialize[count($handlersSerialize) - 1]);

		$handlersUnserialize = $this->reflectionGetValue($container->get(MetadataUnserializeManagerInterface::class), 'handlers');
		$this->assertGreaterThanOrEqual(1, count($handlersUnserialize));
		$this->assertInstanceOf(\GollumSF\RestBundle\Metadata\Unserialize\Handler\AttributeHandler::class, $handlersUnserialize[count($handlersUnserialize) - 1]);

		$handlersValidate = $this->reflectionGetValue($container->get(MetadataValidateManagerInterface::class), 'handlers');
		$this->assertGreaterThanOrEqual(1, count($handlersValidate));
		$this->assertInstanceOf(\GollumSF\RestBundle\Metadata\Validate\Handler\AttributeHandler::class, $handlersValidate[count($handlersValidate) - 1]);

		$this->assertInstanceOf(ValidatorInterface::class, $this->reflectionGetValue($container->get(SerializerSubscriber::class), 'validator'));
		$this->assertNull($this->reflectionGetValue($container->get(SerializerSubscriber::class), 'managerRegistry'));
		$this->assertNull($this->reflectionGetValue($container->get(ApiSearchInterface::class), 'managerRegistry'));
		$this->assertNull($this->reflectionGetValue($container->get(DoctrineIdDenormalizer::class), 'managerRegistry'));
		$this->assertNull($this->reflectionGetValue($container->get(DoctrineObjectDenormalizer::class), 'managerRegistry'));
		$this->assertNull($this->reflectionGetValue($container->get(ExceptionSubscriber::class), 'tokenStorage'));
	}

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	public function testInitBundleWithDoctrine() {
		$container = $this->bootTestKernel(
			[__DIR__ . '/Resources/config_doctrine.yaml'],
			[\Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class]
		);

		$this->assertInstanceOf(ManagerRegistry::class, $this->reflectionGetValue($container->get(SerializerSubscriber::class), 'managerRegistry'));
		$this->assertInstanceOf(ManagerRegistry::class, $this->reflectionGetValue($container->get(ApiSearchInterface::class), 'managerRegistry'));
		$this->assertInstanceOf(ManagerRegistry::class, $this->reflectionGetValue($container->get(DoctrineIdDenormalizer::class), 'managerRegistry'));
		$this->assertInstanceOf(ManagerRegistry::class, $this->reflectionGetValue($container->get(DoctrineObjectDenormalizer::class), 'managerRegistry'));
	}

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	public function testInitBundleWithValidator() {
		$container = $this->bootTestKernel([__DIR__ . '/Resources/config_validator.yaml']);
		$this->assertInstanceOf(ValidatorInterface::class, $this->reflectionGetValue($container->get(SerializerSubscriber::class), 'validator'));
	}

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	public function testInitBundleWithTokenStorage() {
		$container = $this->bootTestKernel(
			[__DIR__ . '/Resources/config_token_storage.yaml'],
			[\Symfony\Bundle\SecurityBundle\SecurityBundle::class]
		);
		$this->assertInstanceOf(TokenStorageInterface::class, $this->reflectionGetValue($container->get(ExceptionSubscriber::class), 'tokenStorage'));
	}
}
