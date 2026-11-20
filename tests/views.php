<?php
declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));
require ROOT_DIR . '/vendor/autoload.php';

function checkView(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$_SESSION = [];
ob_start();
(new App\Controllers\Home([]))->indexAction();
$home = ob_get_clean();
checkView(str_contains($home, 'Block 0'), 'Home must display the genesis block');
checkView(str_contains($home, '<nav') && str_contains($home, '<footer'), 'Home must include navigation and footer');
checkView(str_contains($home, '/blockchain/index'), 'Navigation must link to the API');
checkView(str_contains($home, 'bootstrap@5.3.3/dist/css/bootstrap.min.css') && str_contains($home, 'class="card shadow-sm"'), 'Home must use Bootstrap styles');

$html = Core\View::getTemplate('Home/index.twig', [
    'title' => '<script>alert(1)</script>',
    'blocks' => [['index' => 0, 'hash' => '<unsafe>', 'transactions' => [], 'timestamp' => 0, 'previousHash' => '0', 'nonce' => 0]],
]);
checkView(!str_contains($html, '<script>alert(1)</script>') && str_contains($html, '&lt;unsafe&gt;'), 'Twig must escape dynamic values');
foreach ([404, 500] as $code) {
    $html = Core\View::getTemplate("Errors/$code.twig", ['title' => "$code - Error"]);
    checkView(str_contains($html, (string) $code) && str_contains($html, '<footer'), 'Error page must use the shared layout');
    checkView(str_contains($html, 'bootstrap@5.3.3/dist/css/bootstrap.min.css') && str_contains($html, 'btn btn-primary'), 'Error pages must use Bootstrap styles');
}
echo "View checks passed\n";