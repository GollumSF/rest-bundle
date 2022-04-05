<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Serialize\Handler;

use Doctrine\Common\Annotations\Reader;
use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Metadata\Serialize\Handler\AnnotationHandler;
use PHPUnit\Framework\TestCase;

class AnnoDummy {
	public function action() {}
}

class AnnotationHandlerTest extends TestCase {
	
	public function provideGetMetadata() {
		
		$annotationClass = $this->getMockBuilder(Serialize::class)->disableOriginalConstructor()->getMock();
		$annotationClass
			->expects($this->any())
			->method('getCode')
			->willReturn(1)
		;
		$annotationClass
			->expects($this->any())
			->method('getGroups')
			->willReturn([ 'group1' ])
		;
		$annotationClass
			->expects($this->any())
			->method('getHeaders')
			->willReturn([ 'header1' ])
		;
		
		$annotationMethod = $this->getMockBuilder(Serialize::class)->disableOriginalConstructor()->getMock();
		$annotationMethod
			->expects($this->any())
			->method('getCode')
			->willReturn(2)
		;
		$annotationMethod
			->expects($this->any())
			->method('getGroups')
			->willReturn([ 'group2' ])
		;
		$annotationMethod
			->expects($this->any())
			->method('getHeaders')
			->willReturn([ 'header2' ])
		;
		
		return [
			[ null, null, null, null, null ],
			[ $annotationClass, null, 1, [ 'group1' ], [ 'header1' ] ],
			[ null, $annotationMethod, 2, [ 'group2' ], [ 'header2' ] ],
			[ $annotationClass, $annotationMethod, 2, [ 'group2' ], [ 'header2' ] ],
		];
	}
	
	/**
	 * @dataProvider provideGetMetadata
	 */
	public function testGetMetadata($annotationClass, $annotationMethod, $code, $group, $header) {
		
		$reader = $this->getMockForAbstractClass(Reader::class);
		
		$reader
			->expects($this->once())
			->method('getClassAnnotation')
			->willReturnCallback(function($rClass, $anno) use ($annotationClass) {
				$this->assertInstanceOf(\ReflectionClass::class, $rClass);
				$this->assertEquals($rClass->getName(), AnnoDummy::class);
				$this->assertEquals($anno, Serialize::class);
				return $annotationClass;
			})
		;
		$reader
			->expects($this->once())
			->method('getMethodAnnotation')
			->willReturnCallback(function($rClass, $anno) use ($annotationMethod) {
				$this->assertInstanceOf(\ReflectionMethod::class, $rClass);
				$this->assertEquals($rClass->getName(), 'action');
				$this->assertEquals($anno, Serialize::class);
				return $annotationMethod;
			})
		;
		
		$handler = new AnnotationHandler(
			$reader
		);
		
		$metadata = $handler->getMetadata(AnnoDummy::class, 'action');
		
		if ($code === null) {
			$this->assertNull($metadata);	
		} else {
			$this->assertEquals($metadata->getCode(), $code);
			$this->assertEquals($metadata->getGroups(), $group);
			$this->assertEquals($metadata->getHeaders(), $header);
		}
	}
}
