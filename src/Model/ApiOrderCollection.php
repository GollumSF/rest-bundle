<?php
namespace GollumSF\RestBundle\Model;

/**
 * @implements \IteratorAggregate<int, ApiOrder>
 */
class ApiOrderCollection implements \IteratorAggregate, \Countable {

	/** @var ApiOrder[] */
	private $orders;

	/**
	 * @param ApiOrder[] $orders
	 */
	public function __construct(array $orders = []) {
		$this->orders = array_values($orders);
	}

	public function add(ApiOrder $order): self {
		$this->orders[] = $order;
		return $this;
	}

	/**
	 * @return ApiOrder[]
	 */
	public function all(): array {
		return $this->orders;
	}

	public function isEmpty(): bool {
		return !$this->orders;
	}

	public function count(): int {
		return count($this->orders);
	}

	public function getIterator(): \Traversable {
		return new \ArrayIterator($this->orders);
	}
}
