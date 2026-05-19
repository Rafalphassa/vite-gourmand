<?php
// Les informations de connexion à la base de données
$host = "localhost";      // où est MySQL ? sur notre propre machine
$dbname = "vite_gourmand"; // le nom de notre base
$username = "root";        // utilisateur par défaut de XAMPP
$password = "";            // pas de mot de passe sur XAMPP en local

try {
    // On essaie de se connecter
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );
    
    // Si une erreur arrive, PHP la signale clairement
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Les résultats arrivent sous forme de tableaux associatifs
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Si la connexion échoue, on affiche l'erreur
    die("Erreur de connexion : " . $e->getMessage());
}
?>