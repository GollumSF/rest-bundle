<?php
namespace GollumSF\RestBundle\Model;

use GollumSF\RestBundle\Repository\ApiFinderRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StaticArrayApiList extends ApiList {

	/** @var Request */
	private $request;
	
	public function __construct(array $data, Request $request) {
		parent::__construct($data, count($data));
		$this->request = $request;
	}
	
	/////////////
	// Getters //
	/////////////
	
	/**
	 * @return array
	 */
	public function getData(): array {

		$limit     = (int)$this->request->get('limit', ApiFinderRepositoryInterface::DEFAULT_LIMIT_ITEM);
		$page      = (int)$this->request->get('page' , 0);
		$order     = $this->request->get('order');
		$direction = strtoupper($this->request->get('direction', ApiFinderRepositoryInterface::DIRECTION_ASC));
		if (!in_array($direction, [ ApiFinderRepositoryInterface::DIRECTION_ASC, ApiFinderRepositoryInterface::DIRECTION_DESC ])) {
			$direction = null;
		}
		
		$data = parent::getData();
		if ($order) {
			try {
			uasort($data, function ($a, $b) use ($order, $direction) {

				$valueA = null;
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
				
				$valueB = null;
				$method = 'get'.ucfirst($order);
				if (!method_exists($b, $method)) {
					$method = 'has'.ucfirst($order);
					if (!method_exists($b, $method)) {
						$method = 'is'.ucfirst($order);
						if (!method_exists($b, $method)) {
							throw new \LogicException(sprintf('Method get, has, is nor exist for fiel %s', $order));
						}
					}
				}
				if (method_exists($b, $method)) {
					$valueB = $b->$method();
				}
				
				if ($valueA === null || $valueB === null || $valueA === $valueB) {
					return 0;
				}
				if ($direction === ApiFinderRepositoryInterface::DIRECTION_DESC) {
					return ($valueA > $valueB) ? -1 : 1;
				}
				return ($valueA < $valueB) ? -1 : 1;
			});
			} catch (\Throwable $e) {
				throw new BadRequestHttpException('Bad parameter on sort');
			}
		}
		
		return array_slice($data, $page*$limit, $limit);
	}
	
}