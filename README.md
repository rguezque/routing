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

Cada ruta se compone del *string* de la ruta y un callback. Los métodos aceptados son `GET` y  `POST` que corresponden con las funciones `get()` y  `post()`. Las rutas soportan la definición de *wildcards*, que al coincidir con alguna petición son enviados como argumentos al controlador.

Opcionalmente se puede asignar un nombre único a cada ruta, enviando tanto el nombre como el string de la ruta en un *array*, es ese orden estrictamente. Los nombres sirven para asignar *hooks* (Ver [Hooks](#hooks))y/o generar la URI (Ver [Generate URI](#generateuri)).

```php
?php

require __DIR__.'/vendor/autoload.php';

use function rguezque\{dispatch, get, with_prefix};
use function rguezque\http\json_response;

// Una ruta con nombre
get(['homepage', '/'], function() {
    $data = [
        'greeting' => 'Hola',
        'name' => 'John',
        'lastname' => 'Doe'
    ];
    
    json_response($data);
});

with_prefix('/foo', function() {
    get('/', function() {
        echo 'Foo';
    });

    get('/bar', function() {
        echo 'Bar';
    });

    get('/goo', function() {
        echo 'Goo';
    });
});

get('/baz', function() {
    echo 'Baz';
});

get('/hola/{nombre}', function(array $args) {
    printf('Hola %s.', $args['nombre']);
});

try {
    dispatch();
} catch(RuntimeException $e) {
    printf('<h1>Not Found</h1>%s', $e->getMessage());
}

?>
```

La función `view()` renderiza una plantilla en una ruta directamente sin necesidad de definir un controlador. Como parámetro el string de la ruta (opcional nombre de la ruta), el nombre del archivo plantilla y opcionalmente un *array* asociativo con argumentos para pasar a la plantilla. Este tipo de rutas son de tipo `GET`. 

```php
?php

require __DIR__.'/vendor/autoload.php';

use function rguezque\{dispatch, view};
use function rguezque\template\set_views_path;

set_views_path(__DIR__.'/templates');

view('/welcome/home', 'homepage.php');

dispatch();
?>
```

Los *wildcards* que se definan en la ruta serán agregados como parámetros a ser enviados a la plantilla. Si este tipo de rutas tiene un *hook* asignado antes y este devuelve un valor, este resultado también será agregado como argumentos de la plantilla.

### Groups

Agrupa rutas con un *prefijo* en común.

```php
<?php

require __DIR__.'/vendor/autoload.php';

use function rguezque\{dispatch, get, with_prefix};

get('/', function() {
    echo 'hola mundo';
});

with_prefix('/foo', function() {
    get('/', function() {
        echo 'Foo';
    });

    get('/bar', function() {
        echo 'Bar';
    });

    get('/goo', function() {
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

## <a name="hooks">Hooks</a>

El router permite agregar *hooks* solo a las rutas que tienen nombre, ya sea antes o después, solo basta especificar a que ruta o grupo de rutas (a través de un *array*) se asignara una acción. Un *hook* (`before()`) antes de una ruta puede o no retornar un resultado, si devuelve un valor este se agrega al *array* de argumentos enviados al controlador de dicha ruta y se puede recuperar con la clave `'@bdata'`.

Un *hook* (`after()`) después de una ruta, atrapará el resultado de su controlador en un array y dicho resultado puede ser recuperado con la clave `'@cdata'`.

```php
// Se define una ruta de nombre 'index' y atrapa el resultado del hook previo
get(['index', '/'], function(array $args) {
    return sprintf('Hola %s', $args['@bdata']);
});

// Se asigna a la ruta 'index' un hook antes y devuelve un resultado al controlador
before('index', function() {
    return 'mundo';
});

// Se asigna a la ruta 'index' un hook después y atrapa el resultado del controlador para mostrar un resultado final
after('index', function(array $args) {
    printf('%s cruel', $args['@cdata']);
});
```

Lo anterior muestra en pantalla el mensaje `"Hola mundo cruel"`. **Nota:** Un controlador puede o no devolver un resultado si se define un *hook* después de este.

## Basepath

Define un directorio base para el router si este se aloja en un subdirectorio del *server*.

```php
use function rguezque\set_basepath;

set_basepath('/subdirectorio-router');
```

## Redirect

Devuelve una redireccion a otra URI.

```php
use function rguezque\http\redirect_response;
use function rguezque\{generate_uri, get, dispatch};

get('/', function() {
    // A una ruta según su nombre
    redirect_response(generate_uri('foo_page'));
    // A una URI
    redirect_response('https://www.fakesite.foo');
});

get('/foo', function() {
    echo 'Foo';
});

dispatch();
```

## <a name="generateuri">Generate URI</a>

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

use function rguezque\{get, dispatch};
use function rguezque\template\{set_views_path, render};

set_views_path(__DIR__.'/views');

// Renderiza el template
get('/', function() {
    render('homepage', ['mensaje' => 'Hola mundo']);
});

// Atrapa el template en una variable para uso posterior
get('/', function() {
    $view = render('homepage', ['mensaje' => 'Hola mundo'], true);
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

## PDO MySQL

```php
<?php

require __DIR__.'/vendor/autoload.php';

use function rguezque\connection\mysql_connection;

$db = mysql_connection(
    [
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'nombre_bd',
        'charset' => 'utf8'
    ], 
    'nombre_usuario', 
    'clave_acceso', 
    [/*...opciones...*/]
);
```

## Request/Response

- `get_server_params()`: Devuelve el array $_SERVER
- `get_query_params()`: Devuelve el array $_GET
- `get_cookie_params()`: Devuelve el array $_COOKIE
- `unsetcookie(string $name)`: Elimina una cookie.
- `get_body_params()`: Devuelve el array $_POST
- `get_files_params()`: Devuelve el array $_FILES
- `get_globals_params()`: Devuelve el array $GLOBALS
- `setglobal(string $name, $value)`: Crea/sobrescribe una variable global.
- `getglobal(string $name, $default = null)`: Devuelve una variable global. Si no existe se devuelve el valor especificado como default.
- `build_query(string $uri, array $vars)`: Genera una URI con parámetros de petición GET.
- `response(string $body = '', int $code = HTTP_OK, ?array $header = null)`: Devuelve una respuesta HTTP.
- `json_response($data, bool $encode)`: Devuelve una respuesta http en formato JSON. Si los datos ya están el formato JSON, se envía un segundo argumento `false`.
- `redirect_response(string $uri)`: Redirecciona a otra ruta o URL.



