<?php
session_start();
require_once 'config/database.php';
require_once 'includes/header.php';
?>

<section class="py-5" style="min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div style="background:#fff; border-radius:15px; padding:50px; box-shadow:0 3px 20px rgba(0,0,0,0.08);">

                    <h1 style="font-family:'Playfair Display',serif; color:#1A5F7A; margin-bottom:5px;">
                        Mentions légales
                    </h1>
                    <p style="color:#999; font-size:0.85rem; margin-bottom:40px; border-bottom:1px solid #eee; padding-bottom:20px;">
                        Dernière mise à jour : <?php echo date('d/m/Y'); ?>
                    </p>

                    <!-- Éditeur -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            1. Éditeur du site
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            Le site <strong>Vite &amp; Gourmand</strong> est édité par :<br>
                            <strong>Vite &amp; Gourmand</strong><br>
                            Entreprise fondée il y a 25 ans<br>
                            Siège social : Bordeaux, France<br>
                            Contact : <a href="mailto:contact@vitegourmand.fr" style="color:#1A5F7A;">contact@vitegourmand.fr</a>
                        </p>
                    </div>

                    <!-- Hébergement -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            2. Hébergement
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            Ce site est hébergé par un prestataire d'hébergement web professionnel.
                            Pour toute question relative à l'hébergement, vous pouvez contacter l'éditeur du site.
                        </p>
                    </div>

                    <!-- Propriété intellectuelle -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            3. Propriété intellectuelle
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            L'ensemble du contenu de ce site (textes, images, menus, descriptions) est la propriété exclusive
                            de <strong>Vite &amp; Gourmand</strong>. Toute reproduction, même partielle, est interdite sans
                            autorisation écrite préalable.
                        </p>
                    </div>

                    <!-- Données personnelles / RGPD -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            4. Protection des données personnelles (RGPD)
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique
                            et Libertés, vous disposez des droits suivants concernant vos données personnelles :
                        </p>
                        <ul style="color:#555; line-height:2; margin-top:10px; padding-left:20px;">
                            <li>Droit d'accès à vos données</li>
                            <li>Droit de rectification</li>
                            <li>Droit à l'effacement (droit à l'oubli)</li>
                            <li>Droit à la portabilité</li>
                            <li>Droit d'opposition au traitement</li>
                        </ul>
                        <p style="color:#555; line-height:1.8; margin-top:10px;">
                            Pour exercer ces droits, contactez-nous à :
                            <a href="mailto:contact@vitegourmand.fr" style="color:#1A5F7A;">contact@vitegourmand.fr</a><br>
                            Les données collectées (nom, prénom, email, téléphone, adresse) sont utilisées uniquement
                            dans le cadre de la gestion des commandes et de la relation client. Elles ne sont jamais
                            cédées à des tiers.
                        </p>
                    </div>

                    <!-- Cookies -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            5. Cookies
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            Ce site utilise uniquement des cookies de session nécessaires au bon fonctionnement de
                            l'application (authentification). Aucun cookie publicitaire ou de traçage n'est utilisé.
                        </p>
                    </div>

                    <!-- Responsabilité -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            6. Limitation de responsabilité
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            <strong>Vite &amp; Gourmand</strong> s'efforce de fournir des informations exactes et à jour.
                            Cependant, l'éditeur ne saurait être tenu responsable des erreurs ou omissions dans le contenu
                            du site, ni des dommages directs ou indirects pouvant résulter de son utilisation.
                        </p>
                    </div>

                    <!-- Droit applicable -->
                    <div>
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            7. Droit applicable
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            Le présent site et ses mentions légales sont soumis au droit français.
                            En cas de litige, les tribunaux compétents seront ceux de Bordeaux.
                        </p>
                    </div>

                </div>

                <div class="text-center mt-4">
                    <a href="/vite-gourmand/index.php" style="color:#1A5F7A; font-size:0.9rem; text-decoration:none;">
                        &larr; Retour à l'accueil
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>