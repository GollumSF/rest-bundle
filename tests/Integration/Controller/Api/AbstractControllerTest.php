<?php

namespace Test\GollumSF\RestBundle\Integration\Controller\Api;

use GollumSF\ControllerActionExtractorBundle\GollumSFControllerActionExtractorBundle;
use GollumSF\RestBundle\GollumSFRestBundle;
use Nyholm\BundleTest\TestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractControllerTest extends KernelTestCase {

	protected function getProjectPath(): string {
		return __DIR__ . '/../../../ProjectTest';
	}

	private ?KernelInterface $testKernel = null;

	protected static function getKernelClass(): string {
		return TestKernel::class;
	}

	protected static function createKernel(array $options = []): KernelInterface {
		$kernel = new TestKernel('test', true);
		if (isset($options['config']) && is_callable($options['config'])) {
			$options['config']($kernel);
		}
		return $kernel;
	}

	protected function setUp(): void {
		parent::setUp();
		$_ENV['SHELL_VERBOSITY'] = 1;
	}

	protected function getTestKernel(): KernelInterface {
		if (!$this->testKernel) {
			$projectPath = realpath($this->getProjectPath() . '/../..');
			$configPath = $this->getProjectPath();
			$this->testKernel = self::bootKernel(['config' => function (TestKernel $kernel) use ($projectPath, $configPath) {
				$kernel->addTestBundle(GollumSFRestBundle::class);
				$kernel->addTestBundle(\Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class);
				$kernel->addTestBundle(\Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class);
				$kernel->addTestBundle(\Symfony\Bundle\SecurityBundle\SecurityBundle::class);
				$kernel->addTestBundle(GollumSFControllerActionExtractorBundle::class);
				$kernel->addTestConfig($configPath . '/Resources/config/config.yaml');
				if (PHP_VERSION_ID >= 80400) {
					$kernel->addTestConfig($configPath . '/Resources/config/config_native_lazy.yaml');
				}
				$kernel->setTestProjectDir($projectPath);
				$kernel->setClearCacheAfterShutdown(false);
				$kernel->addTestCompilerPass(new class implements CompilerPassInterface {
					public function process(ContainerBuilder $container): void {
						foreach ($container->getDefinitions() as $id => $definition) {
							if (str_starts_with($id, 'GollumSF\\') || str_starts_with($id, 'Test\\GollumSF\\')) {
								$definition->setPublic(true);
							}
						}
						foreach ($container->getAliases() as $id => $alias) {
							if (str_starts_with($id, 'GollumSF\\') || str_starts_with($id, 'Test\\GollumSF\\') || $id === 'doctrine' || $id === 'test.client') {
								$alias->setPublic(true);
							}
						}
					}
				}, PassConfig::TYPE_BEFORE_REMOVING);
			}]);
		}
		return $this->testKernel;
	}

	protected function runCommand(string $name, array $params = []): CommandTester
	{
		$application = new Application($this->getTestKernel());

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
		$this->runCommand('doctrine:schema:drop',  [ '--force' => true ]);
		$this->runCommand('doctrine:schema:update',  [ '--force' => true, '--complete' => true ]);
		$this->runCommand('doctrine:fixtures:load', [ '--no-interaction' => true ]);
	}

	protected function getServiceContainer(): ContainerInterface {
		return $this->getTestKernel()->getContainer();
	}

	protected function getClient(): AbstractBrowser {
		return $this->getServiceContainer()->get('test.client');
	}
}
