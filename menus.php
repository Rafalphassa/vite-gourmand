<?php
session_start();
require_once 'config/database.php';

$where  = ["m.actif = 1"];
$params = [];

if(!empty($_GET['prix_max']))     { $where[] = "m.prix_par_personne <= ?";       $params[] = (float)$_GET['prix_max']; }
if(!empty($_GET['prix_min']))     { $where[] = "m.prix_par_personne >= ?";       $params[] = (float)$_GET['prix_min']; }
if(!empty($_GET['theme_id']))     { $where[] = "m.theme_id = ?";                 $params[] = (int)$_GET['theme_id']; }
if(!empty($_GET['regime_id']))    { $where[] = "m.regime_id = ?";                $params[] = (int)$_GET['regime_id']; }
if(!empty($_GET['nb_personnes'])) { $where[] = "m.nombre_personne_minimum <= ?"; $params[] = (int)$_GET['nb_personnes']; }

$stmt = getDB()->prepare("
    SELECT m.*, t.libelle as theme, r.libelle as regime,
           (SELECT nom_fichier FROM image_menu WHERE menu_id = m.menu_id ORDER BY ordre ASC LIMIT 1) as photo
    FROM menu m
    LEFT JOIN theme t ON m.theme_id = t.theme_id
    LEFT JOIN regime r ON m.regime_id = r.regime_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY m.menu_id DESC
");
$stmt->execute($params);
$menus   = $stmt->fetchAll();
$themes  = getDB()->query("SELECT * FROM theme ORDER BY libelle")->fetchAll();
$regimes = getDB()->query("SELECT * FROM regime ORDER BY libelle")->fetchAll();

require_once 'includes/header.php';
?>

<section class="py-5">
    <div class="container">

        <div class="text-center mb-5">
            <h2 style="font-family:'Playfair Display',serif; font-size:2.2rem; color:#1A5F7A;">Nos Menus</h2>
            <p style="color:#999;">Découvrez l'ensemble de nos menus et filtrez selon vos besoins.</p>
        </div>

        <!-- FILTRES -->
        <div style="background:#fff; border-radius:15px; padding:25px; margin-bottom:40px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
            <form method="GET">
                <div class="row g-3 align-items-end">

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Prix min (€)</label>
                        <input type="number" name="prix_min" class="form-control" min="0"
                               value="<?php echo htmlspecialchars($_GET['prix_min'] ?? ''); ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Prix max (€)</label>
                        <input type="number" name="prix_max" class="form-control" min="0"
                               value="<?php echo htmlspecialchars($_GET['prix_max'] ?? ''); ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Thème</label>
                        <select name="theme_id" class="form-select">
                            <option value="">Tous</option>
                            <?php foreach($themes as $t): ?>
                                <option value="<?php echo (int)$t['theme_id']; ?>"
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
                                <option value="<?php echo (int)$r['regime_id']; ?>"
                                    <?php echo (($_GET['regime_id'] ?? '') == $r['regime_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r['libelle']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Nb personnes min</label>
                        <input type="number" name="nb_personnes" class="form-control" min="1"
                               value="<?php echo htmlspecialchars($_GET['nb_personnes'] ?? ''); ?>">
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" style="
                            background:#1A5F7A; color:#fff; border:none;
                            border-radius:25px; padding:10px 20px;
                            font-weight:600; cursor:pointer; width:100%;
                        ">Filtrer</button>
                        <a href="/vite-gourmand/menus.php" style="
                            background:#F0EDE4; color:#1A5F7A;
                            border-radius:25px; padding:10px 15px;
                            font-weight:600; text-decoration:none; white-space:nowrap;
                        ">Reset</a>
                    </div>

                </div>
            </form>
        </div>

        <!-- LISTE DES MENUS -->
        <div class="row g-4">
            <?php if(empty($menus)): ?>
                <div class="col-12 text-center py-5">
                    <p style="color:#999; font-size:1.1rem;">Aucun menu ne correspond à votre recherche.</p>
                    <a href="/vite-gourmand/menus.php" style="color:#1A5F7A; font-weight:600;">Voir tous les menus</a>
                </div>
            <?php else: ?>
                <?php foreach($menus as $m): ?>
                <div class="col-md-6 col-lg-4">
                    <div style="
                        background:#fff; border-radius:15px; overflow:hidden;
                        height:100%; box-shadow:0 3px 20px rgba(0,0,0,0.06);
                        display:flex; flex-direction:column;
                    ">
                        <!-- Image -->
                        <div style="height:200px; background:#F0EDE4; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                            <?php if($m['photo']): ?>
                                <img src="/vite-gourmand/uploads/plats/<?php echo htmlspecialchars($m['photo']); ?>"
                                     alt="<?php echo htmlspecialchars($m['titre']); ?>"
                                     style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <span style="color:#ccc; font-size:0.9rem;">Aucune photo</span>
                            <?php endif; ?>
                        </div>

                        <div style="padding:25px; flex:1; display:flex; flex-direction:column;">
                            <!-- Badges -->
                            <div class="d-flex gap-2 mb-2 flex-wrap">
                                <?php if($m['theme']): ?>
                                <span style="background:#F0EDE4; color:#1A5F7A; padding:3px 12px; border-radius:20px; font-size:0.75rem; font-weight:600;">
                                    <?php echo htmlspecialchars($m['theme']); ?>
                                </span>
                                <?php endif; ?>
                                <?php if($m['regime']): ?>
                                <span style="background:#fff3cd; color:#856404; padding:3px 12px; border-radius:20px; font-size:0.75rem; font-weight:600;">
                                    <?php echo htmlspecialchars($m['regime']); ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <h5 style="font-family:'Playfair Display',serif; color:#1A5F7A;">
                                <?php echo htmlspecialchars($m['titre']); ?>
                            </h5>

                            <p style="color:#666; font-size:0.9rem; flex:1;">
                                <?php
                                $desc = $m['description'];
                                echo htmlspecialchars(mb_strlen($desc) > 120 ? mb_substr($desc, 0, 120).'...' : $desc);
                                ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <div style="color:#D4A853; font-weight:700; font-size:1.1rem;">
                                        <?php echo number_format($m['prix_par_personne'], 2, ',', ' '); ?> €/pers
                                    </div>
                                    <div style="color:#999; font-size:0.8rem;">
                                        À partir de <?php echo (int)$m['nombre_personne_minimum']; ?> personnes
                                    </div>
                                </div>
                                <a href="/vite-gourmand/menu-detail.php?id=<?php echo (int)$m['menu_id']; ?>"
                                   style="background:#1A5F7A; color:#fff; border-radius:25px; padding:8px 20px; font-weight:600; text-decoration:none; font-size:0.9rem;">
                                    Voir le menu
                                </a>
                            </div>

                            <!-- Stock -->
                            <?php if((int)$m['quantite_restante'] === 0): ?>
                                <div style="background:#f8d7da; color:#721c24; border-radius:8px; padding:8px 12px; font-size:0.8rem; margin-top:10px;">
                                    Complet
                                </div>
                            <?php elseif((int)$m['quantite_restante'] <= 3): ?>
                                <div style="background:#fff3cd; color:#856404; border-radius:8px; padding:8px 12px; font-size:0.8rem; margin-top:10px;">
                                    Plus que <?php echo (int)$m['quantite_restante']; ?> commande(s) disponible(s)
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>