<?php

namespace Test\GollumSF\RestBundle\Integration\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\EventSubscriber\ExceptionSubscriber;
use Test\GollumSF\RestBundle\ProjectTest\Entity\Book;
use PHPUnit\Framework\Attributes\DataProvider;

class BookControllerTest extends AbstractControllerTestCase {
	
	use ReflectionPropertyTrait;
	
	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
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


		$client->request('GET', '/api/books?limit=10&order=title:desc');
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
	
	/**
	 * The `direction` parameter is deprecated but still honoured.
	 */
	public function testListDeprecatedDirection() {

		$this->loadFixture();

		$this->expectUserDeprecationMessage('Since gollumsf/rest-bundle 4.1: The "direction" query parameter is deprecated, use "order=field:direction" instead.');

		$client = $this->getClient();
		$client->request('GET', '/api/books?limit=3&order=title&direction=desc');
		$response = $client->getResponse();

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals(\json_encode([
			'data' => [
				[ 'id' => 9 , 'title' => 'TITLE_9' ],
				[ 'id' => 8 , 'title' => 'TITLE_8' ],
				[ 'id' => 7 , 'title' => 'TITLE_7' ],
			],
			'total' => 50,
		]), $response->getContent());
	}

	/**
	 * `author` is declared with the path `author.name`: the ordering joins the relation.
	 */
	public function testListOrderOnAJoin() {

		$this->loadFixture();

		$client = $this->getClient();
		$client->request('GET', '/api/books?limit=4&order=author:desc,title:asc');
		$response = $client->getResponse();

		$this->assertEquals(200, $response->getStatusCode());

		$data = \json_decode($response->getContent(), true);
		$this->assertEquals(50, $data['total']);
		$this->assertCount(4, $data['data']);

		$client->request('GET', '/api/books?limit=50&order=author:asc,title:asc');
		$titles = array_column(\json_decode($client->getResponse()->getContent(), true)['data'], 'title');

		$client->request('GET', '/api/books?limit=50&order=author:desc,title:asc');
		$reversed = array_column(\json_decode($client->getResponse()->getContent(), true)['data'], 'title');

		$this->assertNotEquals($titles, $reversed);
		$this->assertEquals(50, count($titles));
		$this->assertEquals([], array_diff($titles, $reversed));
	}

	/**
	 * Book declares sortables, so anything else is refused.
	 */
	public function testListOrderOutsideTheWhitelist() {

		$this->loadFixture();

		$client = $this->getClient();
		$client->request('GET', '/api/books?order=description');

		$this->assertEquals(400, $client->getResponse()->getStatusCode());
	}

	public static function provideFind() {
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

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	#[DataProvider('provideFind')]
	public function testFind($id, $result)
	{

		$this->loadFixture();

		$client = $this->getClient();

		$client->request('GET', '/api/books/'.$id);
		$response = $client->getResponse();
		
		$this->assertEquals($response->getStatusCode(), 200);
		$this->assertEquals($response->getContent(), \json_encode($result));
	}

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	public function testFind404() {

		$this->loadFixture();
		
		/** @var ExceptionSubscriber $exceptionSubscriber */
		$exceptionSubscriber = $this->getServiceContainer()->get(ExceptionSubscriber::class);
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

	public static function providePostSuccess() {
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

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	#[DataProvider('providePostSuccess')]
	public function testPostSuccess($content, $result, $categoryId, $authorId, $authorName) {

		$this->loadFixture();

		$client = $this->getClient();

		$client->request('POST', '/api/books', [], [], [], \json_encode($content));
		$response = $client->getResponse();
		$this->assertEquals($response->getStatusCode(), 201);
		$this->assertEquals($response->getContent(), \json_encode($result));

		/** @var ManagerRegistry $doctrine */
		$doctrine = $this->getServiceContainer()->get('doctrine');
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

	public static function providerPostValidatorError() {
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

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	#[DataProvider('providerPostValidatorError')]
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

	public static function providerPostBadRequest() {
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

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	#[DataProvider('providerPostBadRequest')]
	public function testPostBadRequest($content, $key) {

		$this->loadFixture();

		/** @var ExceptionSubscriber $exceptionSubscriber */
		$exceptionSubscriber = $this->getServiceContainer()->get(ExceptionSubscriber::class);
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

	public static function providerPut() {
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

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	#[DataProvider('providerPut')]
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
		$doctrine = $this->getServiceContainer()->get('doctrine');
		$em = $doctrine->resetManager();
		$em->clear();

		/** @var Book $book */
		$book = $em->getRepository(Book::class)->find(1);
		$this->assertEquals($book->getTitle(), 'TITLE_NEW_1');
		$this->assertEquals($book->getDescription(), 'DESCRIPTION_NEW_1');
	}

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	public function testPostIsGranted() {
		$this->loadFixture();

		/** @var ExceptionSubscriber $exceptionSubscriber */
		$exceptionSubscriber = $this->getServiceContainer()->get(ExceptionSubscriber::class);
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

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	public function testPut404() {
		$this->loadFixture();

		/** @var ExceptionSubscriber $exceptionSubscriber */
		$exceptionSubscriber = $this->getServiceContainer()->get(ExceptionSubscriber::class);
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

	public static function providerPatchTitle() {
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

	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	#[DataProvider('providerPatchTitle')]
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
		$doctrine = $this->getServiceContainer()->get('doctrine');
		$em = $doctrine->resetManager();
		$em->clear();

		/** @var Book $book */
		$book = $em->getRepository(Book::class)->find(1);
		$this->assertEquals($book->getTitle(), 'TITLE_NEW_1');
		$this->assertEquals($book->getDescription(), 'DESCRIPTION_1');
		$this->assertEquals($book->getAuthor()->getId(), 1);
		$this->assertEquals($book->getCategory()->getId(), 1);
	}
	
	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	public function testDelete() {

		$this->loadFixture();

		$client = $this->getClient();
		
		$client->request('DELETE', '/api/books/1');
		/** @var ManagerRegistry $doctrine */
		$doctrine = $this->getServiceContainer()->get('doctrine');
		$em = $doctrine->resetManager();
		$em->clear();

		$this->assertNull(
			$em->getRepository(Book::class)->find(1)
		);
	}
}
