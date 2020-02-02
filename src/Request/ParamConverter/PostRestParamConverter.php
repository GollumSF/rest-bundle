<?php
namespace GollumSF\RestBundle\Request\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use GollumSF\RestBundle\Annotation\Unserialize;
use Symfony\Component\HttpFoundation\Request;

class PostRestParamConverter implements ParamConverterInterface {
	
	public function apply(Request $request, ParamConverter $configuration) {
		/** @var Unserialize $unserializeAnnotation */
		$unserializeAnnotation = $request->attributes->get('_'.Unserialize::ALIAS_NAME);
		if (
			$unserializeAnnotation &&
			$unserializeAnnotation->getName() == $configuration->getName() &&
			!$request->get('id') &&
			!$request->get($configuration->getName())
		) {
			$class = $configuration->getClass();
			$request->attributes->set($configuration->getName(), new $class);
			return true;
		}
		return false;
	}

	public function supports(ParamConverter $configuration) {
		return true;
	}
	
}