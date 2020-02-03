<?php
namespace GollumSF\RestBundle;

use GollumSF\RestBundle\DependencyInjection\Compiler\DoctrineBuilderPass;
use GollumSF\RestBundle\DependencyInjection\Compiler\ValidatorBuilderPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * GollumSFRestBundle
 *
 * @author Damien Duboeuf <smeagolworms4@gmail.com>
 */
class GollumSFRestBundle extends Bundle {
	
	public function build(ContainerBuilder $container) {
		$container->addCompilerPass(new DoctrineBuilderPass());
		$container->addCompilerPass(new ValidatorBuilderPass());
	}
}
