<?php declare(strict_types = 1);
/**
 * @author    Luis Arturo Rodríguez
 * @copyright Copyright (c) 2021 Luis Arturo Rodríguez <rguezque@gmail.com>
 * @license   https://opensource.org/licenses/MIT    MIT License
 */

namespace routing\http;

use InvalidArgumentException;
use OutOfBoundsException;

use function routing\helper\is_assoc_array;

/**
 * Funciones de http
 * 
 * @function array get_server_params() Devuelve los parámetros de $_SERVER
 * @function array get_query_params() Devuelve los parámetros de $_GET
 * @function array get_cookie_params() Devuelve los parámetros de $_COOKIE
 * @function void unsetcookie(string $name) Elimina una cookie
 * @function array get_request_params() Devuelve los parámetros de $_POST
 * @function array get_files_params() Devuelve los parámetros de $_FILES
 * @function array get_globals_params() Devuelve los parámetros de $GLOBALS
 * @function void setglobal(string $name, $value) Crea una variable global
 * @function mixed getglobal(string $name) Devuelve una variable global específica
 * @function void response(string $body = '', int $code = HTTP_OK, ?array $header = null) Devuelve una respuesta HTTP
 * @function void json_response(array $data) Devuelve una respuesta HTTP en formato json
 * @function void redirect_response(string $uri) Redirecciona a otra URI
 */

const HTTP_CONTINUE = 100;
const HTTP_SWITCHING_PROTOCOLS = 101;
const HTTP_PROCESSING = 102;                            // RFC2518
const HTTP_EARLY_HINTS = 103;                           // RFC8297
const HTTP_OK = 200;
const HTTP_CREATED = 201;
const HTTP_ACCEPTED = 202;
const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
const HTTP_NO_CONTENT = 204;
const HTTP_RESET_CONTENT = 205;
const HTTP_PARTIAL_CONTENT = 206;
const HTTP_MULTI_STATUS = 207;                          // RFC4918
const HTTP_ALREADY_REPORTED = 208;                      // RFC5842
const HTTP_IM_USED = 226;                               // RFC3229
const HTTP_MULTIPLE_CHOICES = 300;
const HTTP_MOVED_PERMANENTLY = 301;
const HTTP_FOUND = 302;
const HTTP_SEE_OTHER = 303;
const HTTP_NOT_MODIFIED = 304;
const HTTP_USE_PROXY = 305;
const HTTP_RESERVED = 306;
const HTTP_TEMPORARY_REDIRECT = 307;
const HTTP_PERMANENTLY_REDIRECT = 308;                  // RFC7238
const HTTP_BAD_REQUEST = 400;
const HTTP_UNAUTHORIZED = 401;
const HTTP_PAYMENT_REQUIRED = 402;
const HTTP_FORBIDDEN = 403;
const HTTP_NOT_FOUND = 404;
const HTTP_METHOD_NOT_ALLOWED = 405;
const HTTP_NOT_ACCEPTABLE = 406;
const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
const HTTP_REQUEST_TIMEOUT = 408;
const HTTP_CONFLICT = 409;
const HTTP_GONE = 410;
const HTTP_LENGTH_REQUIRED = 411;
const HTTP_PRECONDITION_FAILED = 412;
const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
const HTTP_REQUEST_URI_TOO_LONG = 414;
const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
const HTTP_EXPECTATION_FAILED = 417;
const HTTP_I_AM_A_TEAPOT = 418;                         // RFC2324
const HTTP_MISDIRECTED_REQUEST = 421;                   // RFC7540
const HTTP_UNPROCESSABLE_ENTITY = 422;                  // RFC4918
const HTTP_LOCKED = 423;  // RFC4918
const HTTP_FAILED_DEPENDENCY = 424;                     // RFC4918
const HTTP_TOO_EARLY = 425;                             // RFC-ietf-httpbis-replay-04
const HTTP_UPGRADE_REQUIRED = 426;                      // RFC2817
const HTTP_PRECONDITION_REQUIRED = 428;                 // RFC6585
const HTTP_TOO_MANY_REQUESTS = 429;                     // RFC6585
const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;       // RFC6585
const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
const HTTP_INTERNAL_SERVER_ERROR = 500;
const HTTP_NOT_IMPLEMENTED = 501;
const HTTP_BAD_GATEWAY = 502;
const HTTP_SERVICE_UNAVAILABLE = 503;
const HTTP_GATEWAY_TIMEOUT = 504;
const HTTP_VERSION_NOT_SUPPORTED = 505;
const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;  // RFC2295
const HTTP_INSUFFICIENT_STORAGE = 507;                  // RFC4918
const HTTP_LOOP_DETECTED = 508;                         // RFC5842
const HTTP_NOT_EXTENDED = 510;                          // RFC2774
const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;

