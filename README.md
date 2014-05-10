Phalcon Bootstrap Component
===========================

Bootstrap is a small code that does all the initialization and configuration job and then spins up the bigger code,
your application. Bootstrap component designed to be:

- **Modular**: routes, database connections, translations and other configuration can be stored in separate files,
  called "modules", and loaded as necessary. Modules can be shared between different applications, for example your
  MVC application, REST or Ajax backend MicroMVC instance and console scripts.
- **Environment aware**: you can specify which environment the module should be executed under.
- Convenient and handy

Bootstrap Environment
---------------------

Inside the bootstrap environment is implemented with the ```Phalcon\Bootstrap\Environment``` class.

When creating environment you can either specify environment as a string or as a function,
which is called "environment detector".

Environment class provides several pre-defined environments you can choose from:

- **Environment::DEVELOPMENT** - local development environment, not stable. This is the **default** environment.
- **Environment::TEST** - testing environment, somewhat stable.
- **Environment::STAGING** - staging or pre-release environment, stable.
- **Environment::PRODUCTION** - production or live environment.

Environment detector function does not take any arguments and must return a string indicating the current environment:

```php
use Phalcon\Bootstrap\Environment;

$environment = new Environment(function() {
    return isset($_SERVER['PHALCON_ENV']) ? $_SERVER['PHALCON_ENV'] : Environment::DEVELOPMENT;
});
```

Note: bootstrap will throw a ```\RuntimeException``` if detector function returns result other then a string or throws
an exception inside the function.

Additionally, environment class provides a single function ```Environment::is($environment)``` that compares current environment
with the one specified in the $environment argument. The comparison is *case-insensitive*.

Environment class also implements "object-to-string" casting, which allows to compare environments directly with strings.
Note that this comparison is *case-sensitive*.

```php
use Phalcon\Bootstrap\Environment;

$environment = new Environment('test');

var_dump( $environment == 'test' );   // TRUE
var_dump( $environment->is('TEST') ); // TRUE
var_dump( $environment == 'TEST' );   // FALSE
var_dump( $environment == 'foo' );    // FALSE
```

Bootstrap Container
-------------------

Bootstrap container is a used to pass data between modules. Container is implemented with the ```Phalcon\Bootstrap\Container```
class. Container class also implements ```Phalcon\InjectionAwareInterface``` and provides ```Container::setDi()``` function to set Phalcon
Di object and ```Container::getDi()``` function to retrieve it from the container instance.

You work with data inside the container as with object properties:

```php
use Phalcon\Bootstrap\Environment;

$container = new Container();

$container->a = 'foo';            // set the property "a" to the container
echo $container->a;               // outputs "foo"
var_dump( isset($container->a) ); // check if "a" is set to the container
unset($container->a);             // unset "a"

$container->merge(array(
    'b' => true,
    'c' => 123
));                               // merge an array of data with the container.
                                  // Existing properties will be replaced.
```

Note: "di" is a property name reserved for Phalcon Di. If you try to specify its value other than an instance of
```Phalcon\DiInterface``` then container will throw an ```\InvalidArgumentException```.

Note: you can specify initial set of variables as an array passed to the container constructor. Variable names are
always *case-sensitive*.

Bootstrap Module
----------------

Module is an actual working unit executed during the bootstrapping process. The module can be specified in two ways:

- As a function; or
- As an array containing module specification.

The first way is quicker and easier, but it has some limitations and also a bit slower than the second way, because reflection
is used to resolve module's function arguments.

Module function arguments are resolved automatically in both cases. This allows to inject container variables, Phalcon Di
services, or container and environment themselves into the module.

Note: any result returned by the module function is ignored.

**Module as an anonymous function**

It is recommended to use type hints when specifying arguments, if possible. Arguments resolving is done using both argument
class name as a hint and argument name.

Arguments are resolved in the following order:

- if an argument is an instance of ```Phalcon\Bootstrap\Container``` or ```Phalcon\Bootstrap\Environment``` then the
  corresponding object is injected; otherwise
- if a property with the same name exists in the container, then its value is injected; finally
- if a Di service with the same name exists inside Phalcon Di, then its value is injected.

Note: if an argument cannot be resolved then a ```\LogicException``` will be thrown, unless you specify a default value for it.

