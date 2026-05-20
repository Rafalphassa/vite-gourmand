<?php
session_start();
require_once 'config/database.php';

// Vérification unique et correcte
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role_id'] != 3) {
    header('Location: /vite-gourmand/connexion.php');
    exit;
}

$user_id = $_SESSION['utilisateur_id'];

// Annulation commande (avant fetchAll pour cohérence)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'annuler') {
    $commande_id = (int)$_POST['commande_id'];
    $stmt = getDB()->prepare("SELECT statut FROM commande WHERE commande_id = ? AND utilisateur_id = ?");
    $stmt->execute([$commande_id, $user_id]);
    $cmd = $stmt->fetch();
    // CDC : annulation possible tant que pas "accepté"
    $non_annulables = ['accepté', 'en préparation', 'en cours de livraison', 'livré', 'terminée', 'annulée'];
    if ($cmd && !in_array($cmd['statut'], $non_annulables)) {
        getDB()->prepare("UPDATE commande SET statut = 'annulée' WHERE commande_id = ?")
            ->execute([$commande_id]);
        header('Location: /vite-gourmand/espace-user.php');
        exit;
    }
}

// Modification commande (sauf menu)
$succes_modif = false;
$erreurs_modif = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'modifier_commande') {
    $commande_id     = (int)$_POST['commande_id'];
    $date_prestation = trim($_POST['date_prestation'] ?? '');
    $heure_prestation= trim($_POST['heure_prestation'] ?? '');
    $adresse_prestation = trim($_POST['adresse_prestation'] ?? '');
    $nombre_personnes= (int)($_POST['nombre_personnes'] ?? 0);

    // Vérifier que la commande appartient à l'user et est modifiable
    $stmt = getDB()->prepare("SELECT c.*, m.nombre_personnes_minimum, m.prix_par_personne FROM commande c JOIN menu m ON c.menu_id = m.menu_id WHERE c.commande_id = ? AND c.utilisateur_id = ?");
    $stmt->execute([$commande_id, $user_id]);
    $cmd = $stmt->fetch();

    $non_modifiables = ['accepté', 'en préparation', 'en cours de livraison', 'livré', 'terminée', 'annulée'];
    if (!$cmd || in_array($cmd['statut'], $non_modifiables)) {
        $erreurs_modif[] = "Cette commande ne peut plus être modifiée.";
    } else {
        if (empty($date_prestation)) $erreurs_modif[] = "La date est obligatoire.";
        if (empty($heure_prestation)) $erreurs_modif[] = "L'heure est obligatoire.";
        if ($nombre_personnes < $cmd['nombre_personnes_minimum']) {
            $erreurs_modif[] = "Minimum " . $cmd['nombre_personnes_minimum'] . " personnes requis.";
        }
        if (empty($erreurs_modif)) {
            // Recalcul prix
            $prix_base = $cmd['prix_par_personne'] * $nombre_personnes;
            $min = $cmd['nombre_personnes_minimum'];
            if ($nombre_personnes >= $min + 5) {
                $prix_base *= 0.90;
            }
            // Frais livraison inchangés
            getDB()->prepare("
                UPDATE commande 
                SET date_prestation=?, heure_prestation=?, adresse_prestation=?, nombre_personnes=?, prix_menu=?, prix_total=prix_livraison+?
                WHERE commande_id=?
            ")->execute([$date_prestation, $heure_prestation, $adresse_prestation, $nombre_personnes, $prix_base, $prix_base, $commande_id]);
            $succes_modif = true;
            header('Location: /vite-gourmand/espace-user.php?modif=ok');
            exit;
        }
    }
}

// Modification profil
$succes_profil = false;
$erreurs_profil = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'modifier_profil') {
    $nom     = trim($_POST['nom'] ?? '');
    $prenom  = trim($_POST['prenom'] ?? '');
    $tel     = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville   = trim($_POST['ville'] ?? '');
    $cp      = trim($_POST['code_postal'] ?? '');

    if (empty($nom))    $erreurs_profil[] = "Le nom est obligatoire.";
    if (empty($prenom)) $erreurs_profil[] = "Le prénom est obligatoire.";

    if (empty($erreurs_profil)) {
        getDB()->prepare("
            UPDATE utilisateur SET nom=?, prenom=?, telephone=?, adresse=?, ville=?, code_postal=?
            WHERE utilisateur_id=?
        ")->execute([$nom, $prenom, $tel, $adresse, $ville, $cp, $user_id]);
        $_SESSION['nom']    = $nom;
        $_SESSION['prenom'] = $prenom;
        $succes_profil = true;
    }
}

// Récupération commandes
$stmt = getDB()->prepare("
    SELECT c.*, m.titre AS menu_titre, m.nombre_personnes_minimum
    FROM commande c
    JOIN menu m ON c.menu_id = m.menu_id
    WHERE c.utilisateur_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$commandes = $stmt->fetchAll();

// Récupération infos user
$stmt = getDB()->prepare("SELECT * FROM utilisateur WHERE utilisateur_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container">

        <h1 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:30px;">
            Mon Espace
        </h1>

        <?php if (isset($_GET['modif']) && $_GET['modif'] === 'ok'): ?>
        <div style="background:#d4edda; color:#155724; border-radius:8px; padding:12px; margin-bottom:20px;">
            Commande modifiée avec succès.
        </div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- Profil -->
            <div class="col-lg-4">
                <div style="background:#fff; border-radius:15px; padding:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
                    <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:20px;">Mon profil</h2>

                    <?php if ($succes_profil): ?>
                        <div style="background:#d4edda; color:#155724; border-radius:8px; padding:12px; margin-bottom:15px; font-size:0.9rem;">
                            Profil mis à jour.
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($erreurs_profil)): ?>
                        <div style="background:#f8d7da; color:#721c24; border-radius:8px; padding:12px; margin-bottom:15px; font-size:0.9rem;">
                            <?php foreach ($erreurs_profil as $e): ?>
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

            <!-- Commandes -->
            <div class="col-lg-8">
                <div style="background:#fff; border-radius:15px; padding:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
                    <h3 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:20px;">Mes commandes</h3>

                    <?php if (empty($commandes)): ?>
                        <p style="color:#999;">Vous n'avez pas encore de commande.</p>
                        <a href="/vite-gourmand/menus.php" style="color:#1A5F7A; font-weight:600;">Voir nos menus</a>
                    <?php else: ?>
                        <?php
                        $non_modifiables = ['accepté', 'en préparation', 'en cours de livraison', 'livré', 'terminée', 'annulée'];
                        $couleurs = [
                            'en attente'               => ['#fff3cd', '#856404'],
                            'accepté'                  => ['#d4edda', '#155724'],
                            'en préparation'           => ['#cce5ff', '#004085'],
                            'en cours de livraison'    => ['#e2d9f3', '#4a235a'],
                            'livré'                    => ['#d4edda', '#155724'],
                            'en attente du retour de matériel' => ['#ffeeba', '#856404'],
                            'terminée'                 => ['#d4edda', '#155724'],
                            'annulée'                  => ['#f8d7da', '#721c24'],
                        ];
                        foreach ($commandes as $c):
                            $statut = $c['statut'];
                            $bg  = $couleurs[$statut][0] ?? '#F0EDE4';
                            $txt = $couleurs[$statut][1] ?? '#333';
                        ?>
                        <div style="border:1px solid #F0EDE4; border-radius:12px; padding:20px; margin-bottom:15px;">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <strong style="color:#1A5F7A;"><?php echo htmlspecialchars($c['menu_titre']); ?></strong>
                                    <div style="font-size:0.85rem; color:#666; margin-top:4px;">
                                        N° <?php echo htmlspecialchars($c['numero_commande']); ?>
                                    </div>
                                    <div style="font-size:0.85rem; color:#666;">
                                        Le <?php echo date('d/m/Y', strtotime($c['date_prestation'])); ?>
                                        à <?php echo htmlspecialchars($c['heure_prestation']); ?>
                                    </div>
                                    <div style="font-size:0.85rem; color:#666;">
                                        <?php echo (int)$c['nombre_personnes']; ?> personnes —
                                        <strong><?php echo number_format($c['prix_total'], 2, ',', ' '); ?> €</strong>
                                    </div>
                                    <div style="font-size:0.85rem; color:#888;">
                                        <?php echo htmlspecialchars($c['adresse_prestation'] ?? ''); ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span style="background:<?php echo $bg; ?>; color:<?php echo $txt; ?>; padding:4px 12px; border-radius:20px; font-size:0.8rem; font-weight:600;">
                                        <?php echo htmlspecialchars($statut); ?>
                                    </span>

                                    <!-- Annulation -->
                                    <?php if (!in_array($statut, $non_modifiables)): ?>
                                    <form method="POST" style="margin-top:8px;">
                                        <input type="hidden" name="action" value="annuler">
                                        <input type="hidden" name="commande_id" value="<?php echo $c['commande_id']; ?>">
                                        <button type="submit"
                                            style="background:#f8d7da; color:#721c24; border:none; border-radius:20px; padding:4px 12px; font-size:0.8rem; cursor:pointer;"
                                            onclick="return confirm('Confirmer l\'annulation ?')">
                                            Annuler
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <!-- Modification (statut modifiable seulement) -->
                                    <?php if (!in_array($statut, $non_modifiables)): ?>
                                    <button type="button"
                                        style="background:#1A5F7A; color:#fff; border:none; border-radius:20px; padding:4px 12px; font-size:0.8rem; cursor:pointer; margin-top:6px;"
                                        onclick="toggleModif(<?php echo $c['commande_id']; ?>)">
                                        Modifier
                                    </button>
                                    <div id="modif-<?php echo $c['commande_id']; ?>" style="display:none; margin-top:12px; text-align:left;">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="modifier_commande">
                                            <input type="hidden" name="commande_id" value="<?php echo $c['commande_id']; ?>">
                                            <div class="mb-2">
                                                <label class="form-label small fw-bold">Date de prestation</label>
                                                <input type="date" name="date_prestation" class="form-control form-control-sm"
                                                    value="<?php echo htmlspecialchars($c['date_prestation']); ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-bold">Heure</label>
                                                <input type="time" name="heure_prestation" class="form-control form-control-sm"
                                                    value="<?php echo htmlspecialchars($c['heure_prestation']); ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-bold">Adresse de prestation</label>
                                                <input type="text" name="adresse_prestation" class="form-control form-control-sm"
                                                    value="<?php echo htmlspecialchars($c['adresse_prestation'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-bold">
                                                    Nombre de personnes (min. <?php echo (int)$c['nombre_personnes_minimum']; ?>)
                                                </label>
                                                <input type="number" name="nombre_personnes" class="form-control form-control-sm"
                                                    value="<?php echo (int)$c['nombre_personnes']; ?>"
                                                    min="<?php echo (int)$c['nombre_personnes_minimum']; ?>" required>
                                            </div>
                                            <button type="submit" style="background:#D4A853; color:#fff; border:none; border-radius:20px; padding:5px 15px; font-size:0.8rem; cursor:pointer;">
                                                Valider la modification
                                            </button>
                                        </form>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Suivi si accepté ou plus -->
                                    <?php if (in_array($statut, ['accepté', 'en préparation', 'en cours de livraison', 'livré', 'en attente du retour de matériel'])): ?>
                                    <?php
                                    $suivi = getDB()->prepare("SELECT statut, created_at FROM suivi_commande WHERE commande_id = ? ORDER BY created_at ASC");
                                    $suivi->execute([$c['commande_id']]);
                                    $etapes = $suivi->fetchAll();
                                    ?>
                                    <div style="margin-top:12px; font-size:0.8rem; background:#F0EDE4; border-radius:8px; padding:10px;">
                                        <strong style="color:#1A5F7A;">Suivi de commande</strong>
                                        <?php foreach ($etapes as $e): ?>
                                        <div style="margin-top:5px; color:#555;">
                                            <?php echo date('d/m/Y H:i', strtotime($e['created_at'])); ?> —
                                            <?php echo htmlspecialchars($e['statut']); ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Avis si terminée -->
                                    <?php if ($statut === 'terminée'): ?>
                                    <a href="/vite-gourmand/avis.php?commande_id=<?php echo $c['commande_id']; ?>"
                                        style="display:block; margin-top:8px; background:#1A5F7A; color:#fff; border-radius:20px; padding:4px 12px; font-size:0.8rem; text-decoration:none; text-align:center;">
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

<script>
function toggleModif(id) {
    var el = document.getElementById('modif-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>