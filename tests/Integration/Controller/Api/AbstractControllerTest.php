<?php

namespace Test\GollumSF\RestBundle\Integration\Controller\Api;

use Doctrine\Common\Annotations\AnnotationReader;
use GollumSF\RestBundle\GollumSFRestBundle;
use Nyholm\BundleTest\BaseBundleTestCase;
use Nyholm\BundleTest\CompilerPass\PublicServicePass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractControllerTest extends BaseBundleTestCase {
	
	protected $projectPath = __DIR__ . '/../../../ProjectTest';
	
	/** @var KernelInterface */
	private $kernel;

	protected function getBundleClass() {
		return GollumSFRestBundle::class;
	}

	protected function setUp(): void {
		parent::setUp();
		$_ENV['SHELL_VERBOSITY'] = 1;
		// Make all services public
		$this->addCompilerPass(new PublicServicePass('|GollumSF*|'));

		AnnotationReader::addGlobalIgnoredName('after');
		AnnotationReader::addGlobalIgnoredName('test');
		AnnotationReader::addGlobalIgnoredName('dataProvider');
		AnnotationReader::addGlobalIgnoredName('covers');
	}

	protected function getKernel(): KernelInterface {
		if (!$this->kernel) {
			// Create a new Kernel
			$this->kernel = $this->createKernel();
	
			// Add some other bundles we depend on
			$this->kernel->addBundle(\Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class);
			$this->kernel->addBundle(\Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class);
			$this->kernel->addBundle(\Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class);
	
			$this->kernel->addCompilerPasses([ new PublicServicePass('|GollumSF*|') ]);
			
			// Add some configuration
			$this->kernel->addConfigFile($this->projectPath.'/Resources/config/config.yaml');
	
			// Boot the kernel.
			$this->kernel->boot();
		}
		return $this->kernel;
	}
	
	protected function runCommand(string $name, array $params = []): CommandTester
	{
		$application = new Application($this->getKernel());

		$command = $application->find($name);
		$commandTester = new CommandTester($command);
		$commandTester->execute(
			array_merge(['command' => $command->getName()], $params),
			[
				'interactive' => false,
				'decorated' => true,
				'verbosity' => true,
			]
		);
		return $commandTester;
	}
	
	protected function loadFixture(): void {
		$this->runCommand('doctrine:schema:drop',  [ '--force' ]);
		$this->runCommand('doctrine:schema:create');
		$this->runCommand('doctrine:schema:update',  [ '--force' ]);
		$this->runCommand('doctrine:fixtures:load');
	}

	protected function getContainer(): ContainerInterface {
		return $this->getKernel()->getContainer();
	}

	protected function getClient(): AbstractBrowser {
		return $this->getContainer()->get('test.client');
	}
}