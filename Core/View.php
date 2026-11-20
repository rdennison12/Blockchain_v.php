<?php
/**
 * Created by: Rick Dennison
 * Project   : blockchain
 * Date      : 11/18/2026
 */

namespace Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class View
{
    public static function render(string $view, array $args = []): void
    {
        echo static::getTemplate($view, $args);
    }

    public static function getTemplate(string $template, array $args = []): string
    {
        static $twig = null;
        $twig ??= new Environment(new FilesystemLoader(ROOT_DIR . '/App/Views'));

        return $twig->render($template, $args);
    }
}
