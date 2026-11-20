<?php

/**
 * Created by: Rick Dennison
 * Project   : blockchain
 * Date      : 11/18/2026
 */

namespace App\Controllers;

use Core\Controller;
use App\Models\Blockchain;
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
        $state = $_SESSION['blockchain'] ??= (new Blockchain())->toArray();
        $blocks = $state['chain'];

        $this->render('Home/index.twig', [
            'title' => 'Blockchain Dashboard',
            'blocks' => $blocks,
        ]);
    }
}