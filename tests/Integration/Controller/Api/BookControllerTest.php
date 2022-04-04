<?php

namespace Test\GollumSF\RestBundle\Integration\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\EventSubscriber\ExceptionSubscriber;
use Test\GollumSF\RestBundle\ProjectTest\Entity\Book;

class BookControllerTest extends AbstractControllerTest {
	
	use ReflectionPropertyTrait;
	
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
	
	public function provideFind() {
		return [
			[ 1, [
				'id' => 1,
				'title' => 'TITLE_1',
				'description' => 'DESCRIPTION_1',
				'author' => [
					'id' => 1,
					'name' => 'AUTHOR_1'
				],
				'category' => [
					'id' => 1
				]
			] ],

			[ 2, [
				'id' => 2,
				'title' => 'TITLE_2',
				'description' => 'DESCRIPTION_2',
				'author' => [
					'id' => 2,
					'name' => 'AUTHOR_2'
				],
				'category' => [
					'id' => 1
				]
			] ],

			[ 3, [
				'id' => 3,
				'title' => 'TITLE_3',
				'description' => 'DESCRIPTION_3',
				'author' => [
					'id' => 2,
					'name' => 'AUTHOR_2'
				],
				'category' => [
					'id' => 1
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

		$client->request('GET', '/api/books/'.$id);
		$response = $client->getResponse();
		
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getContent(), \json_encode($result));
	}

	public function testFind404() {

		$this->loadFixture();
		
		/** @var ExceptionSubscriber $exceptionSubscriber */
		$exceptionSubscriber = $this->getContainer()->get(ExceptionSubscriber::class);
		$this->reflectionSetValue($exceptionSubscriber, 'debug', false);

		$client = $this->getClient();

		$client->request('GET', '/api/books/200');
		$response = $client->getResponse();
		$json = \json_decode($response->getContent(), true);
		
		$this->assertEquals($response->getStatusCode(), 404);
		$this->assertIsArray($json);
		$this->assertArrayHasKey('message', $json);
		$this->assertArrayHasKey('code', $json);
	}

	public function providePostSuccess() {
		return [
			[
				[
					'title' => 'TITLE_51',
					'description' => 'DESCRIPTION_51',
					'author' => 1,
					'category' => 1,
				],
				[
					'id' => 51,
					'title' => 'TITLE_51',
					'description' => 'DESCRIPTION_51',
					'author' => [
						'id' => 1,
						'name' => 'AUTHOR_1'
					],
					'category' => [ 'id' => 1 ]
				], 1, 1, 'AUTHOR_1'
			],
			
			[
				[
					'title' => 'TITLE_51',
					'description' => 'DESCRIPTION_51',
					'author' => [ 'id' => 2 ],
					'category' => [ 'id' => 3 ],
				],
				[
					'id' => 51,
					'title' => 'TITLE_51',
					'description' => 'DESCRIPTION_51',
					'author' => [
						'id' => 2,
						'name' => 'AUTHOR_2'
					],
					'category' => [ 'id' => 3 ]
				], 3, 2, 'AUTHOR_2'
			],

			[
				[
					'title' => 'TITLE_51',
					'description' => 'DESCRIPTION_51',
					'author' => [ 'name' => 'AUTHOR_NEW' ],
					'category' => 5,
				],
				[
					'id' => 51,
					'title' => 'TITLE_51',
					'description' => 'DESCRIPTION_51',
					'author' => [
						'id' => 41,
						'name' => 'AUTHOR_NEW'
					],
					'category' => [ 'id' => 5 ]
				], 5, 41, 'AUTHOR_NEW'
			],

			[
				[
					'title' => 'TITLE_51',
					'description' => 'DESCRIPTION_51',
					'author' => [ 'id' => 1, 'name' => 'AUTHOR_NEW' ],
					'category' => 5,
				],
				[
					'id' => 51,
					'title' => 'TITLE_51',
					'description' => 'DESCRIPTION_51',
					'author' => [
						'id' => 1,
						'name' => 'AUTHOR_NEW'
					],
					'category' => [ 'id' => 5 ]
				], 5, 1, 'AUTHOR_NEW'
			],
		];
	}

	/**
	 * @dataProvider providePostSuccess
	 */
	public function testPostSuccess($content, $result, $categoryId, $authorId, $authorName) {

		$this->loadFixture();

		$client = $this->getClient();

		$client->request('POST', '/api/books', [], [], [], \json_encode($content));
		$response = $client->getResponse();
		$this->assertEquals($response->getStatusCode(), 201);
		$this->assertEquals($response->getContent(), \json_encode($result));

		/** @var ManagerRegistry $doctrine */
		$doctrine = $this->getContainer()->get('doctrine');
		$em = $doctrine->resetManager();
		$em->clear();
		
		/** @var Book $book */
		$book = $em->getRepository(Book::class)->find(51);
		$this->assertEquals($book->getTitle(), 'TITLE_51');
		$this->assertEquals($book->getDescription(), 'DESCRIPTION_51');
		$this->assertEquals($book->getCategory()->getId(), $categoryId);
		$this->assertEquals($book->getAuthor()->getId(), $authorId);
		$this->assertEquals($book->getAuthor()->getName(), $authorName);
	}

	public function providerPostValidatorError() {
		return [
			[ [
				'title' => 'TITLE_ERROR',
				'description' => '',
				'author' => 1,
				'category' => 1,
			], 'description' ],

			[ [
				'title' => 'TITLE_ERROR',
				'author' => 1,
				'category' => 1,
			], 'description' ],

			[ [
				'title' => '',
				'description' => 'DESCRIPTION_ERROR',
				'author' => 1,
				'category' => 1,
			], 'title' ],

			[ [
				'description' => 'DESCRIPTION_ERROR',
				'author' => 1,
				'category' => 1,
			], 'title' ],

			[ [
				'title' => 'TITLE_ERROR',
				'description' => 'DESCRIPTION_ERROR',
				'category' => 1,
			], 'author' ],

			[ [
				'title' => 'TITLE_ERROR',
				'description' => 'DESCRIPTION_ERROR',
				'author' => 1,
			], 'category' ],
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
				'author' => 1,
				'category' => 1,
			], 'description' ],

			[ [
				'title' => 'TITLE_ERROR',
				'description' => 0,
				'author' => 1,
				'category' => 1,
			], 'description' ],

			[ [
				'title' => 'TITLE_ERROR',
				'description' => [],
				'author' => 1,
				'category' => 1,
			], 'description' ],
			[ [
				'title' => null,
				'description' => 'DESCRIPTION_ERROR',
				'author' => 1,
				'category' => 1,
			], 'title' ],

			[ [
				'title' => 0,
				'description' => 'DESCRIPTION_ERROR',
				'author' => 1,
				'category' => 1,
			], 'title' ],

			[ [
				'title' => [],
				'description' => 'DESCRIPTION_ERROR',
				'author' => 1,
				'category' => 1,
			], 'title' ],
		];
	}

	/**
	 * @dataProvider providerPostBadRequest
	 */
	public function testPostBadRequest($content, $key) {

		$this->loadFixture();

		/** @var ExceptionSubscriber $exceptionSubscriber */
		$exceptionSubscriber = $this->getContainer()->get(ExceptionSubscriber::class);
		$this->reflectionSetValue($exceptionSubscriber, 'debug', false);

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
				'author' => 2,
				'category' => 2,
			] ],

