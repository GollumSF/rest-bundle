<?php
namespace GollumSF\RestBundle\Annotation;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Damien Duboeuf <smeagolworms4@gmail.com>
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Rest {
	
	public $code = Response::HTTP_OK;
	
}