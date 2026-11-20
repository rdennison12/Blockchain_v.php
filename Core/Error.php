<?php
/**
 * Created by: Rick Dennison
 * Project   : blockchain
 * Date      : 11/18/2026
 */

namespace Core;

use App\Config;
use ErrorException;
use Exception;
use Throwable;

class Error
{
    /**
     * Error handler. Convert all Errors to Exceptions by throwing an ErrorException.
     *
     * @param int $level Error level
     * @param string $message Error message
     * @param string $file Filename the error was raised in
     * @param int $line Line number in the file
     *
     * @return void
     * @throws ErrorException
     */
    public static function errorHandler(int $level, string $message, string $file, int $line): void
    {
        if (error_reporting() !== 0) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Exception handler.
     *
     * @param Throwable $exception The exception
     *
     * @return void
     * @throws Exception
     */
    public static function exceptionHandler(Throwable $exception): void
    {
        // Code is 404 (not found) or 500 (general error)
        $code = $exception->getCode();
        if ($code !== 404) {
            $code = 500;
        }

        if (!headers_sent()) {
            http_response_code($code);
        }

        if (Config::SHOW_ERRORS) {
            echo "<h1>Fatal error</h1>";
            echo "<p>Uncaught exception: '" . get_class($exception) . "'</p>";
            echo "<p>Message: '" . htmlspecialchars($exception->getMessage()) . "'</p>";
            echo "<p>Stack trace:<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre></p>";
            echo "<p>Thrown in '" . htmlspecialchars($exception->getFile()) . "' on line " . $exception->getLine() . "</p>";
        } else {
            $logDir = ROOT_DIR . '/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }

            $log = $logDir . '/' . date('Y-m-d') . '.txt';
            ini_set('error_log', $log);

            $message = "Uncaught exception: '" . get_class($exception) . "'";
            $message .= " with message '" . $exception->getMessage() . "'";
            $message .= "\nStack trace: " . $exception->getTraceAsString();
            $message .= "\nThrown in '" . $exception->getFile() . "' on line " . $exception->getLine() . "\n";

            error_log($message);

            View::render("Errors/$code.twig", ['title' => "$code - Error"]);
        }
    }
}