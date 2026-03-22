<?php
namespace GollumSF\RestBundle\Model;

use Symfony\Component\Serializer\Annotation\Groups;

class ApiList {

	#[Groups(['get'])]
	private array $data;

	#[Groups(['get'])]
	private int $total;

	public function __construct(array $data, int $total) {
		$this->data  = $data;
		$this->total = $total;
	}

	public function getData(): array {
		return $this->data;
	}

	public function getTotal(): int {
		return $this->total;
	}
}
