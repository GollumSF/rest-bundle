<?php
namespace Test\GollumSF\RestBundle\DependencyInjection;

use GollumSF\RestBundle\DependencyInjection\GollumSFRestExtension;
use GollumSF\RestBundle\EventSubscriber\SerializerSubscriber;
use GollumSF\RestBundle\Reflection\ControllerActionExtractorInterface;
use GollumSF\RestBundle\Request\ParamConverter\PostRestParamConverter;
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
		$this->assertContainerBuilderHasService(ControllerActionExtractorInterface::class);
	}
}