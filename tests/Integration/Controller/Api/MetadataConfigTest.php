<?php

namespace Test\GollumSF\RestBundle\Integration\Controller\Api;

/**
 * The metadata declared in `gollum_sf_rest.metadata` reaches the managers and takes
 * precedence over the attributes carried by the code.
 */
class MetadataConfigTest extends AbstractControllerTestCase {

	protected function getExtraConfigFiles(): array {
		return [ 'config_metadata.yaml' ];
	}

	/**
	 * Book carries #[Serialize(groups: 'book_getc')] on its list action, which exposes
	 * the id and the title only. The configuration asks for `book_get` instead.
	 */
	public function testTheConfiguredSerializeGroupsWin() {

		$this->loadFixture();

		$client = $this->getClient();
		$client->request('GET', '/api/books?limit=1');
		$response = $client->getResponse();

		$this->assertEquals(200, $response->getStatusCode());

		$book = \json_decode($response->getContent(), true)['data'][0];

		$this->assertEquals('TITLE_1', $book['title']);
		$this->assertArrayHasKey('description', $book);
		$this->assertArrayHasKey('author', $book);
	}

	/**
	 * Book declares #[ApiSortable] on id, title and author; the configuration replaces
	 * that catalogue with description and writer.
	 */
	public function testTheConfiguredSortablesWin() {

		$this->loadFixture();

		$client = $this->getClient();
		$client->request('GET', '/api/books?limit=3&order=description:desc');
		$response = $client->getResponse();

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals(
			[ 'DESCRIPTION_9', 'DESCRIPTION_8', 'DESCRIPTION_7' ],
			array_column(\json_decode($response->getContent(), true)['data'], 'description')
		);
	}

	/**
	 * A configured path crossing a relation joins it, just like the attribute does.
	 */
	public function testTheConfiguredPathJoinsTheRelation() {

		$this->loadFixture();

		$client = $this->getClient();
		$client->request('GET', '/api/books?limit=50&order=writer:asc,description:asc');
		$ascending = array_column(\json_decode($client->getResponse()->getContent(), true)['data'], 'description');

		$client->request('GET', '/api/books?limit=50&order=writer:desc,description:asc');
		$descending = array_column(\json_decode($client->getResponse()->getContent(), true)['data'], 'description');

		$this->assertEquals(200, $client->getResponse()->getStatusCode());
		$this->assertCount(50, $ascending);
		$this->assertNotEquals($ascending, $descending);
		$this->assertEquals([], array_diff($ascending, $descending));
	}

	/**
	 * The keys the attributes declared are gone, the configuration replaced them.
	 */
	public function testTheAttributeSortablesAreOverridden() {

		$this->loadFixture();

		$client = $this->getClient();
		$client->request('GET', '/api/books?order=title');

		$this->assertEquals(400, $client->getResponse()->getStatusCode());
	}
}
