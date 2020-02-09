<?php
namespace GollumSF\RestBundle\Request\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use GollumSF\RestBundle\Annotation\Unserialize;
use Symfony\Component\HttpFoundation\Request;

class PostRestParamConverter implements ParamConverterInterface {

	/** @var DoctrineParamConverter */
	private $doctrineParamConverter;

	public function setDoctrineParamConverter(DoctrineParamConverter $doctrineParamConverter): void {
		$this->doctrineParamConverter = $doctrineParamConverter;
	}

	public function apply(Request $request, ParamConverter $configuration) {
		/** @var Unserialize $unserializeAnnotation */
		$unserializeAnnotation = $request->attributes->get('_'.Unserialize::ALIAS_NAME);
		$configurationName = $configuration->getName();
		
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
			$request->attributes->set('_'.Unserialize::ALIAS_NAME.'_class', $configuration->getClass());
			return true;
		}
		return false;
	}

	public function supports(ParamConverter $configuration) {
		return true;
	}
	
}