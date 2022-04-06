<?php declare(strict_types = 1);
/**
 * @author    Luis Arturo Rodríguez
 * @copyright Copyright (c) 2021 Luis Arturo Rodríguez <rguezque@gmail.com>
 * @license   https://opensource.org/licenses/MIT    MIT License
 */

namespace routing\connection;
/**
 * Funcion de conexión PDO MySQL
 * 
 * @function PDO mysql_connection(array $dsn, string $username, string $password, array $options = []) Devuelve una conexión PDO
 */

use PDO;
use PDOException;

/**
 * Devuelve una conexión PDO
 * 
 * @param array $dsn Parámetros de conexión
 * @param string $username Nombre de usuario de la BD
 * @param string $password Contraseña de acceso a la BD
 * @param array $options Opciones adicionales de la conexión
 * @return PDO
 * @throws PDOException
 */
function mysql_connection(array $dsn, string $username, string $password, array $options = [PDO::ATTR_PERSISTENT => true]): PDO {
    $data = [];
    foreach($dsn as $key => $value) {
        $data[] = sprintf('%s=%s', $key, $value);
    }
    $dsn = 'mysql:'.implode(';', $data);

    try {
        $db = new PDO($dsn, $username, $password, $options);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch(PDOException $e) {
        exit(sprintf('<pre>%s</pre>', utf8_encode(print_r($e, true))));
    }
}

?>