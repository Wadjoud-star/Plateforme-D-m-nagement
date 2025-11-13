<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérification du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'demenageur') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Soumettre une Proposition';
$userId = $_SESSION['user_id'];
$errors = [];
$message = '';
$annonce = null;
$photos = []; // Pour stocker les chemins des photos
$annonce_id = $_GET['annonce_id'] ?? null;
$prix_propose = '';

// --- LOGIQUE DE VÉRIFICATION ET CHARGEMENT DE L'ANNONCE ---
if ($annonce_id) {
    try {
        // 1. Charger l'annonce et vérifier si elle est disponible
        $stmt = $pdo->prepare("
            SELECT a.*, (SELECT COUNT(p.id) FROM propositions p WHERE p.annonce_id = a.id AND p.statut = 'acceptee') AS est_attribuee
            FROM annonces a
            WHERE a.id = ?
        ");
        $stmt->execute([$annonce_id]);
        $annonce = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$annonce || $annonce['est_attribuee'] > 0) {
            die("<div class='alert alert-danger container mt-5'>Cette annonce n'est pas disponible ou n'existe pas.</div>");
        }
        
        // 2. Charger les photos liées à l'annonce
        $stmt_photos = $pdo->prepare("SELECT chemin_photo FROM photos_annonces WHERE annonce_id = ?");
        $stmt_photos->execute([$annonce_id]);
        $photos = $stmt_photos->fetchAll(PDO::FETCH_COLUMN);
        
        // 3. Récupération/Simulation des champs logistiques complets
        // NOTE: Si vous avez ajouté ces colonnes à la table annonces, vous devriez les récupérer via la requête principale.
        // Sinon, nous utilisons les simulations pour l'affichage :
        $heure_debut = '09:00'; 
        $nb_demenageurs = 2;
        $poids = 500;
        $details_depart = "Appartement au 3ème étage sans ascenseur."; 
        $details_arrivee = "Maison de plain-pied avec accès facile."; 

    } catch (PDOException $e) {
        $errors['load'] = "Erreur lors du chargement de l'annonce.";
        error_log("Proposer Prix Load Error: " . $e->getMessage());
    }
} else {
    die("<div class='alert alert-warning container mt-5'>Aucune annonce sélectionnée. Veuillez passer par la page 'Annonces Disponibles'.</div>");
}


// --- TRAITEMENT DU FORMULAIRE POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prix_propose = floatval($_POST['prix'] ?? 0);
    $prix_propose_str = number_format($prix_propose, 2, '.', ''); // Formatage pour la BDD (DECIMAL)

    // Vérifier si le déménageur a déjà proposé
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM propositions WHERE annonce_id = ? AND demenageur_id = ?");
    $stmt_check->execute([$annonce_id, $userId]);

    if ($stmt_check->fetchColumn() > 0) {
        $errors['submit'] = "Vous avez déjà soumis une proposition pour cette annonce.";
    } elseif ($prix_propose <= 0) {
        $errors['prix'] = "Le prix proposé doit être supérieur à zéro.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO propositions (annonce_id, demenageur_id, prix, date_proposition, statut)
                VALUES (?, ?, ?, NOW(), 'en_attente')
            ");
            $stmt->execute([$annonce_id, $userId, $prix_propose_str]);
            
            $message = "Votre proposition de prix a été soumise avec succès au client.";
            $prix_propose = ''; // Vider le champ
            
        } catch (PDOException $e) {
            $errors['submit'] = "Erreur lors de l'enregistrement de la proposition.";
            error_log("Proposition Submit Error: " . $e->getMessage());
        }
    }
}
?>

<div class="container py-5">
    <a href="mes-offres.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour aux offres</a>
    
    <h1 class="mb-4 display-6 fw-bold text-primary"><i class="bi bi-cash-stack me-2"></i> Proposer un prix</h1>
    <p class="lead text-muted mb-4">Soumettez votre offre pour l'annonce : <strong><?= htmlspecialchars($annonce['titre'] ?? 'N/A') ?></strong>.</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error) || !empty($errors['submit'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error ?? $errors['submit']) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-5 border-0">
        <div class="card-header bg-light fw-bold"><i class="bi bi-card-list me-1"></i> Détails Logistiques de l'Annonce</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 border-end mb-3">
                    <p class="mb-1"><strong>Départ:</strong> <?= htmlspecialchars($annonce['ville_depart']) ?></p>
                    <p class="mb-1"><strong>Arrivée:</strong> <?= htmlspecialchars($annonce['ville_arrivee']) ?></p>
                    <p class="mb-1"><strong>Date:</strong> <?= date('d/m/Y', strtotime($annonce['date_depot'])) ?> à <?= htmlspecialchars($heure_debut) ?></p>
                    <p class="mb-1"><strong>Volume:</strong> <?= htmlspecialchars($annonce['volume']) ?> m³ (Poids estimé: <?= htmlspecialchars($poids) ?> kg)</p>
                    <p class="mb-1"><strong>Détails Départ:</strong> <?= htmlspecialchars($details_depart) ?></p>
                    <p class="mb-1"><strong>Détails Arrivée:</strong> <?= htmlspecialchars($details_arrivee) ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <p class="mb-1"><strong>Description:</strong> <?= nl2br(htmlspecialchars($annonce['description'])) ?></p>
                    <?php if (!empty($photos)): ?>
                        <h6 class="mt-3 mb-2 fw-bold">Photos jointes (<?= count($photos) ?>) :</h6>
                        <div class="d-flex overflow-auto pb-2">
                            <?php foreach ($photos as $path): ?>
                                <img src="<?= htmlspecialchars('../' . $path) ?>" style="width: 80px; height: 80px; object-fit: cover; margin-right: 5px;" class="rounded shadow-sm" alt="Photo">
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mt-4">Aucune photo fournie par le client.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <div class="card shadow-lg p-4">
        <h4 class="mb-4">Votre Offre</h4>
        <form method="POST" action="proposer-prix.php?annonce_id=<?= $annonce_id ?>">
            
            <div class="mb-4">
                <label for="prix" class="form-label fw-bold">Prix Total Proposé (EUR) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-currency-euro"></i></span>
                    <input type="number" step="0.01" min="1" class="form-control <?= isset($errors['prix']) ? 'is-invalid' : '' ?>" id="prix" name="prix" value="<?= htmlspecialchars($prix_propose) ?>" required>
                    <?php if (isset($errors['prix'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['prix']) ?></div><?php endif; ?>
                </div>
                <small class="form-text text-muted">Ce prix inclut tous les frais de main d'œuvre et de transport.</small>
            </div>
            
            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold">
                <i class="bi bi-send-fill me-2"></i> Soumettre la proposition
            </button>
        </form>
    </div>

</div>

<?php
include '../footer.php';
?>