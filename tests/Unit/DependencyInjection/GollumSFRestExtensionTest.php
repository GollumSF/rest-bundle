<?php
namespace Test\GollumSF\RestBundle\Unit\DependencyInjection;

use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\DependencyInjection\GollumSFRestExtension;
use GollumSF\RestBundle\EventSubscriber\ExceptionSubscriber;
use GollumSF\RestBundle\EventSubscriber\SerializerSubscriber;
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
		$this->assertContainerBuilderHasService(ExceptionSubscriber::class);
		$this->assertContainerBuilderHasService(ApiSearchInterface::class);
		$this->assertContainerBuilderHasService(ApiConfigurationInterface::class);
	}

	public function providerLoadConfiguration() {
		return [
			[ 
				[], 
				ApiConfigurationInterface::DEFAULT_MAX_LIMIT_ITEM, 
				ApiConfigurationInterface::DEFAULT_DEFAULT_LIMIT_ITEM, 
				ApiConfigurationInterface::DEFAULT_ALWAYS_SERIALIZED_EXCEPTION
			],

			[
				[
					'max_limit_item'=> 4242,
					'default_limit_item'=> 42,
					'always_serialized_exception' => false
				], 4242, 42, false
			],
			[
				[
					'max_limit_item'=> 2121,
					'default_limit_item'=> 21,
					'always_serialized_exception' => true
				], 2121, 21, true
			],
		];
	}

	/**
	 * @dataProvider providerLoadConfiguration
	 */
	public function testLoadConfiguration(
		$config,
		$maxLimitItem,
		$defaultLimitItem,
		$alwaysSerializedException
	) {
		$this->load($config);

		$this->assertContainerBuilderHasServiceDefinitionWithArgument(ApiConfigurationInterface::class, 0, $maxLimitItem);
		$this->assertContainerBuilderHasServiceDefinitionWithArgument(ApiConfigurationInterface::class, 1, $defaultLimitItem);
		$this->assertContainerBuilderHasServiceDefinitionWithArgument(ApiConfigurationInterface::class, 2, $alwaysSerializedException);
	}
}