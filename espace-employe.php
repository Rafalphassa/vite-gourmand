<?php
session_start();
require_once 'config/database.php';

// Vérification unique : employé (2) ou admin (1)
if (!isset($_SESSION['utilisateur_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    header('Location: /vite-gourmand/connexion.php');
    exit;
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function enregistrer_suivi(PDO $pdo, int $commande_id, string $statut): void {
    $pdo->prepare("INSERT INTO suivi_commande (commande_id, statut, created_at) VALUES (?, ?, NOW())")
        ->execute([$commande_id, $statut]);
}

function envoyer_mail_materiel(string $email, string $prenom): void {
    $sujet  = "Retour de matériel — Vite & Gourmand";
    $corps  = "Bonjour $prenom,\n\nNous vous informons que du matériel a été prêté lors de votre prestation.\n"
             . "Conformément à nos conditions générales de vente, si le matériel n'est pas restitué "
             . "dans un délai de 10 jours ouvrés, des frais de 600 € vous seront facturés.\n\n"
             . "Pour organiser la restitution, merci de nous contacter directement.\n\n"
             . "Cordialement,\nL'équipe Vite & Gourmand";
    mail($email, $sujet, $corps, "From: contact@vitegourmand.fr");
}

// ── Actions POST ──────────────────────────────────────────────────────────────

$action = $_POST['action'] ?? '';

// Mise à jour statut commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'maj_statut') {
    $commande_id = (int)$_POST['commande_id'];
    $statut      = $_POST['statut'];
    $statuts_ok  = ['accepté', 'en préparation', 'en cours de livraison', 'livré',
                    'en attente du retour de matériel', 'terminée'];

    if (in_array($statut, $statuts_ok)) {
        getDB()->prepare("UPDATE commande SET statut = ? WHERE commande_id = ?")
            ->execute([$statut, $commande_id]);
        enregistrer_suivi(getDB(), $commande_id, $statut);

        // Mail automatique pour retour matériel
        if ($statut === 'en attente du retour de matériel') {
            $stmt = getDB()->prepare("SELECT u.email, u.prenom FROM commande c JOIN utilisateur u ON c.utilisateur_id = u.utilisateur_id WHERE c.commande_id = ?");
            $stmt->execute([$commande_id]);
            $dest = $stmt->fetch();
            if ($dest) {
                envoyer_mail_materiel($dest['email'], $dest['prenom']);
            }
        }
    }
    header('Location: /vite-gourmand/espace-employe.php');
    exit;
}

// Annulation commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'annuler_employe') {
    $commande_id  = (int)$_POST['commande_id'];
    $motif        = trim($_POST['motif'] ?? '');
    $mode_contact = trim($_POST['mode_contact'] ?? '');
    if (!empty($motif) && !empty($mode_contact)) {
        getDB()->prepare("UPDATE commande SET statut='annulée', motif_annulation=?, mode_contact=? WHERE commande_id=?")
            ->execute([$motif, $mode_contact, $commande_id]);
        enregistrer_suivi(getDB(), $commande_id, 'annulée');
    }
    header('Location: /vite-gourmand/espace-employe.php');
    exit;
}

// Validation / refus avis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'valider_avis') {
    $avis_id     = (int)$_POST['avis_id'];
    $statut_avis = $_POST['statut_avis'];
    if (in_array($statut_avis, ['validé', 'refusé'])) {
        getDB()->prepare("UPDATE avis SET statut = ? WHERE avis_id = ?")
            ->execute([$statut_avis, $avis_id]);
    }
    header('Location: /vite-gourmand/espace-employe.php');
    exit;
}

// Suppression menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'supprimer_menu') {
    $menu_id = (int)$_POST['menu_id'];
    getDB()->prepare("DELETE FROM menu WHERE menu_id = ?")->execute([$menu_id]);
    header('Location: /vite-gourmand/espace-employe.php');
    exit;
}

// Suppression plat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'supprimer_plat') {
    $plat_id = (int)$_POST['plat_id'];
    getDB()->prepare("DELETE FROM plat WHERE plat_id = ?")->execute([$plat_id]);
    header('Location: /vite-gourmand/espace-employe.php');
    exit;
}

