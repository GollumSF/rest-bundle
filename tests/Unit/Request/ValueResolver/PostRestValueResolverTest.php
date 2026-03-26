<?php
namespace Test\GollumSF\RestBundle\Unit\Request\ValueResolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerAction;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerActionExtractorInterface;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Attribute\Unserialize;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserialize;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserializeManagerInterface;
use GollumSF\RestBundle\Request\ValueResolver\PostRestValueResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DummyEntity {
	public ?int $id = null;
}

class PostRestValueResolverTest extends TestCase {

	use ReflectionPropertyTrait;

	private function createResolver(
		?ControllerAction $controllerAction = null,
		?MetadataUnserialize $metadata = null,
		?ManagerRegistry $managerRegistry = null
	): PostRestValueResolver {
		$controllerActionExtractor = $this->createMock(ControllerActionExtractorInterface::class);
		$metadataUnserializeManager = $this->createMock(MetadataUnserializeManagerInterface::class);

		$controllerActionExtractor
			->method('extractFromRequest')
			->willReturn($controllerAction)
		;

		$metadataUnserializeManager
			->method('getMetadata')
			->willReturn($metadata)
		;

		$resolver = new PostRestValueResolver($controllerActionExtractor, $metadataUnserializeManager);
		if ($managerRegistry) {
			$resolver->setManagerRegistry($managerRegistry);
		}
		return $resolver;
	}

	private function createArgument(string $name, ?string $type = null): ArgumentMetadata {
		$argument = $this->getMockBuilder(ArgumentMetadata::class)->disableOriginalConstructor()->getMock();
		$argument->method('getName')->willReturn($name);
		$argument->method('getType')->willReturn($type);
		return $argument;
	}

	public function testResolveAlreadyResolved() {
		$resolver = $this->createResolver();
		$request = new Request();
		$request->attributes->set(Unserialize::REQUEST_ATTRIBUTE_NAME, 'book');
		$request->attributes->set('book', 'ENTITY_VALUE');

		$argument = $this->createArgument('book', DummyEntity::class);

		$result = iterator_to_array($resolver->resolve($request, $argument));
		$this->assertEquals(['ENTITY_VALUE'], $result);
	}

	public function testResolveAlreadyResolvedDifferentName() {
		$resolver = $this->createResolver();
		$request = new Request();
		$request->attributes->set(Unserialize::REQUEST_ATTRIBUTE_NAME, 'other');

		$controllerAction = new ControllerAction('Controller', 'action');
		$metadata = $this->getMockBuilder(MetadataUnserialize::class)->disableOriginalConstructor()->getMock();
		$metadata->method('getName')->willReturn('book');

		$resolver = $this->createResolver($controllerAction, $metadata);
		$argument = $this->createArgument('book', DummyEntity::class);

		$result = iterator_to_array($resolver->resolve($request, $argument));
		$this->assertCount(1, $result);
		$this->assertInstanceOf(DummyEntity::class, $result[0]);
	}

	public function testResolveNoControllerAction() {
		$resolver = $this->createResolver(null);
		$request = new Request();
		$argument = $this->createArgument('book', DummyEntity::class);

		$result = iterator_to_array($resolver->resolve($request, $argument));
		$this->assertEquals([], $result);
	}

	public function testResolveNoMetadata() {
		$controllerAction = new ControllerAction('Controller', 'action');
		$resolver = $this->createResolver($controllerAction, null);
		$request = new Request();
		$argument = $this->createArgument('book', DummyEntity::class);

		$result = iterator_to_array($resolver->resolve($request, $argument));
		$this->assertEquals([], $result);
	}

	public function testResolveMetadataNameMismatch() {
		$controllerAction = new ControllerAction('Controller', 'action');
		$metadata = $this->getMockBuilder(MetadataUnserialize::class)->disableOriginalConstructor()->getMock();
		$metadata->method('getName')->willReturn('other');

		$resolver = $this->createResolver($controllerAction, $metadata);
		$request = new Request();
		$argument = $this->createArgument('book', DummyEntity::class);

		$result = iterator_to_array($resolver->resolve($request, $argument));
		$this->assertEquals([], $result);
	}

	public function testResolveNoType() {
		$controllerAction = new ControllerAction('Controller', 'action');
		$metadata = $this->getMockBuilder(MetadataUnserialize::class)->disableOriginalConstructor()->getMock();
		$metadata->method('getName')->willReturn('book');

		$resolver = $this->createResolver($controllerAction, $metadata);
		$request = new Request();
		$argument = $this->createArgument('book', null);

		$result = iterator_to_array($resolver->resolve($request, $argument));
		$this->assertEquals([], $result);
	}

	public function testResolvePostNewEntity() {
		$controllerAction = new ControllerAction('Controller', 'action');
		$metadata = $this->getMockBuilder(MetadataUnserialize::class)->disableOriginalConstructor()->getMock();
		$metadata->method('getName')->willReturn('book');

		$resolver = $this->createResolver($controllerAction, $metadata);
		$request = new Request();
		$argument = $this->createArgument('book', DummyEntity::class);

		$result = iterator_to_array($resolver->resolve($request, $argument));

		$this->assertCount(1, $result);
		$this->assertInstanceOf(DummyEntity::class, $result[0]);
		$this->assertEquals('book', $request->attributes->get(Unserialize::REQUEST_ATTRIBUTE_NAME));
		$this->assertEquals(DummyEntity::class, $request->attributes->get(Unserialize::REQUEST_ATTRIBUTE_CLASS));
	}

