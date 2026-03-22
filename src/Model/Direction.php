<?php
namespace GollumSF\RestBundle\Model;

enum Direction: string {
	case ASC = 'ASC';
	case DESC = 'DESC';

	public static function isValid(string $value): bool {
		return self::tryFrom($value) !== null;
	}
}
