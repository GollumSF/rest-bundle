<?php

namespace Test\GollumSF\RestBundle\ProjectTest\Controller\Api;

use GollumSF\RestBundle\Attribute\Serialize;
use GollumSF\RestBundle\Search\ApiSearchInterface;
use Symfony\Component\Routing\Attribute\Route;
use Test\GollumSF\RestBundle\ProjectTest\Entity\Author;

#[Route('/api/authors')]
class AuthorController {

	#[Route('', methods: ['GET'])]
	#[Serialize(groups: 'author_getc')]
	public function list(ApiSearchInterface $apiSearch) {
		return $apiSearch->apiFindBy(Author::class);
	}

	#[Route('/{id}', methods: ['GET'])]
	#[Serialize(groups: 'author_get')]
	public function find(Author $author) {
		return $author;
	}
}
