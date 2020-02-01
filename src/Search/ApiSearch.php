<?php
namespace GollumSF\RestBundle\Search;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\Model\ApiList;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Model\StaticArrayApiList;
use GollumSF\RestBundle\Repository\ApiFinderRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ApiSearch implements ApiSearchInterface {
	
	/** @var ManagerRegistry  */
	private $doctrine;

	/** @var RequestStack */
	private $requestStack;

	/** @var ApiConfigurationInterface */
	private $apiConfiguration;
	
	public function __construct(
		ManagerRegistry $doctrine,
		RequestStack $requestStack,
		ApiConfigurationInterface $apiConfiguration
	) {
		$this->doctrine = $doctrine;
		$this->requestStack = $requestStack;
		$this->apiConfiguration = $apiConfiguration;
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
		$limit     = (int)$request->get('limit', $this->apiConfiguration->getDefaultLimitItem());
		$page      = (int)$request->get('page' , 0);
		$order     = $request->get('order');
		$direction = strtoupper($request->get('direction'));
		
		$maxtLimitItem = $this->apiConfiguration->getMaxLimitItem();
		if ($maxtLimitItem && $limit >  $maxtLimitItem) {
			$limit = $maxtLimitItem;
		}
		
		if (!Direction::isValid($direction)) {
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
	
	public function staticArrayList(array $data, \Closure $sortCallback = null, $globalSort = false): StaticArrayApiList {
		$request   = $this->getMasterRequest();
		$arrayList = new StaticArrayApiList($data, $request);
		$arrayList->setMaxLimitItem($this->apiConfiguration->getMaxLimitItem());
		$arrayList->setDefaultLimitItem($this->apiConfiguration->getDefaultLimitItem());

		if ($sortCallback) {
			if ($globalSort) {
				$arrayList->setSortPropertiesCallback($sortCallback);
			} else {
				$arrayList->setSortPropertiesCallback($sortCallback);
			}
		}
		
		return $arrayList;
	}
}