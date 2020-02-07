<?php
namespace Test\GollumSF\RestBundle\ProjectTest\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Test\GollumSF\RestBundle\ProjectTest\Repository\BookRepository;

/**
 * @ORM\Entity(repositoryClass=BookRepository::class)
 */
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

	 * @Assert\NotBlank(groups={"book_post", "book_put", "book_patch_title"})

	 * @var string
	 */
	private $title;

	/**
	 * @ORM\Column(type="text")
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