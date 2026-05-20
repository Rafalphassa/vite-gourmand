<?php
session_start();
require_once 'config/database.php';

$pdo = getDB();

$token        = trim($_GET['token'] ?? '');
$succes       = false;
$erreurs      = [];
$token_valide = false;
$uid          = 0;

if ($token !== '') {
    $stmt = $pdo->prepare('SELECT utilisateur_id FROM reset_token WHERE token = ? AND expires_at > NOW()');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if ($row) {
        $token_valide = true;
        $uid = (int)$row['utilisateur_id'];
    }
}

if (!$token_valide) {
    $erreurs[] = 'Ce lien est invalide ou a expire. Veuillez refaire une demande de reinitialisation.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valide) {
    $mdp     = $_POST['mot_de_passe'] ?? '';
    $confirm = $_POST['confirmation'] ?? '';

    if (
        strlen($mdp) < 10 ||
        !preg_match('/[A-Z]/', $mdp) ||
        !preg_match('/[a-z]/', $mdp) ||
        !preg_match('/[0-9]/', $mdp) ||
        !preg_match('/[\W_]/', $mdp)
    ) {
        $erreurs[] = 'Le mot de passe doit contenir au minimum 10 caracteres, une majuscule, une minuscule, un chiffre et un caractere special.';
    } elseif ($mdp !== $confirm) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if (empty($erreurs)) {
        $hash = password_hash($mdp, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE utilisateur SET mot_de_passe = ? WHERE utilisateur_id = ?')
            ->execute([$hash, $uid]);
        $pdo->prepare('DELETE FROM reset_token WHERE utilisateur_id = ?')
            ->execute([$uid]);
        $succes = true;
    }
}

require_once 'includes/header.php';
?>

<main id="contenu-principal">
<section class="py-5" style="background:#F0EDE4; min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div style="background:#fff; border-radius:15px; padding:40px; box-shadow:0 3px 20px rgba(0,0,0,0.08);">

                    <h1 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:5px;">
                        Nouveau mot de passe
                    </h1>
                    <p style="color:#999; font-size:0.9rem; margin-bottom:30px;">
                        Choisissez un mot de passe securise pour votre compte.
                    </p>

                    <?php if ($succes): ?>
                        <div role="alert" style="background:#d4edda; color:#155724; border-radius:10px; padding:20px; text-align:center; margin-bottom:20px;">
                            Votre mot de passe a ete modifie avec succes.
                        </div>
                        <div style="text-align:center;">
                            <a href="/vite-gourmand/connexion.php"
                               style="background:#1A5F7A; color:#fff; border-radius:25px; padding:12px 30px; font-weight:600; text-decoration:none; display:inline-block;">
                                Se connecter
                            </a>
                        </div>

                    <?php else: ?>

                        <?php if (!empty($erreurs)): ?>
                        <div role="alert" style="background:#f8d7da; color:#721c24; border-radius:10px; padding:15px; margin-bottom:20px;">
                            <?php foreach ($erreurs as $e): ?>
                                <div>- <?php echo htmlspecialchars($e); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($token_valide): ?>
                        <form method="POST" action="">
                            <div style="margin-bottom:15px;">
                                <label for="mot_de_passe" style="display:block; font-weight:600; margin-bottom:6px;">
                                    Nouveau mot de passe
                                </label>
                                <input id="mot_de_passe" name="mot_de_passe" type="password" required
                                       style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;">
                                <div style="font-size:0.78rem; color:#888; margin-top:4px;">
                                    10 caracteres min, une majuscule, une minuscule, un chiffre, un caractere special.
                                </div>
                            </div>
                            <div style="margin-bottom:20px;">
                                <label for="confirmation" style="display:block; font-weight:600; margin-bottom:6px;">
                                    Confirmer le mot de passe
                                </label>
                                <input id="confirmation" name="confirmation" type="password" required
                                       style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;">
                            </div>
                            <div style="text-align:center;">
                                <button type="submit"
                                        style="background:#1A5F7A; color:#fff; border-radius:25px; padding:12px 30px; font-weight:600; border:0; cursor:pointer; width:100%;">
                                    Enregistrer
                                </button>
                            </div>
                        </form>
                        <div style="text-align:center; margin-top:20px;">
                            <a href="/vite-gourmand/reinitialisation.php"
                               style="color:#1A5F7A; font-size:0.9rem; text-decoration:none;">
                                Refaire une demande
                            </a>
                        </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</section>
</main>

<?php require_once 'includes/footer.php'; ?>