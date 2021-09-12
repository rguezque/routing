# Routing
 *Simple router PHP No-POO*

## Configure *WebServer*

En *Apache* crea y edita un archivo `.htaccess` con lo siguiente:

```
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
	
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

En *Nginx* agrega lo siguiente en el archivo de configuración:

```
server {
    location / {
        try_files $uri $uri/ /index.php;
    }
}
```

## Routes

Cada ruta se compone de un nombre único, el *string* de la ruta y un callback. Dependiendo del método de petición que aceptará la ruta será la función a utilizar. Los métodos aceptados son `GET` y  `POST` que corresponden con las funciones `get()` y  `post()`.

```php
?php

require __DIR__.'/vendor/autoload.php';

use function routing\{dispatch, get, with_namespace};
use function routing\http\json_response;

get('homepage', '/', function() {
    $data = [
        'greeting' => 'Hola',
        'name' => 'John',
        'lastname' => 'Doe'
    ];
    
    json_response($data);
});

with_namespace('/foo', function() {
    get('foo_index', '/', function() {
        echo 'Foo';
    });

    get('foo_bar', '/bar', function() {
        echo 'Bar';
    });

    get('foo_goo', '/goo', function() {
        echo 'Goo';
    });
});

get('baz_route', '/baz', function() {
    echo 'Baz';
});

get('hola_route', '/hola/{nombre}', function(array $args) {
    printf('Hola %s.', $args['nombre']);
});

try {
    dispatch();
} catch(RuntimeException $e) {
    printf('<h1>Not Found</h1>%s', $e->getMessage());
}

?>
```

La función `view()` renderiza una plantilla en una ruta directamente sin necesidad de definir un controlador. Como parámetro recibe la ruta, el nombre de la plantilla y opcionalmente un *array* asociativo con argumentos para pasar a la plantilla. Este tipo de rutas son de tipo `GET` y su nombre se genera automáticamente a partir del *string* de la ruta.

```php
?php

require __DIR__.'/vendor/autoload.php';

use function routing\{dispatch, view};
use function routing\template\set_views_path;

set_views_path(__DIR__.'/templates');

view('/welcome/home', 'homepage.php');

dispatch();
?>
```

Lo anterior renderiza la plantilla `homepage.php` al solicitar la ruta `'/welcome/home'`. El nombre resultante de esta ruta será `welcome_home`.

### Groups

Agrupa rutas con un *namespace*.

```php
<?php

require __DIR__.'/vendor/autoload.php';

use function routing\{dispatch, get, with_namespace};

get('index', '/', function() {
    echo 'hola mundo';
});

with_namespace('/foo', function() {
    get('index_foo', '/', function() {
        echo 'Foo';
    });

    get('foo_bar', '/bar', function() {
        echo 'Bar';
    });

    get('foo_goo', '/goo', function() {
        echo 'Goo';
    });
});

dispatch();

?>
```

Todo lo anterior genera las rutas:

```
/
/foo/
/foo/bar
/foo/goo
```

## Hooks

El router permite agregar *hooks* a las rutas, ya sea antes o después, solo basta especificar a que ruta o grupo de rutas se asignara una acción. Un *hook* antes de una ruta(`before()`) puede o no retornar un resultado, si devuelve un valor este se agrega al *array* de argumentos enviados al controlador de dicha ruta y se puede recuperar con la clave `before_data`.

Un *hook* después de una ruta (`after`), atrapará el resultado de su controlador en un array y dicho resultado puede ser recuperado con la clave `controller_data`.

```php
// Se define una ruta de nombre 'index' y atrapa el resultado del hook previo
get('index', '/', function($args) {
    return sprintf('Hola%s', $args['before_data']);
});

// Se asigna a la ruta 'index' un hook antes y devuelve un resultado al controlador
before('index', function() {
    return ' mundo';
});

// Se asigna a la ruta 'index' un hook después y atrapa el resultado del controlador para mostrar un resultado final
after('index', function($args) {
    printf('%s cruel', $args['controller_data']);
});
```

Lo anterior muestra en pantalla el mensaje `"Hola mundo cruel"`. **Nota:** Un controlador puede o no devolver un resultado si se define un *hook* después de este.

## Basepath

Define un directorio base para el router si este se aloja en un subdirectorio del *server*.

```php
use function routing\set_basepath;

set_basepath('/subdirectorio-router');
```

## Redirect

Devuelve una redireccion a otra URI.

```php
use function routing\http\redirect_response;
use function routing\{generate_uri, get, dispatch};

get('index', '/', function() {
    // A una ruta según su nombre
    redirect_response(generate_uri('foo_page'));
    // A una URI
    redirect_response('https://www.fakesite.foo');
});

get('foo_page', '/foo', function() {
    echo 'Foo';
});

dispatch();
```

## Generate URI

Genera la URI correspondiente de una ruta según su nombre.

```php
// Genera '/'
generate_uri('homepage');
// Enviando parámetros para la ruta '/show/{id}' genera '/show/9'
generate_uri('show_page', ['id' => 9]);
```

## Templates

Renderiza una plantilla `.php`. Recibe como parámetros el nombre de la plantilla, opcionalmente un array asociativo con parámetros a enviar y un valor booleano; este último define si la plantilla debe devolverse como un *string*. Por default es `false` por lo cual la plantilla se renderiza directamente.

Las plantillas son buscadas en el directorio que se defina previamente con la función `set_views_path`.

```php
// index.php
<?php

require __DIR__.'/vendor/autoload.php';

use function routing\{get, dispatch};
use function routing\template\{set_views_path, template};

set_views_path(__DIR__.'/views');

// Renderiza el template
get('index', '/', function() {
    template('homepage', ['mensaje' => 'Hola mundo']);
});

// Atrapa el template en una variable para uso posterior
get('index', '/', function() {
    $view = template('homepage', ['mensaje' => 'Hola mundo'], true);
});

dispatch();
?>
```

```php
// /views/homepage.php
<?php
    echo $mensaje;
?>
```

## PDO

```php
<?php

require __DIR__.'/vendor/autoload.php';

use function routing\{get, dispatch};
use function routing\connection\pdo_connection;

get('index', '/', function() {
	$db = pdo_connection('mysql:host=localhost;port=3306;dbname=nombre_bd;charset=utf8', 'nombre_usuario', 'clave_acceso', [/*...opciones...*/]);
    //...
});

dispatch();
```

## Request/Response

- `get_server_params()`: Devuelve el array $_SERVER
- `get_query_params()`: Devuelve el array $_GET
- `get_cookie_params()`: Devuelve el array $_COOKIE
- `unsetcookie(string $name)`: Elimina una cookie.
- `get_body_params()`: Devuelve el array $_POST
- `get_files_params()`: Devuelve el array $_FILES
- `get_globals_params()`: Devuelve el array $GLOBALS
- `setglobal(string $name, $value)`: Crea una variable global.
- `getglobal(string $name)`: Devuelve una variable global.
- `response(string $body = '', int $code = HTTP_OK, ?array $header = null)`: Devuelve una respuesta HTTP.
- `json_response(array $data)`: Devuelve una respuesta HTTP en formato json
- `redirect_response(string $uri)`: Redirecciona a otra ruta o URL.



