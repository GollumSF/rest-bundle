<?php
namespace GollumSF\RestBundle\Model;

use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\Sort\ApiOrderParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StaticArrayApiList extends ApiList {

	/** @var Request */
	private $request;

	/** @var int */
	protected $maxLimitItem = ApiConfigurationInterface::DEFAULT_MAX_LIMIT_ITEM;

	/** @var int */
	protected $defaultLimitItem = ApiConfigurationInterface::DEFAULT_DEFAULT_LIMIT_ITEM;

	/** @var \Closure */
	protected $sortPropertiesCallback;

	/** @var \Closure */
	protected $sortGlobalCallback;

	/** @var ApiOrderCollection|null */
	private $orders;

	public function __construct(array $data, Request $request) {
		parent::__construct($data, count($data));

		$this->request = $request;

		$this->sortPropertiesCallback = function ($valueA, $valueB, $objA, $objB, $order) {
			if ($valueA === null && $valueB) {
				return -1;
			}
			if ($valueB === null && $valueA) {
				return 1;
			}
			if ($valueA === null && $valueB === null ) {
				if ($objA === null && $objB) {
					return -1;
				}
				if ($objA && $objB === null) {
					return 1;
				}
				return 0;
			}

			if ($valueA === $valueB) {
				return 0;
			}
			return ($valueA < $valueB) ? -1 : 1;
		};

		$this->sortGlobalCallback = function ($a, $b, $order, $direction) {
			$valueA = null;
			$valueB = null;

			if ($order) {
				$valueA = $this->resolveSortValue($a, $order);
				$valueB = $this->resolveSortValue($b, $order);
			} else {
				$valueA = $a;
				$valueB = $b;
			}
			$result = ($this->sortPropertiesCallback)($valueA, $valueB, $a, $b, $order);
			return $direction === Direction::DESC->value ? -$result : $result;
		};
	}

	/////////////
	// Setters //
	/////////////

	public function setMaxLimitItem(int $maxLimitItem): self {
		$this->maxLimitItem = $maxLimitItem;
		return $this;
	}

	public function setDefaultLimitItem(int $defaultLimitItem): self {
		$this->defaultLimitItem = $defaultLimitItem;
		return $this;
	}

	public function setSortPropertiesCallback(\Closure $sortPropertiesCallback): self {
		$this->sortPropertiesCallback = $sortPropertiesCallback;
		return $this;
	}

	public function setSortGlobalCallback(\Closure $sortGlobalCallback): self {
		$this->sortGlobalCallback = $sortGlobalCallback;
		return $this;
	}

	/**
	 * Orderings already resolved from the request. Without them the list falls back on
	 * reading `order` and `direction` itself, which only understands a single key.
	 */
	public function setOrders(ApiOrderCollection $orders): self {
		$this->orders = $orders;
		return $this;
	}

	/**
	 * Walks a property path, the dots crossing relations, through get/has/is accessors.
	 */
	private function resolveSortValue($object, string $path) {
		$value = $object;
		foreach (explode('.', $path) as $segment) {
			if (!is_object($value) || $segment === '') {
				return null;
			}
			$method = null;
			foreach ([ 'get', 'has', 'is' ] as $prefix) {
				if (method_exists($value, $prefix.ucfirst($segment))) {
					$method = $prefix.ucfirst($segment);
					break;
				}
			}
			if (!$method) {
				return null;
			}
			$value = $value->$method();
		}
		return $value;
	}

	/////////////
	// Getters //
	/////////////

	/**
	 * @return array
	 */
	public function getData(): array {

		$limit = (int)$this->request->query->get('limit', $this->defaultLimitItem);
		$page  = (int)$this->request->query->get('page' , 0);

		if ($this->maxLimitItem && $limit > $this->maxLimitItem) {
			$limit = $this->maxLimitItem;
		}

		$data = parent::getData();
		$orders = $this->orders ?? $this->parseOrders();

		if (!$orders->isEmpty()) {
			$this->sortByOrders($data, $orders);
		} else {
			// No key, but a direction on its own still sorts the raw values.
			$direction = ApiOrderParser::parseDirection($this->request->query->get('direction'));
			if ($direction) {
				$this->uasortData($data, function ($a, $b) use ($direction) {
					return ($this->sortGlobalCallback)($a, $b, null, $direction->value);
				});
			}
		}

		return array_slice($data, $page*$limit, $limit);
	}

	/**
	 * Same `order` syntax as a Doctrine backed collection, so that both answer the same
	 * URL the same way. Going through ApiSearch additionally applies the #[ApiSortable]
	 * whitelist; built by hand, every property path is accepted.
	 */
	private function parseOrders(): ApiOrderCollection {

		$orders = new ApiOrderCollection();

		foreach (ApiOrderParser::parse(
			$this->request->query->get('order'),
			$this->request->query->get('direction')
		) as [ $key, $direction ]) {
			$path = $this->sanitizePath($key);
			if ($path === '') {
				continue;
			}
			$orders->add(new ApiOrder($key, $direction ?? Direction::ASC, $path));
		}

		return $orders;
	}

	/**
	 * Each segment keeps the character set the list has always accepted, the dots
	 * separating them: `my-prop` still resolves to `myProp` as before.
	 */
	private function sanitizePath(string $key): string {
		$segments = [];
		foreach (explode('.', $key) as $segment) {
			$segment = preg_replace("/[^(a-zA-Z0-9_)]/", '', $segment);
			if ($segment !== '') {
				$segments[] = $segment;
			}
		}
		return implode('.', $segments);
	}

	/**
	 * Keys are applied in order: the first one deciding wins, the next ones break ties.
	 */
	private function sortByOrders(array &$data, ApiOrderCollection $orders): void {
		$this->uasortData($data, function ($a, $b) use ($orders) {
			foreach ($orders as $order) {
				if (!$order->getPath()) {
					continue;
				}
				$result = ($this->sortGlobalCallback)($a, $b, $order->getPath(), $order->getDirection()->value);
				if ($result !== 0) {
					return $result;
				}
			}
			return 0;
		});
	}

	private function uasortData(array &$data, \Closure $comparator): void {
		try {
			uasort($data, $comparator);
		} catch (\Throwable $e) {
			throw new BadRequestHttpException('Bad parameter on sort');
		}
	}

}
