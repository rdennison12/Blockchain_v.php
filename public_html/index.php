<?php
/**
 * Created by: Rick Dennison
 * Project   : blockchain
 * Date      : 11/17/2026
 */


declare(strict_types=1);

/**
 * Autoloader
 */

define('ROOT_DIR', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $file = ROOT_DIR . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Error and exception handling
 */
error_reporting(E_ALL);
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');

/**
 * Sessions
 */
// session_start();

/**
 * Routing
 */

$router = new Core\Router();

// Add routes
$router->add('', ['controller' => 'Home', 'action' => 'index']);
$router->add('{controller}/{action}');

// Dispatch request
$url = $_SERVER['QUERY_STRING'] ?? '';
if ($url === '' && isset($_SERVER['REQUEST_URI'])) {
    $url = trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
}

try {
    $router->dispatch($url);
} catch (Exception $e) {

}