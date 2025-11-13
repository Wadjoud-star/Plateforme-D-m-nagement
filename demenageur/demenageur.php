<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérification que l'utilisateur est connecté et a le rôle déménageur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'demenageur') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Tableau de bord Déménageur';
$userId = $_SESSION['user_id'];
$demenageurNom = htmlspecialchars($_SESSION['user_nom']);
$error = '';

// --- VARIABLES DYNAMIQUES (CALCULS SQL) ---
try {
    // 1. Annonces Disponibles
    $stmt1 = $pdo->prepare("
        SELECT COUNT(a.id) 
        FROM annonces a
        WHERE a.id NOT IN (
            SELECT p.annonce_id FROM propositions p WHERE p.statut = 'acceptee'
        )
    ");
    $stmt1->execute();
    $annoncesDisponibles = $stmt1->fetchColumn();

    // 2. Offres en Attente
    $stmt2 = $pdo->prepare("
        SELECT COUNT(id) 
        FROM propositions 
        WHERE demenageur_id = ? AND statut = 'en_attente'
    ");
    $stmt2->execute([$userId]);
    $offresEnAttente = $stmt2->fetchColumn();

    // 3. Missions Acceptées
    $stmt3 = $pdo->prepare("
        SELECT COUNT(id) 
        FROM propositions 
        WHERE demenageur_id = ? AND statut = 'acceptee'
    ");
    $stmt3->execute([$userId]);
    $missionsAcceptees = $stmt3->fetchColumn();

    // 4. Évaluation Moyenne
    $stmt4 = $pdo->prepare("
        SELECT AVG(note) 
        FROM evaluations 
        WHERE demenageur_id = ?
    ");
    $stmt4->execute([$userId]);
    $avgResult = $stmt4->fetchColumn();
    $evaluationMoyenne = ($avgResult !== null) ? number_format($avgResult, 1) : "N/A";

} catch (PDOException $e) {
    $error = "Erreur BDD: Impossible de charger les statistiques.";
    error_log("Demenageur Dashboard Error: " . $e->getMessage());
    $annoncesDisponibles = $offresEnAttente = $missionsAcceptees = 0;
}
// -----------------------------------------------------

?>

<div class="container py-5">
    <h1 class="mb-4 display-5 fw-bold text-primary"><i class="bi bi-truck-flatbed me-2"></i> Espace Déménageur, <?= $demenageurNom ?></h1>
    <p class="lead text-muted mb-5">Consultez les nouvelles annonces, gérez vos offres et suivez vos missions.</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row mb-5 gx-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-search me-2"></i> Annonces Disponibles</h5>
                    <p class="display-4 mb-0 fw-bolder"><?= $annoncesDisponibles ?></p> 
                    <p class="small mb-0">À la recherche de propositions</p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-clock-history me-2"></i> Offres en Attente</h5>
                    <p class="display-4 mb-0 fw-bolder"><?= $offresEnAttente ?></p>
                    <p class="small mb-0">Décisions client en cours</p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-check-circle-fill me-2"></i> Missions Acceptées</h5>
                    <p class="display-4 mb-0 fw-bolder"><?= $missionsAcceptees ?></p>
                    <p class="small mb-0">Contrats sécurisés</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-secondary text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-star-fill me-2"></i> Évaluation Moyenne</h5>
                    <p class="display-4 mb-0 fw-bolder"><?= $evaluationMoyenne ?></p>
                    <p class="small mb-0">Note de la clientèle (sur 5)</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row gy-4">

        <div class="col-12 col-md-6 col-lg-4">
            <a href="voir-annonces.php" class="text-decoration-none">
                <div class="card p-4 h-100 shadow-sm border-0 card-hover bg-light">
                    <i class="bi bi-cash-stack display-4 text-warning mb-3"></i> 
                    <h5 class="card-title fw-bold text-dark">Proposer des Prix & Annonces</h5>
                    <p class="card-text text-muted small">Consultez les demandes actives et soumettez vos offres de prix immédiatement.</p>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <a href="mes-offres.php" class="text-decoration-none">
                <div class="card p-4 h-100 shadow-sm border-0 card-hover bg-light">
                    <i class="bi bi-tag-fill display-4 text-warning mb-3"></i>
                    <h5 class="card-title fw-bold text-dark">Mes Offres Soumises</h5>
                    <p class="card-text text-muted small">Visualisez les propositions de prix que vous avez faites et leur statut (accepté/refusé).</p>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <a href="questions.php" class="text-decoration-none">
                <div class="card p-4 h-100 shadow-sm border-0 card-hover bg-light">
                    <i class="bi bi-question-circle-fill display-4 text-primary mb-3"></i>
                    <h5 class="card-title fw-bold text-dark">Demander Complément d'Info</h5>
                    <p class="card-text text-muted small">Contactez les clients pour obtenir des précisions avant de faire une offre.</p>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    /* Style pour les cartes de navigation */
    .card-hover:hover {
      box-shadow: 0 0 15px rgba(0, 123, 255, 0.7);
      cursor: pointer;
      transform: translateY(-5px);
      transition: 0.3s;
    }
</style>

<?php
include '../footer.php';
?>