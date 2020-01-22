<?php
namespace GollumSF\RestBundle\Serializer\Transform;


interface SerializerTransformInterface {
	
	/**
	 * @param $data
	 * @param string[] $groups
	 * @return string
	 */
	public function serializeTransform($data, array $groups);
	
}