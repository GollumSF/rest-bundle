# GollumSFRestBundle


## Installation:

AppKernel.php
```php
class AppKernel extends Kernel {
	
	public function registerBundles() {
		
		$bundles = [
			
			// [...] //
			
			// GollumSF Bundle
			new GollumSF\RestBundle\GollumSFRestBundle(),
			
			// [...] // 
		}
	}
}
```

config.yml

```yml
gollum_sf_rest:
```
