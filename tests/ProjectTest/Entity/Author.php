<?php
namespace Test\GollumSF\RestBundle\ProjectTest\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use GollumSF\EntityRelationSetter\OneToManySetter;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Test\GollumSF\RestBundle\ProjectTest\Repository\AuthorRepository;

/**
 * @ORM\Entity(repositoryClass=AuthorRepository::class)
 */
class Author {

	use OneToManySetter;

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 *
	 * @Groups({
	 *  "author_get",
	 * 	"book_get", "book_put", "book_post"
	 * })
	 *
	 * @var int
	 */
	private $id;

	/**
	 * @ORM\Column(type="string")
	 *
	 * @Groups({
	 *  "author_get",
	 * 	"book_get", "book_put", "book_post"
	 * })
	 *
	 * @Assert\NotBlank(groups={"book_put", "book_post"})
	 *
	 * @var string
	 */
	private $name;

	/**
	 * @ORM\OneToMany(targetEntity=Book::class, mappedBy="author")
	 * @var Book[]|ArrayCollection
	 * 
	 * @Groups({
	 *  "author_get"
	 * })
	 */
	private $books;

	public function __construct() {
		$this->books = new ArrayCollection();
	}

	/////////////
	// Getters //
	/////////////

	public function getId(): ?int {
		return $this->id;
	}

	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return Book[]|Collection
	 */
	public function getBooks(): Collection {
		return $this->books;
	}

	/////////////
	// Setters //
	/////////////

	public function setName(string $name): self {
		$this->name = $name;
		return $this;
	}

	/////////
	// Add //
	/////////

	public function addBook(Book $book): self {
		return $this->oneToManyAdd($book);
	}

	////////////
	// Remove //
	////////////

	public function removeBook(Book $book): self {
		return $this->oneToManyRemove($book);
	}

}