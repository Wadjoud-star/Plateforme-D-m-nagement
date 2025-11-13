<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérifier que l'utilisateur est connecté et a le rôle 'demenageur'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'demenageur') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Annonces Disponibles';
$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// --- LOGIQUE DE RÉCUPÉRATION DES ANNONCES DISPONIBLES ---
$sql = "
    SELECT 
        a.id, 
        a.titre, 
        a.description, 
        a.date_depot, 
        a.ville_depart, 
        a.ville_arrivee, 
        a.volume,
        a.utilisateur_id AS client_id, -- Ajout de l'ID du client pour la question
        (SELECT COUNT(p.id) FROM propositions p WHERE p.annonce_id = a.id) AS nb_propositions,
        (SELECT COUNT(p.id) FROM propositions p WHERE p.annonce_id = a.id AND p.demenageur_id = ?) AS a_deja_propose
    FROM annonces a
    WHERE 
        a.id NOT IN (SELECT p.annonce_id FROM propositions p WHERE p.statut = 'acceptee')
        AND a.date_depot >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    ORDER BY a.date_depot DESC
";

$annonces = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur de base de données lors du chargement des annonces : " . $e->getMessage();
    error_log("Demenageur Load Error: " . $e->getMessage());
}
?>

<div class="container py-5">
    <a href="demenageur.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour au Tableau de Bord</a>
    
    <h1 class="mb-4 display-6 fw-bold text-primary"><i class="bi bi-list-columns-reverse me-2"></i> Annonces Disponibles</h1>
    <p class="lead text-muted mb-4">Liste des demandes ouvertes. N'oubliez pas de demander un complément d'information si nécessaire !</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($annonces)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="bi bi-info-circle-fill me-2"></i> Aucune nouvelle annonce disponible pour le moment.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($annonces as $annonce): ?>
            <div class="col">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold text-primary"><?= htmlspecialchars($annonce['titre']) ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted small">Publiée le <?= date('d/m/Y', strtotime($annonce['date_depot'])) ?></h6>
                        
                        <p class="card-text small mb-2 flex-grow-1">
                            <?= nl2br(htmlspecialchars(mb_strimwidth($annonce['description'], 0, 100, "..."))) ?>
                        </p>
                        
                        <ul class="list-group list-group-flush small mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <i class="bi bi-pin-map me-2"></i> 
                                <?= htmlspecialchars($annonce['ville_depart']) ?> → <?= htmlspecialchars($annonce['ville_arrivee']) ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <i class="bi bi-box me-2"></i> Volume :
                                <span class="fw-bold"><?= htmlspecialchars($annonce['volume']) ?> m³</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <i class="bi bi-tag me-2"></i> Propositions reçues :
                                <span class="badge bg-secondary"><?= $annonce['nb_propositions'] ?></span>
                            </li>
                        </ul>
                        
                        <?php if ($annonce['a_deja_propose'] > 0): ?>
                            <button class="btn btn-outline-success mt-auto" disabled>
                                <i class="bi bi-check-circle-fill me-1"></i> Offre Déjà Soumise
                            </button>
                        <?php else: ?>
                            <div class="d-flex gap-2 mt-auto">
                                <a href="proposer-prix.php?annonce_id=<?= $annonce['id'] ?>" class="btn btn-warning fw-bold flex-grow-1">
                                    <i class="bi bi-cash-stack me-1"></i> Proposer
                                </a>
                                <a href="questions.php?client_id=<?= $annonce['client_id'] ?>&annonce_id=<?= $annonce['id'] ?>" class="btn btn-outline-primary" title="Demander un complément d'information">
                                    <i class="bi bi-chat-dots-fill"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
include '../footer.php';
?>