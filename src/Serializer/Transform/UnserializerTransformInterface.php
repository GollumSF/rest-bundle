<?php
namespace GollumSF\RestBundle\Serializer\Transform;


interface UnserializerTransformInterface {
	
	/**
	 * @param $data
	 * @param string[] $groups
	 */
	public function unserializeTransform($data, array $groups): void;
	
}