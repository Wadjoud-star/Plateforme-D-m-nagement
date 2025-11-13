<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérification de sécurité : l'utilisateur doit être connecté et avoir le rôle 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Gestion Administrative';
$type = $_GET['type'] ?? 'comptes'; // Valeur par défaut : gérer les comptes
$error = '';
$message = '';
$data = [];
$tableHeader = [];


// --- LOGIQUE DE TRAITEMENT DES ACTIONS (Suppression/Désactivation) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    try {
        if ($type === 'comptes') {
            if ($id === $_SESSION['user_id']) { // Empêcher l'auto-suppression
                $error = "Vous ne pouvez pas effectuer cette action sur votre propre compte.";
            } elseif ($action === 'supprimer') {
                $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Le compte utilisateur (ID: $id) a été définitivement supprimé.";
            } 
            // NOTE: Pour la désactivation, vous devriez ajouter une colonne 'statut' (actif/inactif) à la table utilisateurs.
            
        } elseif ($type === 'annonces' && $action === 'supprimer') {
            $stmt = $pdo->prepare("DELETE FROM annonces WHERE id = ?");
            $stmt->execute([$id]);
            $message = "L'annonce (ID: $id) a été supprimée, ainsi que toutes ses propositions et photos.";
        }
    } catch (PDOException $e) {
        $error = "Erreur SQL lors de l'action: " . $e->getMessage();
        error_log("Admin Action Error: " . $e->getMessage());
    }
}


// --- LOGIQUE DE RÉCUPÉRATION DES DONNÉES ---
try {
    if ($type === 'comptes') {
        $sql = "SELECT id, nom, email, role FROM utilisateurs ORDER BY role DESC, nom ASC";
        $tableHeader = ['ID', 'Nom', 'Email', 'Rôle'];
    } elseif ($type === 'annonces') {
        // Récupère les annonces, le nom du client, et le statut d'attribution
        $sql = "
            SELECT 
                a.id, a.titre, a.ville_depart, a.ville_arrivee, a.date_depot, u.nom AS client_nom,
                (SELECT COUNT(p.id) FROM propositions p WHERE p.annonce_id = a.id AND p.statut = 'acceptee') AS est_attribuee
            FROM annonces a
            JOIN utilisateurs u ON a.utilisateur_id = u.id
            ORDER BY a.date_depot DESC
        ";
        $tableHeader = ['ID', 'Titre', 'Client', 'Trajet', 'Statut'];
    } else {
        $error = "Type de gestion invalide.";
    }

    if (isset($sql)) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error = "Erreur de base de données lors du chargement: " . $e->getMessage();
    error_log("Admin Load Error: " . $e->getMessage());
}

?>

<div class="container py-5">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour au Tableau de Bord</a>
    
    <h1 class="mb-4 display-6 fw-bold text-danger"><i class="bi bi-gear-fill me-2"></i> Gestion des <?= htmlspecialchars(ucfirst($type)) ?></h1>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($data)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="bi bi-info-circle-fill me-2"></i> Aucun <?= htmlspecialchars($type) ?> trouvé.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle shadow-sm">
                <thead class="table-danger text-white">
                    <tr>
                        <?php foreach ($tableHeader as $header): ?>
                            <th><?= $header ?></th>
                        <?php endforeach; ?>
                        <th class="text-center" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $item): ?>
                    <tr>
                        <?php if ($type === 'comptes'): ?>
                            <td><?= htmlspecialchars($item['id']) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($item['nom']) ?></td>
                            <td><?= htmlspecialchars($item['email']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($item['role'])) ?></span></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmAction(<?= $item['id'] ?>, 'supprimer', '<?= $item['role'] ?>', '<?= htmlspecialchars($item['nom']) ?>')">
                                    <i class="bi bi-trash-fill"></i> Supprimer
                                </button>
                            </td>

                        <?php elseif ($type === 'annonces'): ?>
                            <td><?= htmlspecialchars($item['id']) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($item['titre']) ?></td>
                            <td><?= htmlspecialchars($item['client_nom']) ?></td>
                            <td><?= htmlspecialchars($item['ville_depart']) ?> → <?= htmlspecialchars($item['ville_arrivee']) ?></td>
                            <td class="text-center">
                                <?php $statut = $item['est_attribuee'] > 0 ? 'Attribuée' : 'Active'; ?>
                                <span class="badge bg-<?= $statut === 'Attribuée' ? 'success' : 'info' ?>"><?= $statut ?></span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmAction(<?= $item['id'] ?>, 'supprimer', 'annonce', '<?= htmlspecialchars($item['titre']) ?>')">
                                    <i class="bi bi-trash-fill"></i> Supprimer
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    function confirmAction(id, action, type, nom) {
        let confirmMsg = `Êtes-vous sûr de vouloir ${action} l'entité suivante ?\n\n`;
        
        if (type === 'annonce') {
            confirmMsg += `Type: Annonce\nTitre: ${nom}\n\nATTENTION: La suppression d'une annonce supprime aussi toutes les propositions et photos liées.`;
        } else {
            confirmMsg += `Type: Compte ${type}\nNom: ${nom}\n\nATTENTION: La suppression d'un compte supprime aussi toutes ses annonces/propositions/évaluations liées.`;
        }

        if (confirm(confirmMsg)) {
            // Empêcher l'administrateur de se supprimer soi-même (vérification côté client)
            <?php if ($type === 'comptes'): ?>
                if (id === <?= $_SESSION['user_id'] ?>) {
                    alert("ERREUR: Vous ne pouvez pas vous supprimer vous-même.");
                    return;
                }
            <?php endif; ?>
            
            // Redirection vers l'URL de traitement
            window.location.href = `gere.php?type=<?= $type ?>&action=${action}&id=${id}`;
        }
    }
</script>

<?php
include '../footer.php';
?>