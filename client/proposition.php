<?php
session_start();
// Inclusion du Header pour l'uniformité (Contient le début HTML et le menu)
include '../header.php';
require_once '../Config.php';

// Vérifier si utilisateur connecté et rôle client
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Propositions Reçues';
$userId = $_SESSION['user_id'];
$errors = [];

// Requête pour récupérer toutes les propositions liées aux annonces du client (Logique initiale et désirée)
$sql = "
    SELECT 
        p.id AS proposition_id, 
        p.prix, 
        p.date_proposition, 
        p.statut, 
        a.id AS annonce_id,
        a.titre AS titre_annonce, 
        u.nom AS nom_demenageur
    FROM propositions p
    INNER JOIN annonces a ON p.annonce_id = a.id
    INNER JOIN utilisateurs u ON p.demenageur_id = u.id
    WHERE a.utilisateur_id = ?
    ORDER BY p.date_proposition DESC
";

$propositions_globales = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $propositions_globales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors['load'] = "Erreur lors du chargement des propositions. Veuillez vérifier la connexion à la base de données.";
    error_log("DB Error loading all propositions: " . $e->getMessage());
}
?>

<div class="container py-5">
    <a href="client.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour au Tableau de Bord</a>
    
    <h1 class="mb-4 display-6 fw-bold text-primary"><i class="bi bi-tag-fill me-2"></i> Propositions Reçues</h1>
    <p class="lead text-muted mb-4">Liste de toutes les offres soumises par les déménageurs pour l'ensemble de vos annonces.</p>

    <?php if(!empty($errors['load'])): ?>
         <div class="alert alert-danger"><?= htmlspecialchars($errors['load']) ?></div>
    <?php endif; ?>

    <?php if(empty($propositions_globales)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="bi bi-info-circle-fill me-2"></i> Vous n'avez reçu aucune proposition pour le moment.
            <div class="mt-3">
                <a href="creer-annonce.php" class="btn btn-primary">Créer une annonce</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle shadow-sm">
                <thead class="table-primary">
                    <tr>
                        <th>Annonce (Titre)</th>
                        <th>Déménageur</th>
                        <th>Prix proposé</th>
                        <th>Date & Heure</th>
                        <th>Statut</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($propositions_globales as $prop): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($prop['titre_annonce']) ?></td>
                        <td><?= htmlspecialchars($prop['nom_demenageur']) ?></td>
                        <td><?= number_format($prop['prix'], 2, ',', ' ') ?> €</td>
                        <td><?= date('d/m/Y H:i', strtotime($prop['date_proposition'])) ?></td>
                        <td>
                            <?php 
                                $statut = strtolower($prop['statut']);
                                $class = 'secondary';
                                if ($statut === 'acceptee') $class = 'success';
                                else if ($statut === 'refusee') $class = 'danger';
                            ?>
                            <span class="badge bg-<?= $class ?>"><?= htmlspecialchars(ucfirst($statut)) ?></span>
                        </td>
                        <td class="text-center">
                             <a href="choix-demenageur.php?annonce_id=<?= $prop['annonce_id'] ?>" class="btn btn-sm btn-outline-primary" title="Gérer les offres pour cette annonce">
                                <i class="bi bi-eye-fill me-1"></i> Gérer le choix
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
// Inclusion du Footer pour l'uniformité (Contient la fin HTML et les scripts)
include '../footer.php';
?>