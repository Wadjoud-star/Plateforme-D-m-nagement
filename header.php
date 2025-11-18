<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Mon Déménagement';

// --- NOUVEAU : Définition du chemin Racine du Projet ---
// Si votre projet est accessible via http://localhost/demenagement/
$projectRoot = '/demenagement/'; 


$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? null;
$userName = $_SESSION['user_nom'] ?? 'Utilisateur';

// --- Définition du lien de tableau de bord (Chemin ABSOLU) ---
$dashboardLink = $projectRoot . 'index.php'; // Liens toujours basés sur la racine
$dashboardText = 'Accueil';

if ($isLoggedIn) {
    if ($userRole === 'admin') {
        // Chemin absolu : /demenagement/admin/dashboard.php
        $dashboardLink = $projectRoot . 'admin/dashboard.php';
        $dashboardText = 'Admin Dashboard';
    } elseif ($userRole === 'demenageur') {
        // Chemin absolu : /demenagement/demenageur/demenageur.php
        $dashboardLink = $projectRoot . 'demenageur/demenageur.php';
        $dashboardText = 'Espace Déménageur';
    } else { // client
        // Chemin absolu : /demenagement/client/client.php
        $dashboardLink = $projectRoot . 'client/client.php';
        $dashboardText = 'Mon Espace Client';
    }
}
// --- FIN de la définition du lien ---
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* Styles CSS communs à toutes les pages */
        .hero-banner {
            background-size: cover;
            background-position: center;
            height: 40vh;
            position: relative;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
            margin-bottom: 30px;
        }
        .hero-banner-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1;
        }
        .hero-banner-content {
            z-index: 2;
            position: relative;
            text-align: center;
        }
        /* Style pour le Header (Rendu plus distinctif) */
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            color: white !important;
        }
        .navbar-text-welcome {
            color: rgba(255, 255, 255, 0.8);
            padding-right: 0.5rem;
            border-right: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow">
  <div class="container">
    <a class="navbar-brand" href="<?= $projectRoot ?>index.php">
        <i class="bi bi-truck-flatbed me-2"></i> Mon Déménagement
    </a>
    <button
      class="navbar-toggler"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#navbarNav"
      aria-controls="navbarNav"
      aria-expanded="false"
      aria-label="Basculer la navigation"
    >
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item"><a class="nav-link" href="index.php">Accueil</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
        
        <?php if ($isLoggedIn): ?>
            
            <li class="nav-item me-3">
                <span class="navbar-text fw-bold navbar-text-welcome">
                    Bonjour, <?= htmlspecialchars($userName) ?>
                </span>
            </li>
            
            <li class="nav-item me-2">
                <a class="btn btn-light" href="<?= $dashboardLink ?>">
                    <i class="bi bi-person-circle me-1"></i> <?= $dashboardText ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="btn btn-warning" href="<?= $projectRoot ?>deconnexion.php">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </li>
            
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="inscription.php">Inscription</a></li>
            <li class="nav-item">
                <a class="btn btn-warning ms-2" href="connexion.php">
                    <i class="bi bi-lock-fill me-1"></i> Se connecter
                </a>
            </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>