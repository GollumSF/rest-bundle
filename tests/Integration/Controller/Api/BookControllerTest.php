<?php

namespace Test\GollumSF\RestBundle\Integration\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Test\GollumSF\RestBundle\ProjectTest\Entity\Book;

class BookControllerTest extends AbstractControllerTest {
	
	public function testList() {

		$this->loadFixture();
		
		$client = $this->getClient();

		$client->request('GET', '/api/books');
		$response = $client->getResponse();
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getContent(), \json_encode([
			'data' => [
				[ 'id' => 1 , 'title' => 'TITLE_1'  ],
				[ 'id' => 2 , 'title' => 'TITLE_2'  ],
				[ 'id' => 3 , 'title' => 'TITLE_3'  ],
				[ 'id' => 4 , 'title' => 'TITLE_4'  ],
				[ 'id' => 5 , 'title' => 'TITLE_5'  ],
				[ 'id' => 6 , 'title' => 'TITLE_6'  ],
				[ 'id' => 7 , 'title' => 'TITLE_7'  ],
				[ 'id' => 8 , 'title' => 'TITLE_8'  ],
				[ 'id' => 9 , 'title' => 'TITLE_9'  ],
				[ 'id' => 10, 'title' => 'TITLE_10' ],
				[ 'id' => 11, 'title' => 'TITLE_11' ],
				[ 'id' => 12, 'title' => 'TITLE_12' ],
				[ 'id' => 13, 'title' => 'TITLE_13' ],
				[ 'id' => 14, 'title' => 'TITLE_14' ],
				[ 'id' => 15, 'title' => 'TITLE_15' ],
				[ 'id' => 16, 'title' => 'TITLE_16' ],
				[ 'id' => 17, 'title' => 'TITLE_17' ],
				[ 'id' => 18, 'title' => 'TITLE_18' ],
				[ 'id' => 19, 'title' => 'TITLE_19' ],
				[ 'id' => 20, 'title' => 'TITLE_20' ],
				[ 'id' => 21, 'title' => 'TITLE_21' ],
				[ 'id' => 22, 'title' => 'TITLE_22' ],
				[ 'id' => 23, 'title' => 'TITLE_23' ],
				[ 'id' => 24, 'title' => 'TITLE_24' ],
				[ 'id' => 25, 'title' => 'TITLE_25' ],
			],
			'total' => 50,
		]));

