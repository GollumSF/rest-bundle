<?php

namespace GollumSF\RestBundle\Metadata\Unserialize\Handler;

use Doctrine\Common\Annotations\Reader;
use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserialize;

class AnnotationHandler implements HandlerInterface
{
	/** @var Reader */
	private $reader;

	public function __construct(
		Reader $reader
	) {	
		$this->reader = $reader;
	}
	
	public function getMetadata(string $controller, string $action): ?MetadataUnserialize
	{
		$rClass = new \ReflectionClass($controller);
		$rMethod = $rClass->getMethod($action);
		$classAnnotation = $this->reader->getClassAnnotation($rClass, Unserialize::class);
		$methodAnnotation = $this->reader->getMethodAnnotation($rMethod, Unserialize::class);
		if ($methodAnnotation) {
			return new MetadataUnserialize(
				$methodAnnotation->getName(),
				$methodAnnotation->getGroups(),
				$methodAnnotation->isSave()
			);
		}
		if ($classAnnotation) {
			return new MetadataUnserialize(
				$classAnnotation->getName(),
				$classAnnotation->getGroups(),
				$classAnnotation->isSave()
			);
		}
		return null;
	}
}
