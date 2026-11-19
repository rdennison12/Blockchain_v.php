<?php

/**
 * Created by: Rick Dennison
 * Project   : blockchain
 * Date      : 11/18/2026
 */

namespace App\Controllers;

use Core\Controller;
use Exception;

class Home extends Controller
{
    /**
     * Show the index page
     *
     * @return void
     * @throws Exception
     */
    public function indexAction(): void
    {
        $state = $_SESSION['blockchain'] ??= (new Blockchain($this->route_params))->toArray();
        $blocks = $state['chain'];

        $this->render('Home/index.php', [
            'title' => 'Home Page',
            'message' => 'Welcome to your MVC application!',
            'blocks' => $blocks,
        ]);
    }
}