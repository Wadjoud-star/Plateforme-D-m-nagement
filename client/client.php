<?php
// On démarre la session (si non démarrée par header.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier la session et le rôle : Si non connecté OU rôle n'est pas client, redirection
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: ../connexion.php');
    exit();
}

// Inclure la connexion à la base de données
require_once '../Config.php'; 

$userId = $_SESSION['user_id'];
$error = '';

// --- VARIABLES DYNAMIQUES (CALCULS SQL) ---
try {
    // 1. Annonces en cours (Annonces du client SANS offre acceptée)
    $stmt1 = $pdo->prepare("
        SELECT COUNT(a.id)
        FROM annonces a
        LEFT JOIN propositions p ON a.id = p.annonce_id AND p.statut = 'acceptee'
        WHERE a.utilisateur_id = ? AND p.id IS NULL
    ");
    $stmt1->execute([$userId]);
    $annoncesEnCours = $stmt1->fetchColumn();

    // 2. Propositions reçues (Total des propositions reçues pour TOUTES les annonces du client)
    $stmt2 = $pdo->prepare("
        SELECT COUNT(p.id)
        FROM propositions p
        JOIN annonces a ON p.annonce_id = a.id
        WHERE a.utilisateur_id = ?
    ");
    $stmt2->execute([$userId]);
    $propositionsRecues = $stmt2->fetchColumn();

    // 3. Déménagements effectués (Missions évaluées par le client, impliquant la complétion)
    $stmt3 = $pdo->prepare("
        SELECT COUNT(e.id)
        FROM evaluations e
        WHERE e.client_id = ?
    ");
    $stmt3->execute([$userId]);
    $demenagementsEffectues = $stmt3->fetchColumn();

} catch (PDOException $e) {
    $error = "Erreur BDD: Impossible de charger les statistiques.";
    error_log("Client Dashboard Error: " . $e->getMessage());
    $annoncesEnCours = $propositionsRecues = $demenagementsEffectues = 0;
}
// -----------------------------------------------------

// On définit le titre de la page
$pageTitle = 'Tableau de bord Client - ' . htmlspecialchars($_SESSION['user_nom']);

// Inclusion du header (qui gère l'uniformité et le début du HTML)
include '../header.php'; 

$clientNom = htmlspecialchars($_SESSION['user_nom']);
?>

<div class="container py-5">
    <h1 class="mb-4 display-5 fw-bold text-primary"><i class="bi bi-house-door-fill me-2"></i> Bienvenue, <?= $clientNom ?></h1>
    <p class="lead text-muted mb-5">Gérez ici toutes les étapes de votre déménagement : de la publication à l'évaluation.</p>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row mb-5 gx-4">
        
        <div class="col-md-4 mb-3">
            <div class="card bg-warning text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-clock-history me-2"></i> Annonces en cours</h5>
                    <p class="display-4 mb-0 fw-bolder"><?= $annoncesEnCours ?></p> 
                    <p class="small mb-0">Demandes en attente de propositions</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-currency-euro me-2"></i> Propositions reçues</h5>
                    <p class="display-4 mb-0 fw-bolder"><?= $propositionsRecues ?></p>
                    <p class="small mb-0">Total des offres soumises</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card bg-primary text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-award-fill me-2"></i> Déménagements effectués</h5>
                    <p class="display-4 mb-0 fw-bolder"><?= $demenagementsEffectues ?></p>
                    <p class="small mb-0">Missions évaluées/complétées</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row gy-4">

        <div class="col-12 col-md-6 col-lg-4">
            <a href="creer-annonce.php" class="text-decoration-none">
                <div class="card p-4 h-100 shadow-sm border-0 card-hover bg-light">
                    <i class="bi bi-pencil-square display-4 text-success mb-3"></i>
                    <h5 class="card-title fw-bold text-dark">Créer une Annonce</h5>
                    <p class="card-text text-muted small">Publiez une nouvelle demande de déménagement en détaillant vos besoins.</p>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <a href="mes-annonces.php" class="text-decoration-none">
                <div class="card p-4 h-100 shadow-sm border-0 card-hover bg-light">
                    <i class="bi bi-box-seam-fill display-4 text-info mb-3"></i>
                    <h5 class="card-title fw-bold text-dark">Mes Annonces</h5>
                    <p class="card-text text-muted small">Consultez, modifiez ou annulez les annonces que vous avez déjà publiées.</p>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <a href="propositions.php" class="text-decoration-none">
                <div class="card p-4 h-100 shadow-sm border-0 card-hover bg-light">
                    <i class="bi bi-tag-fill display-4 text-warning mb-3"></i>
                    <h5 class="card-title fw-bold text-dark">Propositions Reçues</h5>
                    <p class="card-text text-muted small">Visualisez toutes les offres soumises par les déménageurs pour vos projets.</p>
                </div>
            </a>
        </div>
        
        <div class="col-12 col-md-6 col-lg-4">
            <a href="repondre-questions.php" class="text-decoration-none">
                <div class="card p-4 h-100 shadow-sm border-0 card-hover bg-light">
                    <i class="bi bi-chat-left-text-fill display-4 text-info mb-3"></i>
                    <h5 class="card-title fw-bold text-dark">Questions des Déménageurs</h5>
                    <p class="card-text text-muted small">Visualisez les questions en attente de réponse avant qu'ils ne soumettent leur devis.</p>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <a href="choix-demenageur.php" class="text-decoration-none">
                <div class="card p-4 h-100 shadow-sm border-0 card-hover bg-light">
                    <i class="bi bi-hand-thumbs-up-fill display-4 text-primary mb-3"></i>
                    <h5 class="card-title fw-bold text-dark">Choix du Déménageur</h5>
                    <p class="card-text text-muted small">Comparez les profils, les propositions, et sélectionnez le professionnel pour votre déménagement.</p>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <a href="evaluer.php" class="text-decoration-none">
                <div class="card p-4 h-100 shadow-sm border-0 card-hover bg-light">
                    <i class="bi bi-star-fill display-4 text-danger mb-3"></i>
                    <h5 class="card-title fw-bold text-dark">Évaluer un Déménageur</h5>
                    <p class="card-text text-muted small">Laissez votre évaluation sur le déménageur une fois la mission terminée.</p>
                </div>
            </a>
        </div>

    </div>
</div>

<style>
    .card-hover:hover {
      box-shadow: 0 0 15px rgba(0, 123, 255, 0.7);
      cursor: pointer;
      transform: translateY(-5px);
      transition: 0.3s;
    }
</style>

<?php
// Inclusion du footer (qui gère l'uniformité et la fin du HTML)
include '../footer.php';
?>