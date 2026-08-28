<?php

namespace Test\GollumSF\RestBundle\DependencyInjection\Compiler;

use GollumSF\RestBundle\DependencyInjection\Compiler\ApiOrderResolverPass;
use GollumSF\RestBundle\Sort\ApiOrderResolverInterface;
use GollumSF\RestBundle\Sort\ApiSorterInterface;
use GollumSF\RestBundle\Sort\Handler\MetadataHandler;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ApiOrderResolverPassTest extends AbstractCompilerPassTestCase {

	protected function registerCompilerPass(ContainerBuilder $container): void {
		$container->addCompilerPass(new ApiOrderResolverPass());
	}

	/**
	 * Highest priority first: the resolver stops on the first handler answering.
	 */
	public function testHandlersAreOrderedByDescendingPriority() {

		$low = new Definition();
		$low->addTag(ApiOrderResolverInterface::HANDLER_TAG, [ 'priority' => 0 ]);
		$this->setDefinition('low', $low);

		$high = new Definition();
		$high->addTag(ApiOrderResolverInterface::HANDLER_TAG, [ 'priority' => 100 ]);
		$this->setDefinition('high', $high);

		$noPriority = new Definition();
		$noPriority->addTag(ApiOrderResolverInterface::HANDLER_TAG);
		$this->setDefinition('noPriority', $noPriority);

		$service = new Definition();
		$this->setDefinition(ApiOrderResolverInterface::class, $service);

		$this->compile();

		$calls = $service->getMethodCalls();

		$this->assertEquals('addHandler', $calls[0][0]);
		$this->assertEquals('high', $calls[0][1][0]->__toString());
		$this->assertEquals('low', $calls[1][1][0]->__toString());
		$this->assertEquals('noPriority', $calls[2][1][0]->__toString());
	}

	public function testSortersAreCollectedIntoTheMetadataHandlerLocator() {

		$sorter = new Definition();
		$sorter->addTag(ApiSorterInterface::TAG);
		$this->setDefinition('my.sorter', $sorter);

		$this->setDefinition(ApiOrderResolverInterface::class, new Definition());

		$metadataHandler = new Definition();
		$metadataHandler->setArgument('$sorters', null);
		$this->setDefinition(MetadataHandler::class, $metadataHandler);

		$this->compile();

		$locator = $metadataHandler->getArgument('$sorters');
		$this->assertNotNull($locator);
	}

	public function testResolverNotDeclared() {

		$container = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();

		$container
			->expects($this->once())
			->method('has')
			->with(ApiOrderResolverInterface::class)
			->willReturn(false)
		;
		$container
			->expects($this->never())
			->method('findTaggedServiceIds')
		;

		$compilerPass = new ApiOrderResolverPass();
		$compilerPass->process($container);
	}
}
