<?php

namespace Test\GollumSF\RestBundle\Integration\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Test\GollumSF\RestBundle\ProjectTest\Entity\Book;

class AuthorControllerTest extends AbstractControllerTest {
	
	public function provideFind() {
		return [
			[ 1, [
				'id' => 1,
				'name' => 'AUTHOR_1',
				'books' => [
					[ 'id' => 1 ],
				]
			] ],

			[ 2, [
				'id' => 2,
				'name' => 'AUTHOR_2',
				'books' => [
					[ 'id' => 2 ],
					[ 'id' => 3 ],
				]
			] ],

			[ 3, [
				'id' => 3,
				'name' => 'AUTHOR_3',
				'books' => [
					[ 'id' => 4 ],
				]
			] ],
		];
	}

	/**
	 * @dataProvider provideFind
	 */
	public function testFind($id, $result)
	{

		$this->loadFixture();

		$client = $this->getClient();

		$client->request('GET', '/api/authors/'.$id);
		$response = $client->getResponse();
		
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getContent(), \json_encode($result));
	}
}