<?php

namespace config;

use PDO;
use PDOException;

class database
{
    private static $instancia = null;
    public static function getInstancia(): PDO
    {
        if (self::$instancia === null) {
            try {
                self::$instancia = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                self::$instancia->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                return self::$instancia;
            } catch (PDOException $e) {
                die('Erro de conexão: ' . $e->getMessage());
            }
        }
        return self::$instancia;
    }

    public static function fecharConexao()
    {
        self::$instancia = null;
    }
}