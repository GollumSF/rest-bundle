<?php
namespace GollumSF\RestBundle\Model;

use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
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
				if ($a !== null) {
					$method = 'get'.ucfirst($order);
					if (!method_exists($a, $method)) {
						$method = 'has'.ucfirst($order);
						if (!method_exists($a, $method)) {
							$method = 'is'.ucfirst($order);
						}
					}
					if (method_exists($a, $method)) {
						$valueA = $a->$method();
					}
				}
				if ($b !== null) {
					$method = 'get'.ucfirst($order);
					if (!method_exists($b, $method)) {
						$method = 'has'.ucfirst($order);
						if (!method_exists($b, $method)) {
							$method = 'is'.ucfirst($order);
						}
					}
					if (method_exists($b, $method)) {
						$valueB = $b->$method();
					}
				}
			} else {
				$valueA = $a;
				$valueB = $b;
			}
			$result = ($this->sortPropertiesCallback)($valueA, $valueB, $a, $b, $order);
			return $direction === Direction::DESC ? -$result : $result;
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
	
	/////////////
	// Getters //
	/////////////
	
	/**
	 * @return array
	 */
	public function getData(): array {

		$limit     = (int)$this->request->get('limit', $this->defaultLimitItem);
		$page      = (int)$this->request->get('page' , 0);
		$order     = $this->request->get('order');
		$direction = strtoupper($this->request->get('direction'));
		
		if ($this->maxLimitItem && $limit > $this->maxLimitItem) {
			$limit = $this->maxLimitItem;
		}

		$order = $order !== null ? preg_replace("/[^(a-zA-Z0-9_)]/", '', $order): null;
		
		if (!Direction::isValid($direction)) {
			$direction = null;
		}
		
		$data = parent::getData();
		if ($order || $direction) {
			try {
				uasort($data, function ($a, $b) use ($order, $direction) {
					return ($this->sortGlobalCallback)($a, $b, $order, $direction);
				});
			} catch (\Throwable $e) {
				throw new BadRequestHttpException('Bad parameter on sort');
			}
		}
		
		return array_slice($data, $page*$limit, $limit);
	}
	
}