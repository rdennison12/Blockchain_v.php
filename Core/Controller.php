<?php
/**
 * Created by: Rick Dennison
 * Project   : blockchain
 * Date      : 11/18/2026
 */

namespace Core;

use Exception;

abstract class Controller
{
    /**
     * Parameters from the matched route
     * @var array
     */
    protected array $route_params = [];

    /**
     * Class constructor
     *
     * @param array $route_params Parameters from the route
     */
    public function __construct(array $route_params)
    {
        $this->route_params = $route_params;
    }

    /**
     * Magic method called when a non-existent or inaccessible method is
     * called on an object of this class. Used to execute before and after
     * filter methods on action methods. Action methods need to be named
     * with an "Action" suffix, e.g. indexAction, showAction etc.
     *
     * @param string $name Method name
     * @param array $args Arguments passed to the method
     *
     * @return void
     * @throws Exception
     */
    public function __call(string $name, array $args)
    {
        $method = $name . 'Action';

        if (method_exists($this, $method)) {
            if ($this->before() !== false) {
                call_user_func_array([$this, $method], $args);
                $this->after();
            }
        } else {
            throw new Exception("Method $method not found in controller " . get_class($this), 404);
        }
    }

    /**
     * Before filter - called before an action method.
     *
     * @return void|bool
     */
    protected function before()
    {
    }

    /**
     * After filter - called after an action method.
     *
     * @return void
     */
    protected function after()
    {
    }

    /**
     * Render a view template with parameters
     *
     * @param string $view View template relative to App/Views
     * @param array $args Data passed to view and layout
     * @return void
     * @throws Exception
     */
    protected function render(string $view, array $args = []): void
    {
        View::render($view, $args);
    }

    /**
     * Send a JSON response
     *
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @return void
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data);
    }
}