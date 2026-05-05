<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance === null) {
            $config = require BASE_PATH . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['name']
            );

            // Strict mode SEMPRE attivo. La produzione (Serverplan) e' gia' strict
            // (il bug ENUM segment_filter del 2026-05-05 lo ha dimostrato), quindi
            // non cambia comportamento prod. In locale (XAMPP, di default lasco)
            // allinea il comportamento e fa emergere "warning Data truncated"
            // come exception PDO -> intercettati durante sviluppo invece che
            // post-deploy. Vedi MEMORY.md Lesson #14.
            $initCmd = "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci"
                . ", sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";

            try {
                self::$instance = new PDO($dsn, $config['user'], $config['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES    => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND  => $initCmd,
                ]);
            } catch (PDOException $e) {
                if (env('APP_DEBUG', false)) {
                    die('Database connection failed: ' . $e->getMessage());
                }
                die('Database connection failed.');
            }
        }

        return self::$instance;
    }

    public static function getInstance(): PDO
    {
        return self::connect();
    }
}
