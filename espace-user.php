<?php
require_once 'config/database.php';

if(!isset($_SESSION['utilisateur_id'])) {
    header('Location: /vite-gourmand/connexion.php');
    exit;
}

$user_id = $_SESSION['utilisateur_id'];

// Récupération des commandes de l'utilisateur
$commandes = $pdo->prepare("
    SELECT c.*, m.titre as menu_titre
    FROM commande c
    JOIN menu m ON c.menu_id = m.menu_id
    WHERE c.utilisateur_id = ?
    ORDER BY c.created_at DESC
");
$commandes->execute([$user_id]);
$commandes = $commandes->fetchAll();

// Modification profil
$succes_profil = false;
$erreurs_profil = [];

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier_profil') {
    $nom     = trim($_POST['nom'] ?? '');
    $prenom  = trim($_POST['prenom'] ?? '');
    $tel     = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville   = trim($_POST['ville'] ?? '');
    $cp      = trim($_POST['code_postal'] ?? '');

    if(empty($nom))    $erreurs_profil[] = "Le nom est obligatoire.";
    if(empty($prenom)) $erreurs_profil[] = "Le prénom est obligatoire.";

    if(empty($erreurs_profil)) {
        $pdo->prepare("
            UPDATE utilisateur SET nom=?, prenom=?, telephone=?, adresse=?, ville=?, code_postal=?
            WHERE utilisateur_id=?
        ")->execute([$nom, $prenom, $tel, $adresse, $ville, $cp, $user_id]);
        $_SESSION['nom']    = $nom;
        $_SESSION['prenom'] = $prenom;
        $succes_profil = true;
    }
}

// Annulation commande
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'annuler') {
    $commande_id = (int)$_POST['commande_id'];
    $stmt = $pdo->prepare("SELECT statut FROM commande WHERE commande_id = ? AND utilisateur_id = ?");
    $stmt->execute([$commande_id, $user_id]);
    $cmd = $stmt->fetch();
    if($cmd && $cmd['statut'] === 'en attente') {
        $pdo->prepare("UPDATE commande SET statut = 'annulée' WHERE commande_id = ?")
            ->execute([$commande_id]);
        header('Location: /vite-gourmand/espace-user.php');
        exit;
    }
}

// Récupération infos user à jour
$user = $pdo->prepare("SELECT * FROM utilisateur WHERE utilisateur_id = ?");
$user->execute([$user_id]);
$user = $user->fetch();

require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container">

        <h1 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:30px;">
            Mon Espace
        </h1>

        <div class="row g-4">

            <!-- Colonne gauche : profil -->
            <div class="col-lg-4">
                <div style="background:#fff; border-radius:15px; padding:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
                    <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:20px;">
                        Mon profil
                    </h2>

                    <?php if($succes_profil): ?>
                        <div style="background:#d4edda; color:#155724; border-radius:8px; padding:12px; margin-bottom:15px; font-size:0.9rem;">
                            Profil mis à jour.
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($erreurs_profil)): ?>
                        <div style="background:#f8d7da; color:#721c24; border-radius:8px; padding:12px; margin-bottom:15px; font-size:0.9rem;">
                            <?php foreach($erreurs_profil as $e): ?>
                                <div>- <?php echo htmlspecialchars($e); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="modifier_profil">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nom</label>
                            <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Prénom</label>
                            <input type="text" name="prenom" class="form-control" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control" value="<?php echo htmlspecialchars($user['telephone']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Adresse</label>
                            <input type="text" name="adresse" class="form-control" value="<?php echo htmlspecialchars($user['adresse']); ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Ville</label>
                            <input type="text" name="ville" class="form-control" value="<?php echo htmlspecialchars($user['ville']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Code postal</label>
                            <input type="text" name="code_postal" class="form-control" value="<?php echo htmlspecialchars($user['code_postal']); ?>">
                        </div>
                        <button type="submit" style="background:#1A5F7A; color:#fff; border:none; border-radius:25px; padding:10px 25px; font-weight:600; cursor:pointer; width:100%;">
                            Enregistrer
                        </button>
                    </form>
                </div>
            </div>

            <!-- Colonne droite : commandes -->
            <div class="col-lg-8">
                <div style="background:#fff; border-radius:15px; padding:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
                    <h3 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:20px;">
                        Mes commandes
                    </h3>

                    <?php if(empty($commandes)): ?>
                        <p style="color:#999;">Vous n'avez pas encore de commande.</p>
                        <a href="/vite-gourmand/menus.php" style="color:#1A5F7A; font-weight:600;">Voir nos menus</a>
                    <?php else: ?>
                        <?php foreach($commandes as $c): ?>
                        <div style="border:1px solid #F0EDE4; border-radius:12px; padding:20px; margin-bottom:15px;">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <strong style="color:#1A5F7A;"><?php echo htmlspecialchars($c['menu_titre']); ?></strong>
                                    <div style="font-size:0.85rem; color:#666; margin-top:5px;">
                                        N° <?php echo htmlspecialchars($c['numero_commande']); ?>
                                    </div>
                                    <div style="font-size:0.85rem; color:#666;">
                                        Le <?php echo date('d/m/Y', strtotime($c['date_prestation'])); ?>
                                        à <?php echo htmlspecialchars($c['heure_prestation']); ?>
                                    </div>
                                    <div style="font-size:0.85rem; color:#666;">
                                        <?php echo $c['nombre_personnes']; ?> personnes —
                                        <strong><?php echo number_format($c['prix_total'], 2, ',', ' '); ?> €</strong>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span style="
                                        background:<?php echo $c['statut'] === 'en attente' ? '#fff3cd' : ($c['statut'] === 'annulée' ? '#f8d7da' : '#d4edda'); ?>;
                                        color:<?php echo $c['statut'] === 'en attente' ? '#856404' : ($c['statut'] === 'annulée' ? '#721c24' : '#155724'); ?>;
                                        padding:4px 12px; border-radius:20px; font-size:0.8rem; font-weight:600;
                                    ">
                                        <?php echo htmlspecialchars($c['statut']); ?>
                                    </span>

                                    <?php if($c['statut'] === 'en attente'): ?>
                                    <form method="POST" style="margin-top:8px;">
                                        <input type="hidden" name="action" value="annuler">
                                        <input type="hidden" name="commande_id" value="<?php echo $c['commande_id']; ?>">
                                        <button type="submit" style="background:#f8d7da; color:#721c24; border:none; border-radius:20px; padding:4px 12px; font-size:0.8rem; cursor:pointer;"
                                                onclick="return confirm('Confirmer l\'annulation ?')">
                                            Annuler
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if($c['statut'] === 'accepté'): ?>
                                    <div style="margin-top:10px; font-size:0.8rem; color:#666;">
                                        <strong>Suivi :</strong> <?php echo htmlspecialchars($c['statut']); ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if($c['statut'] === 'terminée'): ?>
                                    <a href="/vite-gourmand/avis.php?commande_id=<?php echo $c['commande_id']; ?>" 
                                       style="display:block; margin-top:8px; background:#1A5F7A; color:#fff; border-radius:20px; padding:4px 12px; font-size:0.8rem; text-decoration:none;">
                                        Donner mon avis
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>