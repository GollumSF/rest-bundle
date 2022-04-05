<?php

namespace GollumSF\RestBundle\Metadata\Validate\Handler;

use Doctrine\Common\Annotations\Reader;
use GollumSF\RestBundle\Annotation\Validate;
use GollumSF\RestBundle\Metadata\Validate\MetadataValidate;

class AnnotationHandler implements HandlerInterface
{
	/** @var Reader */
	private $reader;

	public function __construct(
		Reader $reader
	) {	
		$this->reader = $reader;
	}
	
	public function getMetadata(string $controller, string $action): ?MetadataValidate
	{
		$rClass = new \ReflectionClass($controller);
		$rMethod = $rClass->getMethod($action);
		$classAnnotation = $this->reader->getClassAnnotation($rClass, Validate::class);
		$methodAnnotation = $this->reader->getMethodAnnotation($rMethod, Validate::class);
		if ($methodAnnotation) {
			return new MetadataValidate(
				$methodAnnotation->getGroups()
			);
		}
		if ($classAnnotation) {
			return new MetadataValidate(
				$classAnnotation->getGroups()
			);
		}
		return null;
	}
}
