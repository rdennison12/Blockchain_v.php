<?php

/**
 * Created by: Rick Dennison
 * Project   : blockchain
 * Date      : 11/18/2026
 */

namespace App;

class Config
{
    /**
     * Database host
     * @var string
     */
    public const DB_HOST = 'localhost';

    /**
     * Database name
     * @var string
     */
    public const DB_NAME = 'blockchain';

    /**
     * Database user
     * @var string
     */
    public const DB_USER = 'root';

    /**
     * Database password
     * @var string
     */
    public const DB_PASSWORD = '';

    /**
     * Show or hide error messages on screen
     * @var bool
     */
    public const SHOW_ERRORS = true;
}
