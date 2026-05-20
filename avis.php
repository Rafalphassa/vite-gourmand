<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: /vite-gourmand/connexion.php');
    exit;
}

$pdo = getDB();

$commande_id = isset($_GET['commande_id']) ? (int)$_GET['commande_id'] : 0;
if ($commande_id === 0) {
    header('Location: /vite-gourmand/espace-user.php');
    exit;
}

// Verifier que la commande appartient a l'utilisateur et est terminee
$stmt = $pdo->prepare("SELECT * FROM commande WHERE commande_id = ? AND utilisateur_id = ? AND statut = 'terminee'");
$stmt->execute([$commande_id, $_SESSION['utilisateur_id']]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    header('Location: /vite-gourmand/espace-user.php');
    exit;
}

// Verifier si avis deja donne
$stmt = $pdo->prepare('SELECT avis_id FROM avis WHERE commande_id = ?');
$stmt->execute([$commande_id]);
$avis_existant = $stmt->fetch();

$succes  = false;
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$avis_existant) {
    $note        = (int)($_POST['note'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');

    if ($note < 1 || $note > 5) $erreurs[] = 'La note doit etre entre 1 et 5.';
    if (empty($commentaire))     $erreurs[] = 'Le commentaire est obligatoire.';

    if (empty($erreurs)) {
        $pdo->prepare("INSERT INTO avis (commande_id, utilisateur_id, note, commentaire, statut) VALUES (?,?,?,?,'en attente')")
            ->execute([$commande_id, $_SESSION['utilisateur_id'], $note, $commentaire]);
        $succes = true;
    }
}

require_once 'includes/header.php';
?>

<main id="contenu-principal">
<section class="py-5" style="background:#F0EDE4; min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div style="background:#fff; border-radius:15px; padding:40px; box-shadow:0 3px 20px rgba(0,0,0,0.08);">

                    <h1 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:5px;">
                        Donner mon avis
                    </h1>
                    <p style="color:#999; font-size:0.9rem; margin-bottom:30px;">
                        Commande N° <?php echo htmlspecialchars($commande['numero_commande']); ?>
                    </p>

                    <?php if ($avis_existant): ?>
                        <div role="alert" style="background:#fff3cd; color:#856404; border-radius:10px; padding:20px;">
                            Vous avez deja donne votre avis pour cette commande.
                        </div>

                    <?php elseif ($succes): ?>
                        <div role="alert" style="background:#d4edda; color:#155724; border-radius:10px; padding:20px; text-align:center;">
                            Merci pour votre avis ! Il sera visible apres validation par notre equipe.
                        </div>

                    <?php else: ?>

                        <?php if (!empty($erreurs)): ?>
                        <div role="alert" style="background:#f8d7da; color:#721c24; border-radius:10px; padding:15px; margin-bottom:20px;">
                            <?php foreach ($erreurs as $e): ?>
                                <div>- <?php echo htmlspecialchars($e); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" novalidate>

                            <!-- Etoiles interactives -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold" style="font-size:0.9rem;" id="label-note">
                                    Note (1 a 5 etoiles)
                                </label>
                                <div role="group" aria-labelledby="label-note" style="display:flex; gap:8px; margin-top:6px;">
                                    <?php
                                    $note_post = (int)($_POST['note'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++):
                                    ?>
                                    <label for="note_<?php echo $i; ?>"
                                           style="cursor:pointer; font-size:2rem; line-height:1; color:<?php echo $note_post >= $i ? '#D4A853' : '#ccc'; ?>;"
                                           class="etoile-label"
                                           data-val="<?php echo $i; ?>">
                                        <input type="radio" id="note_<?php echo $i; ?>" name="note"
                                               value="<?php echo $i; ?>"
                                               style="position:absolute; opacity:0; width:0; height:0;"
                                               <?php echo $note_post === $i ? 'checked' : ''; ?>>
                                        &#9733;
                                    </label>
                                    <?php endfor; ?>
                                </div>
                                <div style="font-size:0.8rem; color:#888; margin-top:4px;" id="note-texte">
                                    <?php
                                    $labels = ['', 'Tres decu', 'Decu', 'Correct', 'Bien', 'Excellent'];
                                    echo $note_post > 0 ? $labels[$note_post] : 'Cliquez pour noter';
                                    ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="commentaire" class="form-label fw-semibold" style="font-size:0.9rem;">
                                    Commentaire
                                </label>
                                <textarea id="commentaire" name="commentaire" class="form-control"
                                          rows="5" required
                                          placeholder="Partagez votre experience..."><?php echo htmlspecialchars($_POST['commentaire'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit"
                                    style="background:#1A5F7A; color:#fff; border:none; border-radius:25px; padding:12px 35px; font-weight:600; width:100%; font-size:1rem; cursor:pointer;">
                                Envoyer mon avis
                            </button>
                        </form>

                    <?php endif; ?>

                    <div style="text-align:center; margin-top:20px;">
                        <a href="/vite-gourmand/espace-user.php"
                           style="color:#1A5F7A; font-size:0.9rem; text-decoration:none;">
                            Retour a mon espace
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</main>

<script>
(function() {
    var labelsNL  = document.querySelectorAll('.etoile-label');
    var labels    = Array.from(labelsNL);
    var texte     = document.getElementById('note-texte');
    var libelles  = ['', 'Tres decu', 'Decu', 'Correct', 'Bien', 'Excellent'];

    function colorier(valeur) {
        labels.forEach(function(l) {
            l.style.color = parseInt(l.getAttribute('data-val'), 10) <= valeur ? '#D4A853' : '#ccc';
        });
        if (texte) { texte.textContent = libelles[valeur] || 'Cliquez pour noter'; }
    }

    labels.forEach(function(label) {
        label.addEventListener('click', function() {
            colorier(parseInt(label.getAttribute('data-val'), 10));
        });
        label.addEventListener('mouseover', function() {
            colorier(parseInt(label.getAttribute('data-val'), 10));
        });
    });

    var container = labels.length > 0 ? labels[0].parentElement : null;
    if (container) {
        container.addEventListener('mouseleave', function() {
            var checked = document.querySelector('input[name="note"]:checked');
            colorier(checked ? parseInt(checked.value, 10) : 0);
        });
    }
})();
</script>

<?php require_once 'includes/footer.php'; ?>