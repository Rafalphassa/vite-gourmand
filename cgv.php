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
                        Conditions Générales de Vente
                    </h1>
                    <p style="color:#999; font-size:0.85rem; margin-bottom:40px; border-bottom:1px solid #eee; padding-bottom:20px;">
                        Dernière mise à jour : <?php echo date('d/m/Y'); ?>
                    </p>

                    <!-- Objet -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            1. Objet
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            Les présentes Conditions Générales de Vente régissent les relations contractuelles entre
                            <strong>Vite &amp; Gourmand</strong> (ci-après "le prestataire") et tout client passant
                            commande via le site ou par tout autre moyen. Toute commande implique l'acceptation
                            pleine et entière des présentes CGV.
                        </p>
                    </div>

                    <!-- Commandes -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            2. Commandes
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            Toute commande est soumise à validation par l'équipe <strong>Vite &amp; Gourmand</strong>.
                            Le client doit respecter le délai minimum de commande indiqué sur chaque fiche menu.
                            Le nombre minimum de personnes par menu doit obligatoirement être respecté.
                            Une réduction de <strong>10%</strong> est appliquée pour toute commande incluant au moins
                            5 personnes supplémentaires par rapport au minimum requis.
                        </p>
                    </div>

                    <!-- Tarifs et livraison -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            3. Tarifs et frais de livraison
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            Les prix sont indiqués en euros TTC par personne. Les frais de livraison s'appliquent
                            comme suit :
                        </p>
                        <ul style="color:#555; line-height:2; margin-top:10px; padding-left:20px;">
                            <li>Livraison dans Bordeaux : <strong>gratuite</strong></li>
                            <li>Livraison hors Bordeaux : <strong>5,00 €</strong> de base, majorés de <strong>0,59 € par kilomètre</strong> parcouru</li>
                        </ul>
                    </div>

                    <!-- Annulation et modification -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            4. Annulation et modification de commande
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            Le client peut annuler ou modifier sa commande tant que celle-ci n'a pas été
                            acceptée par l'équipe. Une fois la commande passée au statut "accepté",
                            toute annulation ou modification nécessite un contact préalable avec l'équipe
                            (par téléphone ou email). L'annulation par l'équipe est motivée et implique
                            un contact avec le client.
                        </p>
                    </div>

                    <!-- Matériel prêté -->
                    <div style="margin-bottom:35px; background:#fff3cd; border-left:4px solid #D4A853; border-radius:8px; padding:20px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#856404; font-size:1.2rem; margin-bottom:15px;">
                            5. Matériel prêté
                        </h2>
                        <p style="color:#856404; line-height:1.8; margin:0;">
                            Dans le cadre de certaines prestations, du matériel peut être prêté au client.
                            Ce matériel doit impérativement être restitué à <strong>Vite &amp; Gourmand</strong>
                            dans un délai de <strong>10 jours ouvrés</strong> suivant la prestation.
                        </p>
                        <p style="color:#856404; line-height:1.8; margin-top:10px; margin-bottom:0;">
                            En cas de non-restitution du matériel dans ce délai, le client s'expose à des
                            frais de <strong>600,00 euros</strong> qui lui seront facturés.
                            Pour organiser la restitution du matériel, le client doit prendre contact avec
                            la société via la page de contact ou par email.
                        </p>
                    </div>

                    <!-- Responsabilité -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            6. Responsabilité
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            <strong>Vite &amp; Gourmand</strong> s'engage à réaliser la prestation commandée dans
                            les meilleures conditions. En cas d'impossibilité de réalisation (force majeure,
                            événement exceptionnel), le client sera informé dans les meilleurs délais et
                            la commande sera annulée sans frais.
                        </p>
                    </div>

                    <!-- Avis clients -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            7. Avis clients
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            Après chaque prestation terminée, le client peut déposer un avis (note de 1 à 5 et
                            commentaire) depuis son espace personnel. Les avis sont soumis à validation par
                            l'équipe avant publication sur le site.
                        </p>
                    </div>

                    <!-- Données personnelles -->
                    <div style="margin-bottom:35px;">
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            8. Données personnelles
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            Les données personnelles collectées lors de la commande sont traitées conformément
                            à notre politique de confidentialité détaillée dans les
                            <a href="/vite-gourmand/mentions-legales.php" style="color:#1A5F7A;">mentions légales</a>.
                        </p>
                    </div>

                    <!-- Droit applicable -->
                    <div>
                        <h2 style="font-family:'Playfair Display',serif; color:#1A5F7A; font-size:1.2rem; margin-bottom:15px;">
                            9. Droit applicable et litiges
                        </h2>
                        <p style="color:#555; line-height:1.8; margin:0;">
                            Les présentes CGV sont soumises au droit français. En cas de litige,
                            et après tentative de résolution amiable, les tribunaux compétents
                            seront ceux de Bordeaux.
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