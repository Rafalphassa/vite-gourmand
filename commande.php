<?php
session_start();
require_once 'config/database.php';

$pdo = getDB();

// Vérification que l'utilisateur est connecté
if(!isset($_SESSION['utilisateur_id'])) {
    header('Location: /vite-gourmand/connexion.php?redirect=commande');
    exit;
}

$menu_id = isset($_GET['menu_id']) ? (int)$_GET['menu_id'] : 0;
if($menu_id === 0) {
    header('Location: /vite-gourmand/menus.php');
    exit;
}

// Récupération du menu
$stmt = $pdo->prepare("SELECT * FROM menu WHERE menu_id = ? AND actif = 1");
$stmt->execute([$menu_id]);
$menu = $stmt->fetch();

if(!$menu) {
    header('Location: /vite-gourmand/menus.php');
    exit;
}

// Récupération des infos utilisateur connecté
$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE utilisateur_id = ?");
$stmt->execute([$_SESSION['utilisateur_id']]);
$user = $stmt->fetch();

$erreurs = [];
$succes  = false;

$erreurs = [];
$succes  = false;
$numero_commande = '';   // AJOUTER
$recap = [];             // AJOUTER

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adresse_prestation = trim($_POST['adresse_prestation'] ?? '');
    $ville_prestation   = trim($_POST['ville_prestation'] ?? '');
    $date_prestation    = $_POST['date_prestation'] ?? '';
    $heure_prestation   = $_POST['heure_prestation'] ?? '';
    $nb_personnes       = (int)($_POST['nb_personnes'] ?? 0);
    $km_livraison       = (float)($_POST['km_livraison'] ?? 0);

    if(empty($adresse_prestation)) $erreurs[] = "L'adresse de prestation est obligatoire.";
    if(empty($ville_prestation))   $erreurs[] = "La ville de prestation est obligatoire.";
    if(empty($date_prestation))    $erreurs[] = "La date de prestation est obligatoire.";
    if(empty($heure_prestation))   $erreurs[] = "L'heure de prestation est obligatoire.";
    if($date_prestation && $date_prestation <= date('Y-m-d')) {
        $erreurs[] = "La date de prestation doit être dans le futur.";
    }
    if($nb_personnes < $menu['nombre_personne_minimum']) {
        $erreurs[] = "Le nombre minimum de personnes pour ce menu est " . $menu['nombre_personne_minimum'] . ".";
    }
    $hors_bordeaux = strtolower(trim($ville_prestation)) !== 'bordeaux';
    if($hors_bordeaux && $km_livraison <= 0) {
        $erreurs[] = "Veuillez indiquer la distance en km (hors Bordeaux).";
    }

    if(empty($erreurs)) {
        // Calcul du prix
        $prix_menu = $menu['prix_par_personne'] * $nb_personnes;

        // Réduction 10% si 5 personnes de plus que le minimum
        if($nb_personnes >= ($menu['nombre_personne_minimum'] + 5)) {
            $prix_menu = $prix_menu * 0.90;
        }

        // Frais de livraison : 5€ + 0.59€/km si hors Bordeaux
        $prix_livraison = 0;
        if($hors_bordeaux) {
            $prix_livraison = 5.00 + ($km_livraison * 0.59);
        }

        $prix_total = $prix_menu + $prix_livraison;

        // Génération numéro de commande unique
        $numero = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        $stmt = $pdo->prepare("
            INSERT INTO commande 
            (numero_commande, utilisateur_id, menu_id, date_prestation, heure_prestation, 
             adresse_prestation, ville_prestation, nombre_personnes, 
             prix_menu, prix_livraison, prix_total, statut)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en attente')
        ");
        $stmt->execute([
            $numero,
            $_SESSION['utilisateur_id'],
            $menu_id,
            $date_prestation,
            $heure_prestation,
            $adresse_prestation,
            $ville_prestation,
            $nb_personnes,
            round($prix_menu, 2),
            round($prix_livraison, 2),
            round($prix_total, 2)
        ]);

        // Décrémenter le stock (1 commande = -1)
        $pdo->prepare("UPDATE menu SET quantite_restante = quantite_restante - 1 WHERE menu_id = ? AND quantite_restante > 0")
            ->execute([$menu_id]);

        $succes = true;
        $numero_commande = $numero;
        $recap = [
            'prix_menu'      => round($prix_menu, 2),
            'prix_livraison' => round($prix_livraison, 2),
            'prix_total'     => round($prix_total, 2),
            'nb_personnes'   => $nb_personnes,
            'reduction'      => ($nb_personnes >= ($menu['nombre_personne_minimum'] + 5)),
        ];
    }
}

