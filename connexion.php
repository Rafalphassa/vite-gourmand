<?php
require_once 'config/database.php';

$erreur = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if(empty($email) || empty($mdp)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("
            SELECT u.*, r.libelle as role_libelle 
            FROM utilisateur u
            JOIN role r ON u.role_id = r.role_id
            WHERE u.email = ? AND u.actif = 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if($user && password_verify($mdp, $user['mot_de_passe'])) {
            $_SESSION['utilisateur_id'] = $user['utilisateur_id'];
            $_SESSION['nom']            = $user['nom'];
            $_SESSION['prenom']         = $user['prenom'];
            $_SESSION['email']          = $user['email'];
            $_SESSION['role_id']        = $user['role_id'];
            $_SESSION['role_libelle']   = $user['role_libelle'];

            if($user['role_id'] == 1) {
                header('Location: /vite-gourmand/espace-admin.php');
            } elseif($user['role_id'] == 2) {
                header('Location: /vite-gourmand/espace-employe.php');
            } else {
                header('Location: /vite-gourmand/espace-user.php');
            }
            exit;
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    }
}

require_once 'includes/header.php';
?>

<section class="py-5" style="min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div style="background:#fff; border-radius:15px; padding:40px; box-shadow:0 3px 20px rgba(0,0,0,0.08);">

                    <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:5px;">
                        Connexion
                    </h2>
                    <p style="color:#999; font-size:0.9rem; margin-bottom:30px;">
                        Accédez à votre espace personnel.
                    </p>

                    <?php if($erreur): ?>
                        <div style="background:#f8d7da; color:#721c24; border-radius:10px; padding:15px; margin-bottom:20px;">
                            <?php echo htmlspecialchars($erreur); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Mot de passe</label>
                            <input type="password" name="mot_de_passe" class="form-control" required>
                        </div>
                        <div class="mb-3 text-end">
                            <a href="/vite-gourmand/reinitialisation.php" style="color:#1A5F7A; font-size:0.85rem;">
                                Mot de passe oublié ?
                            </a>
                        </div>
                        <button type="submit" style="
                            background:#1A5F7A; color:#fff; border:none;
                            border-radius:25px; padding:12px 35px;
                            font-weight:600; width:100%; font-size:1rem;
                            cursor:pointer;
                        ">
                            Se connecter
                        </button>
                        <p class="text-center mt-3" style="color:#999; font-size:0.9rem;">
                            Pas encore de compte ?
                            <a href="/vite-gourmand/inscription.php" style="color:#1A5F7A; font-weight:600;">
                                S'inscrire
                            </a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>