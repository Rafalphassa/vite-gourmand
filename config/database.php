<?php
function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host   = 'localhost';
    $dbname = 'vite_gourmand';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8",
            $username,
            $password
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die('Erreur de connexion : ' . $e->getMessage());
    }

    return $pdo;
}