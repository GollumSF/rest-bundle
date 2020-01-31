<?php
namespace GollumSF\RestBundle\Search;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use GollumSF\RestBundle\Model\ApiList;
use GollumSF\RestBundle\Repository\ApiFinderRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ApiSearch implements ApiSearchInterface {
	
	/** @var ManagerRegistry  */
	private $doctrine;
	
	/** @var RequestStack */
	private $requestStack;
	
	public function __construct(
		ManagerRegistry $doctrine,
		RequestStack $requestStack
	) {
		$this->doctrine = $doctrine;
		$this->requestStack = $requestStack;
	}

	protected function getMasterRequest(): Request {
		return $this->requestStack->getMasterRequest();
	}

	/**
	 * @return ObjectRepository
	 */
	protected function getRepository(string $entityClass) {
		$manager = $this->doctrine->getManagerForClass($entityClass);
		return $manager ? $manager->getRepository($entityClass) : null;
	}
	
	public function apiFindBy(string $entityClass, \Closure $queryCallback = null): ApiList {

		$request   = $this->getMasterRequest();
		$limit     = (int)$request->get('limit', ApiFinderRepositoryInterface::DEFAULT_LIMIT_ITEM);
		$page      = (int)$request->get('page' , 0);
		$order     = $request->get('order');
		$direction = strtoupper($request->get('direction'));
		if (!in_array($direction, [ ApiFinderRepositoryInterface::DIRECTION_ASC, ApiFinderRepositoryInterface::DIRECTION_DESC ])) {
			$direction = null;
		}

		/** @var ApiFinderRepositoryInterface $repository */
		$repository = $this->getRepository($entityClass);
		if (!$repository) {
			throw new \LogicException(sprintf('Repository not found for class %s', $entityClass));
		}
		if (!($repository instanceof ApiFinderRepositoryInterface)) {
			throw new \LogicException(sprintf('Repository of class %s must implement ApiFinderRepositoryInterface or extends ApiFinderRepository', $entityClass));
		}
		return $repository->apiFindBy($limit, $page, $order, $direction, $queryCallback);
	}
}