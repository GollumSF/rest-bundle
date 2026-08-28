<?php
namespace GollumSF\RestBundle\Search;

use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ManagerRegistry;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerActionExtractorInterface;
use GollumSF\RestBundle\Model\ApiList;
use GollumSF\RestBundle\Model\ApiOrderCollection;
use GollumSF\RestBundle\Model\StaticArrayApiList;
use GollumSF\RestBundle\Repository\ApiFinderOrderRepositoryInterface;
use GollumSF\RestBundle\Repository\ApiFinderRepositoryInterface;
use GollumSF\RestBundle\Sort\ApiOrderResolverInterface;
use GollumSF\RestBundle\Sort\ApiSortContext;
use GollumSF\RestBundle\Traits\ManagerRegistryToManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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

	/** @var ApiOrderResolverInterface|null */
	private $apiOrderResolver;

	/** @var ControllerActionExtractorInterface|null */
	private $controllerActionExtractor;

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

	public function setApiOrderResolver(ApiOrderResolverInterface $apiOrderResolver): self {
		$this->apiOrderResolver = $apiOrderResolver;
		return $this;
	}

	public function setControllerActionExtractor(ControllerActionExtractorInterface $controllerActionExtractor): self {
		$this->controllerActionExtractor = $controllerActionExtractor;
		return $this;
	}

	protected function getMasterRequest(): Request {
		return $this->requestStack->getMainRequest();
	}

	public function apiFindBy(string $entityClass, ?\Closure $queryCallback = null): ApiList {

		$request   = $this->getMasterRequest();
		$limit     = (int)$request->query->get('limit', $this->apiConfiguration->getDefaultLimitItem());
		$page      = (int)$request->query->get('page' , 0);
		$order     = $request->query->get('order');
		$direction = $request->query->get('direction');

		$maxtLimitItem = $this->apiConfiguration->getMaxLimitItem();
		if ($maxtLimitItem && $limit >  $maxtLimitItem) {
			$limit = $maxtLimitItem;
		}

		if ($direction !== null && function_exists('trigger_deprecation')) {
			trigger_deprecation('gollumsf/rest-bundle', '5.0', 'The "direction" query parameter is deprecated, use "order=field:direction" instead.');
		}

		$orders = $this->resolveOrders($entityClass, $order, $direction);

		/** @var ApiFinderRepositoryInterface $repository */
		$repository = $this->getEntityRepositoryForClass($entityClass);
		if (!$repository) {
			throw new \LogicException(sprintf('Repository not found for class %s', $entityClass));
		}
		if (!($repository instanceof ApiFinderRepositoryInterface)) {
			throw new \LogicException(sprintf('Repository of class %s must implement ApiFinderRepositoryInterface or extends ApiFinderRepository', $entityClass));
		}

		try {
			if ($repository instanceof ApiFinderOrderRepositoryInterface || method_exists($repository, 'apiFindByOrder')) {
				return $repository->apiFindByOrder($limit, $page, $orders, $queryCallback);
			}
			return $this->apiFindByLegacy($repository, $limit, $page, $orders, $queryCallback);
		} catch (QueryException $e) {
			if ($this->logger) {
				$this->logger->warning(sprintf('Error on execute ApiSearch: %s', $e->getMessage()));
			}
			throw new BadRequestHttpException('Bad parameter');
		}
	}

	private function resolveOrders(string $entityClass, ?string $order, ?string $direction): ApiOrderCollection {

		if (!$this->apiOrderResolver) {
			return new ApiOrderCollection();
		}

		$controllerAction = $this->controllerActionExtractor
			? $this->controllerActionExtractor->extractFromRequest($this->getMasterRequest())
			: null
		;

		return $this->apiOrderResolver->resolve(
			new ApiSortContext(
				$entityClass,
				$controllerAction ? $controllerAction->getControllerClass() : null,
				$controllerAction ? $controllerAction->getActionMethod() : null
			),
			$order,
			$direction
		);
	}

	/**
	 * A repository implementing apiFindBy() by hand only understands one key.
	 */
	private function apiFindByLegacy(
		ApiFinderRepositoryInterface $repository,
		int $limit,
		int $page,
		ApiOrderCollection $orders,
		?\Closure $queryCallback
	): ApiList {
		$first = $orders->all()[0] ?? null;
		return $repository->apiFindBy(
			$limit,
			$page,
			$first ? $first->getPath() : null,
			$first ? $first->getDirection()->value : null,
			$queryCallback
		);
	}

	public function staticArrayList(array $data, ?\Closure $sortCallback = null, $globalSort = false): StaticArrayApiList {
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
