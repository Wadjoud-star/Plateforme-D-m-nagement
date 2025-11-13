<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérification de sécurité : l'utilisateur doit être connecté et avoir le rôle 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Tableau de Bord Administrateur';
$userId = $_SESSION['user_id'];
$adminNom = htmlspecialchars($_SESSION['user_nom']);
$error = '';

// --- VARIABLES DYNAMIQUES (STATISTIQUES DE SUPERVISION) ---
try {
    // 1. Nombre total de Clients
    $stmt1 = $pdo->prepare("SELECT COUNT(id) FROM utilisateurs WHERE role = 'client'");
    $stmt1->execute();
    $totalClients = $stmt1->fetchColumn();

    // 2. Nombre total de Déménageurs
    $stmt2 = $pdo->prepare("SELECT COUNT(id) FROM utilisateurs WHERE role = 'demenageur'");
    $stmt2->execute();
    $totalDemenageurs = $stmt2->fetchColumn();

    // 3. Nombre d'Annonces Actives (sans offre acceptée)
    $stmt3 = $pdo->prepare("
        SELECT COUNT(a.id) 
        FROM annonces a
        WHERE a.id NOT IN (SELECT p.annonce_id FROM propositions p WHERE p.statut = 'acceptee')
    ");
    $stmt3->execute();
    $annoncesActives = $stmt3->fetchColumn();

    // 4. Nombre de Propositions en Attente de Décision
    $stmt4 = $pdo->prepare("SELECT COUNT(id) FROM propositions WHERE statut = 'en_attente'");
    $stmt4->execute();
    $propositionsEnAttente = $stmt4->fetchColumn();

} catch (PDOException $e) {
    $error = "Erreur BDD: Impossible de charger les statistiques.";
    error_log("Admin Dashboard Error: " . $e->getMessage());
    $totalClients = $totalDemenageurs = $annoncesActives = $propositionsEnAttente = 0;
}
// -----------------------------------------------------
?>

<div class="container py-5">
    <h1 class="mb-4 display-5 fw-bold text-danger"><i class="bi bi-shield-fill me-2"></i> Tableau de Bord Administrateur, <?= $adminNom ?></h1>
    <p class="lead text-muted mb-5">Bienvenue dans l'espace de supervision de la plateforme. Gérez les utilisateurs, les annonces et surveillez l'activité.</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row mb-5 gx-4">
        
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-people-fill me-2"></i> Clients</h5>
                    <p class="display-4 mb-0 fw-bolder"><?= $totalClients ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-truck me-2"></i> Déménageurs</h5>
                    <p class="display-4 mb-0 fw-bolder"><?= $totalDemenageurs ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-list-task me-2"></i> Annonces Actives</h5>
                    <p class="display-4 mb-0 fw-bolder"><?= $annoncesActives ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-secondary text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-hourglass-split me-2"></i> Propositions en Attente</h5>
                    <p class="display-4 mb-0 fw-bolder"><?= $propositionsEnAttente ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="row gy-4">

        <div class="col-12 col-md-6 col-lg-6">
            <a href="gere.php?type=comptes" class="text-decoration-none">
                <div class="card p-4 h-100 shadow-sm border-0 card-hover bg-light">
                    <i class="bi bi-people-fill display-4 text-danger mb-3"></i>
                    <h5 class="card-title fw-bold text-dark">Gérer les Comptes Utilisateurs</h5>
                    <p class="card-text text-muted small">Consulter, activer ou désactiver les comptes Clients et Déménageurs.</p>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-6">
            <a href="gere.php?type=annonces" class="text-decoration-none">
                <div class="card p-4 h-100 shadow-sm border-0 card-hover bg-light">
                    <i class="bi bi-archive-fill display-4 text-danger mb-3"></i>
                    <h5 class="card-title fw-bold text-dark">Gérer les Annonces</h5>
                    <p class="card-text text-muted small">Supprimer les annonces non conformes ou problématiques (surveillance du contenu).</p>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    /* Style spécifique pour les cartes */
    .card-hover:hover {
      box-shadow: 0 0 15px rgba(220, 53, 69, 0.7); /* Rouge Bootstrap pour admin */
      cursor: pointer;
      transform: translateY(-5px);
      transition: 0.3s;
    }
</style>

<?php
include '../footer.php';
?>