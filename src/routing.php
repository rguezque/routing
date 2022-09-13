<?php declare(strict_types = 1);
/**
 * @author    Luis Arturo Rodríguez
 * @copyright Copyright (c) 2021 Luis Arturo Rodríguez <rguezque@gmail.com>
 * @license   https://opensource.org/licenses/MIT    MIT License
 */

namespace routing;
/**
 * Funciones del router
 * 
 * @function void set_basepath(string $path) Define un directorio base donde se ubica el router.
 * @function string get_basepath() Devuelve el string del directorio base.
 * @function void get(string $path, $callback, ?string $name = null) Agrega una ruta GET.
 * @function void post(string $path, $callback, ?string $name = null) Agrega una ruta POST.
 * @function void with_prefix(string $namespace, Closure $closure) Define grupos de rutas bajo un prefijo de ruta en común.
 * @function void before($route_name, Closure $action) Add a hook before a route or routes group
 * @function void after($route_name, Closure $action) Add a hook after a route or routes group
 * @function string generate_uri(string $route_name) Genera una URI de una ruta nombrada.
 * @function void dispatch() Despacha el enrutador.
 */

use ArgumentCountError;
use Closure;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use OutOfRangeException;
use RuntimeException;

use function routing\helper\is_assoc_array;
use function routing\helper\remove_trailing_slash;
use function routing\helper\pathformat;
use function routing\http\setglobal;
use function routing\http\getglobal;
use function routing\http\get_server_params;
use function routing\http\response;
use function routing\template\render;

/**
 * Inicializa la variable global que contendra todas las variables del router
 */
setglobal('ROUTING_DATA', []);

/**
 * Métodos de petición soportados por el router
 */
set_routing_data('ALLOWED_REQUEST_METHODS', ['GET', 'POST']);

/**
 * Colección de rutas
 */
set_routing_data('ROUTES', []);

/**
 * Colección de URI de cada ruta
 */
set_routing_data('ROUTE_NAMES', []);

/**
 * Almacena definiciones de hooks (ganchos)
 */
set_routing_data('HOOKS', ['before' => [], 'after' => []]);

/**
 * Define un namespace para un grupo de rutas
 */
set_routing_data('GROUP_PREFIX', '');

/**
 * Define el subdirectorio donde se aloja el router
 * 
 * @param string $path Ruta del ubdirectorio
 * @return void
 */
function set_basepath(string $path): void {
    set_routing_data('BASEPATH', pathformat($path));
}

/**
 * Devuelve la ruta del subdirectorio donde se aloja el router
 * 
 * @return string
 */
function get_basepath(): string {
    return get_routing_data('BASEPATH') ?? '';
}

/**
 * Crea una variable de acceso global utilizada por el router
 * 
 * @param string $key Nombre de la variable
 * @param mixed $value Valor de la variable
 * @return void
 */
function set_routing_data(string $key, $value): void {
    $data = getglobal('ROUTING_DATA');
    $data[$key] = $value;
    setglobal('ROUTING_DATA', $data);
}

/**
 * Devuelve una variable de acceso global utilizada por el router
 * 
 * @param string $key Nombre de la variable del router
 * @param mixed $default Valor default a devolver si no existe la variable solicitada
 * @return mixed
 */
function get_routing_data(string $key, $default = null) {
    $data = getglobal('ROUTING_DATA');
    return $data[$key] ?? $default;
}

/**
 * Ejecuta el router
 */
function dispatch() {
    // Variable bandera que asegura una sola ejecución de la función routing\handle_request()
    static $invoke_once = false;

    if(!$invoke_once) {
        handle_request();
        $invoke_once = true;
    }
}

/**
 * Evalua la petición http y despacha los controladores de las rutas correspondientes
 * 
 * @throws OutOfBoundsException
 * @throws RuntimeException
 * @return void
 */
