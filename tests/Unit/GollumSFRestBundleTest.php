<?php
namespace Test\GollumSF\RestBundle\Unit;

use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Configuration\ApiConfiguration;
use Doctrine\Persistence\ManagerRegistry;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\EventSubscriber\ExceptionSubscriber;
use GollumSF\RestBundle\EventSubscriber\SerializerSubscriber;
use GollumSF\RestBundle\GollumSFRestBundle;
use GollumSF\RestBundle\Request\ParamConverter\PostRestParamConverter;
use GollumSF\RestBundle\Search\ApiSearch;
use GollumSF\RestBundle\Search\ApiSearchInterface;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineIdDenormalizer;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineObjectDenormalizer;
use GollumSF\RestBundle\Serializer\Normalizer\RecursiveObjectNormalizer;
use Nyholm\BundleTest\BaseBundleTestCase;
use Nyholm\BundleTest\CompilerPass\PublicServicePass;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter;
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
		$kernel->addBundle(\Symfony\Bundle\SecurityBundle\SecurityBundle::class);

		// Add some configuration
		$kernel->addConfigFile(__DIR__.'/Resources/config_token_storage.yaml');

		// Boot the kernel.
		$this->bootKernel();

		// Get the container
		$container = $this->getContainer();

		$this->assertInstanceOf(TokenStorageInterface::class, $this->reflectionGetValue($container->get(ExceptionSubscriber::class), 'tokenStorage'));
	}
	
}
