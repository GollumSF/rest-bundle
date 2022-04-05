<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Validate\Handler;

use Doctrine\Common\Annotations\Reader;
use GollumSF\RestBundle\Annotation\Validate;
use GollumSF\RestBundle\Metadata\Validate\Handler\AnnotationHandler;
use PHPUnit\Framework\TestCase;

class AnnoDummy {
	public function action() {}
}

class AnnotationHandlerTest extends TestCase {
	
	public function provideGetMetadata() {
		
		$annotationClass = $this->getMockBuilder(Validate::class)->disableOriginalConstructor()->getMock();
		$annotationClass
			->expects($this->any())
			->method('getGroups')
			->willReturn([ 'group1' ])
		;
		
		$annotationMethod = $this->getMockBuilder(Validate::class)->disableOriginalConstructor()->getMock();
		$annotationMethod
			->expects($this->any())
			->method('getGroups')
			->willReturn([ 'group2' ])
		;
		
		return [
			[ null, null, null ],
			[ $annotationClass, null, [ 'group1' ] ],
			[ null, $annotationMethod, [ 'group2' ] ],
			[ $annotationClass, $annotationMethod, [ 'group2' ] ],
		];
	}
	
	/**
	 * @dataProvider provideGetMetadata
	 */
	public function testGetMetadata($annotationClass, $annotationMethod, $group) {
		
		$reader = $this->getMockForAbstractClass(Reader::class);
		
		$reader
			->expects($this->once())
			->method('getClassAnnotation')
			->willReturnCallback(function($rClass, $anno) use ($annotationClass) {
				$this->assertInstanceOf(\ReflectionClass::class, $rClass);
				$this->assertEquals($rClass->getName(), AnnoDummy::class);
				$this->assertEquals($anno, Validate::class);
				return $annotationClass;
			})
		;
		$reader
			->expects($this->once())
			->method('getMethodAnnotation')
			->willReturnCallback(function($rClass, $anno) use ($annotationMethod) {
				$this->assertInstanceOf(\ReflectionMethod::class, $rClass);
				$this->assertEquals($rClass->getName(), 'action');
				$this->assertEquals($anno, Validate::class);
				return $annotationMethod;
			})
		;
		
		$handler = new AnnotationHandler(
			$reader
		);
		
		$metadata = $handler->getMetadata(AnnoDummy::class, 'action');
		
		if ($group === null) {
			$this->assertNull($metadata);
		} else {
			$this->assertEquals($metadata->getGroups(), $group);
		}
	}
}
