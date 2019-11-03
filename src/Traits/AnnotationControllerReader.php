<?php
namespace GollumSF\RestBundle\Traits;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpFoundation\Request;

trait AnnotationControllerReader {
	
	protected $reader;
	
	/**
	 * @required
	 */
	public function setReader(Reader $reader) {
		$this->reader = $reader;
	}
	
	protected function getAnnotation(Request $request, string $annotationClass) {
		$explode = explode ('::', $request->attributes->get('_controller', ''));
		if (count ($explode) === 1) {
			$explode[1] = '__invoke';
		}
		if (count ($explode) === 2 && class_exists($explode[0])) {
			$controllerClass  = $explode[0];
			$controllerAction = $explode[1];
			$rClass = new \ReflectionClass($controllerClass);
			
			return $this->reader->getMethodAnnotation ($rClass->getMethod($controllerAction), $annotationClass);
		}
		return null;
	}
}