		$client->request('GET', '/api/books?limit=10&page=1');
		$response = $client->getResponse();
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getContent(), \json_encode([
			'data' => [
				[ 'id' => 11, 'title' => 'TITLE_11' ],
				[ 'id' => 12, 'title' => 'TITLE_12' ],
				[ 'id' => 13, 'title' => 'TITLE_13' ],
				[ 'id' => 14, 'title' => 'TITLE_14' ],
				[ 'id' => 15, 'title' => 'TITLE_15' ],
				[ 'id' => 16, 'title' => 'TITLE_16' ],
				[ 'id' => 17, 'title' => 'TITLE_17' ],
				[ 'id' => 18, 'title' => 'TITLE_18' ],
				[ 'id' => 19, 'title' => 'TITLE_19' ],
				[ 'id' => 20, 'title' => 'TITLE_20' ],
			],
			'total' => 50,
		]));


		$client->request('GET', '/api/books?limit=10&order=title&direction=desc');
		$response = $client->getResponse();
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getContent(), \json_encode([
			'data' => [
				[ 'id' => 9 , 'title' => 'TITLE_9'  ],
				[ 'id' => 8 , 'title' => 'TITLE_8'  ],
				[ 'id' => 7 , 'title' => 'TITLE_7'  ],
				[ 'id' => 6 , 'title' => 'TITLE_6'  ],
				[ 'id' => 50, 'title' => 'TITLE_50' ],
				[ 'id' => 5 , 'title' => 'TITLE_5'  ],
				[ 'id' => 49, 'title' => 'TITLE_49' ],
				[ 'id' => 48, 'title' => 'TITLE_48' ],
				[ 'id' => 47, 'title' => 'TITLE_47' ],
				[ 'id' => 46, 'title' => 'TITLE_46' ],
			],
			'total' => 50,
		]));
	}
	
	public function testFind()
	{

		$this->loadFixture();

		$client = $this->getClient();

		$client->request('GET', '/api/books/1');
		$response = $client->getResponse();
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getContent(), \json_encode([
			'id' => 1,
			'title' => 'TITLE_1',
			'description' => 'DESCRIPTION_1',
		]));

		$client->request('GET', '/api/books/2');
		$response = $client->getResponse();
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getContent(), \json_encode([
			'id' => 2,
			'title' => 'TITLE_2',
			'description' => 'DESCRIPTION_2',
		]));
	}

	public function testFind404() {

		$this->loadFixture();

		$client = $this->getClient();

		$client->request('GET', '/api/books/200');
		$response = $client->getResponse();
		$json = \json_decode($response->getContent(), true);
		
		$this->assertEquals($response->getStatusCode(), 404);
		$this->assertIsArray($json);
		$this->assertArrayHasKey('message', $json);
		$this->assertArrayHasKey('code', $json);
	}

	public function testPostSuccess()
	{

		$this->loadFixture();

		$client = $this->getClient();

		$client->request('POST', '/api/books', [], [], [], \json_encode([
			'title' => 'TITLE_51',
			'description' => 'DESCRIPTION_51',
		]));
		$response = $client->getResponse();
		$this->assertEquals($response->getStatusCode(), 201);
		$this->assertEquals($response->getContent(), \json_encode([
			'id' => 51,
			'title' => 'TITLE_51',
			'description' => 'DESCRIPTION_51',
		]));

		/** @var ManagerRegistry $doctrine */
		$doctrine = $this->getContainer()->get('doctrine');
		$em = $doctrine->resetManager();
		$em->clear();
		
		/** @var Book $book */
		$book = $em->getRepository(Book::class)->find(51);
		$this->assertEquals($book->getTitle(), 'TITLE_51');
		$this->assertEquals($book->getDescription(), 'DESCRIPTION_51');
	}

	public function providerPostValidatorError() {
		return [
			[ [
				'title' => 'TITLE_ERROR',
				'description' => '',
			], 'description' ],

			[ [
				'title' => 'TITLE_ERROR',
			], 'description' ],

			[ [
				'title' => '',
				'description' => 'DESCRIPTION_ERROR',
			], 'title' ],

			[ [
				'description' => 'DESCRIPTION_ERROR',
			], 'title' ],
		];
	}

	/**
	 * @dataProvider providerPostValidatorError
	 */
	public function testPostValidatorError($content, $key) {

		$this->loadFixture();

		$client = $this->getClient();

		$client->request('POST', '/api/books', [], [], [], \json_encode($content));
		$response = $client->getResponse();
		$json = \json_decode($response->getContent(), true);

		$this->assertEquals($response->getStatusCode(), 400);
		$this->assertIsArray($json);
		$this->assertArrayHasKey($key, $json);
		$this->assertIsArray($json[$key]);

	}

	public function providerPostBadRequest() {
		return [
			[ [
				'title' => 'TITLE_ERROR',
				'description' => null,
			], 'description' ],

			[ [
				'title' => 'TITLE_ERROR',
				'description' => 0,
			], 'description' ],

			[ [
				'title' => 'TITLE_ERROR',
				'description' => [],
			], 'description' ],
			[ [
				'title' => null,
				'description' => 'DESCRIPTION_ERROR',
			], 'title' ],

			[ [
				'title' => 0,
				'description' => 'DESCRIPTION_ERROR',
			], 'title' ],

			[ [
				'title' => [],
				'description' => 'DESCRIPTION_ERROR',
			], 'title' ],
		];
	}

	/**
	 * @dataProvider providerPostBadRequest
	 */
	public function testPostBadRequest($content, $key) {

		$this->loadFixture();

		$client = $this->getClient();

		$client->request('POST', '/api/books', [], [], [], \json_encode($content));
		$response = $client->getResponse();
		$json = \json_decode($response->getContent(), true);
		
		$this->assertEquals($response->getStatusCode(), 400);
		$this->assertIsArray($json);
		$this->assertArrayHasKey('message', $json);
		$this->assertStringContainsString($key, $json['message']);
	}

	public function providerPut() {
		return [
			[ [
				'title' => 'TITLE_NEW_1',
				'description' => 'DESCRIPTION_NEW_1',
			] ],

			[ [
				'id' => 1,
				'title' => 'TITLE_NEW_1',
				'description' => 'DESCRIPTION_NEW_1',
			] ],

			[ [
				'id' => 2,
				'title' => 'TITLE_NEW_1',
				'description' => 'DESCRIPTION_NEW_1',
			] ]
		];
	}

	/**
	 * @dataProvider providerPut
	 */
	public function testPut($content) {

		$this->loadFixture();

		$client = $this->getClient();

		$client->request('PUT', '/api/books/1', [], [], [], \json_encode($content));
		$response = $client->getResponse();
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getContent(), \json_encode([
			'id' => 1,
			'title' => 'TITLE_NEW_1',
			'description' => 'DESCRIPTION_NEW_1',
		]));

		/** @var ManagerRegistry $doctrine */
		$doctrine = $this->getContainer()->get('doctrine');
		$em = $doctrine->resetManager();
		$em->clear();

		/** @var Book $book */
		$book = $em->getRepository(Book::class)->find(1);
		$this->assertEquals($book->getTitle(), 'TITLE_NEW_1');
		$this->assertEquals($book->getDescription(), 'DESCRIPTION_NEW_1');
	}

	public function providerPatchTitle() {
		return [
			[ [
				'title' => 'TITLE_NEW_1',
			] ],
			
			[ [
				'title' => 'TITLE_NEW_1',
				'description' => 'DESCRIPTION_NEW_1',
			] ],

			[ [
				'id' => 1,
				'title' => 'TITLE_NEW_1',
				'description' => 'DESCRIPTION_NEW_1',
			] ],

			[ [
				'id' => 2,
				'title' => 'TITLE_NEW_1',
				'description' => 'DESCRIPTION_NEW_1',
			] ]
		];
	}

	/**
	 * @dataProvider providerPatchTitle
	 */
	public function testPatchTitle($content) {

		$this->loadFixture();

		$client = $this->getClient();

		$client->request('PATCH', '/api/books/1/title', [], [], [], \json_encode($content));
		$response = $client->getResponse();
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getContent(), \json_encode([
			'id' => 1,
			'title' => 'TITLE_NEW_1',
			'description' => 'DESCRIPTION_1',
		]));

		/** @var ManagerRegistry $doctrine */
		$doctrine = $this->getContainer()->get('doctrine');
		$em = $doctrine->resetManager();
		$em->clear();

		/** @var Book $book */
		$book = $em->getRepository(Book::class)->find(1);
		$this->assertEquals($book->getTitle(), 'TITLE_NEW_1');
		$this->assertEquals($book->getDescription(), 'DESCRIPTION_1');
	}
	
	public function testDelete() {

		$this->loadFixture();

		$client = $this->getClient();
		
		$client->request('DELETE', '/api/books/1');
		/** @var ManagerRegistry $doctrine */
		$doctrine = $this->getContainer()->get('doctrine');
		$em = $doctrine->resetManager();
		$em->clear();

		$this->assertNull(
			$em->getRepository(Book::class)->find(1)
		);
	}
}