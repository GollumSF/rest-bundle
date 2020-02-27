<?php
namespace GollumSF\RestBundle\Request\ParamConverter;

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
	
	public function __construct(SerializerInterface $serializer) {
		$this->serializer = $serializer;
	}

	public function setDoctrineParamConverter(DoctrineParamConverter $doctrineParamConverter): void
	{
		$this->doctrineParamConverter = $doctrineParamConverter;
	}

	public function apply(Request $request, ParamConverter $configuration) {
		/** @var Unserialize $unserializeAnnotation */
		$unserializeAnnotation = $request->attributes->get('_'.Unserialize::ALIAS_NAME);
		$configurationName = $configuration->getName();
		$class = $configuration->getClass();
		
		if (
			$unserializeAnnotation &&
			$unserializeAnnotation->getName() === $configurationName &&
			!$request->attributes->get($configurationName)
		) {
			if ($this->doctrineParamConverter && $this->doctrineParamConverter->supports($configuration)) {
				$isOptional = $configuration->isOptional();
				$configuration->setIsOptional(true);
				$this->doctrineParamConverter->apply($request, $configuration);
				$configuration->setIsOptional($isOptional);
			}
			if (!$request->attributes->get($configurationName)) {
				$content = $request->getContent();
				if ($content) {
					try {
						$entity = $this->serializer->deserialize($content, $class, 'json', $context = [
							'groups' => $unserializeAnnotation->getGroups(),
						]);
						$request->attributes->set($configurationName, $entity);
					} catch (MissingConstructorArgumentsException $e) {
						throw new BadRequestHttpException($e->getMessage());
					} catch (\UnexpectedValueException $e) {
						throw new BadRequestHttpException($e->getMessage());
					}
				}
			}
			$request->attributes->set('_'.Unserialize::ALIAS_NAME.'_class', $class);
			return true;
		}
		return false;
	}

	public function supports(ParamConverter $configuration) {
		return true;
	}
	
}