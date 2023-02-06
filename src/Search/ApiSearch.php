<?php
namespace GollumSF\RestBundle\Search;

use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ManagerRegistry;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\Model\ApiList;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Model\StaticArrayApiList;
use GollumSF\RestBundle\Repository\ApiFinderRepositoryInterface;
use GollumSF\RestBundle\Traits\ManagerRegistryToManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Kernel;

class ApiSearch implements ApiSearchInterface {

	use ManagerRegistryToManager;

	/** @var ManagerRegistry  */
	private $managerRegistry;

	/** @var RequestStack */
	private $requestStack;

	/** @var ApiConfigurationInterface */
	private $apiConfiguration;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		RequestStack $requestStack,
		LoggerInterface $logger,
		ApiConfigurationInterface $apiConfiguration
	) {
		$this->requestStack = $requestStack;
		$this->logger = $logger;
		$this->apiConfiguration = $apiConfiguration;
	}

	public function setManagerRegistry(ManagerRegistry $managerRegistry): self {
		$this->managerRegistry = $managerRegistry;
		return $this;
	}

	protected function getMasterRequest(): Request {
		return version_compare(Kernel::VERSION, '6.0.0', '<') ? $this->requestStack->getMasterRequest() : $this->requestStack->getMainRequest();
	}

	public function apiFindBy(string $entityClass, \Closure $queryCallback = null): ApiList {

		$request   = $this->getMasterRequest();
		$limit     = (int)$request->get('limit', $this->apiConfiguration->getDefaultLimitItem());
		$page      = (int)$request->get('page' , 0);
		$order     = $request->get('order');
		$direction = strtoupper($request->get('direction', ''));

		$maxtLimitItem = $this->apiConfiguration->getMaxLimitItem();
		if ($maxtLimitItem && $limit >  $maxtLimitItem) {
			$limit = $maxtLimitItem;
		}

		if (!Direction::isValid($direction)) {
			$direction = null;
		}

		/** @var ApiFinderRepositoryInterface $repository */
		$repository = $this->getEntityRepositoryForClass($entityClass);
		if (!$repository) {
			throw new \LogicException(sprintf('Repository not found for class %s', $entityClass));
		}
		if (!($repository instanceof ApiFinderRepositoryInterface)) {
			throw new \LogicException(sprintf('Repository of class %s must implement ApiFinderRepositoryInterface or extends ApiFinderRepository', $entityClass));
		}

		try {
			return $repository->apiFindBy($limit, $page, $order, $direction, $queryCallback);
		} catch (QueryException $e) {
			if ($this->logger) {
				$this->logger->warning(sprintf('Error on execute ApiSearch: %s', $e->getMessage()));
			}
			throw new BadRequestHttpException('Bad parameter');
		}
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
