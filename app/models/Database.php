<?php
namespace App\Models;

use Medoo\Medoo;

class Database {
    private static $instance = null;

    public static function getInstance(): Medoo {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';
            self::$instance = new Medoo($config);
        }
        return self::$instance;
    }
}