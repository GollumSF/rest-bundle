<?php
namespace GollumSF\RestBundle\Sort;

/**
 * What a sort handler needs to know about the listing being ordered.
 */
class ApiSortContext {

	/** @var string */
	private $entityClass;

	/** @var string|null */
	private $controller;

	/** @var string|null */
	private $action;

	public function __construct(string $entityClass, ?string $controller = null, ?string $action = null) {
		$this->entityClass = $entityClass;
		$this->controller = $controller;
		$this->action = $action;
	}

	public function getEntityClass(): string {
		return $this->entityClass;
	}

	public function getController(): ?string {
		return $this->controller;
	}

	public function getAction(): ?string {
		return $this->action;
	}
}
