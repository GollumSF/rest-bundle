<?php

namespace GollumSF\RestBundle\Reflection;

interface ControllerActionExtractorInterface
{
	public function extractFromString($controllerAction): ?ControllerAction;
}