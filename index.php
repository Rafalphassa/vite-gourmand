<?php 
require_once 'config/database.php';
require_once 'includes/header.php'; 
?>

<?php
$avis = $pdo->query("
    SELECT a.note, a.commentaire, u.prenom, u.nom 
    FROM avis a
    JOIN utilisateur u ON a.utilisateur_id = u.utilisateur_id
    WHERE a.statut = 'validé'
    ORDER BY a.avis_id DESC
    LIMIT 6
")->fetchAll();
?>

<!-- HERO -->
<section style="
    background: linear-gradient(135deg, #1A5F7A 0%, #134a5e 60%, #0d3545 100%);
    min-height: 85vh;
    display: flex;
    align-items: center;
">
    <div class="container py-5">
        <div class="row align-items-center">

            <div class="col-lg-7">
                <h1 style="
                    font-family:'Playfair Display',serif;
                    font-size:clamp(2.2rem, 5vw, 3.8rem);
                    color:#FFFFFF;
                    line-height:1.2;
                    margin-bottom:20px;
                ">
                    Vite & Gourmand<br>
                    <span style="color:#D4A853;">Traiteur à Bordeaux</span>
                </h1>
                <p style="color:rgba(255,255,255,0.8); font-size:1.1rem; max-width:500px; line-height:1.8;">
                    Julie & José proposent leurs prestations pour tout événement 
                    au travers d'un menu en constante évolution depuis 25 ans.
                </p>
                <div class="mt-4 d-flex gap-3 flex-wrap">
                    <a href="/vite-gourmand/menus.php" style="
                        background:#D4A853; color:#fff; border:none;
                        border-radius:25px; padding:12px 35px;
                        font-weight:600; text-decoration:none;
                    ">
                        Voir nos menus
                    </a>
                    <a href="/vite-gourmand/contact.php" style="
                        border:2px solid rgba(255,255,255,0.5); color:#fff;
                        border-radius:25px; padding:12px 35px;
                        font-weight:600; text-decoration:none;
                    ">
                        Nous contacter
                    </a>
                </div>
            </div>

            <!-- Présentation de l'équipe -->
            <div class="col-lg-5 mt-5 mt-lg-0">
                <div style="
                    background:rgba(255,255,255,0.08);
                    border:1px solid rgba(255,255,255,0.15);
                    border-radius:15px; padding:35px;
                ">
                    <h3 style="
                        font-family:'Playfair Display',serif;
                        color:#D4A853; margin-bottom:20px;
                    ">Notre équipe</h3>
                    <p style="color:rgba(255,255,255,0.85); line-height:1.8;">
                        Julie et José forment une équipe de deux personnes passionnées, 
                        à votre service pour tout événement — repas de Noël, Pâques, 
                        ou tout autre occasion.
                    </p>
                    <hr style="border-color:rgba(255,255,255,0.2);">
                    <p style="color:rgba(255,255,255,0.7); font-size:0.9rem; margin:0;">
                        25 ans d'expérience à Bordeaux
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- SECTION : Présentation professionnalisme -->
<section class="py-5" style="background:#FFFFFF;">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 style="font-family:'Playfair Display',serif; font-size:2.2rem;">
                Notre professionnalisme
            </h2>
        </div>

        <div class="row g-4">
            <?php
            $atouts = [
                ['Menus sur mesure', 'Chaque événement est unique. Nos menus s\'adaptent à vos envies, votre budget et le nombre de convives.'],
                ['Produits frais', 'Nous travaillons avec des producteurs locaux de la région bordelaise pour garantir fraîcheur et qualité.'],
                ['Equipe dévouée', 'Julie et José mettent 25 ans de savoir-faire à votre service.'],
                ['Livraison', 'Nous nous déplaçons chez vous à Bordeaux et alentours.'],
            ];
            foreach($atouts as $a): ?>
            <div class="col-md-6 col-lg-3">
                <div style="
                    background:#F0EDE4; border-radius:15px;
                    padding:30px 25px; text-align:center;
                    height:100%;
                ">
                    <h5 style="font-family:'Playfair Display',serif; color:#1A5F7A;">
                        <?php echo $a[0]; ?>
                    </h5>
                    <p style="color:#666; font-size:0.9rem; margin:0;">
                        <?php echo $a[1]; ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SECTION : Avis clients validés -->
<section class="py-5" style="background:#F0EDE4;">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 style="font-family:'Playfair Display',serif; font-size:2.2rem;">
                Avis de nos clients
            </h2>
        </div>

        <?php if(empty($avis)): ?>
            <p class="text-center" style="color:#999;">
                Aucun avis pour le moment.
            </p>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach($avis as $a): ?>
            <div class="col-md-6 col-lg-4">
                <div style="
                    background:#FFFFFF; border-radius:15px;
                    padding:30px; height:100%;
                    box-shadow:0 3px 20px rgba(0,0,0,0.06);
                ">
                    <div style="color:#D4A853; font-size:1.1rem; margin-bottom:12px;">
                        <?php echo str_repeat('★', $a['note']) . str_repeat('☆', 5 - $a['note']); ?>
                    </div>
                    <p style="color:#444; font-style:italic; line-height:1.7;">
                        "<?php echo htmlspecialchars($a['commentaire']); ?>"
                    </p>
                    <p style="color:#1A5F7A; font-weight:600; font-size:0.9rem; margin:0;">
                        — <?php echo htmlspecialchars($a['prenom'].' '.$a['nom']); ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>