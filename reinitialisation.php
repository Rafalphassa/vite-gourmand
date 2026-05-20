<?php
session_start();
require_once 'config/database.php';

$pdo = getDB();

$succes  = false;
$erreurs = [];

// ----------------------------------------------------------------
// ETAPE 1 : demande de reinitialisation (envoi du lien par mail)
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'Adresse email invalide.';
    } else {
        $stmt = $pdo->prepare('SELECT utilisateur_id FROM utilisateur WHERE email = ? AND actif = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generer un token securise + expiration 1h
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);

            // Stocker le token en BDD (table reset_token si elle existe, sinon colonne)
            // On suppose une table : reset_token(utilisateur_id, token, expires_at)
            // Supprimer les anciens tokens de cet utilisateur
            $pdo->prepare('DELETE FROM reset_token WHERE utilisateur_id = ?')
                ->execute([$user['utilisateur_id']]);
            $pdo->prepare('INSERT INTO reset_token (utilisateur_id, token, expires_at) VALUES (?,?,?)')
                ->execute([$user['utilisateur_id'], $token, $expires]);

            // Envoi du mail avec le lien (CDC)
            $lien    = 'https://vitegourmand.fr/vite-gourmand/nouveau-mot-de-passe.php?token=' . $token;
            $sujet   = 'Vite & Gourmand - Reinitialisation de votre mot de passe';
            $corps   = "Bonjour,\n\n";
            $corps  .= "Vous avez demande la reinitialisation de votre mot de passe.\n";
            $corps  .= "Cliquez sur le lien ci-dessous (valable 1 heure) :\n\n";
            $corps  .= $lien . "\n\n";
            $corps  .= "Si vous n'etes pas a l'origine de cette demande, ignorez ce mail.\n\n";
            $corps  .= "Cordialement,\nL'equipe Vite & Gourmand";
            $headers = 'From: noreply@vitegourmand.fr' . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
            mail($email, $sujet, $corps, $headers);

            $succes = true;
        } else {
            // Message volontairement identique pour ne pas exposer si l'email existe
            $succes = true;
        }
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
                        Mot de passe oublie
                    </h1>
                    <p style="color:#999; font-size:0.9rem; margin-bottom:30px;">
                        Entrez votre email pour recevoir un lien de reinitialisation.
                    </p>

                    <?php if ($succes): ?>
                        <div role="alert" style="background:#d4edda; color:#155724; border-radius:10px; padding:20px; text-align:center;">
                            Si un compte est associe a cet email, un lien de reinitialisation a ete envoye.
                            Verifiez votre boite mail (et vos spams).
                        </div>
                        <div style="text-align:center; margin-top:20px;">
                            <a href="/vite-gourmand/connexion.php"
                               style="color:#1A5F7A; font-size:0.9rem; text-decoration:none;">
                                Retour a la connexion
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

                        <form method="POST" novalidate>
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold" style="font-size:0.9rem;">
                                    Adresse email
                                </label>
                                <input type="email" id="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="votre@email.fr">
                            </div>
                            <button type="submit"
                                    style="background:#1A5F7A; color:#fff; border:none; border-radius:25px; padding:12px 35px; font-weight:600; width:100%; font-size:1rem; cursor:pointer;">
                                Envoyer le lien
                            </button>
                        </form>

                        <p style="text-align:center; margin-top:20px; margin-bottom:0;">
                            <a href="/vite-gourmand/connexion.php"
                               style="color:#1A5F7A; font-size:0.9rem; text-decoration:none;">
                                Retour a la connexion
                            </a>
                        </p>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
</main>

<?php require_once 'includes/footer.php'; ?>