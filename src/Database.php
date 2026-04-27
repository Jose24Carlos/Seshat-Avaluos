<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $host = Env::get('DB_HOST', 'localhost');
        $port = Env::get('DB_PORT', '3306');
        $db = Env::get('DB_DATABASE', '');
        $user = Env::get('DB_USERNAME', '');
        $pass = Env::get('DB_PASSWORD', '');
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);
        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Error de conexión a la base de datos.';
            exit;
        }
        return self::$pdo;
    }
}
