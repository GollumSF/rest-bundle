<?php

namespace GollumSF\RestBundle\Metadata\Serialize\Handler;

use Doctrine\Common\Annotations\Reader;
use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerialize;

class AnnotationHandler implements HandlerInterface
{
	/** @var Reader */
	private $reader;

	public function __construct(
		Reader $reader
	) {	
		$this->reader = $reader;
	}
	
	public function getMetadata(string $controller, string $action): ?MetadataSerialize
	{
		$rClass = new \ReflectionClass($controller);
		$rMethod = $rClass->getMethod($action);
		$classAnnotation = $this->reader->getClassAnnotation($rClass, Serialize::class);
		$methodAnnotation = $this->reader->getMethodAnnotation($rMethod, Serialize::class);
		if ($methodAnnotation) {
			return new MetadataSerialize(
				$methodAnnotation->getCode(),
				$methodAnnotation->getGroups(),
				$methodAnnotation->getHeaders()
			);
		}
		if ($classAnnotation) {
			return new MetadataSerialize(
				$classAnnotation->getCode(),
				$classAnnotation->getGroups(),
				$classAnnotation->getHeaders()
			);
		}
		return null;
	}
}
