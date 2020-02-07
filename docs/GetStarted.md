# Get started

Create your model or entity, with serialize groups and validator

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class Book {

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 *
	 * @Groups({
	 * 	"book_get", "book_getc"
	 * })
	 *
	 * @var int
	 */
	private $id;
	
	/**
	 * @ORM\Column(type="string")
	 * 
	 * @Groups({
	 * 	"book_get", "book_getc", "book_post", "book_put", "book_patch_title",
	 * })
	 * 
	 * @Assert\NotBlank(groups={"book_post", "book_put", "book_patch_title"})
	 * 
	 * @var string
	 */
	private $title;

	/**
	 * @ORM\Column(type="string")
	 * 
	 * @Groups({
	 * 	"book_get", "book_post", "book_put",
	 * })
	 *
	 * @Assert\Length(max=512, groups={"book_post", "book_put"})
	 * @Assert\NotBlank(groups={"book_post", "book_put"})
	 *
	 * @var string
	 */
	private $description;
	
	/////////////
	// Getters //
	/////////////
	
	public function getId(): ?int {
		return $this->id;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function getDescription(): string {
		return $this->description;
	}
	
	/////////////
	// Setters //
	/////////////

	public function setTitle(string $title): self {
		$this->title = $title;
		return $this;
	}

	public function setDescription(string $description): self {
		$this->description = $description;
		return $this;
	}
}
```

Create your Api Controller

```php
<?php
namespace App\Controller\Api;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Annotation\Validate;
use GollumSF\RestBundle\Search\ApiSearchInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/books")
 */
class BookController {

	/**
	 * @Route("", methods={"GET"})
	 * @Serialize(groups="book_getc")
	 */
	public function list(ApiSearchInterface $apiSearch) { // Load Service
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
	 * @Validate({ "book_post" })
	 * @Serialize(groups="book_get", code=Response::HTTP_CREATED)
	 *
	 */
	public function post(Book $book) {
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
	 * @Unserialize"book", groups="book_patch_title")
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
```

## Request and Response

### `list` action:
 - url: `GET http://127.0.0.1/api/books`
 - request: 
	- query parameters: (example : `GET http://127.0.0.1/api/books?limit=10&page1&order=title&directionp=desc`)
		- limit (**integer**): Number returned items
		- page: (**integer**): Page of returned items into total
		- order (**string**): Name or property for sort
		- direction (**asc** or **desc**): Direction of sort
 - response:
	- body content:
```json
{
	"total": 100,
	"data": [
		{ "id": 1, "title": "Dune" },
		{ "id": 2, "title": "Robots" },
		{ "id": 3, "title": "Game of Throne" },
		{ "id": 4, "title": "Harry Potters" }
	]
}
```

### `find` action:

 - url: `GET http://127.0.0.1/api/books/1`
 - request: *none*
 - response:
	- body content:
```json
{
	"id": 1,
	"title": "Dune",
	"description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
}
```

### `post` action:

 - url: `POST http://127.0.0.1/api/books`
 - request:
	- body content:
```json
{
	"title": "Deus ex machina",
	"description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
}
```
 - response:
	- body content:
```json
{
	"id": 5,
	"title": "Deus ex machina",
	"description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
}
```

### `put` action:

 - url: `PUT http://127.0.0.1/api/books/5`
 - request:
	- body content:
```json
{
	"title": "Deus ex machina - The Book",
	"description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
}
```
 - response:
	- body content:
```json
{
	"id": 5,
	"title": "Deus ex machina - The Book",
	"description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
}
```

### `patchTitle` action:

 - url: `PATCH http://127.0.0.1/api/books/5/patch-title`
 - request:
	- body content:
```json
{
	"title": "Deus ex machina - The Book",
}
```
 - response:
	- body content:
```json
{
	"id": 5,
	"title": "Deus ex machina - The Book",
	"description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
}
```

### `delete` action:

 - url: `DELETE http://127.0.0.1/api/books/5`
 - request: *none*
 - response:
	- body content:
```json
{
	"id": null,
	"title": "Deus ex machina - The Book",
	"description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
}
```