require_once 'includes/header.php';
?>

<section class="py-5" style="min-height:80vh; background:#F7F5F0;">
<div class="container">
<div class="row justify-content-center">
<div class="col-lg-8">

<?php if($succes): ?>
<!-- Confirmation -->
<div style="background:#fff; border-radius:16px; padding:50px 40px; text-align:center; box-shadow:0 4px 24px rgba(0,0,0,0.07);">
    <div style="width:70px; height:70px; background:#e8f5e9; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 25px;">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
    </div>
    <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:10px;">Commande confirmée !</h2>
    <p style="color:#666; margin:0 0 5px;">Numéro de commande : <strong style="color:#1A5F7A;"><?php echo htmlspecialchars($numero_commande); ?></strong></p>
    <p style="color:#999; font-size:0.9rem; margin-bottom:25px;">Un email de confirmation vous a été envoyé.</p>

    <!-- Récap final -->
    <div style="background:#F0EDE4; border-radius:10px; padding:20px; text-align:left; margin-bottom:30px;">
        <strong style="color:#1A5F7A; display:block; margin-bottom:12px;">Récapitulatif</strong>
        <div style="display:flex; justify-content:space-between; font-size:0.9rem; padding:5px 0; border-bottom:1px solid #e0dbd0;">
            <span>Menu (<?php echo $recap['nb_personnes']; ?> personnes)</span>
            <span><?php echo number_format($recap['prix_menu'], 2, ',', ' '); ?> €</span>
        </div>
        <?php if($recap['reduction']): ?>
        <div style="display:flex; justify-content:space-between; font-size:0.85rem; padding:5px 0; color:#2e7d32; border-bottom:1px solid #e0dbd0;">
            <span>Réduction 10% appliquée</span>
            <span>incluse</span>
        </div>
        <?php endif; ?>
        <div style="display:flex; justify-content:space-between; font-size:0.9rem; padding:5px 0; border-bottom:1px solid #e0dbd0;">
            <span>Livraison</span>
            <span><?php echo $recap['prix_livraison'] > 0 ? number_format($recap['prix_livraison'], 2, ',', ' ') . ' €' : 'Gratuite'; ?></span>
        </div>
        <div style="display:flex; justify-content:space-between; font-weight:700; font-size:1rem; padding:10px 0 0; color:#1A5F7A;">
            <span>Total</span>
            <span><?php echo number_format($recap['prix_total'], 2, ',', ' '); ?> €</span>
        </div>
    </div>

    <a href="/vite-gourmand/espace-user.php" style="background:#1A5F7A; color:#fff; border-radius:25px; padding:13px 35px; text-decoration:none; font-weight:600; display:inline-block;">
        Voir mes commandes
    </a>
</div>

<?php else: ?>

