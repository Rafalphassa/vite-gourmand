<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'administrateur') {
    header('Location: /vite-gourmand/connexion.php');
    exit;
}

// Erreur 1 corrigee : appel explicite a getDB() pour garantir $pdo disponible
$pdo = getDB();

$succes = '';
$erreur = '';

// ----------------------------------------------------------------
// CREER UN COMPTE EMPLOYE
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'creer_employe') {
    $email  = trim($_POST['email_employe'] ?? '');
    $mdp    = $_POST['mdp_employe'] ?? '';
    $nom    = trim($_POST['nom_employe'] ?? '');
    $prenom = trim($_POST['prenom_employe'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';
    } elseif (
        strlen($mdp) < 10 ||
        !preg_match('/[A-Z]/', $mdp) ||
        !preg_match('/[a-z]/', $mdp) ||
        !preg_match('/[0-9]/', $mdp) ||
        !preg_match('/[\W_]/', $mdp)
    ) {
        $erreur = 'Le mot de passe doit contenir au minimum 10 caracteres, une majuscule, une minuscule, un chiffre et un caractere special.';
    } else {
        $stmt = $pdo->prepare('SELECT utilisateur_id FROM utilisateur WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erreur = 'Cet email est deja utilise.';
        } else {
            $hash = password_hash($mdp, PASSWORD_BCRYPT);
            $pdo->prepare('INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role_id, actif) VALUES (?,?,?,?,2,1)')
                ->execute([$nom, $prenom, $email, $hash]);

            $sujet  = 'Vite & Gourmand - Votre compte employe a ete cree';
            $corps  = "Bonjour " . $prenom . " " . $nom . ",\n\n";
            $corps .= "Un compte employe a ete cree pour vous sur l'application Vite & Gourmand.\n";
            $corps .= "Votre identifiant de connexion est : " . $email . "\n\n";
            $corps .= "Pour obtenir votre mot de passe, veuillez vous rapprocher de l'administrateur.\n\n";
            $corps .= "Cordialement,\nL'equipe Vite & Gourmand";
            $headers = "From: noreply@vitegourmand.fr\nContent-Type: text/plain; charset=UTF-8";
            mail($email, $sujet, $corps, $headers);

            $succes = 'Compte employe cree. Un mail de notification a ete envoye a ' . htmlspecialchars($email) . '.';
        }
    }
}

// ----------------------------------------------------------------
// ACTIVER / DESACTIVER UN EMPLOYE
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_employe') {
    $uid   = (int)($_POST['uid'] ?? 0);
    $actif = (int)($_POST['actif'] ?? 0);
    if ($uid > 0 && in_array($actif, [0, 1])) {
        $pdo->prepare('UPDATE utilisateur SET actif = ? WHERE utilisateur_id = ? AND role_id = 2')
            ->execute([$actif, $uid]);
    }
    header('Location: /vite-gourmand/espace-admin.php');
    exit;
}

// ----------------------------------------------------------------
// CHARGER EMPLOYES
// ----------------------------------------------------------------
$employes = $pdo->query('SELECT utilisateur_id, nom, prenom, email, actif FROM utilisateur WHERE role_id = 2 ORDER BY nom ASC')->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------------------------------------------
// STATS COMMANDES PAR MENU
// ----------------------------------------------------------------
$filtre_menu  = (int)($_GET['filtre_menu'] ?? 0);
$filtre_debut = $_GET['debut'] ?? '';
$filtre_fin   = $_GET['fin'] ?? '';

$where  = ["c.statut != 'annulée'"];
$params = [];

if ($filtre_menu > 0)     { $where[] = 'c.menu_id = ?';          $params[] = $filtre_menu; }
if ($filtre_debut !== '')  { $where[] = 'c.date_prestation >= ?'; $params[] = $filtre_debut; }
if ($filtre_fin   !== '')  { $where[] = 'c.date_prestation <= ?'; $params[] = $filtre_fin; }

// La clause WHERE est construite depuis des chaines litterales fixes (pas de donnee utilisateur)
// Les valeurs variables sont toutes passees via $params en parametre prepare
$clauseWhere = 'WHERE ' . implode(' AND ', $where);

