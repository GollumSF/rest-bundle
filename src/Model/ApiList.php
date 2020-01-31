<?php
namespace GollumSF\RestBundle\Model;

use Symfony\Component\Serializer\Annotation\Groups;

class ApiList {
	
	/**
	 * @Groups("get")
	 * 
	 * @var array 
	 */
	private $data;
	
	/**
	 * @Groups("get")
	 * 
	 * @var int 
	 */
	private $total;
	
	public function __construct(array $data, int $total) {
		$this->data  = $data;
		$this->total = $total;
	}
	
	
	/////////////
	// Getters //
	/////////////
	
	/**
	 * @return array
	 */
	public function getData(): array {
		return $this->data;
	}
	
	/**
	 * @return number
	 */
	public function getTotal(): int {
		return $this->total;
	}
	
}