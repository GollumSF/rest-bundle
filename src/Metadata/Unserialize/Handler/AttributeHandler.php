<?php

namespace GollumSF\RestBundle\Metadata\Unserialize\Handler;

use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserialize;

/**
 * @codeCoverageIgnore PHP 8.0.0
 */
class AttributeHandler implements HandlerInterface
{
	public function getMetadata(string $controller, string $action): ?MetadataUnserialize
	{
		$rClass = new \ReflectionClass($controller);
		$rMethod = $rClass->getMethod($action);
		$classAttributes = $rClass->getAttributes(Unserialize::class);
		$methodAttributes = $rMethod->getAttributes(Unserialize::class);
		$classAnnotation = count($classAttributes) ? $classAttributes[0]->newInstance() : null;
		$methodAnnotation = count($methodAttributes) ? $methodAttributes[0]->newInstance() : null;
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
