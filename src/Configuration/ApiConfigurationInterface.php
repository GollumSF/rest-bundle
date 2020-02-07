<?php

namespace GollumSF\RestBundle\Configuration;

interface ApiConfigurationInterface {

	const DEFAULT_MAX_LIMIT_ITEM = 100;
	const DEFAULT_DEFAULT_LIMIT_ITEM = 25;
	const DEFAULT_ALWAYS_SERIALIZED_EXCEPTION = false;
	
	public function getMaxLimitItem(): int;
	public function getDefaultLimitItem(): int;
	public function isAlwaysSerializedException(): bool;
}