<!-- Formulaire commande -->
<div style="background:#fff; border-radius:16px; padding:40px; box-shadow:0 4px 24px rgba(0,0,0,0.07);">

    <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:4px;">Commander un menu</h2>
    <p style="color:#999; font-size:0.9rem; margin-bottom:28px;">
        Menu : <strong style="color:#1A5F7A;"><?php echo htmlspecialchars($menu['titre']); ?></strong>
        &nbsp;|&nbsp; Prix : <strong><?php echo number_format($menu['prix_par_personne'], 2, ',', ' '); ?> € / pers.</strong>
        &nbsp;|&nbsp; Min. : <strong><?php echo $menu['nombre_personne_minimum']; ?> pers.</strong>
    </p>

    <!-- Erreurs -->
    <?php if(!empty($erreurs)): ?>
    <div style="background:#f8d7da; color:#721c24; border-radius:10px; padding:15px; margin-bottom:20px; font-size:0.9rem;">
        <?php foreach($erreurs as $e): ?>
        <div>&#8212; <?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Conditions du menu bien en évidence (CDC : obligatoire) -->
    <?php if(!empty($menu['conditions'])): ?>
    <div style="background:#fff8e1; border-left:5px solid #D4A853; border-radius:8px; padding:16px 18px; margin-bottom:28px;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#D4A853" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <strong style="color:#856404;">Conditions importantes à lire avant de commander</strong>
        </div>
        <p style="color:#856404; font-size:0.88rem; margin:0; line-height:1.6;">
            <?php echo nl2br(htmlspecialchars($menu['conditions'])); ?>
        </p>
    </div>
    <?php endif; ?>

    <form method="POST" id="formCommande">

        <!-- Infos client (lecture seule) -->
        <div style="background:#F7F5F0; border-radius:10px; padding:18px; margin-bottom:24px;">
            <p style="font-size:0.8rem; font-weight:700; color:#1A5F7A; text-transform:uppercase; letter-spacing:.05em; margin-bottom:12px;">Vos informations</p>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nom</label>
                    <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($user['nom']); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Prénom</label>
                    <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($user['prenom']); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Email</label>
                    <input type="email" class="form-control form-control-sm" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Téléphone</label>
                    <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($user['telephone']); ?>" disabled>
                </div>
            </div>
        </div>

        <!-- Infos prestation -->
        <p style="font-size:0.8rem; font-weight:700; color:#1A5F7A; text-transform:uppercase; letter-spacing:.05em; margin-bottom:12px;">Informations de la prestation</p>
        <div class="row g-3">

            <div class="col-12">
                <label class="form-label small fw-bold">Adresse de la prestation <span style="color:#dc3545;">*</span></label>
                <input type="text" name="adresse_prestation" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['adresse_prestation'] ?? ''); ?>"
                       placeholder="ex: 12 rue des Fleurs" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold">Ville <span style="color:#dc3545;">*</span></label>
                <input type="text" name="ville_prestation" id="villeInput" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['ville_prestation'] ?? ''); ?>"
                       placeholder="ex: Bordeaux" required>
            </div>

            <!-- Champ km visible seulement si hors Bordeaux -->
            <div class="col-md-6" id="zoneKm" style="display:<?php echo (!empty($_POST['ville_prestation']) && strtolower(trim($_POST['ville_prestation'])) !== 'bordeaux') ? 'block' : 'none'; ?>;">
                <label class="form-label small fw-bold">Distance estimée (km) <span style="color:#dc3545;">*</span></label>
                <input type="number" name="km_livraison" id="kmInput" class="form-control"
                       min="1" step="1"
                       value="<?php echo htmlspecialchars($_POST['km_livraison'] ?? ''); ?>"
                       placeholder="ex: 15">
                <div class="form-text" style="font-size:0.78rem; color:#999;">Frais : 5 € + 0,59 € par km</div>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold">Date de la prestation <span style="color:#dc3545;">*</span></label>
                <input type="date" name="date_prestation" id="dateInput" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['date_prestation'] ?? ''); ?>"
                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold">Heure souhaitée <span style="color:#dc3545;">*</span></label>
                <input type="time" name="heure_prestation" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['heure_prestation'] ?? ''); ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold">
                    Nombre de personnes <span style="color:#dc3545;">*</span>
                    <span style="font-weight:400; color:#999;">(min. <?php echo $menu['nombre_personne_minimum']; ?>)</span>
                </label>
                <input type="number" name="nb_personnes" id="nbInput" class="form-control"
                       min="<?php echo $menu['nombre_personne_minimum']; ?>"
                       value="<?php echo htmlspecialchars($_POST['nb_personnes'] ?? $menu['nombre_personne_minimum']); ?>"
                       required>
                <div class="form-text" style="font-size:0.78rem; color:#2e7d32;">
                    -10% automatique à partir de <?php echo $menu['nombre_personne_minimum'] + 5; ?> personnes
                </div>
            </div>

        </div>

        <!-- Récapitulatif dynamique -->
        <div style="background:#F0EDE4; border-radius:12px; padding:22px; margin-top:24px;">
            <strong style="color:#1A5F7A; display:block; margin-bottom:14px;">Récapitulatif du prix</strong>

            <div style="display:flex; justify-content:space-between; font-size:0.9rem; padding:6px 0; border-bottom:1px solid #ddd8cc;">
                <span>Menu (<span id="recapNb"><?php echo $menu['nombre_personne_minimum']; ?></span> pers. × <?php echo number_format($menu['prix_par_personne'], 2, ',', ' '); ?> €)</span>
                <span id="recapMenu">—</span>
            </div>
            <div id="ligneReduction" style="display:none; justify-content:space-between; font-size:0.85rem; padding:6px 0; border-bottom:1px solid #ddd8cc; color:#2e7d32;">
                <span>Réduction 10%</span>
                <span id="recapReduction">—</span>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:0.9rem; padding:6px 0; border-bottom:1px solid #ddd8cc;">
                <span>Livraison</span>
                <span id="recapLivraison">Gratuite (Bordeaux)</span>
            </div>
            <div style="display:flex; justify-content:space-between; font-weight:700; font-size:1.05rem; padding:10px 0 0; color:#1A5F7A;">
                <span>Total estimé</span>
                <span id="recapTotal">—</span>
            </div>
        </div>

        <button type="submit" style="background:#1A5F7A; color:#fff; border:none; border-radius:25px; padding:14px 35px; font-weight:600; width:100%; font-size:1rem; cursor:pointer; margin-top:20px; transition:background .2s;"
                onmouseover="this.style.background='#154e66'" onmouseout="this.style.background='#1A5F7A'">
            Confirmer la commande
        </button>

    </form>
