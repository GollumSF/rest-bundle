<?php

namespace Test\GollumSF\RestBundle\ProjectTest\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Annotation\Validate;
use GollumSF\RestBundle\Model\StaticArrayApiList;
use GollumSF\RestBundle\Search\ApiSearchInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Test\GollumSF\RestBundle\ProjectTest\Entity\Book;

/**
 * @Route("/api/books")
 */
class BookController {
	
	/**
	 * @Route("", methods={"GET"})
	 * @Serialize(groups="book_getc")
	 */
	public function list(ApiSearchInterface $apiSearch) {
		return $apiSearch->apiFindBy(Book::class);
	}

	/**
	 * @Route("/{id}", methods={"GET"})
	 * @Serialize(groups="book_get")
	 */
	public function find(Book $book) {
		return $book;
	}

	/**
	 * @Route("", methods={"POST"})
	 * @Unserialize("book", groups="book_post")
	 * @Validate("book_post")
	 * @Serialize(groups="book_get", code=Response::HTTP_CREATED)
	 *
	 */
	public function post(Book $book) {
		return $book;
	}

	/**
	 * @Route("/is-granted", methods={"POST"})
	 * @IsGranted("IS_AUTHENTICATED_FULLY")
	 * @Unserialize("book", groups="book_post")
	 * @Validate("book_post")
	 * @Serialize(groups="book_get", code=Response::HTTP_CREATED)
	 *
	 */
	public function postDenyIsGranted(Book $book) {
		return $book;
	}

	/**
	 * @Route("/security", methods={"POST"})
	 * @Security("is_granted('AUTHENTICATED_FULLY')")
	 * @Unserialize("book", groups="book_post")
	 * @Validate("book_post")
	 * @Serialize(groups="book_get", code=Response::HTTP_CREATED)
	 *
	 */
	public function postDenySecurity(Book $book) {
		return $book;
	}

	/**
	 * @Route("/{id}", methods={"PUT"})
	 * @Unserialize("book", groups="book_put")
	 * @Validate({ "book_put" })
	 * @Serialize(groups="book_get")
	 */
	public function put(Book $book) {
		return $book;
	}

	/**
	 * @Route("/{id}/title", methods={"PATCH"})
	 * @Unserialize("book", groups="book_patch_title")
	 * @Serialize(groups="book_get")
	 */
	public function patchTitle(Book $book) {
		return $book;
	}

	/**
	 * @Route("/{id}", methods={"DELETE"})
	 * @Serialize(groups="book_get")
	 */
	public function delete(Book $book, EntityManagerInterface $em) {
		$em->remove($book);
		$em->flush();
		return $book;
	}
}