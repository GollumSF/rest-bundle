<?php
namespace Test\GollumSF\RestBundle\ProjectTest\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use GollumSF\EntityRelationSetter\OneToManySetter;
use Symfony\Component\Serializer\Annotation\Groups;
use Test\GollumSF\RestBundle\ProjectTest\Repository\CategoryRepository;

/**
 * @ORM\Entity(repositoryClass=CategoryRepository::class)
 */
class Category {

	use OneToManySetter;

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 *
	 * @Groups({
	 * 	"book_get"
	 * })
	 *
	 * @var int
	 */
	private $id;

	/**
	 * @ORM\OneToMany(targetEntity=Book::class, mappedBy="category")
	 * @var Book[]|ArrayCollection
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