const HTTP_STATUS_TEXT = [
    100 => 'Continue',
    101 => 'Switching Protocols',
    102 => 'Processing',                                // RFC2518
    103 => 'Early Hints',
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',
    207 => 'Multi-Status',                              // RFC4918
    208 => 'Already Reported',                          // RFC5842
    226 => 'IM Used',                                   // RFC3229
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    307 => 'Temporary Redirect',
    308 => 'Permanent Redirect',                        // RFC7238
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Payload Too Large',
    414 => 'URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Range Not Satisfiable',
    417 => 'Expectation Failed',
    418 => 'I\'m a teapot',                             // RFC2324
    421 => 'Misdirected Request',                       // RFC7540
    422 => 'Unprocessable Entity',                      // RFC4918
    423 => 'Locked',                                    // RFC4918
    424 => 'Failed Dependency',                         // RFC4918
    425 => 'Too Early',                                 // RFC-ietf-httpbis-replay-04
    426 => 'Upgrade Required',                          // RFC2817
    428 => 'Precondition Required',                     // RFC6585
    429 => 'Too Many Requests',                         // RFC6585
    431 => 'Request Header Fields Too Large',           // RFC6585
    451 => 'Unavailable For Legal Reasons',             // RFC7725
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported',
    506 => 'Variant Also Negotiates',                   // RFC2295
    507 => 'Insufficient Storage',                      // RFC4918
    508 => 'Loop Detected',                             // RFC5842
    510 => 'Not Extended',                              // RFC2774
    511 => 'Network Authentication Required',           // RFC6585
];

/**
 * Devuelve los parámetros de $_SERVER
 * 
 * @return array
 */
function get_server_params(): array {
    return $_SERVER;
}

/**
 * Devuelve los parámetros de $_GET
 * 
 * @return array
 */
function get_query_params(): array {
    return $_GET;
}

/**
 * Devuelve los parámetros de $_COOKIE
 * 
 * @return array
 */
function get_cookie_params(): array {
    return $_COOKIE;
}

/**
 * Elimina una cookie
 * 
 * @param string $name Nombre de la cookie
 * @return void
 */
function unsetcookie(string $name): void {
    setcookie($name, '', time()-3600);
}

/**
 * Devuelve los parámetros de $_POST
 * 
 * @return array
 */
function get_body_params(): array {
    return $_POST;
}

/**
 * Devuelve los parámetros de $_FILES
 * 
 * @return array
 */
function get_files_params(): array {
    return $_FILES;
}

/**
 * Devuelve los parámetros de $GLOBALS
 * 
 * @return array
 */
function get_globals_params(): array {
    return $GLOBALS;
}

/**
 * Crea una variable global
 * 
 * @param string $name Nombre de la variable global
 * @param mixed $value Valor de la variable global
 * @return void
 */
function setglobal(string $name, $value): void {
    $GLOBALS[$name] = $value;
}

/**
 * Devuelve una variable global específica
 * 
 * @param string $name Nombre de la variable global
 * @return mixed
 */
function getglobal(string $name) {
    $globals = get_globals_params();

    return isset($globals[$name]) ? $globals[$name] : null;
}

/**
 * Devuelve una respuesta HTTP
 * 
 * @param string $content Contenido del response
 * @param int $code Código de estatus HTTP
 * @param array $headers Array asociativo con encabezado(s) del response
 * @return void
 * @throws OutOfBoundsException
 */
function response(string $content = '', int $code = HTTP_OK, array $headers = []): void {
    if(!array_key_exists($code, HTTP_STATUS_TEXT)) {
        throw new OutOfBoundsException(sprintf('El código %d no es un estatus HTTP válido.', $code));
    }

    if(!headers_sent()) {
        $server = get_server_params();
        header(sprintf('%s %d %s', $server['SERVER_PROTOCOL'], $code, HTTP_STATUS_TEXT[$code]));
        
        if(!empty($headers) && is_assoc_array($headers)) {
            foreach($headers as $header => $value) {
                header(sprintf('%s: %s', $header, $value), true, $code);
            }
        }
    }
    
    echo $content;
}

/**
 * Devuelve una respuesta HTTP en formato json
 * 
 * @param array $data Array de datos a procesar y devolver
 * @return void
 */
function json_response(array $data): void {
    // Solo acepta datos en array asociativo
    if(!is_assoc_array($data)) {
        throw new InvalidArgumentException('Formato incorrecto de datos. Se esperaba un array asociativo.');
    }

    response(json_encode($data, JSON_PRETTY_PRINT), HTTP_OK, ['Content-Type' => 'application/json']);
}

/**
 * Redirecciona a otra URI
 * 
 * @param string $uri Ruta de redirección
 * @return void
 */
function redirect_response(string $uri): void {
    response('', HTTP_FOUND, ['location' => $uri]);
}

?>