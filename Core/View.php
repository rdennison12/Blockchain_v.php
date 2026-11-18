<?php
/**
 * Created by: Rick Dennison
 * Project   : blockchain
 * Date      : 11/18/2026
 */

namespace Core;

use Exception;
class View
{
    /**
     * Render a view file wrapped inside a base layout
     *
     * @param string $view View file relative to App/Views (e.g. 'Home/index.php')
     * @param array $args Data passed to view and layout
     * @param string|null $layout Layout template relative to App/Views (defaults to 'layouts/base.php', null for standalone)
     * @return void
     * @throws Exception
     */
    public static function render(string $view, array $args = [], ?string $layout = 'layouts/base.php'): void
    {
        $content = static::getTemplate($view, $args);

        if ($layout !== null) {
            // Render the base template with the captured content and data
            $args['content'] = $content;
            echo static::getTemplate($layout, $args);
        } else {
            echo $content;
        }
    }

    /**
     * Get the rendered contents of a view template as a string
     *
     * @param string $template Template path relative to App/Views
     * @param array $args Data to extract
     * @return string
     * @throws Exception
     */
    public static function getTemplate(string $template, array $args = []): string
    {
        extract($args, EXTR_SKIP);

        $file = ROOT_DIR . "/App/Views/$template";

        if (!is_readable($file)) {
            throw new Exception("View template $file not found");
        }

        ob_start();
        require $file;
        return ob_get_clean();
    }
}