// Modification horaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'modifier_horaire') {
    $horaire_id    = (int)$_POST['horaire_id'];
    $heure_ouv     = trim($_POST['heure_ouverture'] ?? '');
    $heure_ferm    = trim($_POST['heure_fermeture'] ?? '');
    if (!empty($heure_ouv) && !empty($heure_ferm)) {
        getDB()->prepare("UPDATE horaire SET heure_ouverture=?, heure_fermeture=? WHERE horaire_id=?")
            ->execute([$heure_ouv, $heure_ferm, $horaire_id]);
    }
    header('Location: /vite-gourmand/espace-employe.php');
    exit;
}

// ── Données affichage ─────────────────────────────────────────────────────────

// Filtre commandes
$where  = ["1=1"];
$params = [];
if (!empty($_GET['statut'])) {
    $where[]  = "c.statut = ?";
    $params[] = $_GET['statut'];
}
if (!empty($_GET['client'])) {
    $where[]  = "(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
    $params[] = '%' . $_GET['client'] . '%';
    $params[] = '%' . $_GET['client'] . '%';
    $params[] = '%' . $_GET['client'] . '%';
}

$stmt = getDB()->prepare("
    SELECT c.*, m.titre AS menu_titre, u.nom, u.prenom, u.email, u.telephone
    FROM commande c
    JOIN menu m ON c.menu_id = m.menu_id
    JOIN utilisateur u ON c.utilisateur_id = u.utilisateur_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.created_at DESC
");
$stmt->execute($params);
$commandes = $stmt->fetchAll();

// Avis en attente
$avis = getDB()->query("
    SELECT a.*, u.prenom, u.nom
    FROM avis a
    JOIN utilisateur u ON a.utilisateur_id = u.utilisateur_id
    WHERE a.statut = 'en attente'
")->fetchAll();

// Menus
$menus = getDB()->query("SELECT menu_id, titre FROM menu ORDER BY titre")->fetchAll();

// Plats
$plats = getDB()->query("SELECT plat_id, titre FROM plat ORDER BY titre")->fetchAll();

// Horaires
$horaires = getDB()->query("SELECT * FROM horaire ORDER BY horaire_id")->fetchAll();

require_once 'includes/header.php';
?>

<section class="py-5">
<div class="container">

    <h1 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:30px;">
        Espace Employé
    </h1>

    <!-- ── FILTRES COMMANDES ─────────────────────────────────────────────── -->
    <div style="background:#fff; border-radius:15px; padding:20px; margin-bottom:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Filtrer par statut</label>
                <select name="statut" class="form-select">
                    <option value="">Tous</option>
                    <?php foreach (['en attente','accepté','en préparation','en cours de livraison','livré','en attente du retour de matériel','terminée','annulée'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo (($_GET['statut'] ?? '') === $s) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($s); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Rechercher un client</label>
                <input type="text" name="client" class="form-control"
                    value="<?php echo htmlspecialchars($_GET['client'] ?? ''); ?>"
                    placeholder="Nom, prénom ou email">
            </div>
            <div class="col-md-2">
                <button type="submit" style="background:#1A5F7A; color:#fff; border:none; border-radius:25px; padding:10px 20px; font-weight:600; cursor:pointer; width:100%;">
                    Filtrer
                </button>
            </div>
            <div class="col-md-2">
                <a href="/vite-gourmand/espace-employe.php"
                    style="background:#F0EDE4; color:#1A5F7A; border-radius:25px; padding:10px 15px; font-weight:600; text-decoration:none; display:block; text-align:center;">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- ── COMMANDES ────────────────────────────────────────────────────── -->
    <div style="background:#fff; border-radius:15px; padding:30px; margin-bottom:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:20px;">Commandes</h2>

        <?php if (empty($commandes)): ?>
            <p style="color:#999;">Aucune commande trouvée.</p>
        <?php else: ?>
            <?php foreach ($commandes as $c): ?>
            <div style="border:1px solid #F0EDE4; border-radius:12px; padding:20px; margin-bottom:15px;">
                <div class="row g-3 align-items-start">

                    <!-- Infos commande -->
                    <div class="col-md-4">
                        <strong style="color:#1A5F7A;"><?php echo htmlspecialchars($c['menu_titre']); ?></strong>
                        <div style="font-size:0.85rem; color:#666; margin-top:5px;">
                            N° <?php echo htmlspecialchars($c['numero_commande']); ?><br>
                            Client : <?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?><br>
                            Tel : <?php echo htmlspecialchars($c['telephone']); ?><br>
                            <?php echo date('d/m/Y', strtotime($c['date_prestation'])); ?>
                            à <?php echo htmlspecialchars($c['heure_prestation']); ?><br>
                            <?php echo (int)$c['nombre_personnes']; ?> personnes —
                            <strong><?php echo number_format($c['prix_total'], 2, ',', ' '); ?> €</strong>
                        </div>
                        <!-- Motif annulation si présent -->
                        <?php if ($c['statut'] === 'annulée' && !empty($c['motif_annulation'])): ?>
                        <div style="font-size:0.8rem; color:#721c24; margin-top:6px; background:#f8d7da; border-radius:6px; padding:6px 10px;">
                            Annulée — <?php echo htmlspecialchars($c['mode_contact']); ?> :
                            <?php echo htmlspecialchars($c['motif_annulation']); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mise à jour statut -->
                    <div class="col-md-4">
                        <?php if ($c['statut'] !== 'annulée'): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="maj_statut">
                            <input type="hidden" name="commande_id" value="<?php echo $c['commande_id']; ?>">
                            <label class="form-label small fw-bold">Statut</label>
                            <select name="statut" class="form-select form-select-sm">
                                <?php foreach (['en attente','accepté','en préparation','en cours de livraison','livré','en attente du retour de matériel','terminée'] as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo ($c['statut'] === $s) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($s); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" style="background:#1A5F7A; color:#fff; border:none; border-radius:20px; padding:5px 15px; font-size:0.85rem; cursor:pointer; margin-top:8px;">
                                Mettre à jour
                            </button>
                        </form>
                        <?php else: ?>
                            <span style="font-size:0.85rem; color:#721c24; font-weight:600;">Commande annulée</span>
                        <?php endif; ?>
                    </div>

                    <!-- Annulation (uniquement si pas encore acceptée) -->
                    <?php if (!in_array($c['statut'], ['accepté','en préparation','en cours de livraison','livré','en attente du retour de matériel','terminée','annulée'])): ?>
                    <div class="col-md-4">
                        <form method="POST">
                            <input type="hidden" name="action" value="annuler_employe">
                            <input type="hidden" name="commande_id" value="<?php echo $c['commande_id']; ?>">
                            <label class="form-label small fw-bold">Mode de contact</label>
                            <select name="mode_contact" class="form-select form-select-sm mb-2">
                                <option value="appel GSM">Appel GSM</option>
                                <option value="mail">Mail</option>
                            </select>
                            <label class="form-label small fw-bold">Motif d'annulation</label>
                            <input type="text" name="motif" class="form-control form-control-sm mb-2"
                                placeholder="Motif obligatoire" required>
                            <button type="submit"
                                style="background:#f8d7da; color:#721c24; border:none; border-radius:20px; padding:5px 15px; font-size:0.85rem; cursor:pointer;"
                                onclick="return confirm('Confirmer l\'annulation ?')">
                                Annuler la commande
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── GESTION MENUS ─────────────────────────────────────────────────── -->
    <div style="background:#fff; border-radius:15px; padding:30px; margin-bottom:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin:0;">Gestion des menus</h3>
            <a href="/vite-gourmand/menu-form.php"
                style="background:#D4A853; color:#fff; border-radius:25px; padding:8px 20px; font-size:0.9rem; font-weight:600; text-decoration:none;">
                + Nouveau menu
            </a>
        </div>
        <?php if (empty($menus)): ?>
            <p style="color:#999;">Aucun menu.</p>
        <?php else: ?>
            <?php foreach ($menus as $m): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #F0EDE4; padding:10px 0;">
                <span style="color:#333;"><?php echo htmlspecialchars($m['titre']); ?></span>
                <div class="d-flex gap-2">
                    <a href="/vite-gourmand/menu-form.php?id=<?php echo $m['menu_id']; ?>"
                        style="background:#1A5F7A; color:#fff; border-radius:20px; padding:4px 14px; font-size:0.8rem; text-decoration:none;">
                        Modifier
                    </a>
                    <form method="POST" style="margin:0;"
                        onsubmit="return confirm('Supprimer ce menu ?')">
                        <input type="hidden" name="action" value="supprimer_menu">
                        <input type="hidden" name="menu_id" value="<?php echo $m['menu_id']; ?>">
                        <button type="submit"
                            style="background:#f8d7da; color:#721c24; border:none; border-radius:20px; padding:4px 14px; font-size:0.8rem; cursor:pointer;">
                            Supprimer
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── GESTION PLATS ─────────────────────────────────────────────────── -->
    <div style="background:#fff; border-radius:15px; padding:30px; margin-bottom:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin:0;">Gestion des plats</h3>
            <a href="/vite-gourmand/plat-form.php"
                style="background:#D4A853; color:#fff; border-radius:25px; padding:8px 20px; font-size:0.9rem; font-weight:600; text-decoration:none;">
                + Nouveau plat
            </a>
        </div>
        <?php if (empty($plats)): ?>
            <p style="color:#999;">Aucun plat.</p>
        <?php else: ?>
            <?php foreach ($plats as $p): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #F0EDE4; padding:10px 0;">
                <span style="color:#333;"><?php echo htmlspecialchars($p['titre']); ?></span>
                <div class="d-flex gap-2">
                    <a href="/vite-gourmand/plat-form.php?id=<?php echo $p['plat_id']; ?>"
                        style="background:#1A5F7A; color:#fff; border-radius:20px; padding:4px 14px; font-size:0.8rem; text-decoration:none;">
                        Modifier
                    </a>
                    <form method="POST" style="margin:0;"
                        onsubmit="return confirm('Supprimer ce plat ?')">
                        <input type="hidden" name="action" value="supprimer_plat">
                        <input type="hidden" name="plat_id" value="<?php echo $p['plat_id']; ?>">
                        <button type="submit"
                            style="background:#f8d7da; color:#721c24; border:none; border-radius:20px; padding:4px 14px; font-size:0.8rem; cursor:pointer;">
                            Supprimer
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── HORAIRES ──────────────────────────────────────────────────────── -->
    <div style="background:#fff; border-radius:15px; padding:30px; margin-bottom:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
        <h3 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:20px;">Horaires</h3>
        <?php if (empty($horaires)): ?>
            <p style="color:#999;">Aucun horaire enregistré.</p>
        <?php else: ?>
            <?php foreach ($horaires as $h): ?>
            <form method="POST" class="row g-2 align-items-end mb-3">
                <input type="hidden" name="action" value="modifier_horaire">
                <input type="hidden" name="horaire_id" value="<?php echo $h['horaire_id']; ?>">
                <div class="col-md-3">
                    <label class="form-label small fw-bold"><?php echo htmlspecialchars($h['jour']); ?></label>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Ouverture</label>
                    <input type="time" name="heure_ouverture" class="form-control form-control-sm"
                        value="<?php echo htmlspecialchars($h['heure_ouverture']); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Fermeture</label>
                    <input type="time" name="heure_fermeture" class="form-control form-control-sm"
                        value="<?php echo htmlspecialchars($h['heure_fermeture']); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" style="background:#1A5F7A; color:#fff; border:none; border-radius:20px; padding:6px 15px; font-size:0.85rem; cursor:pointer; width:100%;">
                        Enregistrer
                    </button>
                </div>
            </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── AVIS EN ATTENTE ───────────────────────────────────────────────── -->
    <div style="background:#fff; border-radius:15px; padding:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
        <h3 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:20px;">Avis en attente de validation</h3>

        <?php if (empty($avis)): ?>
            <p style="color:#999;">Aucun avis en attente.</p>
        <?php else: ?>
            <?php foreach ($avis as $a): ?>
            <div style="border:1px solid #F0EDE4; border-radius:12px; padding:20px; margin-bottom:15px;">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <strong><?php echo htmlspecialchars($a['prenom'] . ' ' . $a['nom']); ?></strong>
                        <div style="color:#D4A853;">
                            <?php echo str_repeat('★', (int)$a['note']) . str_repeat('☆', 5 - (int)$a['note']); ?>
                        </div>
                        <p style="color:#555; font-size:0.9rem; margin:8px 0 0; font-style:italic;">
                            "<?php echo htmlspecialchars($a['commentaire']); ?>"
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="POST">
                            <input type="hidden" name="action" value="valider_avis">
                            <input type="hidden" name="avis_id" value="<?php echo $a['avis_id']; ?>">
                            <input type="hidden" name="statut_avis" value="validé">
                            <button type="submit" style="background:#d4edda; color:#155724; border:none; border-radius:20px; padding:6px 15px; font-size:0.85rem; cursor:pointer;">
                                Valider
                            </button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="action" value="valider_avis">
                            <input type="hidden" name="avis_id" value="<?php echo $a['avis_id']; ?>">
                            <input type="hidden" name="statut_avis" value="refusé">
                            <button type="submit" style="background:#f8d7da; color:#721c24; border:none; border-radius:20px; padding:6px 15px; font-size:0.85rem; cursor:pointer;">
                                Refuser
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</section>

<?php require_once 'includes/footer.php'; ?>