<?php

namespace GollumSF\RestBundle\Metadata\Serialize\Handler;

use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerialize;

/**
 * @codeCoverageIgnore PHP 8.0.0
 */
class AttributeHandler implements HandlerInterface
{
	public function getMetadata(string $controller, string $action): ?MetadataSerialize
	{
		$rClass = new \ReflectionClass($controller);
		$rMethod = $rClass->getMethod($action);
		$classAttributes = $rClass->getAttributes(Serialize::class);
		$methodAttributes = $rMethod->getAttributes(Serialize::class);
		$classAnnotation = count($classAttributes) ? $classAttributes[0]->newInstance() : null;
		$methodAnnotation = count($methodAttributes) ? $methodAttributes[0]->newInstance() : null;
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
