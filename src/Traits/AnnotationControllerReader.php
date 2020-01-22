<?php
namespace GollumSF\RestBundle\Traits;

use Doctrine\Common\Annotations\Reader;
use GollumSF\RestBundle\Reflection\ControllerActionExtractorInterface;
use Symfony\Component\HttpFoundation\Request;

trait AnnotationControllerReader {

	/** @var ControllerActionExtractorInterface */
	protected $controllerActionExtractor;

	/** @var Reader */
	protected $reader;

	/** @required */
	public function setControllerActionExtractor(ControllerActionExtractorInterface $controllerActionExtractor) {
		$this->controllerActionExtractor = $controllerActionExtractor;
	}

	/** @required */
	public function setReader(Reader $reader) {
		$this->reader = $reader;
	}
	
	protected function getAnnotation(Request $request, string $annotationClass) {

		$controllerAction = $this->controllerActionExtractor->extractFromString(
			$request->attributes->get('_controller', '')
		);
		
		if ($controllerAction) {
			$rClass = new \ReflectionClass($controllerAction->getControllerClass());
			return $this->reader->getMethodAnnotation ($rClass->getMethod($controllerAction->getAction()), $annotationClass);
		}
		return null;
	}
}