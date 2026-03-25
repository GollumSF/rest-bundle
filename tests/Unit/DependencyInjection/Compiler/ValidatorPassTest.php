<?php
namespace Test\GollumSF\RestBundle\Unit\DependencyInjection\Compiler;

use GollumSF\RestBundle\DependencyInjection\Compiler\ValidatorPass;
use GollumSF\RestBundle\EventSubscriber\SerializerSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorPassTest extends TestCase {

	public function testProcessWithValidator() {
		$container = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();
		$definition = $this->getMockBuilder(Definition::class)->disableOriginalConstructor()->getMock();

		$container
			->expects($this->once())
			->method('hasDefinition')
			->with(ValidatorInterface::class)
			->willReturn(true)
		;

		$container
			->expects($this->once())
			->method('getDefinition')
			->with(SerializerSubscriber::class)
			->willReturn($definition)
		;

		$definition
			->expects($this->once())
			->method('addMethodCall')
			->with('setValidator', $this->callback(function ($args) {
				return count($args) === 1 && $args[0] instanceof Reference;
			}))
		;

		$pass = new ValidatorPass();
		$pass->process($container);
	}

	public function testProcessWithoutValidator() {
		$container = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();

		$container
			->expects($this->once())
			->method('hasDefinition')
			->with(ValidatorInterface::class)
			->willReturn(false)
		;

		$container
			->expects($this->once())
			->method('hasAlias')
			->with(ValidatorInterface::class)
			->willReturn(false)
		;

		$container
			->expects($this->never())
			->method('getDefinition')
		;

		$pass = new ValidatorPass();
		$pass->process($container);
	}

	public function testProcessWithValidatorAlias() {
		$container = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();
		$definition = $this->getMockBuilder(Definition::class)->disableOriginalConstructor()->getMock();

		$container
			->expects($this->once())
			->method('hasDefinition')
			->with(ValidatorInterface::class)
			->willReturn(false)
		;

		$container
			->expects($this->once())
			->method('hasAlias')
			->with(ValidatorInterface::class)
			->willReturn(true)
		;

		$container
			->expects($this->once())
			->method('getDefinition')
			->with(SerializerSubscriber::class)
			->willReturn($definition)
		;

		$definition
			->expects($this->once())
			->method('addMethodCall')
			->with('setValidator', $this->callback(function ($args) {
				return count($args) === 1 && $args[0] instanceof Reference;
			}))
		;

		$pass = new ValidatorPass();
		$pass->process($container);
	}
}
