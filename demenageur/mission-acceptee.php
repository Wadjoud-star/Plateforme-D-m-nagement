<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérification du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'demenageur') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Détails de la Mission Acceptée';
$userId = $_SESSION['user_id'];
$error = '';
$annonce = null;
$client_details = null;
$offre_acceptee = null;
$photos = [];

$annonce_id = $_GET['annonce_id'] ?? null;

if (!$annonce_id) {
    header('Location: mes-offres.php');
    exit();
}

try {
    // 1. Charger l'offre acceptée et vérifier si elle appartient à ce déménageur
    $stmt_offre = $pdo->prepare("
        SELECT p.*, a.*, u.nom AS client_nom, u.email AS client_email
        FROM propositions p
        JOIN annonces a ON p.annonce_id = a.id
        JOIN utilisateurs u ON a.utilisateur_id = u.id
        WHERE p.annonce_id = ? AND p.demenageur_id = ? AND p.statut = 'acceptee'
    ");
    $stmt_offre->execute([$annonce_id, $userId]);
    $result = $stmt_offre->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        // Bloquer si la mission n'existe pas ou n'est pas attribuée à ce déménageur
        die("<div class='alert alert-danger container mt-5'>Mission introuvable ou non attribuée.</div>");
    }

    $annonce = $result;
    $offre_acceptee = $result;
    $client_details = ['nom' => $result['client_nom'], 'email' => $result['client_email']];
    
    // Champs simulés pour l'affichage complet
    $heure_debut = '09:00'; 
    $nb_demenageurs = 2;
    $poids = 500;
    $details_depart = "Appartement au 3ème étage sans ascenseur."; 
    $details_arrivee = "Maison de plain-pied avec accès facile."; 

    // 2. Charger les photos
    $stmt_photos = $pdo->prepare("SELECT chemin_photo FROM photos_annonces WHERE annonce_id = ?");
    $stmt_photos->execute([$annonce_id]);
    $photos = $stmt_photos->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $error = "Erreur BDD: Impossible de charger les détails de la mission.";
    error_log("Mission Load Error: " . $e->getMessage());
}
?>

<div class="container py-5">
    <a href="mes-offres.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour à Mes Offres</a>
    
    <h1 class="mb-4 display-6 fw-bold text-success"><i class="bi bi-check-circle-fill me-2"></i> MISSION ATTRIBUÉE</h1>
    <p class="lead text-muted mb-4">Détails de la mission **<?= htmlspecialchars($annonce['titre']) ?>** et informations de contact client.</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card bg-success text-white shadow-lg mb-5">
        <div class="card-header fw-bold">
            <i class="bi bi-person-fill me-2"></i> CONTACT CLIENT
        </div>
        <div class="card-body">
            <h4 class="mb-3">Nom : <?= htmlspecialchars($client_details['nom']) ?></h4>
            <p class="lead mb-1"><i class="bi bi-envelope-fill me-2"></i> Email : **<?= htmlspecialchars($client_details['email']) ?>**</p>
            <p class="lead"><i class="bi bi-calendar-check-fill me-2"></i> Date demandée : **<?= date('d/m/Y', strtotime($offre_acceptee['date_depot'])) ?>**</p>
        </div>
    </div>
    
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-light fw-bold">Détails Logistiques de l'Annonce</div>
        <div class="card-body">
             <div class="row">
                <div class="col-md-6 border-end mb-3">
                    <p class="mb-1"><strong>Votre Prix Accepté:</strong> <span class="text-success fw-bolder fs-5"><?= number_format($offre_acceptee['prix'], 2, ',', ' ') ?> €</span></p>
                    <hr>
                    <p class="mb-1"><strong>Départ:</strong> <?= htmlspecialchars($annonce['ville_depart']) ?></p>
                    <p class="mb-1"><strong>Arrivée:</strong> <?= htmlspecialchars($annonce['ville_arrivee']) ?></p>
                    <p class="mb-1"><strong>Date:</strong> <?= date('d/m/Y', strtotime($annonce['date_depot'])) ?> à <?= htmlspecialchars($heure_debut) ?></p>
                    <p class="mb-1"><strong>Volume:</strong> <?= htmlspecialchars($annonce['volume']) ?> m³ (Poids estimé: <?= htmlspecialchars($poids) ?> kg)</p>
                </div>
                <div class="col-md-6 mb-3">
                    <p class="mb-1"><strong>Détails Départ:</strong> <?= htmlspecialchars($details_depart) ?></p>
                    <p class="mb-1"><strong>Détails Arrivée:</strong> <?= htmlspecialchars($details_arrivee) ?></p>
                    <p class="mb-1"><strong>Description:</strong> <?= nl2br(htmlspecialchars($annonce['description'])) ?></p>
                </div>
            </div>
             <?php if (!empty($photos)): ?>
                <hr class="mt-4">
                <p class="mb-2 fw-bold">Photos jointes (<?= count($photos) ?>) :</p>
                <div class="d-flex overflow-auto pb-2">
                    <?php foreach ($photos as $path): ?>
                        <img src="<?= htmlspecialchars('../' . $path) ?>" style="width: 80px; height: 80px; object-fit: cover; margin-right: 5px;" class="rounded shadow-sm" alt="Photo">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include '../footer.php';
?>