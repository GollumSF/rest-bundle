<?php

namespace GollumSF\RestBundle\Metadata\Validate\Handler;

use GollumSF\RestBundle\Annotation\Validate;
use GollumSF\RestBundle\Metadata\Validate\MetadataValidate;

/**
 * @codeCoverageIgnore PHP 8.0.0
 */
class AttributeHandler implements HandlerInterface
{
	public function getMetadata(string $controller, string $action): ?MetadataValidate
	{
		$rClass = new \ReflectionClass($controller);
		$rMethod = $rClass->getMethod($action);
		$classAttributes = $rClass->getAttributes(Validate::class);
		$methodAttributes = $rMethod->getAttributes(Validate::class);
		$classAnnotation = count($classAttributes) ? $classAttributes[0]->newInstance() : null;
		$methodAnnotation = count($methodAttributes) ? $methodAttributes[0]->newInstance() : null;
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
