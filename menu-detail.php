<?php
session_start();
require_once 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id === 0) {
    header('Location: /vite-gourmand/menus.php');
    exit;
}

$stmt = getDB()->prepare("
    SELECT m.*, t.libelle as theme, r.libelle as regime
    FROM menu m
    LEFT JOIN theme t ON m.theme_id = t.theme_id
    LEFT JOIN regime r ON m.regime_id = r.regime_id
    WHERE m.menu_id = ? AND m.actif = 1
");
$stmt->execute([$id]);
$menu = $stmt->fetch();

if(!$menu) {
    header('Location: /vite-gourmand/menus.php');
    exit;
}

$stmtImgs = getDB()->prepare("SELECT * FROM image_menu WHERE menu_id = ? ORDER BY ordre ASC");
$stmtImgs->execute([$id]);
$images = $stmtImgs->fetchAll();

$stmtPlats = getDB()->prepare("
    SELECT p.*, GROUP_CONCAT(a.libelle ORDER BY a.libelle SEPARATOR ', ') as allergenes
    FROM plat p
    JOIN menu_plat mp ON p.plat_id = mp.plat_id
    LEFT JOIN plat_allergene pa ON p.plat_id = pa.plat_id
    LEFT JOIN allergene a ON pa.allergene_id = a.allergene_id
    WHERE mp.menu_id = ?
    GROUP BY p.plat_id
    ORDER BY p.type_plat ASC
");
$stmtPlats->execute([$id]);
$plats = $stmtPlats->fetchAll();

require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container">

        <a href="/vite-gourmand/menus.php" style="color:#1A5F7A; text-decoration:none; font-size:0.9rem;">
            &larr; Retour aux menus
        </a>

        <div class="row mt-4 g-5">

            <!-- Colonne gauche -->
            <div class="col-lg-7">

                <!-- Galerie -->
                <?php if(!empty($images)): ?>
                <div style="border-radius:15px; overflow:hidden; margin-bottom:25px;">
                    <img src="/vite-gourmand/uploads/plats/<?php echo htmlspecialchars($images[0]['nom_fichier']); ?>"
                         alt="<?php echo htmlspecialchars($menu['titre']); ?>"
                         style="width:100%; height:350px; object-fit:cover;">
                </div>
                <?php if(count($images) > 1): ?>
                <div class="d-flex gap-2 mb-4 flex-wrap">
                    <?php foreach(array_slice($images, 1) as $img): ?>
                    <img src="/vite-gourmand/uploads/plats/<?php echo htmlspecialchars($img['nom_fichier']); ?>"
                         alt="Photo du menu"
                         style="width:80px; height:80px; object-fit:cover; border-radius:8px; cursor:pointer;">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div style="
                    background:#F0EDE4; border-radius:15px; height:250px;
                    display:flex; align-items:center; justify-content:center; margin-bottom:25px;
                ">
                    <span style="color:#ccc;">Aucune photo disponible</span>
                </div>
                <?php endif; ?>

                <!-- Badges -->
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <?php if($menu['theme']): ?>
                    <span style="background:#F0EDE4; color:#1A5F7A; padding:5px 15px; border-radius:20px; font-size:0.85rem; font-weight:600;">
                        <?php echo htmlspecialchars($menu['theme']); ?>
                    </span>
                    <?php endif; ?>
                    <?php if($menu['regime']): ?>
                    <span style="background:#fff3cd; color:#856404; padding:5px 15px; border-radius:20px; font-size:0.85rem; font-weight:600;">
                        <?php echo htmlspecialchars($menu['regime']); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <h1 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:2rem;">
                    <?php echo htmlspecialchars($menu['titre']); ?>
                </h1>

                <p style="color:#555; line-height:1.8; margin:20px 0;">
                    <?php echo nl2br(htmlspecialchars($menu['description'])); ?>
                </p>

                <!-- Plats par type -->
                <?php
                $types = ['entrée' => 'Entrées', 'plat' => 'Plats', 'dessert' => 'Desserts'];
                foreach($types as $type => $label):
                    $filtres = array_filter($plats, fn($p) => $p['type_plat'] === $type);
                    if(empty($filtres)) continue;
                ?>
                <h5 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-top:25px;">
                    <?php echo $label; ?>
                </h5>
                <?php foreach($filtres as $p): ?>
                <div style="background:#F0EDE4; border-radius:10px; padding:15px; margin-bottom:10px;">
                    <strong><?php echo htmlspecialchars($p['nom']); ?></strong>
                    <?php if($p['description']): ?>
                    <p style="color:#666; font-size:0.85rem; margin:5px 0 0;">
                        <?php echo htmlspecialchars($p['description']); ?>
                    </p>
                    <?php endif; ?>
                    <?php if($p['allergenes']): ?>
                    <p style="color:#856404; font-size:0.8rem; margin:5px 0 0;">
                        Allergènes : <?php echo htmlspecialchars($p['allergenes']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endforeach; ?>

            </div>

            <!-- Colonne droite : prix + conditions + commande -->
            <div class="col-lg-5">
                <div style="
                    background:#fff; border-radius:15px; padding:30px;
                    position:sticky; top:90px;
                    box-shadow:0 3px 20px rgba(0,0,0,0.08);
                ">
                    <div style="color:#D4A853; font-size:1.8rem; font-weight:700; font-family:'Playfair Display',serif;">
                        <?php echo number_format($menu['prix_par_personne'], 2, ',', ' '); ?> €
                        <span style="font-size:1rem; color:#999; font-family:'Inter',sans-serif;">/ personne</span>
                    </div>

                    <p style="color:#666; font-size:0.9rem; margin:10px 0 20px;">
                        Minimum <?php echo (int)$menu['nombre_personne_minimum']; ?> personnes
                    </p>

                    <!-- Stock -->
                    <?php if((int)$menu['quantite_restante'] === 0): ?>
                    <div style="background:#f8d7da; color:#721c24; border-radius:8px; padding:12px; margin-bottom:15px; font-size:0.9rem;">
                        Ce menu est complet.
                    </div>
                    <?php elseif((int)$menu['quantite_restante'] <= 3): ?>
                    <div style="background:#fff3cd; color:#856404; border-radius:8px; padding:12px; margin-bottom:15px; font-size:0.9rem;">
                        Plus que <?php echo (int)$menu['quantite_restante']; ?> commande(s) disponible(s).
                    </div>
                    <?php endif; ?>

                    <!-- CONDITIONS bien en évidence (exigé par le CDC) -->
                    <?php if($menu['conditions']): ?>
                    <div style="
                        background:#fff3cd; border-left:4px solid #D4A853;
                        border-radius:8px; padding:15px; margin-bottom:20px;
                    ">
                        <strong style="color:#856404; font-size:0.9rem;">Conditions importantes</strong>
                        <p style="color:#856404; font-size:0.85rem; margin:8px 0 0; line-height:1.6;">
                            <?php echo nl2br(htmlspecialchars($menu['conditions'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- Bouton commande -->
                    <?php if((int)$menu['quantite_restante'] > 0): ?>
                        <?php if(isset($_SESSION['utilisateur_id'])): ?>
                            <a href="/vite-gourmand/commande.php?menu_id=<?php echo (int)$menu['menu_id']; ?>" style="
                                display:block; background:#1A5F7A; color:#fff;
                                border-radius:25px; padding:14px;
                                font-weight:600; text-decoration:none;
                                text-align:center; font-size:1rem;
                            ">Commander ce menu</a>
                        <?php else: ?>
                            <a href="/vite-gourmand/connexion.php" style="
                                display:block; background:#1A5F7A; color:#fff;
                                border-radius:25px; padding:14px;
                                font-weight:600; text-decoration:none;
                                text-align:center; font-size:1rem;
                            ">Se connecter pour commander</a>
                            <p style="color:#999; font-size:0.8rem; text-align:center; margin-top:10px;">
                                Pas encore de compte ?
                                <a href="/vite-gourmand/inscription.php" style="color:#1A5F7A;">S'inscrire</a>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>