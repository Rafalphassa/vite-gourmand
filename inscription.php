<?php
session_start();
require_once 'config/database.php';

$erreurs = [];
$succes  = false;
$nom = $prenom = $email = $tel = $adresse = $ville = $cp = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = trim($_POST['nom'] ?? '');
    $prenom  = trim($_POST['prenom'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $tel     = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville   = trim($_POST['ville'] ?? '');
    $cp      = trim($_POST['code_postal'] ?? '');
    $mdp     = $_POST['mot_de_passe'] ?? '';
    $mdp2    = $_POST['mot_de_passe2'] ?? '';

    if(empty($nom))    $erreurs[] = "Le nom est obligatoire.";
    if(empty($prenom)) $erreurs[] = "Le prénom est obligatoire.";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = "Email invalide.";
    if($mdp !== $mdp2) $erreurs[] = "Les mots de passe ne correspondent pas.";
    if(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/', $mdp)) {
        $erreurs[] = "Mot de passe : 10 caractères min, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial.";
    }

    if(empty($erreurs)) {
        $stmt = getDB()->prepare("SELECT utilisateur_id FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        if($stmt->fetch()) $erreurs[] = "Cet email est déjà utilisé.";
    }

    if(empty($erreurs)) {
        $hash = password_hash($mdp, PASSWORD_BCRYPT);
        $stmt = getDB()->prepare("
            INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, adresse, ville, code_postal, role_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 3)
        ");
        $stmt->execute([$nom, $prenom, $email, $hash, $tel, $adresse, $ville, $cp]);

        // Mail de bienvenue (simulé pour l'ECF)
        $sujet  = "Bienvenue chez Vite & Gourmand !";
        $corps  = "Bonjour $prenom,\n\nVotre compte a été créé avec succès.\nBienvenue chez Vite & Gourmand !\n\nL'équipe";
        $entete = "From: ne-pas-repondre@vitegourmand.fr";
        @mail($email, $sujet, $corps, $entete);

        $succes = true;
    }
}

require_once 'includes/header.php';
?>

<section class="py-5" style="min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div style="background:#fff; border-radius:15px; padding:40px; box-shadow:0 3px 20px rgba(0,0,0,0.08);">

                    <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:5px;">
                        Créer un compte
                    </h2>
                    <p style="color:#999; font-size:0.9rem; margin-bottom:30px;">
                        Rejoignez-nous pour commander nos menus.
                    </p>

                    <?php if($succes): ?>
                        <div style="background:#d4edda; color:#155724; border-radius:10px; padding:20px;">
                            Compte créé avec succès ! Un email de bienvenue vous a été envoyé.<br>
                            <a href="/vite-gourmand/connexion.php" style="color:#1A5F7A; font-weight:600;">
                                Se connecter
                            </a>
                        </div>
                    <?php else: ?>

                        <?php if(!empty($erreurs)): ?>
                            <div style="background:#f8d7da; color:#721c24; border-radius:10px; padding:15px; margin-bottom:20px;">
                                <?php foreach($erreurs as $e): ?>
                                    <div>- <?php echo htmlspecialchars($e); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Nom</label>
                                    <input type="text" name="nom" class="form-control"
                                           value="<?php echo htmlspecialchars($nom); ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Prénom</label>
                                    <input type="text" name="prenom" class="form-control"
                                           value="<?php echo htmlspecialchars($prenom); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Email</label>
                                    <input type="email" name="email" class="form-control"
                                           value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Téléphone (GSM)</label>
                                    <input type="tel" name="telephone" class="form-control"
                                           value="<?php echo htmlspecialchars($tel); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Adresse</label>
                                    <input type="text" name="adresse" class="form-control"
                                           value="<?php echo htmlspecialchars($adresse); ?>">
                                </div>
                                <div class="col-8">
                                    <label class="form-label small fw-bold">Ville</label>
                                    <input type="text" name="ville" class="form-control"
                                           value="<?php echo htmlspecialchars($ville); ?>">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-bold">Code postal</label>
                                    <input type="text" name="code_postal" class="form-control"
                                           value="<?php echo htmlspecialchars($cp); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Mot de passe</label>
                                    <input type="password" name="mot_de_passe" class="form-control" required>
                                    <div class="form-text" style="font-size:0.8rem; color:#999;">
                                        10 caractères min, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Confirmer le mot de passe</label>
                                    <input type="password" name="mot_de_passe2" class="form-control" required>
                                </div>
                                <div class="col-12 mt-2">
                                    <button type="submit" style="
                                        background:#1A5F7A; color:#fff; border:none;
                                        border-radius:25px; padding:12px 35px;
                                        font-weight:600; width:100%; font-size:1rem;
                                        cursor:pointer;
                                    ">Créer mon compte</button>
                                </div>
                                <div class="col-12 text-center">
                                    <p style="color:#999; font-size:0.9rem; margin:0;">
                                        Déjà un compte ?
                                        <a href="/vite-gourmand/connexion.php" style="color:#1A5F7A; font-weight:600;">
                                            Se connecter
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </form>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>