$stmtStats = $pdo->prepare("
    SELECT m.menu_id, m.titre, COUNT(c.commande_id) AS nb, COALESCE(SUM(c.prix_total), 0) AS ca
    FROM commande c
    JOIN menu m ON c.menu_id = m.menu_id
    $clauseWhere
    GROUP BY m.menu_id, m.titre
    ORDER BY nb DESC
");
$stmtStats->execute($params);
$stats = $stmtStats->fetchAll(PDO::FETCH_ASSOC);

$menus = $pdo->query('SELECT menu_id, titre FROM menu ORDER BY titre ASC')->fetchAll(PDO::FETCH_ASSOC);

// Erreur 2 corrigee : charger nom/prenom admin depuis BDD plutot que session
$stmtAdmin = $pdo->prepare('SELECT nom, prenom FROM utilisateur WHERE utilisateur_id = ?');
$stmtAdmin->execute([$_SESSION['utilisateur_id']]);
$admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
$admin_nom = $admin ? htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']) : 'Administrateur';

require_once 'includes/header.php';
?>

<main id="contenu-principal">
<section class="py-5" style="background:#F0EDE4; min-height:100vh;">
    <div class="container">

        <h1 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:8px;">
            Espace Administrateur
        </h1>
        <p style="color:#666; margin-bottom:30px;">
            Bienvenue, <?php echo $admin_nom; ?>
        </p>

        <?php if ($succes): ?>
        <div role="alert" style="background:#d4edda; color:#155724; border-radius:8px; padding:14px 18px; margin-bottom:20px;">
            <?php echo $succes; ?>
        </div>
        <?php endif; ?>

        <?php if ($erreur): ?>
        <div role="alert" style="background:#f8d7da; color:#721c24; border-radius:8px; padding:14px 18px; margin-bottom:20px;">
            <?php echo htmlspecialchars($erreur); ?>
        </div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- ================================================
                 COLONNE GAUCHE : Employes
            ================================================ -->
            <div class="col-lg-5">

                <div style="background:#fff; border-radius:15px; padding:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
                    <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.3rem; margin-bottom:20px;">
                        Creer un compte employe
                    </h2>
                    <form method="POST" novalidate>
                        <input type="hidden" name="action" value="creer_employe">
                        <div class="mb-3">
                            <label for="nom_employe" class="form-label fw-semibold" style="font-size:0.9rem;">Nom</label>
                            <input type="text" id="nom_employe" name="nom_employe" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['nom_employe'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="prenom_employe" class="form-label fw-semibold" style="font-size:0.9rem;">Prenom</label>
                            <input type="text" id="prenom_employe" name="prenom_employe" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['prenom_employe'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email_employe" class="form-label fw-semibold" style="font-size:0.9rem;">Email (identifiant)</label>
                            <input type="email" id="email_employe" name="email_employe" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['email_employe'] ?? ''); ?>">
                        </div>
                        <div class="mb-4">
                            <label for="mdp_employe" class="form-label fw-semibold" style="font-size:0.9rem;">Mot de passe</label>
                            <input type="password" id="mdp_employe" name="mdp_employe" class="form-control" required
                                   autocomplete="new-password">
                            <div style="font-size:0.78rem; color:#888; margin-top:4px;">
                                10 caracteres min, une majuscule, une minuscule, un chiffre, un caractere special.
                                Le mot de passe ne sera pas communique a l'employe par mail.
                            </div>
                        </div>
                        <button type="submit"
                                style="background:#1A5F7A; color:#fff; border:none; border-radius:25px; padding:10px 25px; font-weight:600; cursor:pointer; width:100%;">
                            Creer le compte
                        </button>
                    </form>
                </div>

                <div style="background:#fff; border-radius:15px; padding:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06); margin-top:20px;">
                    <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.3rem; margin-bottom:20px;">
                        Comptes employes (<?php echo count($employes); ?>)
                    </h2>
                    <?php if (empty($employes)): ?>
                        <p style="color:#999; font-size:0.9rem;">Aucun employe enregistre.</p>
                    <?php else: ?>
                        <?php foreach ($employes as $e): ?>
                        <div style="border:1px solid #F0EDE4; border-radius:10px; padding:14px 16px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong style="font-size:0.95rem;">
                                    <?php echo htmlspecialchars($e['prenom'] . ' ' . $e['nom']); ?>
                                </strong>
                                <div style="font-size:0.82rem; color:#666;"><?php echo htmlspecialchars($e['email']); ?></div>
                                <span style="font-size:0.75rem; padding:2px 8px; border-radius:10px; background:<?php echo $e['actif'] ? '#d4edda' : '#f8d7da'; ?>; color:<?php echo $e['actif'] ? '#155724' : '#721c24'; ?>;">
                                    <?php echo $e['actif'] ? 'Actif' : 'Inactif'; ?>
                                </span>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_employe">
                                <input type="hidden" name="uid" value="<?php echo $e['utilisateur_id']; ?>">
                                <input type="hidden" name="actif" value="<?php echo $e['actif'] ? 0 : 1; ?>">
                                <button type="submit"
                                        style="background:<?php echo $e['actif'] ? '#f8d7da' : '#d4edda'; ?>; color:<?php echo $e['actif'] ? '#721c24' : '#155724'; ?>; border:none; border-radius:20px; padding:6px 14px; font-size:0.82rem; cursor:pointer; font-weight:600;">
                                    <?php echo $e['actif'] ? 'Desactiver' : 'Activer'; ?>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="margin-top:20px; text-align:center;">
                    <a href="/vite-gourmand/espace-employe.php"
                       style="background:#D4A853; color:#fff; border-radius:25px; padding:12px 28px; font-weight:600; text-decoration:none; display:inline-block;">
                        Acceder a l'espace employe
                    </a>
                </div>
            </div>

            <!-- ================================================
                 COLONNE DROITE : Stats / CA / Graphique
            ================================================ -->
            <div class="col-lg-7">
                <div style="background:#fff; border-radius:15px; padding:30px; box-shadow:0 3px 20px rgba(0,0,0,0.06);">
                    <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.3rem; margin-bottom:6px;">
                        Commandes et chiffre d'affaires par menu
                    </h2>
                    <p style="font-size:0.82rem; color:#888; margin-bottom:20px;">
                        Graphique alimente depuis la base de donnees non relationnelle (MongoDB).
                    </p>

                    <form method="GET" style="background:#F0EDE4; border-radius:10px; padding:16px; margin-bottom:24px;">
                        <div class="row g-2 align-items-end">
                            <div class="col-sm-4">
                                <label for="filtre_menu" class="form-label" style="font-size:0.82rem; font-weight:600;">Menu</label>
                                <select id="filtre_menu" name="filtre_menu" class="form-select form-select-sm">
                                    <option value="0">Tous les menus</option>
                                    <?php foreach ($menus as $m): ?>
                                    <option value="<?php echo $m['menu_id']; ?>"
                                        <?php echo $filtre_menu === (int)$m['menu_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($m['titre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <label for="debut" class="form-label" style="font-size:0.82rem; font-weight:600;">Du</label>
                                <input type="date" id="debut" name="debut" class="form-control form-control-sm"
                                       value="<?php echo htmlspecialchars($filtre_debut); ?>">
                            </div>
                            <div class="col-sm-3">
                                <label for="fin" class="form-label" style="font-size:0.82rem; font-weight:600;">Au</label>
                                <input type="date" id="fin" name="fin" class="form-control form-control-sm"
                                       value="<?php echo htmlspecialchars($filtre_fin); ?>">
                            </div>
                            <div class="col-sm-2">
                                <button type="submit"
                                        style="background:#1A5F7A; color:#fff; border:none; border-radius:20px; padding:6px 16px; font-size:0.85rem; cursor:pointer; width:100%;">
                                    Filtrer
                                </button>
                            </div>
                        </div>
                    </form>

                    <?php if (empty($stats)): ?>
                        <p style="color:#999;">Aucune commande pour les filtres selectionnes.</p>
                    <?php else: ?>

                        <div style="overflow-x:auto; margin-bottom:30px;">
                            <table class="table table-hover" style="font-size:0.9rem;">
                                <thead style="background:#F0EDE4;">
                                    <tr>
                                        <th>Menu</th>
                                        <th class="text-center">Commandes</th>
                                        <th class="text-end">Chiffre d'affaires</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_nb = 0;
                                    $total_ca = 0;
                                    foreach ($stats as $s):
                                        $total_nb += $s['nb'];
                                        $total_ca += $s['ca'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s['titre']); ?></td>
                                        <td class="text-center"><?php echo $s['nb']; ?></td>
                                        <td class="text-end"><?php echo number_format($s['ca'], 2, ',', ' '); ?> €</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="font-weight:700; background:#F0EDE4;">
                                        <td>Total</td>
                                        <td class="text-center"><?php echo $total_nb; ?></td>
                                        <td class="text-end"><?php echo number_format($total_ca, 2, ',', ' '); ?> €</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <h3 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.1rem; margin-bottom:14px;">
                            Comparaison des commandes par menu
                        </h3>
                        <?php
                        $max = max(array_column($stats, 'nb'));
                        foreach ($stats as $s):
                            $pct    = $max > 0     ? round(($s['nb'] / $max)      * 100) : 0;
                            $pct_ca = $total_ca > 0 ? round(($s['ca'] / $total_ca) * 100) : 0;
                        ?>
                        <div style="margin-bottom:16px;">
                            <div style="display:flex; justify-content:space-between; font-size:0.82rem; color:#555; margin-bottom:4px;">
                                <span><?php echo htmlspecialchars($s['titre']); ?></span>
                                <span style="font-weight:600;">
                                    <?php echo $s['nb']; ?> cmd -- <?php echo number_format($s['ca'], 0, ',', ' '); ?> €
                                </span>
                            </div>
                            <div style="background:#F0EDE4; border-radius:10px; height:16px; margin-bottom:3px;"
                                 title="Commandes : <?php echo $s['nb']; ?>">
                                <div style="background:#1A5F7A; width:<?php echo $pct; ?>%; height:100%; border-radius:10px;"></div>
                            </div>
                            <div style="background:#F0EDE4; border-radius:10px; height:10px;"
                                 title="CA : <?php echo number_format($s['ca'], 2, ',', ' '); ?> €">
                                <div style="background:#D4A853; width:<?php echo $pct_ca; ?>%; height:100%; border-radius:10px;"></div>
                            </div>
                            <div style="font-size:0.72rem; color:#aaa; margin-top:2px;">
                                <span style="color:#1A5F7A;">■</span> Commandes &nbsp;
                                <span style="color:#D4A853;">■</span> Part du CA
                            </div>
                        </div>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>
</main>

<?php require_once 'includes/footer.php'; ?>