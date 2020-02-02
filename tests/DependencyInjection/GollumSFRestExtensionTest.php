<?php
namespace Test\GollumSF\RestBundle\DependencyInjection;

use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\DependencyInjection\GollumSFRestExtension;
use GollumSF\RestBundle\EventSubscriber\SerializerSubscriber;
use GollumSF\RestBundle\Reflection\ControllerActionExtractorInterface;
use GollumSF\RestBundle\Request\ParamConverter\PostRestParamConverter;
use GollumSF\RestBundle\Search\ApiSearchInterface;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineIdDenormalizer;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineObjectDenormalizer;
use GollumSF\RestBundle\Serializer\Normalizer\RecursiveObjectNormalizer;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class GollumSFRestExtensionTest extends AbstractExtensionTestCase {

	protected function getContainerExtensions(): array {
		return [
			new GollumSFRestExtension()
		];
	}
	
	public function testLoad() {
		$this->load();
		$this->assertContainerBuilderHasService(DoctrineIdDenormalizer::class);
		$this->assertContainerBuilderHasService(DoctrineObjectDenormalizer::class);
		$this->assertContainerBuilderHasService(RecursiveObjectNormalizer::class);
		$this->assertContainerBuilderHasService(PostRestParamConverter::class);
		$this->assertContainerBuilderHasService(SerializerSubscriber::class);
		$this->assertContainerBuilderHasService(ApiSearchInterface::class);
		$this->assertContainerBuilderHasService(ApiConfigurationInterface::class);
	}

	public function providerLoadConfiguration() {
		return [
			[ [], ApiConfigurationInterface::DEFAULT_MAX_LIMIT_ITEM, ApiConfigurationInterface::DEFAULT_DEFAULT_LIMIT_ITEM ],
			
			[ 
				[
					'max_limit_item'=> 4242,
					'default_limit_item'=> 42,
				], 4242, 42 
			],
		];
	}

	/**
	 * @dataProvider providerLoadConfiguration
	 */
	public function testLoadConfiguration(
		$config,
		$secret
	) {
		$this->load($config);

		$this->assertContainerBuilderHasServiceDefinitionWithArgument(ApiConfigurationInterface::class, 0, $secret);
	}
}