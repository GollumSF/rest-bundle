<?php

namespace GollumSF\RestBundle\DependencyInjection\Compiler;


use Doctrine\Persistence\ManagerRegistry;
use GollumSF\RestBundle\EventSubscriber\SerializerSubscriber;
use GollumSF\RestBundle\Request\ParamConverter\PostRestParamConverter;
use GollumSF\RestBundle\Search\ApiSearchInterface;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineIdDenormalizer;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineObjectDenormalizer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineBuilderPass implements CompilerPassInterface {
	
	public function process(ContainerBuilder $container) {
		if (!$container->hasDefinition(ManagerRegistry::class) && !$container->hasAlias(ManagerRegistry::class)) {
			return;
		}
		$container->getDefinition(SerializerSubscriber::class      )->addMethodCall('setManagerRegistry', [ new Reference(ManagerRegistry::class) ]);
		$container->getDefinition(DoctrineIdDenormalizer::class    )->addMethodCall('setManagerRegistry', [ new Reference(ManagerRegistry::class) ]);
		$container->getDefinition(DoctrineObjectDenormalizer::class)->addMethodCall('setManagerRegistry', [ new Reference(ManagerRegistry::class) ]);
		$container->getDefinition(ApiSearchInterface::class        )->addMethodCall('setManagerRegistry', [ new Reference(ManagerRegistry::class) ]);

		if ($container->hasDefinition('sensio_framework_extra.converter.doctrine.orm')) {
			$container->getDefinition(PostRestParamConverter::class)
				->addMethodCall('setDoctrineParamConverter', [ 
					new Reference('sensio_framework_extra.converter.doctrine.orm')
				])
			;
		}
			
	}
}
