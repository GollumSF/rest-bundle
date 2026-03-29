<?php
namespace GollumSF\RestBundle\Request\ValueResolver;

use Doctrine\Persistence\ManagerRegistry;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerActionExtractorInterface;
use GollumSF\RestBundle\Attribute\Unserialize;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserializeManagerInterface;
use GollumSF\RestBundle\Traits\ManagerRegistryToManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\SerializerInterface;

class PostRestValueResolver implements ValueResolverInterface
{
	use ManagerRegistryToManager;

	private ControllerActionExtractorInterface $controllerActionExtractor;
	private MetadataUnserializeManagerInterface $metadataUnserializeManager;
	private SerializerInterface $serializer;
	private ?ManagerRegistry $managerRegistry = null;

	public function __construct(
		ControllerActionExtractorInterface $controllerActionExtractor,
		MetadataUnserializeManagerInterface $metadataUnserializeManager,
		SerializerInterface $serializer
	) {
		$this->controllerActionExtractor = $controllerActionExtractor;
		$this->metadataUnserializeManager = $metadataUnserializeManager;
		$this->serializer = $serializer;
	}

	public function setManagerRegistry(ManagerRegistry $managerRegistry): void {
		$this->managerRegistry = $managerRegistry;
	}

	public function resolve(Request $request, ArgumentMetadata $argument): iterable
	{
		// If already resolved by a previous pass (e.g. by the SerializerSubscriber)
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

		// If we have an identifier in the route (PUT/PATCH with {id}), load entity from Doctrine
		if ($this->hasIdentifier($request, $argumentName)) {
			$entity = $this->resolveFromDoctrine($request, $class, $argumentName);
			if ($entity) {
				$request->attributes->set($argumentName, $entity);
			} else {
				throw new NotFoundHttpException(sprintf('"%s" object not found by "%s".', $class, self::class));
			}
		} else {
			// No identifier in route: deserialize the body to create/find the entity
			// This replicates the old PostRestParamConverter behavior
			$content = $request->getContent();
			if ($content) {
				try {
					$entity = $this->serializer->deserialize($content, $class, 'json', [
						'groups' => $metadata->getGroups(),
					]);
					$request->attributes->set($argumentName, $entity);
				} catch (MissingConstructorArgumentsException $e) {
					throw new BadRequestHttpException($e->getMessage());
				} catch (\UnexpectedValueException $e) {
					throw new BadRequestHttpException($e->getMessage());
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
		if (!$this->isEntity($class)) {
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
