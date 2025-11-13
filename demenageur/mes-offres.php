<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérification de sécurité : l'utilisateur doit être connecté et avoir le rôle 'demenageur'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'demenageur') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Mes Offres Soumises';
$userId = $_SESSION['user_id'];
$error = '';

// --- LOGIQUE DE RÉCUPÉRATION DES OFFRES SOUMISES ---
$sql = "
    SELECT 
        p.id AS proposition_id, 
        p.prix, 
        p.date_proposition, 
        p.statut,
        a.id AS annonce_id,
        a.titre AS annonce_titre,
        a.ville_depart,
        a.ville_arrivee,
        u.nom AS client_nom
    FROM propositions p
    INNER JOIN annonces a ON p.annonce_id = a.id
    INNER JOIN utilisateurs u ON a.utilisateur_id = u.id 
    WHERE p.demenageur_id = ?
    ORDER BY p.date_proposition DESC
";

$offres = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $offres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur de base de données : Impossible de charger vos offres.";
    error_log("Mes Offres Load Error: " . $e->getMessage());
}
?>

<div class="container py-5">
    <a href="demenageur.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour au Tableau de Bord</a>
    
    <h1 class="mb-4 display-6 fw-bold text-primary"><i class="bi bi-tag-fill me-2"></i> Mes Offres Soumises</h1>
    <p class="lead text-muted mb-4">Consultez le statut de toutes les propositions de prix que vous avez envoyées aux clients.</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($offres)): ?>
        <div class="alert alert-info text-center py-4 rounded-3 shadow-sm">
            <h4 class="alert-heading"><i class="bi bi-info-circle-fill me-2"></i> Aucune Offre Soumise</h4>
            <p>Vous n'avez encore soumis aucune proposition de prix. Commencez à trouver de nouvelles missions !</p>
            <hr>
            <div class="mt-3">
                <a href="voir-annonces.php" class="btn btn-primary">Parcourir les annonces disponibles</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle shadow-sm">
                <thead class="table-warning">
                    <tr>
                        <th>Annonce & Client</th>
                        <th>Prix Proposé</th>
                        <th>Statut</th>
                        <th>Date Soumission</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offres as $offre): ?>
                    <tr>
                        <td class="fw-bold">
                            <?= htmlspecialchars($offre['annonce_titre']) ?>
                            <p class="small text-muted mb-0">Client: <?= htmlspecialchars($offre['client_nom']) ?></p>
                            <p class="small text-muted mb-0"><?= htmlspecialchars($offre['ville_depart']) ?> → <?= htmlspecialchars($offre['ville_arrivee']) ?></p>
                        </td>
                        <td class="fw-bolder">
                            <?= number_format($offre['prix'], 2, ',', ' ') ?> €
                        </td>
                        <td>
                            <?php 
                                $statut = strtolower($offre['statut']);
                                $class = 'secondary';
                                if ($statut === 'acceptee') $class = 'success';
                                else if ($statut === 'refusee') $class = 'danger';
                                else if ($statut === 'en_attente') $class = 'warning';
                            ?>
                            <span class="badge bg-<?= $class ?> p-2"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $statut))) ?></span>
                        </td>
                        <td>
                            <?= date('d/m/Y H:i', strtotime($offre['date_proposition'])) ?>
                        </td>
                        <td class="text-center">
                            <?php if ($statut === 'acceptee'): ?>
                                <a href="mission-acceptee.php?annonce_id=<?= $offre['annonce_id'] ?>" class="btn btn-sm btn-success fw-bold" title="Voir les détails de la mission et le contact client.">
                                    <i class="bi bi-eye-fill me-1"></i> Mission Attribuée
                                </a>
                            <?php else: ?>
                                <a href="proposer-prix.php?annonce_id=<?= $offre['annonce_id'] ?>" class="btn btn-sm btn-outline-info" title="Voir les détails de l'annonce et de votre proposition.">
                                    <i class="bi bi-eye-fill me-1"></i> Voir Détails
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
include '../footer.php';
?>