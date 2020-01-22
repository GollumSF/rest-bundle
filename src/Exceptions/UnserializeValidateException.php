<?php
namespace GollumSF\RestBundle\Exceptions;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class UnserializeValidateException extends \Exception {
	
	/**
	 * @var ConstraintViolationListInterface
	 */
	private $constraints;
	
	public function __construct(ConstraintViolationListInterface $constraints) {
		parent::__construct('', 0, null);
		$this->constraints = $constraints;
	}
	
	public function getConstraints(): ConstraintViolationListInterface {
		return $this->constraints;
	}
	
}