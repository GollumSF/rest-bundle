<?php

namespace Test\GollumSF\RestBundle\ProjectTestPhp8\Controller\Api;

use GollumSF\RestBundle\Annotation\Serialize;
use Symfony\Component\Routing\Annotation\Route;
use Test\GollumSF\RestBundle\ProjectTest\Entity\Author;

 #[Route('/api/authors')]
class AuthorController {
	
	 #[Route('/{id}', methods: ['GET'])]
	 #[Serialize(groups: 'author_get')]
	public function find(Author $author) {
		return $author;
	}
}