function handle_request(): void {
    $server         = get_server_params();
    $request_uri    = parse_request_uri($server['REQUEST_URI']);
    $request_method = $server['REQUEST_METHOD'];

    // Valida que el método de petición recibido sea soportado por el router
    if(!in_array($request_method, get_routing_data('ALLOWED_REQUEST_METHODS'))) {
        throw new OutOfBoundsException(sprintf('El método de petición %s no está soportado.', $request_method));
    }

    // Dependiendo del método de petición http se elige el array correspondiente de rutas
    $all_routes = get_routing_data('ROUTES');
    $routes = $all_routes[$request_method];

    // Separa y extrae el URI solicitado de los parámetros GET que pudieran ser enviados por la URI
    parse_request_uri($request_uri);
    // El slash al final no se toma en cuenta
    $request_uri = ('/' !== $request_uri) ? remove_trailing_slash($request_uri) : $request_uri;

    foreach($routes as $route) {
        // Prepara el string de la ruta
        $path = $route['path'];
        $path = get_basepath() . $path;
        
        if(preg_match(route_pattern($path), $request_uri, $arguments)) {
            array_shift($arguments);
        
            $callback = $route['callback'];
            $route_name = $route['name'];
                
            $hooks = get_routing_data('HOOKS');
            // Busca si existe un hook antes y lo ejecuta
            if(array_key_exists($route_name, $hooks['before'])) {
                $result = call_user_func($hooks['before'][$route_name]);
                if($result) {
                    $arguments = array_merge($arguments, ['@bdata' => $result]);
                }
            }

            // Ejecuta el controlador de la ruta
            $result = call_user_func($callback, $arguments);

            // Busca si existe un hook después y lo ejecuta
            if(array_key_exists($route_name, $hooks['after'])) {
                $result 
                    ? call_user_func($hooks['after'][$route_name], ['@cdata' => $result]) 
                    : call_user_func($hooks['after'][$route_name]);
            }

            return;
        }
    }

    response('', 404);
    throw new RuntimeException(sprintf('No se encontró la ruta solicitada "%s"', $request_uri));
}

/**
 * Renderiza una plantilla directamente sin tener que definir toda una ruta y controlador
 * 
 * @param string $name Nombre de la ruta
 * @param string $path Definición de ruta
 * @param string $template Nombre de la plantilla
 * @param array $arguments Parámetros pasados a la plantilla
 * @return void
 */
function view(string $name, string $path, string $template, array $arguments = []): void {
    get($name, $path, function() use($template, $arguments) {
        // Detecta si se envía un argumento de un hook before y lo agrega
        if(0 < func_num_args()) {
            $arguments = array_merge($arguments, func_get_arg(0));
        }
        
        render($template, $arguments);
    });
}

/**
 * Mapea una ruta que solo acepta el método de petición GET
 * 
 * @param array|string $path_data Nombre y/o definición del string de ruta
 * @param mixed $callback Controlador de la ruta
 * @return void
 */
function get($path_data, $callback): void {
    route('GET', $path_data, $callback);
}

/**
 * Mapea una ruta que solo acepta el método de petición POST
 * 
 * @param array|string $path_data Nombre y/o definición del string de ruta
 * @param mixed $callback Controlador de la ruta
 * @return void
 */
function post($path_data, $callback): void {
    route('POST', $path_data, $callback);
}

/**
 * Mapea una ruta
 * 
 * @param string $method Método de petición
 * @param array|string $path_data Nombre y/o definición del string de ruta
 * @param mixed $callback Controlador de la ruta
 * @return void
 * @throws OutOfRangeException
 * @throws InvalidArgumentException
 * @throws LogicException
 */
function route(string $method, $path_data, $callback): void {
    if(is_array($path_data)) {
        list($name, $path) = $path_data;
        $name = trim($name);
    } else {
        $name = null;
        $path = $path_data;
    }

    $method = strtoupper(trim($method));
    // Valida que el método de petición recibido sea soportado por el router
    if(!in_array($method, get_routing_data('ALLOWED_REQUEST_METHODS'))) {
        throw new OutOfRangeException(sprintf('El método de petición %s no está soportado en la definición de la ruta %s:"%s".', $method, $name, $path));
    }

    // Verifica si ya existe una ruta con el mismo nombre
    if(isset($name)) {
        if('' === $name) {
            throw new InvalidArgumentException(sprintf('El nombre de la ruta "%s" no puede ser una cadena vacía.', $path));
        }
        if(array_key_exists($name, get_routing_data('ROUTE_NAMES'))) {
            throw new LogicException(sprintf('Ya existe una ruta con el nombre "%s".', $name));
        }
    }

    $path = pathformat($path);
    $path = get_routing_data('GROUP_PREFIX') . $path;
    // Guarda la ruta en la colección de rutas
    $routes = (array) get_routing_data('ROUTES');
    $routes[$method][] = [
        'name' => $name, 
        'path' => $path, 
        'callback' => $callback
    ];
    set_routing_data('ROUTES', $routes);

    // Guarda el nombre de la ruta
    if(isset($name)) {
        save_route_name($path, $name);
    }
}

