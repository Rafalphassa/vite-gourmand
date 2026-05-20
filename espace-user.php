<?php
require_once 'config/database.php';

if(!isset($_SESSION['utilisateur_id']) || $_SESSION['role_id'] != 3) {
    header('Location: /vite-gourmand/');
    exit;
}

require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A;">Espace Utilisateur</h2>
        <p>En construction...</p>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>