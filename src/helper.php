<?php declare(strict_types = 1);
/**
 * @author    Luis Arturo Rodríguez
 * @copyright Copyright (c) 2021 Luis Arturo Rodríguez <rguezque@gmail.com>
 * @license   https://opensource.org/licenses/MIT    MIT License
 */

 /**
  * Utilidades
  * 
  * @function string glue(string ...$vars) Concatena cadenas de texto o caracteres
  * @function string pre(string $string) Preformatea una cadena de texto
  * @function string add_trailing_slash(string $str) Agrega un slash al final de una cadena de texto
  * @function string remove_trailing_slash(string $str) Remueve slashes al final de una cadena de texto
  * @function string add_leading_slash(string $str) Agrega un slash al inicio de una cadena de texto
  * @function string remove_leading_slash(string $str) Remueve slashes al inicio de una cadena de texto
  * @function bool str_starts_with(string $haystack, string $needle) Devuelve true si una cadena de texto tiene un prefijo específico
  * @function bool str_ends_with(string $haystack, string $needle) Devuelve true si una cadena de texto tiene un sufijo específico
  * @function string str_path(string $path) Limpia y prepara el string de una ruta
  * @function bool is_assoc_array(array $array) Devuelve true si el array evaluado es asociativo
  */
namespace routing\helper;

/**
 * Concatena cadenas de texto o caracteres
 * 
 * @param string $vars Cadenas de texto separadas por comas
 * @return string
 */
function glue(string ...$vars): string {
    return implode('', $vars);
}

/**
 * Preformatea una cadena de texto
 * 
 * @param string $string Texto a preformatear
 * @return string
 */
function pre(string $string): string {
    return sprintf('<pre>%s</pre>', $string);
}

/**
 * Agrega un slash al final de una cadena de texto
 * 
 * @param string $str Cadena de texto
 * @return string
 */
function add_trailing_slash(string $str): string {
    return sprintf('%s/', remove_trailing_slash($str));
}

/**
 * Remueve slashes al final de una cadena de texto
 * 
 * @param string $str Cadena de texto
 * @return string
 */
function remove_trailing_slash(string $str): string {
    return rtrim($str, '/\\');
}

/**
 * Agrega un slash al inicio de una cadena de texto
 * 
 * @param string $str Cadena de texto
 * @return string
 */
function add_leading_slash(string $str): string {
    return sprintf('/%s', remove_leading_slash($str));
}

/**
 * Remueve slashes al inicio de una cadena de texto
 * 
 * @param string $str Cadena de texto
 * @return string
 */
function remove_leading_slash(string $str): string {
    return ltrim($str, '/\\');
}

/**
 * Devuelve true si una cadena de texto tiene un prefijo específico
 * 
 * @param string $haystack Cadena de texro a evaluar
 * @param string $needle Prefijo a buscar
 * @return bool
 */
function str_starts_with(string $haystack, string $needle): bool {
    return $needle === substr($haystack, 0, strlen($needle));
}

/**
 * Devuelve true si una cadena de texto tiene un sufijo específico
 * 
 * @param string $haystack Cadena de texro a evaluar
 * @param string $needle Sufijo a buscar
 * @return bool
 */
function str_ends_with(string $haystack, string $needle): bool {
    return $needle === substr($haystack, -strlen($needle));
}

/**
 * Limpia y prepara el string de una ruta
 * 
 * @param string $path String path
 * @return string
 */
function str_path(string $path): string {
    return add_leading_slash(remove_trailing_slash($path));
}

/**
 * Devuelve true si el array evaluado es asociativo
 * 
 * @param array $array El array a evaluar
 * @return bool
 */
function is_assoc_array(array $array) {
    return (is_array($array) && (count($array)==0 || 0 !== count(array_diff_key($array, array_keys(array_keys($array))) )));
}

?>