			[ [
				'id' => 1,
				'title' => 'TITLE_NEW_1',
				'description' => 'DESCRIPTION_NEW_1',
				'author' => 2,
				'category' => 2,
			] ],

			[ [
				'id' => 2,
				'title' => 'TITLE_NEW_1',
				'description' => 'DESCRIPTION_NEW_1',
				'author' => 2,
				'category' => 2,
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
			'author' => [
				'id' => 2,
				'name' => 'AUTHOR_2'
			],
			'category' => [ 'id' => 2 ]
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

	public function testPostIsGranted() {
		$this->loadFixture();

		/** @var ExceptionSubscriber $exceptionSubscriber */
		$exceptionSubscriber = $this->getContainer()->get(ExceptionSubscriber::class);
		$this->reflectionSetValue($exceptionSubscriber, 'debug', false);

		$client = $this->getClient();

		$client->request('POST', '/api/books/is-granted', [], [], [], \json_encode([
			'title' => 'TITLE_NEW_1',
			'description' => 'DESCRIPTION_NEW_1',
			'author' => 2,
			'category' => 2,
		]));
		$response = $client->getResponse();
		$this->assertEquals($response->getStatusCode(), 401);
	}

	public function testPut404() {
		$this->loadFixture();

		/** @var ExceptionSubscriber $exceptionSubscriber */
		$exceptionSubscriber = $this->getContainer()->get(ExceptionSubscriber::class);
		$this->reflectionSetValue($exceptionSubscriber, 'debug', false);

		$client = $this->getClient();

		$client->request('PUT', '/api/books/4242', [], [], [], \json_encode([
			'title' => 'TITLE_NEW_1',
			'description' => 'DESCRIPTION_NEW_1',
			'author' => 2,
			'category' => 2,
		]));
		$response = $client->getResponse();
		$this->assertEquals($response->getStatusCode(), 404);
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
			] ],

			[ [
				'title' => 'TITLE_NEW_1',
				'author' => 2,
			] ],

			[ [
				'title' => 'TITLE_NEW_1',
				'category' => 2,
			] ],
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
			'author' => [
				'id' => 1,
				'name' => 'AUTHOR_1'
			],
			'category' => [ 'id' => 1 ]
		]));

		/** @var ManagerRegistry $doctrine */
		$doctrine = $this->getContainer()->get('doctrine');
		$em = $doctrine->resetManager();
		$em->clear();

		/** @var Book $book */
		$book = $em->getRepository(Book::class)->find(1);
		$this->assertEquals($book->getTitle(), 'TITLE_NEW_1');
		$this->assertEquals($book->getDescription(), 'DESCRIPTION_1');
		$this->assertEquals($book->getAuthor()->getId(), 1);
		$this->assertEquals($book->getCategory()->getId(), 1);
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
