<?php
namespace GollumSF\RestBundle\Request\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Traits\AnnotationControllerReader;
use Symfony\Component\HttpFoundation\Request;

class PostRestParamConverter implements ParamConverterInterface {
	
	use AnnotationControllerReader;
	
	function apply(Request $request, ParamConverter $configuration) {
		/** @var Unserialize $unserializeAnnotation */
		$unserializeAnnotation = $this->getAnnotation($request, Unserialize::class);
		if (
			$unserializeAnnotation &&
			$unserializeAnnotation->name == $configuration->getName() &&
			!$request->get('id') &&
			!$request->get($configuration->getName())
		) {
			$class = $configuration->getClass();
			$request->attributes->set($configuration->getName(), new $class);
			return true;
		}
		return false;
	}
	
	function supports(ParamConverter $configuration) {
		return true;
	}
	
}