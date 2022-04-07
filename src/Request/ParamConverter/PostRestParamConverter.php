<?php
namespace GollumSF\RestBundle\Request\ParamConverter;

use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerActionExtractorInterface;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserializeManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use GollumSF\RestBundle\Annotation\Unserialize;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\SerializerInterface;

class PostRestParamConverter implements ParamConverterInterface {

	/** @var DoctrineParamConverter */
	private $doctrineParamConverter;

	/** @var SerializerInterface */
	private $serializer;

	/** @var ControllerActionExtractorInterface */
	private $controllerActionExtractor;

	/** @var MetadataUnserializeManagerInterface */
	private $metadataUnserializeManager;

	public function __construct(
		SerializerInterface $serializer,
		ControllerActionExtractorInterface $controllerActionExtractor,
		MetadataUnserializeManagerInterface $metadataUnserializeManager
	) {
		$this->serializer = $serializer;
		$this->controllerActionExtractor = $controllerActionExtractor;
		$this->metadataUnserializeManager = $metadataUnserializeManager;
	}

	public function setDoctrineParamConverter(DoctrineParamConverter $doctrineParamConverter): void {
		$this->doctrineParamConverter = $doctrineParamConverter;
	}

	public function apply(Request $request, ParamConverter $configuration) {

		$controllerAction = $this->controllerActionExtractor->extractFromRequest($request);
        $metadata = null;
        if ($controllerAction) {
            $metadata = $this->metadataUnserializeManager->getMetadata($controllerAction->getControllerClass(), $controllerAction->getActionMethod());
		}

		$configurationName = $configuration->getName();
		$class = $configuration->getClass();

		if (
			$metadata &&
			$metadata->getName() === $configurationName &&
			!$request->attributes->get($configurationName)
		) {
			if ($this->hasIdentifier($request, $configuration)) {
				if ($this->doctrineParamConverter && $this->doctrineParamConverter->supports($configuration)) {
					$this->doctrineParamConverter->apply($request, $configuration);
				}
			} else {
				$content = $request->getContent();
				if ($content) {
					try {
						$entity = $this->serializer->deserialize($content, $class, 'json', $context = [
							'groups' => $metadata->getGroups(),
						]);
						$request->attributes->set($configurationName, $entity);
					} catch (MissingConstructorArgumentsException $e) {
						throw new BadRequestHttpException($e->getMessage());
					} catch (\UnexpectedValueException $e) {
						throw new BadRequestHttpException($e->getMessage());
					}
				}
			}
			$request->attributes->set(Unserialize::REQUEST_ATTRIBUTE_CLASS, $class);
			return true;
		}
		return false;
	}

	/**
	 * Copy of getIdentifier doctrine
	 */
	protected function hasIdentifier(Request $request, ParamConverter $configuration): bool
	{
		$idName = isset($configuration->getOptions()['id']) ? $configuration->getOptions()['id'] : null;
		$name = $configuration->getName();
		if (null !== $idName) {
			if (!\is_array($idName)) {
				$name = $idName;
			} elseif (\is_array($idName)) {
				return true;
			}
		}

		if ($request->attributes->has($name)) {
			return $request->attributes->get($name) !== null;
		}
		if ($request->attributes->has('id') && !$idName) {
			return $request->attributes->get('id') !== null;
		}
		return false;
	}

	public function supports(ParamConverter $configuration) {
		return true;
	}

}
