<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Unserialize\Handler;

use Doctrine\Common\Annotations\Reader;
use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Metadata\Unserialize\Handler\AnnotationHandler;
use PHPUnit\Framework\TestCase;

class AnnoDummy {
	public function action() {}
}

class AnnotationHandlerTest extends TestCase {
	
	public function provideGetMetadata() {
		
		$annotationClass = $this->getMockBuilder(Unserialize::class)->disableOriginalConstructor()->getMock();
		$annotationClass
			->expects($this->any())
			->method('getName')
			->willReturn('name1')
		;
		$annotationClass
			->expects($this->any())
			->method('getGroups')
			->willReturn([ 'group1' ])
		;
		$annotationClass
			->expects($this->any())
			->method('isSave')
			->willReturn(false)
		;
		
		$annotationMethod = $this->getMockBuilder(Unserialize::class)->disableOriginalConstructor()->getMock();
		$annotationMethod
			->expects($this->any())
			->method('getName')
			->willReturn('name2')
		;
		$annotationMethod
			->expects($this->any())
			->method('getGroups')
			->willReturn([ 'group2' ])
		;
		$annotationMethod
			->expects($this->any())
			->method('isSave')
			->willReturn(true)
		;
		
		return [
			[ null, null, null, null, null ],
			[ $annotationClass, null, 'name1', [ 'group1' ], false ],
			[ null, $annotationMethod, 'name2', [ 'group2' ], true ],
			[ $annotationClass, $annotationMethod, 'name2', [ 'group2' ], true ],
		];
	}
	
	/**
	 * @dataProvider provideGetMetadata
	 */
	public function testGetMetadata($annotationClass, $annotationMethod, $name, $group, $isSave) {
		
		$reader = $this->getMockForAbstractClass(Reader::class);
		
		$reader
			->expects($this->once())
			->method('getClassAnnotation')
			->willReturnCallback(function($rClass, $anno) use ($annotationClass) {
				$this->assertInstanceOf(\ReflectionClass::class, $rClass);
				$this->assertEquals($rClass->getName(), AnnoDummy::class);
				$this->assertEquals($anno, Unserialize::class);
				return $annotationClass;
			})
		;
		$reader
			->expects($this->once())
			->method('getMethodAnnotation')
			->willReturnCallback(function($rClass, $anno) use ($annotationMethod) {
				$this->assertInstanceOf(\ReflectionMethod::class, $rClass);
				$this->assertEquals($rClass->getName(), 'action');
				$this->assertEquals($anno, Unserialize::class);
				return $annotationMethod;
			})
		;
		
		$handler = new AnnotationHandler(
			$reader
		);
		
		$metadata = $handler->getMetadata(AnnoDummy::class, 'action');
		
		if ($name === null) {
			$this->assertNull($metadata);
		} else {
			$this->assertEquals($metadata->getName(), $name);
			$this->assertEquals($metadata->getGroups(), $group);
			$this->assertEquals($metadata->isSave(), $isSave);
		}
	}
}