	public function testResolveWithIdentifierFromDoctrine() {
		$controllerAction = new ControllerAction('Controller', 'action');
		$metadata = $this->getMockBuilder(MetadataUnserialize::class)->disableOriginalConstructor()->getMock();
		$metadata->method('getName')->willReturn('book');

		$entity = new DummyEntity();
		$entity->id = 42;

		$repository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
		$repository->method('find')->with(42)->willReturn($entity);

		$em = $this->createMock(EntityManagerInterface::class);
		$em->method('getRepository')->with(DummyEntity::class)->willReturn($repository);

		$managerRegistry = $this->createMock(ManagerRegistry::class);
		$managerRegistry->method('getManagerForClass')->with(DummyEntity::class)->willReturn($em);

		$resolver = $this->createResolver($controllerAction, $metadata, $managerRegistry);
		$request = new Request();
		$request->attributes->set('book', 42);
		$argument = $this->createArgument('book', DummyEntity::class);

		$result = iterator_to_array($resolver->resolve($request, $argument));

		$this->assertCount(1, $result);
		$this->assertSame($entity, $result[0]);
	}

	public function testResolveWithIdAttribute() {
		$controllerAction = new ControllerAction('Controller', 'action');
		$metadata = $this->getMockBuilder(MetadataUnserialize::class)->disableOriginalConstructor()->getMock();
		$metadata->method('getName')->willReturn('book');

		$entity = new DummyEntity();
		$entity->id = 7;

		$repository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
		$repository->method('find')->with(7)->willReturn($entity);

		$em = $this->createMock(EntityManagerInterface::class);
		$em->method('getRepository')->with(DummyEntity::class)->willReturn($repository);

		$managerRegistry = $this->createMock(ManagerRegistry::class);
		$managerRegistry->method('getManagerForClass')->with(DummyEntity::class)->willReturn($em);

		$resolver = $this->createResolver($controllerAction, $metadata, $managerRegistry);
		$request = new Request();
		$request->attributes->set('id', 7);
		$argument = $this->createArgument('book', DummyEntity::class);

		$result = iterator_to_array($resolver->resolve($request, $argument));

		$this->assertCount(1, $result);
		$this->assertSame($entity, $result[0]);
	}

	public function testResolveWithIdentifierNotFound() {
		$controllerAction = new ControllerAction('Controller', 'action');
		$metadata = $this->getMockBuilder(MetadataUnserialize::class)->disableOriginalConstructor()->getMock();
		$metadata->method('getName')->willReturn('book');

		$repository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
		$repository->method('find')->willReturn(null);

		$em = $this->createMock(EntityManagerInterface::class);
		$em->method('getRepository')->willReturn($repository);

		$managerRegistry = $this->createMock(ManagerRegistry::class);
		$managerRegistry->method('getManagerForClass')->willReturn($em);

		$resolver = $this->createResolver($controllerAction, $metadata, $managerRegistry);
		$request = new Request();
		$request->attributes->set('book', 99);
		$argument = $this->createArgument('book', DummyEntity::class);

		$this->expectException(NotFoundHttpException::class);
		iterator_to_array($resolver->resolve($request, $argument));
	}

	public function testResolveWithIdentifierNoManagerRegistry() {
		$controllerAction = new ControllerAction('Controller', 'action');
		$metadata = $this->getMockBuilder(MetadataUnserialize::class)->disableOriginalConstructor()->getMock();
		$metadata->method('getName')->willReturn('book');

		$resolver = $this->createResolver($controllerAction, $metadata, null);
		$request = new Request();
		$request->attributes->set('book', 42);
		$argument = $this->createArgument('book', DummyEntity::class);

		$this->expectException(NotFoundHttpException::class);
		iterator_to_array($resolver->resolve($request, $argument));
	}

	public function testResolveWithIdentifierNoEntityManager() {
		$controllerAction = new ControllerAction('Controller', 'action');
		$metadata = $this->getMockBuilder(MetadataUnserialize::class)->disableOriginalConstructor()->getMock();
		$metadata->method('getName')->willReturn('book');

		$managerRegistry = $this->createMock(ManagerRegistry::class);
		$managerRegistry->method('getManagerForClass')->willReturn(null);

		$resolver = $this->createResolver($controllerAction, $metadata, $managerRegistry);
		$request = new Request();
		$request->attributes->set('book', 42);
		$argument = $this->createArgument('book', DummyEntity::class);

		$this->expectException(NotFoundHttpException::class);
		iterator_to_array($resolver->resolve($request, $argument));
	}

	public function testResolveFromDoctrineNullId() {
		$controllerAction = new ControllerAction('Controller', 'action');
		$metadata = $this->getMockBuilder(MetadataUnserialize::class)->disableOriginalConstructor()->getMock();
		$metadata->method('getName')->willReturn('book');

		$em = $this->createMock(EntityManagerInterface::class);
		$managerRegistry = $this->createMock(ManagerRegistry::class);
		$managerRegistry->method('getManagerForClass')->willReturn($em);

		$resolver = $this->createResolver($controllerAction, $metadata, $managerRegistry);

		$request = new Request();
		$result = $this->reflectionCallMethod($resolver, 'resolveFromDoctrine', [$request, DummyEntity::class, 'book']);
		$this->assertNull($result);
	}
}
