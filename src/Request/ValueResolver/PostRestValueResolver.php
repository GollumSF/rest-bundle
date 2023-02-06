<?php

namespace GollumSF\RestBundle\Request\ValueResolver;

use GollumSF\RestBundle\Annotation\Unserialize;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @codeCoverageIgnore SF 6.2
 */
class PostRestValueResolver implements ValueResolverInterface
{
	public function resolve(Request $request, ArgumentMetadata $argument): iterable
	{
		if (
			$request->attributes->has(Unserialize::REQUEST_ATTRIBUTE_NAME) &&
			$request->attributes->get(Unserialize::REQUEST_ATTRIBUTE_NAME) === $argument->getName()
		) {
			return [ $request->attributes->get($argument->getName()) ];
		}

		return [];
	}
}
