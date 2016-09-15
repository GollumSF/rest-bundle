# GollumSFRestBundle


## Installation:

AppKernel.php
```php
class AppKernel extends Kernel {
	
	public function registerBundles() {
		
		$bundles = [
			
			// [...] //
			
			// JMS Bundle
			new JMS\DiExtraBundle\JMSDiExtraBundle($this),
			new JMS\AopBundle\JMSAopBundle(),
			new JMS\SerializerBundle\JMSSerializerBundle(),
			
			// GollumSF Bundle
			new GollumSF\CoreBundle\GollumSFCoreBundle(),
			new GollumSF\RestBundle\GollumSFRestBundle(),
			
			// [...] // 
		}
	}
}
```

config.yml

```yml
gollum_sf_rest:
    format: [ 'json', 'xml' ]    # (optional) Output format availlable for serialization
    schemes: [ 'http', 'https' ] # (optional) Availlable shemes for rest url
    overrideUrlExtension: true   # (optional) Add extention on rest url (.json or .xml)
    defaultFormat: 'json'        # (optional) Default format if overrideUrlExtension is false
```
