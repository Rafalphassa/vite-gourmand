<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vite & Gourmand — Traiteur à Bordeaux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        
        :root {
            --bleu-turquoise : #1A5F7A;
            --or-chaud       : #D4A853;
            --creme          : #F0EDE4;
            --texte          : #1C1C1C;
            --blanc          : #FFFFFF;
        }

        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--creme);
            color: var(--texte);
        }

       
        h1, h2, h3, .titre-elegant {
            font-family: 'Playfair Display', serif;
        }

        
        .navbar {
            background-color: var(--bleu-turquoise);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.15);
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--blanc) !important;
            letter-spacing: 1px;
        }

        .navbar-brand span {
            color: var(--or-chaud);
        }

        .nav-link {
            color: rgba(255,255,255,0.85) !important;
            font-weight: 400;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            transition: color 0.3s ease;
            padding: 8px 15px !important;
        }

        .nav-link:hover {
            color: var(--or-chaud) !important;
        }

        
        .btn-navbar-connexion {
            background-color: transparent;
            border: 1.5px solid var(--or-chaud);
            color: var(--or-chaud) !important;
            border-radius: 25px;
            padding: 6px 20px !important;
            transition: all 0.3s ease;
        }

        .btn-navbar-connexion:hover {
            background-color: var(--or-chaud);
            color: var(--blanc) !important;
        }

        
        .btn-navbar-inscription {
            background-color: var(--or-chaud);
            border: 1.5px solid var(--or-chaud);
            color: var(--blanc) !important;
            border-radius: 25px;
            padding: 6px 20px !important;
            margin-left: 8px;
            transition: all 0.3s ease;
        }

        .btn-navbar-inscription:hover {
            background-color: transparent;
            color: var(--or-chaud) !important;
        }

        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 25px rgba(0,0,0,0.12);
            border-radius: 10px;
        }

        .dropdown-item:hover {
            background-color: var(--creme);
            color: var(--bleu-turquoise);
        }

        
        .btn-principal {
            background-color: var(--bleu-turquoise);
            color: var(--blanc);
            border: none;
            border-radius: 25px;
            padding: 10px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-principal:hover {
            background-color: #134a5e;
            color: var(--blanc);
            transform: translateY(-2px);
        }

        .btn-secondaire {
            background-color: var(--or-chaud);
            color: var(--blanc);
            border: none;
            border-radius: 25px;
            padding: 10px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondaire:hover {
            background-color: #b8902e;
            color: var(--blanc);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>


<nav class="navbar navbar-expand-lg">
    <div class="container">
        
    <a class="navbar-brand" href="/vite-gourmand/">
            Vite &amp; <span>Gourmand</span>
        </a>

        
        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menuNav"
                aria-label="Ouvrir le menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        
        <div class="collapse navbar-collapse" id="menuNav">
            <ul class="navbar-nav ms-auto align-items-center">

                <li class="nav-item">
                    <a class="nav-link" href="/vite-gourmand/">Accueil</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/vite-gourmand/menus.php">Nos Menus</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/vite-gourmand/contact.php">Contact</a>
                </li>

                <?php if(isset($_SESSION['utilisateur_id'])): ?>
                   
                    <li class="nav-item dropdown ms-2">
                        <a class="nav-link dropdown-toggle btn-navbar-connexion"
                           href="#" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($_SESSION['prenom']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">

                            <?php if($_SESSION['role_id'] == 1): ?>
                                <li>
                                    <a class="dropdown-item" href="/vite-gourmand/espace-admin.php">
                                        Espace Admin
                                    </a>
                                </li>
                            <?php elseif($_SESSION['role_id'] == 2): ?>
                                <li>
                                    <a class="dropdown-item" href="/vite-gourmand/espace-employe.php">
                                        Espace Employe
                                    </a>
                                </li>
                            <?php else: ?>
                                <li>
                                    <a class="dropdown-item" href="/vite-gourmand/espace-user.php">
                                        Mon Espace
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger"
                                   href="/vite-gourmand/deconnexion.php">
                                    Deconnexion
                                </a>
                            </li>
                        </ul>
                    </li>

                
            <?php else: ?>
                    <li class="nav-item ms-2">
                        <a class="nav-link btn-navbar-connexion"
                           href="/vite-gourmand/connexion.php">
                            Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-navbar-inscription"
                           href="/vite-gourmand/inscription.php">
                            Inscription
                        </a>
                    </li>
            <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>