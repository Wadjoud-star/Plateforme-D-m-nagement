<?php
session_start();
include 'header.php';
require_once 'Config.php';

$pageTitle = 'Détails de l\'Annonce';
$annonce_id = $_GET['id'] ?? null;
$annonce = null;
$photos = [];
$error = '';

// Vérification de la connexion
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

// Champs simulés pour l'affichage complet (basés sur les autres pages)
$heure_debut = '09:00'; 
$nb_demenageurs = 2;
$poids = 500;
$details_depart = "Appartement au 3ème étage sans ascenseur."; 
$details_arrivee = "Maison de plain-pied avec accès facile."; 

if (!$annonce_id) {
    die("<div class='alert alert-warning container mt-5'>Aucun identifiant d'annonce spécifié.</div>");
}

try {
    // Charger l'annonce
    $stmt = $pdo->prepare("SELECT * FROM annonces WHERE id = ?");
    $stmt->execute([$annonce_id]);
    $annonce = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$annonce) {
        die("<div class='alert alert-danger container mt-5'>Annonce introuvable.</div>");
    }

    // Charger les photos
    $stmt_photos = $pdo->prepare("SELECT chemin_photo FROM photos_annonces WHERE annonce_id = ?");
    $stmt_photos->execute([$annonce_id]);
    $photos = $stmt_photos->fetchAll(PDO::FETCH_COLUMN);
    
    // Déterminer si l'annonce est toujours ouverte (aucune proposition acceptée)
    $stmt_statut = $pdo->prepare("SELECT COUNT(id) FROM propositions WHERE annonce_id = ? AND statut = 'acceptee'");
    $stmt_statut->execute([$annonce_id]);
    $estAttribuee = $stmt_statut->fetchColumn() > 0;

} catch (PDOException $e) {
    $error = "Erreur lors du chargement de l'annonce : " . $e->getMessage();
    error_log("Public Annonce Load Error: " . $e->getMessage());
}
?>

<div class="container py-5">
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <h1 class="mb-4 display-6 fw-bold text-primary"><?= htmlspecialchars($annonce['titre']) ?></h1>
    <p class="lead text-muted mb-4">Publiée le <?= date('d/m/Y', strtotime($annonce['date_depot'])) ?></p>

    <?php if ($estAttribuee): ?>
        <div class="alert alert-success fw-bold">
            <i class="bi bi-check-circle-fill me-2"></i> Mission Attribuée. Les propositions ne sont plus acceptées.
        </div>
    <?php else: ?>
        <div class="alert alert-info fw-bold">
            <i class="bi bi-tags-fill me-2"></i> Cette annonce est ouverte aux propositions !
        </div>
    <?php endif; ?>

    
    <div class="card shadow-sm mb-5 border-0">
        <div class="card-header bg-light fw-bold">Détails Logistiques</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 border-end mb-3">
                    <p class="mb-1"><strong>Départ:</strong> <?= htmlspecialchars($annonce['ville_depart']) ?></p>
                    <p class="mb-1"><strong>Arrivée:</strong> <?= htmlspecialchars($annonce['ville_arrivee']) ?></p>
                    <p class="mb-1"><strong>Volume:</strong> <?= htmlspecialchars($annonce['volume']) ?> m³ (Poids estimé: <?= htmlspecialchars($poids) ?> kg)</p>
                    <p class="mb-1"><strong>Date/Heure:</strong> <?= date('d/m/Y', strtotime($annonce['date_depot'])) ?> à <?= htmlspecialchars($heure_debut) ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <p class="mb-1"><strong>Logement Départ:</strong> <?= htmlspecialchars($details_depart) ?></p>
                    <p class="mb-1"><strong>Logement Arrivée:</strong> <?= htmlspecialchars($details_arrivee) ?></p>
                    <p class="mb-1"><strong>Déménageurs souhaités:</strong> <?= htmlspecialchars($nb_demenageurs) ?></p>
                </div>
            </div>
            
            <h5 class="mt-4 fw-bold">Description Complète</h5>
            <p><?= nl2br(htmlspecialchars($annonce['description'])) ?></p>
        </div>
    </div>
    
    <?php if (!empty($photos)): ?>
        <div class="card shadow-sm mb-5 border-0">
            <div class="card-header bg-light fw-bold">Photos Jointes (Aperçu)</div>
            <div class="card-body">
                <div class="d-flex overflow-auto pb-2">
                    <?php foreach ($photos as $path): ?>
                        <img src="<?= htmlspecialchars($path) ?>" style="width: 150px; height: 100px; object-fit: cover; margin-right: 10px;" class="rounded shadow-sm" alt="Photo de l'annonce">
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <div class="text-center py-4 bg-light rounded shadow-sm">
        <?php if ($estAttribuee): ?>
            <button class="btn btn-secondary btn-lg" disabled>Mission déjà Attribuée</button>
        <?php elseif ($isLoggedIn && $userRole === 'demenageur'): ?>
            <a href="demenageur/proposer-prix.php?annonce_id=<?= $annonce_id ?>" class="btn btn-warning btn-lg fw-bold">
                <i class="bi bi-cash-stack me-2"></i> Soumettre ma proposition
            </a>
        <?php elseif ($isLoggedIn && $userRole === 'client'): ?>
            <a href="client/mes-annonces.php" class="btn btn-info btn-lg">
                <i class="bi bi-list-check me-2"></i> Gérer Mes Annonces
            </a>
        <?php else: ?>
            <a href="connexion.php" class="btn btn-primary btn-lg fw-bold">
                <i class="bi bi-lock-fill me-2"></i> Se connecter 
            </a>
            <a href="inscription.php" class="btn btn-primary btn-lg fw-bold">
                <i class="bi bi-lock-fill me-2"></i> S'inscrire
            </a>
            <p class="mt-3 small text-muted">Seuls les déménageurs connectés peuvent soumettre un prix.</p>
        <?php endif; ?>
    </div>

</div>

<?php
include 'footer.php';
?>