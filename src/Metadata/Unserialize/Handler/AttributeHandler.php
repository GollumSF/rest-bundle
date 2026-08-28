<?php

namespace GollumSF\RestBundle\Metadata\Unserialize\Handler;

use GollumSF\RestBundle\Attribute\Unserialize;
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
				$methodAnnotation->isSave(),
				$this->resolveType($rMethod, $methodAnnotation)
			);
		}
		if ($classAnnotation) {
			return new MetadataUnserialize(
				$classAnnotation->getName(),
				$classAnnotation->getGroups(),
				$classAnnotation->isSave(),
				$this->resolveType($rMethod, $classAnnotation)
			);
		}
		return null;
	}

	/**
	 * The target class is the type of the action parameter the attribute points at,
	 * unless the attribute forces one.
	 */
	private function resolveType(\ReflectionMethod $rMethod, Unserialize $annotation): ?string {
		if ($annotation->getType()) {
			return $annotation->getType();
		}
		foreach ($rMethod->getParameters() as $rParameter) {
			if ($rParameter->getName() !== $annotation->getName()) {
				continue;
			}
			$rType = $rParameter->getType();
			return $rType instanceof \ReflectionNamedType && !$rType->isBuiltin()
				? $rType->getName()
				: null
			;
		}
		return null;
	}
}