/**
 * Define grupos de rutas con un mismo prefijo
 * 
 * @param string $prefix Prefijo de las rutas
 * @param Closure $closure Callback con la definición de rutas
 * @return void
 */
function with_prefix(string $prefix, Closure $closure): void {
    $prefix = pathformat($prefix);
    set_routing_data('GROUP_PREFIX', $prefix);
    call_user_func($closure);
    set_routing_data('GROUP_PREFIX', '');
}

/**
 * Guarda las rutas con un nombre definido o automático
 * 
 * @param string $path URI de la ruta
 * @param string $name Nombre de la ruta
 * @return void
 */
function save_route_name(string $path, string $name): void {
    $routes_path = get_routing_data('ROUTE_NAMES');
    $routes_path[$name] = $path;
    set_routing_data('ROUTE_NAMES', $routes_path);
}

/**
 * Add a hook before a route or routes group
 * 
 * @param string|array $route_name Route or route names
 * @param Closure $action Action to exec before a route controller
 * @return void
 */
function before($route_name, Closure $action): void {
    $hooks = get_routing_data('HOOKS');
    if(is_array($route_name)) {
        foreach($route_name as $route) {
            $hooks['before'][$route] = $action;
        }
    } else {
        $hooks['before'][$route_name] = $action;
    }
    set_routing_data('HOOKS', $hooks);
}

/**
 * Add a hook after a route or routes group
 * 
 * @param string|array $route_name Route or route names
 * @param Closure $action Action to exec after a route controller
 * @return void
 */
function after($route_name, Closure $action): void {
    $hooks = get_routing_data('HOOKS');
    if(is_array($route_name)) {
        foreach($route_name as $route) {
            $hooks['after'][$route] = $action;
        }
    } else {
        $hooks['after'][$route_name] = $action;
    }
    set_routing_data('HOOKS', $hooks);
}

/**
 * Genera la URI de una ruta a partir de su nombre y parámetros
 * 
 * @param string $route_name Nombre de la ruta
 * @param array $params Parámetros a ser cazados con los wildcards de la ruta
 * @return string
 * @throws OutOfBoundsException
 * @throws InvalidArgumentException
 * @throws ArgumentCountError
 */
function generate_uri(string $route_name, array $params = []): string {
    $route_names = get_routing_data('ROUTE_NAMES');

    if(!array_key_exists($route_name, $route_names)) {
        throw new OutOfBoundsException(sprintf('No existe una ruta con el nombre "%s".', $route_name));
    }
    
    $path = $route_names[$route_name];

    if(!empty($params)) {
        if(!is_assoc_array($params)) {
            throw new InvalidArgumentException(sprintf('Se esperaba un array asociativo. Las claves deben coincidir con los wildcards de la ruta "%s".', $route_name));
        }

        $path = preg_replace_callback('#{(\w+)}#', function($match) use($route_name, $path, $params) {
            $key = $match[1];
            if(!array_key_exists($key, $params)) {
                throw new ArgumentCountError(sprintf('Parámetros insuficientes al intentar generar la URI para la ruta %s:"%s".', $route_name, $path));
            }
            
            return $params[$key];
        },$path);
    }

    return pathformat($path);
}

/**
 * Construye el patrón regex de la ruta
 * 
 * @param string $path Definición de la ruta
 * @return string
 */
function route_pattern(string $path): string {
    $parse_path = str_replace('/', '\/', pathformat($path));
    $parse_path = preg_replace('#{(\w+)}#', '(?<$1>\w+)', $parse_path);

    return '#^'.$parse_path.'$#i';
}

/**
 * Analiza la URI de la petición
 * 
 * Si se envian parámetros a través de la URI (ej. /path/?foo=bar) se toma el 
 * componente 'path' y los parámetros son atrapados en el array $_GET y son 
 * accesibles con la función get_query_params()
 * 
 * @param string $uri URI a analizar
 * @return string
 */
function parse_request_uri(string $uri): string {
    return rawurldecode(parse_url($uri, PHP_URL_PATH));
}
