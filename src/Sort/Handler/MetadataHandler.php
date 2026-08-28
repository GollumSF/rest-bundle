<?php
namespace GollumSF\RestBundle\Sort\Handler;

use GollumSF\RestBundle\Metadata\Sort\MetadataSortManagerInterface;
use GollumSF\RestBundle\Model\ApiOrder;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Sort\ApiSortContext;
use GollumSF\RestBundle\Sort\ApiSorterInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Resolves a key against the #[ApiSortable] declarations of the entity and the action.
 *
 * Declaring at least one sortable turns the whitelist on: any other key is rejected.
 * Declaring none leaves the key to the next handler, which keeps everything open.
 */
class MetadataHandler implements HandlerInterface {

	/** @var MetadataSortManagerInterface */
	private $metadataSortManager;

	/** @var ContainerInterface */
	private $sorters;

	public function __construct(
		MetadataSortManagerInterface $metadataSortManager,
		ContainerInterface $sorters
	) {
		$this->metadataSortManager = $metadataSortManager;
		$this->sorters = $sorters;
	}

	public function getOrder(ApiSortContext $context, string $key, ?Direction $direction): ?ApiOrder {

		$metadata = $this->metadataSortManager->getMetadata(
			$context->getEntityClass(),
			$context->getController(),
			$context->getAction()
		);

		if ($metadata->isEmpty()) {
			return null;
		}

		$sortable = $metadata->get($key);
		if (!$sortable) {
			throw new BadRequestHttpException(sprintf(
				'Order "%s" is not sortable, expected one of: %s',
				$key,
				implode(', ', $metadata->getKeys())
			));
		}

		return new ApiOrder(
			$key,
			$direction ?? $sortable->getDirection() ?? Direction::ASC,
			$sortable->getPath(),
			$this->getSorter($sortable->getSorter())
		);
	}

	private function getSorter(?string $class): ?ApiSorterInterface {
		if (!$class) {
			return null;
		}
		if (!$this->sorters->has($class)) {
			throw new \LogicException(sprintf(
				'Sorter "%s" not found, did you tag it with "%s"?',
				$class,
				ApiSorterInterface::TAG
			));
		}
		return $this->sorters->get($class);
	}
}
