<img src="https://raw.githubusercontent.com/apie-lib/apie-lib-monorepo/main/docs/apie-logo.svg" width="100px" align="left" />
<h1>ServiceProvider generator</h1>






 [![Latest Stable Version](https://poser.pugx.org/apie/service-provider-generator/v)](https://packagist.org/packages/apie/service-provider-generator) [![Total Downloads](https://poser.pugx.org/apie/service-provider-generator/downloads)](https://packagist.org/packages/apie/service-provider-generator) [![Latest Unstable Version](https://poser.pugx.org/apie/service-provider-generator/v/unstable)](https://packagist.org/packages/apie/service-provider-generator) [![License](https://poser.pugx.org/apie/service-provider-generator/license)](https://packagist.org/packages/apie/service-provider-generator) [![PHP Version Require](https://poser.pugx.org/apie/service-provider-generator/require/php)](https://packagist.org/packages/apie/service-provider-generator) [![Code coverage](https://raw.githubusercontent.com/apie-lib/service-provider-generator/main/coverage_badge.svg)](https://apie-lib.github.io/coverage/service-provider-generator/index.html)  

[![PHP Composer](https://github.com/apie-lib/service-provider-generator/actions/workflows/php.yml/badge.svg?event=push)](https://github.com/apie-lib/service-provider-generator/actions/workflows/php.yml)

This package is part of the [Apie](https://github.com/apie-lib) library.

## Documentation
One issue with writing framework agnostic code is that most frameworks have different ways to register classes in their service container.
- The Symfony framework works with configuration files, attributes and autowiring.
- Laravel works with autowiring everything or writing PHP code in a ServiceProvider how to instantiate a specific service.

If we want our code to be framework agnostic we need a way to make sure we do not need maintain 2 'service container registries'.

That's where this library comes in. We use the Symfony yaml configuration files as basis and let it generate a ServiceProvider class that Laravel can use. That way we can make our library work effortlessly

### Code usage
The class only creates a string with the source code, you have to manually store it in a file (recommended) or use the 'evil' eval() method.

```php
<?php
use Apie\ServiceProviderGenerator\ServiceProviderGenerator;
$generator = new ServiceProviderGenerator();
file_put_contents('/src/ExampleServiceProvider.php', $generator->generateClass(App\ExampleServiceProvider::class, 'resources/config/example-service.yaml'));
```

### Supported Symfony dependency injection features:
Here is a list of everything supported. Not much is supported, because a transition is not trivial
or Laravel does not support it without hacks. For example private services do not exist in Laravel.
Also some of these features are considered bad practices if you use them outside your application in
your package, so are highly likely not to be implemented.

| Feature | Support |
| ------- | ------- |
| _defaults | No |
| autowire | No |
| autoconfigure | No |
| reading PHP 8 attributes | No |
| deprecated | Yes |
| resource + exclude | No |
| arguments by index | Yes |
| arguments by name | >= PHP8 code |
| service references | Yes, not within array constants |
| constant strings starting with '@' | Yes |
| reading parameters | Mapped as Laravel config() |
| reading environment variables | Mapped as laravel env() |
| environment variable parsers | No |
| !closure | No |
| public/private services | NA |
| tags | Only simple tags |
| from_callable | No |
| aliasing | Yes |
| anonymous services | No |
| method calls | Yes |
| !returns_clone | No |
| expression language | No |
| other imports | No |
| Factory Service | Yes |
| Static Factory | Yes |
| Invokable Factory | Yes |
| Property injection | No |
| Lazy services | No |
| Optional arguments | Mapped as app()->bound() |
| parent definitions | No |
| !service_closure | No |
| decorators | No |
| optional decorator | No |
| service subscriber | No |
| !service_locator | No |
| @service_container | No |
| !tagged_locator | Basic support |
| !tagged_service | Basic support |
| synthetic | Yes |
| shared | Yes |






