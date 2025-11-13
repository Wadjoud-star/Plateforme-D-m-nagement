<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérifier session client
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Choix du Déménageur';
$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// --- 1. TRAITEMENT DE L'ACTION (ACCEPTER/REFUSER) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proposition_id'], $_POST['action'])) {
    $propositionId = intval($_POST['proposition_id']);
    $action = $_POST['action'];

    if ($action === 'accepter') {
        $pdo->beginTransaction();

        try {
            // 1) Vérifier si l'annonce est toujours "en_attente" d'être choisie ET RECUPERER SON TITRE
            $stmt_check = $pdo->prepare("
                SELECT a.id, a.titre 
                FROM annonces a
                JOIN propositions p ON a.id = p.annonce_id
                WHERE p.id = ? AND p.statut = 'en_attente' AND a.utilisateur_id = ?
            ");
            $stmt_check->execute([$propositionId, $userId]);
            $annonceData = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if (!$annonceData) {
                throw new Exception("Proposition non trouvée, non en attente, ou ne vous appartient pas.");
            }
            
            $annonceId = $annonceData['id'];
            $annonceTitre = $annonceData['titre']; // <-- CORRECTION : On récupère le titre

            // 2) Mettre à jour la proposition sélectionnée en 'acceptee'
            $stmt_accept = $pdo->prepare("UPDATE propositions SET statut = 'acceptee' WHERE id = ?");
            $stmt_accept->execute([$propositionId]);

            // 3) Refuser toutes les autres propositions pour cette annonce
            $stmt_refuse = $pdo->prepare("UPDATE propositions SET statut = 'refusee' WHERE annonce_id = ? AND id != ?");
            $stmt_refuse->execute([$annonceId, $propositionId]);
            
            // 4) [OPTIONNEL] Mettre à jour le statut de l'annonce si vous ajoutez un champ 'statut' à la table annonces

            $pdo->commit();
            
            // CORRECTION : MESSAGE UTILISANT LE TITRE
            $message = "Le déménageur a été sélectionné avec succès pour l'annonce : <strong>" . htmlspecialchars($annonceTitre) . "</strong>. Les autres offres ont été refusées.";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la mise à jour : " . $e->getMessage();
            error_log("Choix Déménageur Error: " . $e->getMessage());
        }
    } elseif ($action === 'refuser') {
        // Refuser cette proposition uniquement
        $stmt = $pdo->prepare("UPDATE propositions SET statut = 'refusee' WHERE id = ? AND statut = 'en_attente'");
        $stmt->execute([$propositionId]);
        $message = "La proposition a été refusée.";
    }
}

// --- 2. RÉCUPÉRER LES PROPOSITIONS EN ATTENTE ---
$stmt = $pdo->prepare("
    SELECT 
        p.id AS proposition_id, 
        p.prix, 
        p.date_proposition, 
        a.id AS annonce_id,
        a.titre AS titre_annonce, 
        u.nom AS nom_demenageur
    FROM propositions p
    INNER JOIN annonces a ON p.annonce_id = a.id
    INNER JOIN utilisateurs u ON p.demenageur_id = u.id
    WHERE a.utilisateur_id = ? AND p.statut = 'en_attente'
    ORDER BY p.date_proposition DESC
");
$stmt->execute([$userId]);
$propositions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
    <a href="client.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour au Tableau de Bord</a>
    
    <h1 class="mb-4 display-6 fw-bold text-primary"><i class="bi bi-person-check-fill me-2"></i> Choix du Déménageur</h1>
    <p class="lead text-muted mb-4">Propositions en attente d'une décision. Cliquez sur "Accepter" pour attribuer l'annonce et refuser les autres.</p>

    <?php if (!empty($message)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (empty($propositions)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="bi bi-info-circle-fill me-2"></i> Aucune proposition en attente de votre décision pour le moment.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle shadow-sm">
                <thead class="table-primary">
                    <tr>
                        <th>Annonce</th>
                        <th>Déménageur</th>
                        <th>Prix proposé</th>
                        <th>Date proposition</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($propositions as $prop): ?>
                    <tr>
                        <td class="fw-bold">
                            <?= htmlspecialchars($prop['titre_annonce']) ?>
                            <a href="propositions.php?annonce_id=<?= $prop['annonce_id'] ?>" class="badge bg-secondary ms-2 text-decoration-none">Détails</a>
                        </td>
                        <td><?= htmlspecialchars($prop['nom_demenageur']) ?></td>
                        <td><?= number_format($prop['prix'], 2, ',', ' ') ?> €</td>
                        <td><?= date('d/m/Y H:i', strtotime($prop['date_proposition'])) ?></td>
                        <td class="text-center" style="min-width: 180px;">
                            <form method="post" style="display:inline-block;" class="me-2">
                                <input type="hidden" name="proposition_id" value="<?= $prop['proposition_id'] ?>" />
                                <button type="submit" name="action" value="accepter" class="btn btn-success btn-sm fw-bold">
                                    <i class="bi bi-check-lg"></i> Accepter
                                </button>
                            </form>
                            <form method="post" style="display:inline-block;">
                                <input type="hidden" name="proposition_id" value="<?= $prop['proposition_id'] ?>" />
                                <button type="submit" name="action" value="refuser" class="btn btn-danger btn-sm">
                                    <i class="bi bi-x-lg"></i> Refuser
                                </button>
                            </form>
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