<?php
namespace GollumSF\RestBundle\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\Persistence\Proxy;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @property ManagerRegistry managerRegistry
 */
trait ManagerRegistryToManager {
	protected function getEntityClass($entityOrClass): string {
		$class = $entityOrClass;
		if (!is_string($entityOrClass)) {
			$class = get_class($entityOrClass);
		}
		if (is_subclass_of($class, Proxy::class)) {
			$class = get_parent_class($class);
		}
		return $class;
	}

	protected function getEntityManagerForClass($entityOrClass): ?ObjectManager {
		if (!isset($this->managerRegistry) || !$this->managerRegistry) {
			return null;
		}
		return $this->managerRegistry->getManagerForClass($this->getEntityClass($entityOrClass));
	}

	protected function isEntity($entityOrClass): bool {
		if (!isset($this->managerRegistry) || !$this->managerRegistry) {
			return false;
		}
		$em = $this->getEntityManagerForClass($entityOrClass);
		return $em && !$em->getMetadataFactory()->isTransient($this->getEntityClass($entityOrClass));
	}

	protected function getEntityRepositoryForClass($entityOrClass): ?ObjectRepository {
		if (!isset($this->managerRegistry) || !$this->managerRegistry) {
			return null;
		}
		$em = $this->getEntityManagerForClass($entityOrClass);
		return $em ? $em->getRepository($this->getEntityClass($entityOrClass)) : null;
	}

}