<?php
require_once 'config/database.php';

if(!isset($_SESSION['utilisateur_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    header('Location: /vite-gourmand/');
    exit;
}

// Filtre commandes
$where  = ["1=1"];
$params = [];

if(!empty($_GET['statut'])) {
    $where[] = "c.statut = ?";
    $params[] = $_GET['statut'];
}
if(!empty($_GET['client'])) {
    $where[] = "(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
    $params[] = '%'.$_GET['client'].'%';
    $params[] = '%'.$_GET['client'].'%';
    $params[] = '%'.$_GET['client'].'%';
}

$stmt = $pdo->prepare("
    SELECT c.*, m.titre as menu_titre, u.nom, u.prenom, u.email, u.telephone
    FROM commande c
    JOIN menu m ON c.menu_id = m.menu_id
    JOIN utilisateur u ON c.utilisateur_id = u.utilisateur_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.created_at DESC
");
$stmt->execute($params);
$commandes = $stmt->fetchAll();

// Mise à jour statut
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'maj_statut') {
    $commande_id = (int)$_POST['commande_id'];
    $statut      = $_POST['statut'];
    $statuts_ok  = ['accepté', 'en préparation', 'en cours de livraison', 'livré', 'en attente du retour de matériel', 'terminée'];
    if(in_array($statut, $statuts_ok)) {
        $pdo->prepare("UPDATE commande SET statut = ? WHERE commande_id = ?")
            ->execute([$statut, $commande_id]);
    }
    header('Location: /vite-gourmand/espace-employe.php');
    exit;
}

// Annulation commande employé
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'annuler_employe') {
    $commande_id    = (int)$_POST['commande_id'];
    $motif          = trim($_POST['motif'] ?? '');
    $mode_contact   = trim($_POST['mode_contact'] ?? '');
    if(!empty($motif) && !empty($mode_contact)) {
        $pdo->prepare("UPDATE commande SET statut='annulée', motif_annulation=?, mode_contact=? WHERE commande_id=?")
            ->execute([$motif, $mode_contact, $commande_id]);
    }
    header('Location: /vite-gourmand/espace-employe.php');
    exit;
}

// Validation avis
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'valider_avis') {
    $avis_id = (int)$_POST['avis_id'];
    $statut  = $_POST['statut_avis'];
    if(in_array($statut, ['validé', 'refusé'])) {
        $pdo->prepare("UPDATE avis SET statut = ? WHERE avis_id = ?")
            ->execute([$statut, $avis_id]);
    }
    header('Location: /vite-gourmand/espace-employe.php');
    exit;
}

$avis = $pdo->query("
    SELECT a.*, u.prenom, u.nom
    FROM avis a
    JOIN utilisateur u ON a.utilisateur_id = u.utilisateur_id
    WHERE a.statut = 'en attente'
")->fetchAll();

require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container">

        <h1 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:30px;">
            Espace Employé
        </h1>

        <!-- Filtres commandes -->
        <div style="background:#fff; border-radius:15px; padding:20px; margin-bottom:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Filtrer par statut</label>
                    <select name="statut" class="form-select">
                        <option value="">Tous</option>
                        <?php foreach(['en attente','accepté','en préparation','en cours de livraison','livré','en attente du retour de matériel','terminée','annulée'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo (($_GET['statut'] ?? '') === $s) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($s); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Rechercher un client</label>
                    <input type="text" name="client" class="form-control" value="<?php echo htmlspecialchars($_GET['client'] ?? ''); ?>" placeholder="Nom, prénom ou email">
                </div>
                <div class="col-md-2">
                    <button type="submit" style="background:#1A5F7A; color:#fff; border:none; border-radius:25px; padding:10px 20px; font-weight:600; cursor:pointer; width:100%;">
                        Filtrer
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="/vite-gourmand/espace-employe.php" style="background:#F0EDE4; color:#1A5F7A; border-radius:25px; padding:10px 15px; font-weight:600; text-decoration:none; display:block; text-align:center;">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Liste commandes -->
        <div style="background:#fff; border-radius:15px; padding:30px; margin-bottom:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
            <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:20px;">Commandes</h2>

            <?php if(empty($commandes)): ?>
                <p style="color:#999;">Aucune commande trouvée.</p>
            <?php else: ?>
                <?php foreach($commandes as $c): ?>
                <div style="border:1px solid #F0EDE4; border-radius:12px; padding:20px; margin-bottom:15px;">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-5">
                            <strong style="color:#1A5F7A;"><?php echo htmlspecialchars($c['menu_titre']); ?></strong>
                            <div style="font-size:0.85rem; color:#666; margin-top:5px;">
                                N° <?php echo htmlspecialchars($c['numero_commande']); ?><br>
                                Client : <?php echo htmlspecialchars($c['prenom'].' '.$c['nom']); ?><br>
                                Tel : <?php echo htmlspecialchars($c['telephone']); ?><br>
                                Le <?php echo date('d/m/Y', strtotime($c['date_prestation'])); ?> à <?php echo htmlspecialchars($c['heure_prestation']); ?><br>
                                <?php echo $c['nombre_personnes']; ?> personnes — <strong><?php echo number_format($c['prix_total'], 2, ',', ' '); ?> €</strong>
                            </div>
                        </div>

                        <!-- Mise à jour statut -->
                        <div class="col-md-4">
                            <form method="POST">
                                <input type="hidden" name="action" value="maj_statut">
                                <input type="hidden" name="commande_id" value="<?php echo $c['commande_id']; ?>">
                                <label class="form-label small fw-bold">Statut</label>
                                <select name="statut" class="form-select form-select-sm">
                                    <?php foreach(['en attente','accepté','en préparation','en cours de livraison','livré','en attente du retour de matériel','terminée'] as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo ($c['statut'] === $s) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($s); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" style="background:#1A5F7A; color:#fff; border:none; border-radius:20px; padding:5px 15px; font-size:0.85rem; cursor:pointer; margin-top:8px;">
                                    Mettre à jour
                                </button>
                            </form>
                        </div>

                        <!-- Annulation -->
                        <?php if($c['statut'] === 'en attente'): ?>
                        <div class="col-md-3">
                            <form method="POST">
                                <input type="hidden" name="action" value="annuler_employe">
                                <input type="hidden" name="commande_id" value="<?php echo $c['commande_id']; ?>">
                                <label class="form-label small fw-bold">Mode de contact</label>
                                <select name="mode_contact" class="form-select form-select-sm mb-2">
                                    <option value="appel GSM">Appel GSM</option>
                                    <option value="mail">Mail</option>
                                </select>
                                <label class="form-label small fw-bold">Motif</label>
                                <input type="text" name="motif" class="form-control form-control-sm mb-2" placeholder="Motif d'annulation" required>
                                <button type="submit" style="background:#f8d7da; color:#721c24; border:none; border-radius:20px; padding:5px 15px; font-size:0.85rem; cursor:pointer;"
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

        <!-- Avis en attente -->
        <div style="background:#fff; border-radius:15px; padding:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
            <h3 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:20px;">Avis en attente de validation</h3>

            <?php if(empty($avis)): ?>
                <p style="color:#999;">Aucun avis en attente.</p>
            <?php else: ?>
                <?php foreach($avis as $a): ?>
                <div style="border:1px solid #F0EDE4; border-radius:12px; padding:20px; margin-bottom:15px;">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <strong><?php echo htmlspecialchars($a['prenom'].' '.$a['nom']); ?></strong>
                            <div style="color:#D4A853;"><?php echo str_repeat('★', $a['note']) . str_repeat('☆', 5 - $a['note']); ?></div>
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