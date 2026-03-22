<?php
namespace GollumSF\RestBundle\Request\ValueResolver;

use Doctrine\Persistence\ManagerRegistry;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerActionExtractorInterface;
use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserializeManagerInterface;
use GollumSF\RestBundle\Traits\ManagerRegistryToManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PostRestValueResolver implements ValueResolverInterface
{
	use ManagerRegistryToManager;

	private ControllerActionExtractorInterface $controllerActionExtractor;
	private MetadataUnserializeManagerInterface $metadataUnserializeManager;
	private ?ManagerRegistry $managerRegistry = null;

	public function __construct(
		ControllerActionExtractorInterface $controllerActionExtractor,
		MetadataUnserializeManagerInterface $metadataUnserializeManager
	) {
		$this->controllerActionExtractor = $controllerActionExtractor;
		$this->metadataUnserializeManager = $metadataUnserializeManager;
	}

	public function setManagerRegistry(ManagerRegistry $managerRegistry): void {
		$this->managerRegistry = $managerRegistry;
	}

	public function resolve(Request $request, ArgumentMetadata $argument): iterable
	{
		// If already resolved by a previous pass
		if (
			$request->attributes->has(Unserialize::REQUEST_ATTRIBUTE_NAME) &&
			$request->attributes->get(Unserialize::REQUEST_ATTRIBUTE_NAME) === $argument->getName()
		) {
			return [ $request->attributes->get($argument->getName()) ];
		}

		$controllerAction = $this->controllerActionExtractor->extractFromRequest($request);
		if (!$controllerAction) {
			return [];
		}

		$metadata = $this->metadataUnserializeManager->getMetadata(
			$controllerAction->getControllerClass(),
			$controllerAction->getActionMethod()
		);

		if (!$metadata || $metadata->getName() !== $argument->getName()) {
			return [];
		}

		$class = $argument->getType();
		if (!$class) {
			return [];
		}

		$argumentName = $argument->getName();

		// If we have an identifier (PUT/PATCH), load entity from Doctrine
		if ($this->hasIdentifier($request, $argumentName)) {
			$entity = $this->resolveFromDoctrine($request, $class, $argumentName);
			if ($entity) {
				$request->attributes->set($argumentName, $entity);
			} else {
				throw new NotFoundHttpException(sprintf('"%s" object not found by "%s".', $class, self::class));
			}
		} else {
			// For POST (no identifier), create an empty instance so that
			// the SerializerSubscriber can populate it via object_to_populate
			if (class_exists($class)) {
				$ref = new \ReflectionClass($class);
				if ($ref->isInstantiable()) {
					$request->attributes->set($argumentName, $ref->newInstanceWithoutConstructor());
				}
			}
		}

		$request->attributes->set(Unserialize::REQUEST_ATTRIBUTE_CLASS, $class);
		$request->attributes->set(Unserialize::REQUEST_ATTRIBUTE_NAME, $argumentName);

		return [ $request->attributes->get($argumentName) ];
	}

	protected function hasIdentifier(Request $request, string $argumentName): bool
	{
		if ($request->attributes->has($argumentName)) {
			return $request->attributes->get($argumentName) !== null;
		}
		if ($request->attributes->has('id')) {
			return $request->attributes->get('id') !== null;
		}
		return false;
	}

	private function resolveFromDoctrine(Request $request, string $class, string $argumentName): ?object
	{
		if (!$this->managerRegistry) {
			return null;
		}

		$em = $this->getEntityManagerForClass($class);
		if (!$em) {
			return null;
		}

		$id = $request->attributes->get($argumentName) ?? $request->attributes->get('id');
		if ($id === null) {
			return null;
		}

		return $em->getRepository($class)->find($id);
	}
}
