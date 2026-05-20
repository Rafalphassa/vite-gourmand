<?php
require_once __DIR__ . '/../config/database.php';
$horaires = getDB()->query("SELECT * FROM horaire ORDER BY horaire_id ASC")->fetchAll();
?>

<footer class="mt-5 py-5" style="background-color: #1A5F7A; color: #F0EDE4;">
    <div class="container">
        <div class="row g-4">

            <div class="col-md-4">
                <h5 style="font-family:'Playfair Display',serif; color:#D4A853;">
                    Vite &amp; Gourmand
                </h5>
                <p class="small">Traiteur à Bordeaux depuis 25 ans.<br>
                Julie &amp; José pour tous vos événements.</p>
                <p class="small mb-0">jose@vitegourmand.fr</p>
                <p class="small">06 12 34 56 78</p>
            </div>

            <div class="col-md-4">
                <h5 style="font-family:'Playfair Display',serif; color:#D4A853;">
                    Horaires
                </h5>
                <?php foreach($horaires as $h): ?>
                <div class="d-flex justify-content-between small">
                    <span><?php echo htmlspecialchars($h['jour']); ?></span>
                    <?php if($h['ferme']): ?>
                        <span style="color:#D4A853;">Ferme</span>
                    <?php else: ?>
                        <span><?php echo htmlspecialchars($h['heure_ouverture']).' - '.htmlspecialchars($h['heure_fermeture']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="col-md-4">
                <h5 style="font-family:'Playfair Display',serif; color:#D4A853;">
                    Legal
                </h5>
                <ul class="list-unstyled small">
                    <li><a href="/vite-gourmand/mentions-legales.php" style="color:#F0EDE4;">Mentions legales</a></li>
                    <li><a href="/vite-gourmand/cgv.php" style="color:#F0EDE4;">CGV</a></li>
                    <li><a href="/vite-gourmand/contact.php" style="color:#F0EDE4;">Contact</a></li>
                </ul>
            </div>

        </div>

        <hr style="border-color:rgba(255,255,255,0.2); margin-top:30px;">
        <p class="text-center small mb-0">
            &copy; <?php echo date('Y'); ?> Vite &amp; Gourmand — Tous droits réservés
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>