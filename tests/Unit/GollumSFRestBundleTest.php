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
use GollumSF\RestBundle\Request\ParamConverter\PostRestParamConverter;
use GollumSF\RestBundle\Search\ApiSearch;
use GollumSF\RestBundle\Search\ApiSearchInterface;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineIdDenormalizer;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineObjectDenormalizer;
use GollumSF\RestBundle\Serializer\Normalizer\RecursiveObjectNormalizer;
use Nyholm\BundleTest\BaseBundleTestCase;
use Nyholm\BundleTest\CompilerPass\PublicServicePass;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GollumSFRestBundleTest extends BaseBundleTestCase {
	
	use ReflectionPropertyTrait;
	
	protected function getBundleClass() {
		return GollumSFRestBundle::class;
	}
	
	protected function setUp(): void {
		parent::setUp();
		
		// Make all services public
		$this->addCompilerPass(new PublicServicePass('|GollumSF*|'));
	}

	public function testInitBundle() {

		// Create a new Kernel
		$kernel = $this->createKernel();
		
		// Add some other bundles we depend on
		$kernel->addBundle(\GollumSF\ControllerActionExtractorBundle\GollumSFControllerActionExtractorBundle::class);

		// Add some configuration
		$kernel->addConfigFile(__DIR__.'/Resources/config.yaml');

		// Boot the kernel.
		$this->bootKernel();

		// Get the container
		$container = $this->getContainer();

		$this->assertInstanceOf(ApiSearchInterface::class       , $container->get(ApiSearchInterface::class));
		$this->assertInstanceOf(ApiConfigurationInterface::class, $container->get(ApiConfigurationInterface::class));

		$this->assertInstanceOf(ApiSearch::class       , $container->get(ApiSearchInterface::class));
		$this->assertInstanceOf(ApiConfiguration::class, $container->get(ApiConfigurationInterface::class));

		$this->assertInstanceOf(DoctrineIdDenormalizer::class    , $container->get(DoctrineIdDenormalizer::class));
		$this->assertInstanceOf(DoctrineObjectDenormalizer::class, $container->get(DoctrineObjectDenormalizer::class));
		$this->assertInstanceOf(RecursiveObjectNormalizer::class , $container->get(RecursiveObjectNormalizer::class));
		$this->assertInstanceOf(PostRestParamConverter::class    , $container->get(PostRestParamConverter::class));
		$this->assertInstanceOf(SerializerSubscriber::class      , $container->get(SerializerSubscriber::class));
		$this->assertInstanceOf(ExceptionSubscriber::class       , $container->get(ExceptionSubscriber::class));
		
		$this->assertInstanceOf(MetadataSerializeManager::class  , $container->get(MetadataSerializeManagerInterface::class));
		$this->assertInstanceOf(MetadataUnserializeManager::class, $container->get(MetadataUnserializeManagerInterface::class));
		$this->assertInstanceOf(MetadataValidateManager::class   , $container->get(MetadataValidateManagerInterface::class));
		
		$handlersSerialize = $this->reflectionGetValue($container->get(MetadataSerializeManagerInterface::class), 'handlers');
		if (version_compare(PHP_VERSION, '8.0.0', '<')) {
			$this->assertCount(1, $handlersSerialize);
			$this->assertInstanceOf(\GollumSF\RestBundle\Metadata\Serialize\Handler\AnnotationHandler::class, $handlersSerialize[0]);
		} else {
			$this->assertCount(2, $handlersSerialize);
			$this->assertInstanceOf(\GollumSF\RestBundle\Metadata\Serialize\Handler\AnnotationHandler::class, $handlersSerialize[0]);
			$this->assertInstanceOf(\GollumSF\RestBundle\Metadata\Serialize\Handler\AttributeHandler::class, $handlersSerialize[1]);
		}
		
		$handlersUnserialize = $this->reflectionGetValue($container->get(MetadataUnserializeManagerInterface::class), 'handlers');
		if (version_compare(PHP_VERSION, '8.0.0', '<')) {
			$this->assertCount(1, $handlersUnserialize);
			$this->assertInstanceOf(\GollumSF\RestBundle\Metadata\Unserialize\Handler\AnnotationHandler::class, $handlersUnserialize[0]);
		} else {
			$this->assertCount(2, $handlersSerialize);
			$this->assertInstanceOf(\GollumSF\RestBundle\Metadata\Unserialize\Handler\AnnotationHandler::class, $handlersUnserialize[0]);
			$this->assertInstanceOf(\GollumSF\RestBundle\Metadata\Unserialize\Handler\AttributeHandler::class, $handlersUnserialize[1]);
		}
		
		$handlersValidate = $this->reflectionGetValue($container->get(MetadataValidateManagerInterface::class), 'handlers');
		if (version_compare(PHP_VERSION, '8.0.0', '<')) {
			$this->assertCount(1, $handlersValidate);
			$this->assertInstanceOf(\GollumSF\RestBundle\Metadata\Validate\Handler\AnnotationHandler::class, $handlersValidate[0]);
		} else {
			$this->assertCount(2, $handlersValidate);
			$this->assertInstanceOf(\GollumSF\RestBundle\Metadata\Validate\Handler\AnnotationHandler::class, $handlersValidate[0]);
			$this->assertInstanceOf(\GollumSF\RestBundle\Metadata\Validate\Handler\AttributeHandler::class, $handlersValidate[1]);
		}

		$this->assertNull($this->reflectionGetValue($container->get(SerializerSubscriber::class), 'validator'));
		$this->assertNull($this->reflectionGetValue($container->get(SerializerSubscriber::class), 'managerRegistry'));
		$this->assertNull($this->reflectionGetValue($container->get(ApiSearchInterface::class), 'managerRegistry'));
		$this->assertNull($this->reflectionGetValue($container->get(DoctrineIdDenormalizer::class), 'managerRegistry'));
		$this->assertNull($this->reflectionGetValue($container->get(DoctrineObjectDenormalizer::class), 'managerRegistry'));
		$this->assertNull($this->reflectionGetValue($container->get(PostRestParamConverter::class), 'doctrineParamConverter'));
		$this->assertNull($this->reflectionGetValue($container->get(ExceptionSubscriber::class), 'tokenStorage'));
	}

	public function testInitBundleWithDoctrine() {

		// Create a new Kernel
		$kernel = $this->createKernel();

		// Add some other bundles we depend on
		$kernel->addBundle(\Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class);
		$kernel->addBundle(\Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class);
		$kernel->addBundle(\GollumSF\ControllerActionExtractorBundle\GollumSFControllerActionExtractorBundle::class);

		// Add some configuration
		$kernel->addConfigFile(__DIR__ . '/Resources/config_doctrine.yaml');

		// Boot the kernel.
		$this->bootKernel();

		// Get the container
		$container = $this->getContainer();

		$this->assertInstanceOf(ManagerRegistry::class, $this->reflectionGetValue($container->get(SerializerSubscriber::class), 'managerRegistry'));
		$this->assertInstanceOf(ManagerRegistry::class, $this->reflectionGetValue($container->get(ApiSearchInterface::class), 'managerRegistry'));
		$this->assertInstanceOf(ManagerRegistry::class, $this->reflectionGetValue($container->get(DoctrineIdDenormalizer::class), 'managerRegistry'));
		$this->assertInstanceOf(ManagerRegistry::class, $this->reflectionGetValue($container->get(DoctrineObjectDenormalizer::class), 'managerRegistry'));
		$this->assertInstanceOf(DoctrineParamConverter::class, $this->reflectionGetValue($container->get(PostRestParamConverter::class), 'doctrineParamConverter'));
	}

	public function testInitBundleWithValidator() {

		// Create a new Kernel
		$kernel = $this->createKernel();
		
		// Add some other bundles we depend on
		$kernel->addBundle(\GollumSF\ControllerActionExtractorBundle\GollumSFControllerActionExtractorBundle::class);

		// Add some configuration
		$kernel->addConfigFile(__DIR__ . '/Resources/config_validator.yaml');

		// Boot the kernel.
		$this->bootKernel();

		// Get the container
		$container = $this->getContainer();

		$this->assertInstanceOf(ValidatorInterface::class, $this->reflectionGetValue($container->get(SerializerSubscriber::class), 'validator'));
	}

	public function testInitBundleWithTokenStorage() {

		// Create a new Kernel
		$kernel = $this->createKernel();
		
		// Add some other bundles we depend on
		$kernel->addBundle(\GollumSF\ControllerActionExtractorBundle\GollumSFControllerActionExtractorBundle::class);

		// Add some other bundles we depend on
		$kernel->addBundle(\Symfony\Bundle\SecurityBundle\SecurityBundle::class);

		// Add some configuration
		
		if (version_compare(Kernel::VERSION, '5.2.0', '<')) {
			$kernel->addConfigFile(__DIR__ . '/Resources/config_token_storage_sf4.4.yaml');
		} else {
			$kernel->addConfigFile(__DIR__ . '/Resources/config_token_storage.yaml');
		}

		// Boot the kernel.
		$this->bootKernel();

		// Get the container
		$container = $this->getContainer();

		$this->assertInstanceOf(TokenStorageInterface::class, $this->reflectionGetValue($container->get(ExceptionSubscriber::class), 'tokenStorage'));
	}
	
}
