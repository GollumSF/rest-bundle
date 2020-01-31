<?php
namespace Test\GollumSF\RestBundle\Model;

use GollumSF\RestBundle\Model\StaticArrayApiList;
use GollumSF\RestBundle\Repository\ApiFinderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StaticArrayApiListTest extends TestCase {
	
	public function testGetData() {

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request
			->expects($this->at(0))
			->method('get')
			->with('limit')
			->willReturn(20)
		;
		$request
			->expects($this->at(1))
			->method('get')
			->with('page')
			->willReturn(0)
		;
		$request
			->expects($this->at(2))
			->method('get')
			->with('order')
			->willReturn(null)
		;
		$request
			->expects($this->at(3))
			->method('get')
			->with('direction')
			->willReturn(ApiFinderRepositoryInterface::DIRECTION_ASC)
		;
		
		$apiList = new StaticArrayApiList([
			'AAA',
			'BBB',
		], 2, $request);

		$this->assertEquals($apiList->getData(), [
			'AAA',
			'BBB',
		]);
	}
	
}