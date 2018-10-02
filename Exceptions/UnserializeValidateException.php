<?php
namespace Serializer\Exceptions;

use Symfony\Component\Validator\ConstraintViolationList;

class UnserializeValidateException extends \Exception {
	
	/**
	 * @var ConstraintViolationList
	 */
	private $constraints;
	
	public function __construct(ConstraintViolationList $constraints) {
		parent::__construct('', 0, null);
		$this->constraints = $constraints;
	}
	
	public function getConstraints(): ConstraintViolationList {
		return $this->constraints;
	}
	
}