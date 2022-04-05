<?php
namespace GollumSF\RestBundle;

use GollumSF\RestBundle\DependencyInjection\Compiler\DoctrinePass;
use GollumSF\RestBundle\DependencyInjection\Compiler\MetadataSerializeManagerPass;
use GollumSF\RestBundle\DependencyInjection\Compiler\MetadataUnserializeManagerPass;
use GollumSF\RestBundle\DependencyInjection\Compiler\MetadataValidateManagerPass;
use GollumSF\RestBundle\DependencyInjection\Compiler\TokenStoragePass;
use GollumSF\RestBundle\DependencyInjection\Compiler\ValidatorPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * GollumSFRestBundle
 *
 * @author Damien Duboeuf <smeagolworms4@gmail.com>
 */
class GollumSFRestBundle extends Bundle {
	
	public function build(ContainerBuilder $container) {
		$container->addCompilerPass(new DoctrinePass());
		$container->addCompilerPass(new ValidatorPass());
		$container->addCompilerPass(new TokenStoragePass());
		$container->addCompilerPass(new MetadataSerializeManagerPass());
		$container->addCompilerPass(new MetadataUnserializeManagerPass());
		$container->addCompilerPass(new MetadataValidateManagerPass());
	}
}
