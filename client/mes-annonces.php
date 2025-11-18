<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérifier si utilisateur connecté et rôle client
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Mes Annonces';
$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// --- GESTION DE LA SUPPRESSION ---
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $annonceId = intval($_GET['id']);
    
    // Vérifier si l'annonce appartient bien à l'utilisateur avant de supprimer
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM annonces WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$annonceId, $userId]);

    if ($stmt->fetchColumn() > 0) {
        try {
            // La suppression de l'annonce supprimera automatiquement les propositions et photos liées
            $stmt = $pdo->prepare("DELETE FROM annonces WHERE id = ?");
            $stmt->execute([$annonceId]);
            $message = "L'annonce a été supprimée avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression de l'annonce : " . $e->getMessage();
        }
    } else {
        $error = "Vous ne pouvez pas supprimer cette annonce ou elle n'existe pas.";
    }
}


// --- RÉCUPÉRATION DES ANNONCES AVEC STATUT ---
$sql = "
    SELECT 
        a.*, 
        (SELECT COUNT(id) FROM propositions WHERE annonce_id = a.id) AS nb_propositions,
        CASE 
            WHEN EXISTS (SELECT 1 FROM propositions p WHERE p.annonce_id = a.id AND p.statut = 'acceptee') THEN 'Attribuée'
            WHEN EXISTS (SELECT 1 FROM propositions p WHERE p.annonce_id = a.id) THEN 'En attente de choix'
            ELSE 'Publiée'
        END AS statut_annonce
    FROM annonces a
    WHERE a.utilisateur_id = ?
    ORDER BY a.date_depot DESC
";

$annonces = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur de base de données lors du chargement des annonces.";
    error_log("Mes Annonces Load Error: " . $e->getMessage());
}
?>

<div class="container py-5">
    <a href="client.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour au Tableau de Bord</a>
    
    <h1 class="mb-4 display-6 fw-bold text-primary"><i class="bi bi-list-columns-reverse me-2"></i> Mes Annonces</h1>
    <p class="lead text-muted mb-4">Gérez le statut de vos annonces et les offres reçues.</p>

    <a href="creer-annonce.php" class="btn btn-primary mb-4"><i class="bi bi-plus-circle-fill me-2"></i> Créer une nouvelle annonce</a>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($annonces)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="bi bi-info-circle-fill me-2"></i> Vous n'avez pas encore créé d'annonces.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle shadow-sm">
                <thead class="table-primary">
                    <tr>
                        <th>Titre & Description</th>
                        <th>Départ → Arrivée</th>
                        <th>Volume (m³)</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center" style="min-width: 250px;">Actions & Offres</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($annonces as $annonce): ?>
                    <tr>
                        <td class="fw-bold">
                            <?= htmlspecialchars($annonce['titre']) ?>
                            <p class="text-muted small mb-0 fw-normal"><?= nl2br(htmlspecialchars(mb_strimwidth($annonce['description'], 0, 50, "..."))) ?></p>
                        </td>
                        <td>
                            <i class="bi bi-pin-map-fill me-1"></i> <?= htmlspecialchars($annonce['ville_depart']) ?> <i class="bi bi-arrow-right-short"></i> <?= htmlspecialchars($annonce['ville_arrivee']) ?>
                        </td>
                        <td><?= htmlspecialchars($annonce['volume']) ?></td>
                        
                        <td class="text-center">
                            <?php
                                $statut = htmlspecialchars($annonce['statut_annonce']);
                                $class = 'secondary';
                                if ($statut === 'Attribuée') $class = 'success';
                                else if ($statut === 'En attente de choix') $class = 'warning';
                                else if ($statut === 'Publiée') $class = 'info';
                            ?>
                            <span class="badge bg-<?= $class ?> p-2"><?= $statut ?></span>
                        </td>
                        
                        <td class="text-center" style="min-width: 250px;">
                            
                            <?php if ($annonce['statut_annonce'] === 'Attribuée'): ?>
                                <a href="propositions.php?annonce_id=<?= $annonce['id'] ?>" class="btn btn-sm btn-success fw-bold me-2">
                                    <i class="bi bi-check-circle-fill"></i> Attribuée
                                </a>
                                <a href="evaluer.php" class="btn btn-sm btn-outline-primary" title="Évaluer le déménageur">
                                    <i class="bi bi-star-fill"></i> Évaluer
                                </a>
                            
                            <?php elseif ($annonce['nb_propositions'] > 0): ?>
                                <a href="choix-demenageur.php" class="btn btn-sm btn-warning fw-bold">
                                    <i class="bi bi-tags-fill"></i> Gérer Choix (<?= $annonce['nb_propositions'] ?>)
                                </a>
                            
                            <?php else: ?>
                                <span class="badge bg-light text-dark border p-2 me-2">0 offre reçue</span>
                            <?php endif; ?>
                                <a href="modifier-annonce.php?id=<?= $annonce['id'] ?>" class="btn btn-sm btn-outline-info mt-1">
                                    <i class="bi"></i> Voir Détail
                                </a>
                            <?php if ($annonce['statut_annonce'] !== 'Attribuée'): ?>
                                <a href="modifier-annonce.php?id=<?= $annonce['id'] ?>" class="btn btn-sm btn-outline-info mt-1">
                                    <i class="bi bi-pencil-fill"></i> Modifier
                                </a>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-sm btn-outline-danger mt-1" title="Supprimer l'annonce" onclick="confirmDelete(<?= $annonce['id'] ?>)">
                                <i class="bi bi-trash-fill"></i> Supprimer
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    function confirmDelete(annonceId) {
        if (confirm("Êtes-vous sûr de vouloir supprimer cette annonce ? Cette action est irréversible.")) {
            window.location.href = 'mes-annonces.php?action=supprimer&id=' + annonceId;
        }
    }
</script>

<?php
include '../footer.php';
?>