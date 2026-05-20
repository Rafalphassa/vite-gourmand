<?php
session_start();
require_once 'config/database.php';

$succes  = false;
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre       = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $email       = trim($_POST['email'] ?? '');

    if (empty($titre))                                    $erreurs[] = 'Le titre est obligatoire.';
    if (empty($description))                              $erreurs[] = 'La description est obligatoire.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))       $erreurs[] = 'Adresse email invalide.';

    if (empty($erreurs)) {
        // Envoi du mail a l'entreprise (CDC)
        $destinataire = 'contact@vitegourmand.fr';
        $sujet        = '[Contact] ' . $titre;
        $corps        = "Message recu depuis le formulaire de contact.\n\n";
        $corps       .= "De : " . $email . "\n";
        $corps       .= "Titre : " . $titre . "\n\n";
        $corps       .= "Message :\n" . $description;
        $headers      = 'From: noreply@vitegourmand.fr' . "\r\n"
                      . 'Reply-To: ' . $email . "\r\n"
                      . 'Content-Type: text/plain; charset=UTF-8';

        mail($destinataire, $sujet, $corps, $headers);
        $succes = true;
    }
}

require_once 'includes/header.php';
?>

<main id="contenu-principal">
<section class="py-5" style="background:#F0EDE4; min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div style="background:#fff; border-radius:15px; padding:40px; box-shadow:0 3px 20px rgba(0,0,0,0.08);">

                    <h1 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:5px;">
                        Nous contacter
                    </h1>
                    <p style="color:#999; font-size:0.9rem; margin-bottom:30px;">
                        Une question ? Nous vous repondrons par mail.
                    </p>

                    <?php if ($succes): ?>
                        <div role="alert" style="background:#d4edda; color:#155724; border-radius:10px; padding:20px; text-align:center;">
                            Votre message a ete envoye. Nous vous repondrons dans les plus brefs delais.
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
                            <div class="mb-3">
                                <label for="titre" class="form-label small fw-bold">Titre</label>
                                <input type="text" id="titre" name="titre" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['titre'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label small fw-bold">Description</label>
                                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label for="email_contact" class="form-label small fw-bold">Votre email</label>
                                <input type="email" id="email_contact" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            <button type="submit"
                                    style="background:#1A5F7A; color:#fff; border:none; border-radius:25px; padding:12px 35px; font-weight:600; width:100%; font-size:1rem; cursor:pointer;">
                                Envoyer
                            </button>
                        </form>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
</main>

<?php require_once 'includes/footer.php'; ?>