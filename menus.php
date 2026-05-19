<?php
require_once 'config/database.php';

// Récupération des filtres
$where = ["m.actif = 1"];
$params = [];

if(!empty($_GET['prix_max'])) {
    $where[] = "m.prix_par_personne <= ?";
    $params[] = $_GET['prix_max'];
}
if(!empty($_GET['prix_min'])) {
    $where[] = "m.prix_par_personne >= ?";
    $params[] = $_GET['prix_min'];
}
if(!empty($_GET['theme_id'])) {
    $where[] = "m.theme_id = ?";
    $params[] = $_GET['theme_id'];
}
if(!empty($_GET['regime_id'])) {
    $where[] = "m.regime_id = ?";
    $params[] = $_GET['regime_id'];
}
if(!empty($_GET['nb_personnes'])) {
    $where[] = "m.nombre_personne_minimum <= ?";
    $params[] = $_GET['nb_personnes'];
}

$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT m.*, t.libelle as theme, r.libelle as regime
    FROM menu m
    LEFT JOIN theme t ON m.theme_id = t.theme_id
    LEFT JOIN regime r ON m.regime_id = r.regime_id
    WHERE $whereSQL
    ORDER BY m.menu_id DESC
");
$stmt->execute($params);
$menus = $stmt->fetchAll();

// Récupération themes et regimes pour les filtres
$themes  = $pdo->query("SELECT * FROM theme")->fetchAll();
$regimes = $pdo->query("SELECT * FROM regime")->fetchAll();

require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container">

        <div class="text-center mb-5">
            <h2 style="font-family:'Playfair Display',serif; font-size:2.2rem; color:#1A5F7A;">
                Nos Menus
            </h2>
            <p style="color:#999;">Découvrez l'ensemble de nos menus et filtrez selon vos besoins.</p>
        </div>

        <!-- FILTRES -->
        <div style="background:#fff; border-radius:15px; padding:25px; margin-bottom:40px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
            <form method="GET" id="formFiltres">
                <div class="row g-3 align-items-end">

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Prix min (€)</label>
                        <input type="number" name="prix_min" class="form-control"
                               value="<?php echo htmlspecialchars($_GET['prix_min'] ?? ''); ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Prix max (€)</label>
                        <input type="number" name="prix_max" class="form-control"
                               value="<?php echo htmlspecialchars($_GET['prix_max'] ?? ''); ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Thème</label>
                        <select name="theme_id" class="form-select">
                            <option value="">Tous</option>
                            <?php foreach($themes as $t): ?>
                                <option value="<?php echo $t['theme_id']; ?>"
                                    <?php echo (($_GET['theme_id'] ?? '') == $t['theme_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['libelle']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Régime</label>
                        <select name="regime_id" class="form-select">
                            <option value="">Tous</option>
                            <?php foreach($regimes as $r): ?>
                                <option value="<?php echo $r['regime_id']; ?>"
                                    <?php echo (($_GET['regime_id'] ?? '') == $r['regime_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r['libelle']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Nb personnes min</label>
                        <input type="number" name="nb_personnes" class="form-control"
                               value="<?php echo htmlspecialchars($_GET['nb_personnes'] ?? ''); ?>">
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" style="
                            background:#1A5F7A; color:#fff; border:none;
                            border-radius:25px; padding:10px 20px;
                            font-weight:600; cursor:pointer; width:100%;
                        ">
                            Filtrer
                        </button>
                        <a href="/vite-gourmand/menus.php" style="
                            background:#F0EDE4; color:#1A5F7A; border:none;
                            border-radius:25px; padding:10px 15px;
                            font-weight:600; text-decoration:none;
                            white-space:nowrap;
                        ">
                            Reset
                        </a>
                    </div>

                </div>
            </form>
        </div>

        <!-- LISTE DES MENUS -->
        <div class="row g-4" id="listeMenus">
            <?php if(empty($menus)): ?>
                <div class="col-12 text-center py-5">
                    <p style="color:#999; font-size:1.1rem;">
                        Aucun menu ne correspond à votre recherche.
                    </p>
                    <a href="/vite-gourmand/menus.php" style="color:#1A5F7A; font-weight:600;">
                        Voir tous les menus
                    </a>
                </div>
            <?php else: ?>
                <?php foreach($menus as $m): ?>
                <div class="col-md-6 col-lg-4">
                    <div style="
                        background:#fff; border-radius:15px;
                        overflow:hidden; height:100%;
                        box-shadow:0 3px 20px rgba(0,0,0,0.06);
                        display:flex; flex-direction:column;
                    ">
                        <!-- Image du menu -->
                        <?php
                        $img = $pdo->prepare("SELECT nom_fichier FROM image_menu WHERE menu_id = ? ORDER BY ordre ASC LIMIT 1");
                        $img->execute([$m['menu_id']]);
                        $photo = $img->fetchColumn();
                        ?>
                        <div style="
                            height:200px; background:#F0EDE4;
                            display:flex; align-items:center; justify-content:center;
                            overflow:hidden;
                        ">
                            <?php if($photo): ?>
                                <img src="/vite-gourmand/uploads/plats/<?php echo htmlspecialchars($photo); ?>"
                                     style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <span style="color:#ccc; font-size:0.9rem;">Aucune photo</span>
                            <?php endif; ?>
                        </div>

                        <div style="padding:25px; flex-grow:1;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