```php
use Phalcon\Bootstrap\Container;
use Phalcon\Bootstrap\Environment;
use Phalcon\Di;
use Phalcon\Mvc\Router;

$module = function(
          Container $container,     // bootstrap container injected

          Environment $environment, // bootstrap environment injected

          Di $di,                   // the Phalcon Di injected, "di" is a reserved name in the bootstrap container

          Router $router,           // the router instance, "router" is a name of the service inside Phalcon Di

          $b,                       // a value of "b" property set on the container. If it is not defined, then
                                    // an exception will be thrown

          $a = "foo")               // a value of "a" property set on the container. If it is not defined, then
                                    // "foo" as its value will be used
{
    // ...
};
```

**Module as a specification**

The specification is an array whose elements consist of a list of strings (the names of the dependencies) followed by the
function itself.

The following names are reserved:
- **$container**, is resolved to the bootstrap container
- **$environment**, is resolved to the bootstrap environment
- **$di**, is resolved to the Phalcon Di

Note: must be enclosed into single quotes.

Phalcon Di services names must begin with "@" symbol and container properties should be listed as normal.

Note: container properties names are *case-sensitive*.

Module function from previous example rewritten as a specification:

```php
use Phalcon\Bootstrap\Container;
use Phalcon\Bootstrap\Environment;
use Phalcon\Di;
use Phalcon\Mvc\Router;

$module = [                  '$container',           '$environment',  '$di',      '@router', 'b', 'a' => foo,
           function(Container $container, Environment $environment, Di $di, Router $router,  $b,  $a)
{
    // ...
}];
```

Default values of the function arguments can be specified as array values, see "a" dependency above. Function arguments
can have different names, but the number of arguments and the number of dependencies must match.

**Organising modules**

The typical structure of bootstrap files and modules is as follows:

```
/app *(your application directory)*
    /bootstrap
        /view.php
        /routes.php
        /config.php
    /bootstrap.init.php
    /bootstrap.mvc.php
    /bootstrap.cli.php
/scripts *(console scripts)*
    /example.php *(includes /app/bootstrap.cli.php)*
/public
    /index.php *(includes /app/bootstrap.mvc.php)*
```

```/app/bootstrap.init.php``` may contain high level initialization, like ```Phalcon\Loader```, locale etc that is common for all applications.

```php
use Phalcon\Loader;
use Locale;

date_default_timezone_set('Australia/Sydney');

Locale::setDefault('en_AU');

$loader = new Loader();

$loader->registerNamespaces(
    array(
       "Example\Base"    => "vendor/example/base/",
       "Example\Adapter" => "vendor/example/adapter/",
       "Example"         => "vendor/example/",
    )
);

$loader->register();
```

```/app/bootstrap.mvc.php``` contains initialization for your MVC application and ```/app/bootstrap.cli.php``` for
command-line scripts respectively.

```php
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\View;

require 'bootstrap.ini.php';

$bootstrap = Bootstrap::init(array(
    'di' => new FactoryDefault() // set Phalcon Di to the bootstrap container
), function() {
    // ... detect environment
});

$bootstrap->addModule( require 'bootstrap/config.php' )
          ->addModule( require 'bootstrap/view.php' )
          ->addModule( require 'bootstrap/routes.php' );

$bootstrap->addModule(['$di', function($di) {

    /**
     * Run the application
     */

    try {
        $application = new Application($di);
        echo $application->handle()->getContent();
    } catch (\Exception $e) {
        echo $e->getMessage();
    }

}])
          ->execute(); // execute bootstrap
```

Example of bootstrap files:

```php
// config.php

return ['$di', function($di) {
    $config = new \Phalcon\Config(array(
        'database' => array(
            'host' => 'localhost',
            'dbname' => 'test_db'
        ),
        'debug' => 1
    ));

    $di->setShared('config', $config);
}];

// view.php

return ['$di', function($di) {
    $di->set('view', function() {
        $view = new View();
        $view->setViewsDir('../apps/views/');

        return $view;
    });
}]);

// routes.php

return ['@router', function($router) {
    $router->add(
        "/admin/users/my-profile",
        array(
            "controller" => "users",
            "action"     => "profile",
        )
    );

    $router->add(
        "/admin/users/change-password",
        array(
            "controller" => "users",
            "action"     => "changePassword",
        )
    );
}]);
```