</div>
<?php endif; ?>

</div>
</div>
</div>
</section>

<script>
(function() {
    const prixParPersonne = <?php echo $menu['prix_par_personne']; ?>;
    const minimum        = <?php echo $menu['nombre_personne_minimum']; ?>;

    const nbInput    = document.getElementById('nbInput');
    const villeInput = document.getElementById('villeInput');
    const kmInput    = document.getElementById('kmInput');
    const zoneKm     = document.getElementById('zoneKm');

    function fmt(n) {
        return n.toFixed(2).replace('.', ',') + ' \u20ac';
    }

    function calculer() {
        const nb = parseInt(nbInput ? nbInput.value : minimum) || minimum;
        const ville = villeInput ? villeInput.value.trim().toLowerCase() : 'bordeaux';
        const km  = kmInput ? parseFloat(kmInput.value) || 0 : 0;
        const horsBx = ville !== 'bordeaux';

        // Affichage champ km
        if(zoneKm) zoneKm.style.display = horsBx ? 'block' : 'none';

        let prixMenu = prixParPersonne * nb;
        let reduction = 0;
        const avecReduction = nb >= (minimum + 5);

        if(avecReduction) {
            reduction = prixMenu * 0.10;
            prixMenu  = prixMenu * 0.90;
        }

        const livraison = horsBx ? (5 + km * 0.59) : 0;
        const total     = prixMenu + livraison;

        // Mise à jour DOM
        document.getElementById('recapNb').textContent   = nb;
        document.getElementById('recapMenu').textContent = fmt(prixMenu);

        const ligneRed = document.getElementById('ligneReduction');
        if(avecReduction) {
            ligneRed.style.display = 'flex';
            document.getElementById('recapReduction').textContent = '-' + fmt(reduction);
        } else {
            ligneRed.style.display = 'none';
        }

        document.getElementById('recapLivraison').textContent = horsBx
            ? fmt(livraison) + (km > 0 ? ' (5 \u20ac + ' + km + ' km)' : ' (distance non saisie)')
            : 'Gratuite (Bordeaux)';

        document.getElementById('recapTotal').textContent = fmt(total);
    }

    if(nbInput)    nbInput.addEventListener('input', calculer);
    if(villeInput) villeInput.addEventListener('input', calculer);
    if(kmInput)    kmInput.addEventListener('input', calculer);

    calculer();
})();
</script>

<?php require_once 'includes/footer.php'; ?>