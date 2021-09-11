<?php declare(strict_types = 1);
/**
 * @author    Luis Arturo Rodríguez
 * @copyright Copyright (c) 2021 Luis Arturo Rodríguez <rguezque@gmail.com>
 * @license   https://opensource.org/licenses/MIT    MIT License
 */

namespace routing\template;
/**
 * Funciones de template
 * 
 * @function void set_views_path(string $path)
 * @function string get_views_path()
 * @function void template(string $template, array $arguments = [])
 */

use RuntimeException;

use function routing\helper\add_leading_slash;
use function routing\helper\str_ends_with;
use function routing\http\setglobal;
use function routing\http\getglobal;

/**
 * Asigna la ruta al directorio de plantillas
 * 
 * @param string $path Ruta al directorio
 * @return void
 */
function set_views_path(string $path): void {
    setglobal('VIEWS_PATH', rtrim($path, '/\\'));
}

/**
 * Devuelve la ruta al directorio de plantillas
 * 
 * @return string
 */
function get_views_path(): string {
    return getglobal('VIEWS_PATH') ?? '';
}

/**
 * Renderiza una plantilla
 * 
 * @param string $template Nombre de la plantilla
 * @param array $arguments Parámetros pasados a la plantilla
 * @param bool $as_string Define si se devuelve la plantilla como un string
 * @return mixed
 * @throws RuntimeException
 */
function template(string $template, array $arguments = [], bool $as_string = false) {
    if(!str_ends_with($template, '.php')) {$template .= '.php';}

    $template = get_views_path() . add_leading_slash($template);

    if(!file_exists($template)) {
        throw new RuntimeException(sprintf('No se encontró el archivo de plantilla "%s"', $template));
    }
    
    extract($arguments);

    if($as_string) {
        ob_start();
        include $template;

        return ob_get_clean();
    } else {
        include $template;
    }
}

?>