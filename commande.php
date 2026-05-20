<?php
require_once 'config/database.php';

// Vérification que l'utilisateur est connecté
if(!isset($_SESSION['utilisateur_id'])) {
    header('Location: /vite-gourmand/connexion.php');
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

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adresse_prestation = trim($_POST['adresse_prestation'] ?? '');
    $ville_prestation   = trim($_POST['ville_prestation'] ?? '');
    $date_prestation    = $_POST['date_prestation'] ?? '';
    $heure_prestation   = $_POST['heure_prestation'] ?? '';
    $nb_personnes       = (int)($_POST['nb_personnes'] ?? 0);

    if(empty($adresse_prestation)) $erreurs[] = "L'adresse de prestation est obligatoire.";
    if(empty($ville_prestation))   $erreurs[] = "La ville de prestation est obligatoire.";
    if(empty($date_prestation))    $erreurs[] = "La date de prestation est obligatoire.";
    if(empty($heure_prestation))   $erreurs[] = "L'heure de prestation est obligatoire.";
    if($nb_personnes < $menu['nombre_personne_minimum']) {
        $erreurs[] = "Le nombre minimum de personnes pour ce menu est " . $menu['nombre_personne_minimum'] . ".";
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
        if(strtolower(trim($ville_prestation)) !== 'bordeaux') {
            $prix_livraison = 5.00;
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
            $prix_menu,
            $prix_livraison,
            $prix_total
        ]);

        // Mise à jour stock
        $pdo->prepare("UPDATE menu SET quantite_restante = quantite_restante - 1 WHERE menu_id = ?")
            ->execute([$menu_id]);

        $succes  = true;
        $numero_commande = $numero;
    }
}

require_once 'includes/header.php';
?>

<section class="py-5" style="min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <?php if($succes): ?>
                    <div style="background:#fff; border-radius:15px; padding:40px; text-align:center; box-shadow:0 3px 20px rgba(0,0,0,0.08);">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A;">Commande confirmée !</h2>
                        <p style="color:#666; margin:15px 0;">Votre numéro de commande : <strong><?php echo $numero_commande; ?></strong></p>
                        <p style="color:#666;">Vous allez recevoir un mail de confirmation.</p>
                        <a href="/vite-gourmand/espace-user.php" style="background:#1A5F7A; color:#fff; border-radius:25px; padding:12px 30px; text-decoration:none; font-weight:600;">
                            Voir mes commandes
                        </a>
                    </div>
                <?php else: ?>

                <div style="background:#fff; border-radius:15px; padding:40px; box-shadow:0 3px 20px rgba(0,0,0,0.08);">

                    <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:5px;">
                        Commander un menu
                    </h2>
                    <p style="color:#999; font-size:0.9rem; margin-bottom:30px;">
                        Menu sélectionné : <strong><?php echo htmlspecialchars($menu['titre']); ?></strong>
                    </p>

                    <?php if(!empty($erreurs)): ?>
                        <div style="background:#f8d7da; color:#721c24; border-radius:10px; padding:15px; margin-bottom:20px;">
                            <?php foreach($erreurs as $e): ?>
                                <div>- <?php echo htmlspecialchars($e); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Conditions bien en évidence -->
                    <?php if($menu['conditions']): ?>
                    <div style="background:#fff3cd; border-left:4px solid #D4A853; border-radius:8px; padding:15px; margin-bottom:25px;">
                        <strong style="color:#856404;">Conditions importantes</strong>
                        <p style="color:#856404; font-size:0.85rem; margin:8px 0 0;">
                            <?php echo nl2br(htmlspecialchars($menu['conditions'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">

                            <!-- Infos auto-remplies -->
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nom</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['nom']); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Prénom</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['prenom']); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Téléphone</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['telephone']); ?>" disabled>
                            </div>

                            <!-- Infos prestation -->
                            <div class="col-12">
                                <label class="form-label small fw-bold">Adresse de la prestation</label>
                                <input type="text" name="adresse_prestation" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['adresse_prestation'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Ville de la prestation</label>
                                <input type="text" name="ville_prestation" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['ville_prestation'] ?? ''); ?>" required>
                                <div class="form-text" style="font-size:0.8rem; color:#999;">
                                    Hors Bordeaux : frais de livraison de 5€ minimum.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Date de la prestation</label>
                                <input type="date" name="date_prestation" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['date_prestation'] ?? ''); ?>" 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Heure souhaitée</label>
                                <input type="time" name="heure_prestation" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['heure_prestation'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">
                                    Nombre de personnes (min. <?php echo $menu['nombre_personne_minimum']; ?>)
                                </label>
                                <input type="number" name="nb_personnes" class="form-control"
                                       min="<?php echo $menu['nombre_personne_minimum']; ?>"
                                       value="<?php echo htmlspecialchars($_POST['nb_personnes'] ?? $menu['nombre_personne_minimum']); ?>"
                                       required>
                                <div class="form-text" style="font-size:0.8rem; color:#999;">
                                    -10% à partir de <?php echo $menu['nombre_personne_minimum'] + 5; ?> personnes.
                                </div>
                            </div>

                            <!-- Récap prix -->
                            <div class="col-12">
                                <div style="background:#F0EDE4; border-radius:10px; padding:20px;">
                                    <strong style="color:#1A5F7A;">Récapitulatif du prix</strong>
                                    <div class="d-flex justify-content-between mt-2" style="font-size:0.9rem;">
                                        <span>Prix par personne</span>
                                        <span><?php echo number_format($menu['prix_par_personne'], 2, ',', ' '); ?> €</span>
                                    </div>
                                    <div class="d-flex justify-content-between" style="font-size:0.9rem;">
                                        <span>Livraison hors Bordeaux</span>
                                        <span>5,00 € minimum + 0,59 €/km</span>
                                    </div>
                                    <div class="d-flex justify-content-between" style="font-size:0.9rem;">
                                        <span>Réduction</span>
                                        <span>-10% dès <?php echo $menu['nombre_personne_minimum'] + 5; ?> personnes</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-2">
                                <button type="submit" style="background:#1A5F7A; color:#fff; border:none; border-radius:25px; padding:12px 35px; font-weight:600; width:100%; font-size:1rem; cursor:pointer;">
                                    Confirmer la commande
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
