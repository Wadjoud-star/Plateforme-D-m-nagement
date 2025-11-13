<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérifier que l'utilisateur est connecté et est un client
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Gestion des Évaluations';
$user_id = $_SESSION['user_id'];
$errors = [];
$message = '';
$annonce_id = $_GET['annonce_id'] ?? null;
$evaluationExistante = null;

// Variables pour le formulaire d'évaluation (seulement en mode formulaire)
$annonce = null;
$demenageur_id = null;
$demenageur_nom = null;
$note = $_POST['note'] ?? 5.0;
$commentaire = $_POST['commentaire'] ?? '';


// --- MODE 2 : TRAITEMENT DU FORMULAIRE D'ÉVALUATION (Si ID est présent) ---
if ($annonce_id) {
    try {
        // 1. VÉRIFIER L'ANNONCE ET RÉCUPÉRER LE DÉMÉNAGEUR ATTRIBUÉ
        $stmt_annonce = $pdo->prepare("
            SELECT 
                a.id, a.titre, 
                p.demenageur_id, 
                u.nom AS demenageur_nom
            FROM annonces a
            JOIN propositions p ON a.id = p.annonce_id
            JOIN utilisateurs u ON p.demenageur_id = u.id
            WHERE a.id = ? AND a.utilisateur_id = ? AND p.statut = 'acceptee'
        ");
        $stmt_annonce->execute([$annonce_id, $user_id]);
        $result = $stmt_annonce->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $errors['load'] = "Annonce introuvable, ou aucun déménageur n'a été attribué.";
        } else {
            $annonce = $result;
            $demenageur_id = $result['demenageur_id'];
            $demenageur_nom = $result['demenageur_nom'];
            
            // 2. VÉRIFIER SI L'ÉVALUATION EXISTE DÉJÀ
            $stmt_existing = $pdo->prepare("SELECT * FROM evaluations WHERE annonce_id = ? AND client_id = ?");
            $stmt_existing->execute([$annonce_id, $user_id]);
            $evaluationExistante = $stmt_existing->fetch(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        $errors['load'] = "Erreur de base de données lors du chargement des données.";
        error_log("Eval Load Error: " . $e->getMessage());
    }

    // --- Traitement POST du Formulaire d'Évaluation ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$evaluationExistante && empty($errors)) {
        
        $note = floatval($_POST['note'] ?? 0);
        $commentaire = trim($_POST['commentaire'] ?? '');

        if ($note < 1 || $note > 5) {
            $errors['note'] = "La note doit être comprise entre 1 et 5.";
        }
        if (empty($commentaire)) {
            $errors['commentaire'] = "Un commentaire est obligatoire.";
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO evaluations (annonce_id, client_id, demenageur_id, note, commentaire)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$annonce_id, $user_id, $demenageur_id, $note, $commentaire]);
                
                // Redirection vers la LISTE GLOBALE après succès avec un statut
                header("Location: evaluer.php?status=submitted&titre=" . urlencode($annonce['titre']));
                exit();

            } catch (PDOException $e) {
                $errors['submit'] = "Erreur lors de l'enregistrement de votre évaluation.";
                error_log("Eval Submit Error: " . $e->getMessage());
            }
        }
    }
}


// --- MODE 1 : CHARGEMENT DE LA LISTE GLOBALE (Si pas d'ID ou après soumission) ---
if (!$annonce_id || !empty($errors) || $evaluationExistante || (isset($_GET['status']) && $_GET['status'] === 'submitted')) {
    
    // Récupérer toutes les annonces attribuées et non encore évaluées
    $sql_list = "
        SELECT 
            a.id AS annonce_id, a.titre, a.date_depot,
            p.prix,
            u_dem.nom AS nom_demenageur
        FROM annonces a
        INNER JOIN propositions p ON a.id = p.annonce_id
        INNER JOIN utilisateurs u_dem ON p.demenageur_id = u_dem.id
        LEFT JOIN evaluations e ON a.id = e.annonce_id AND e.client_id = a.utilisateur_id
        WHERE a.utilisateur_id = ?
        AND p.statut = 'acceptee'
        AND e.id IS NULL -- Exclut les annonces déjà évaluées
        ORDER BY a.date_depot DESC
    ";

    $annonces_a_evaluer = [];
    try {
        $stmt_list = $pdo->prepare($sql_list);
        $stmt_list->execute([$user_id]);
        $annonces_a_evaluer = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors['load_list'] = "Erreur de base de données lors du chargement de la liste.";
    }

    // --- Affichage du MODE LISTE ---
    ?>
    <div class="container py-5">
        <a href="client.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour au Tableau de Bord</a>
        
        <h1 class="mb-4 display-6 fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i> Évaluations en Attente</h1>
        <p class="lead text-muted mb-4">Voici les missions terminées pour lesquelles vous devez évaluer le service rendu.</p>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'submitted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> Merci ! Votre évaluation pour l'annonce <strong><?= htmlspecialchars($_GET['titre'] ?? '') ?></strong> a été enregistrée.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors['load_list'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['load_list']) ?></div>
        <?php endif; ?>

        <?php if (empty($annonces_a_evaluer)): ?>
            <div class="alert alert-info text-center py-4">
                <i class="bi bi-info-circle-fill me-2"></i> Aucune évaluation en attente pour le moment !
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle shadow-sm">
                    <thead class="table-primary">
                        <tr>
                            <th>Annonce</th>
                            <th>Déménageur Attribué</th>
                            <th>Prix Accepté</th>
                            <th>Date de l'Annonce</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($annonces_a_evaluer as $item): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($item['titre']) ?></td>
                            <td><?= htmlspecialchars($item['nom_demenageur']) ?></td>
                            <td><?= number_format($item['prix'], 2, ',', ' ') ?> €</td>
                            <td><?= date('d/m/Y', strtotime($item['date_depot'])) ?></td>
                            <td class="text-center">
                                <a href="evaluer.php?annonce_id=<?= $item['annonce_id'] ?>" class="btn btn-sm btn-warning fw-bold">
                                    <i class="bi bi-star-fill me-1"></i> Évaluer ce service
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
}


// --- MODE 2 : AFFICHAGE DU FORMULAIRE D'ÉVALUATION (Si ID est présent et valide) ---
if ($annonce_id && $annonce && !isset($_GET['status'])) {
    
    // --- Affichage du Formulaire ---
    ?>
    <div class="container py-5">
        <a href="evaluer.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour à la liste d'évaluation</a>
        
        <h1 class="mb-4 display-6 fw-bold text-primary"><i class="bi bi-star-fill me-2"></i> Évaluer le service</h1>
        <p class="lead text-muted mb-4">Laissez votre évaluation pour le déménageur de l'annonce : <strong><?= htmlspecialchars($annonce['titre'] ?? 'N/A') ?></strong>.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error_msg): ?>
                    <p class="mb-0"><?= htmlspecialchars($error_msg) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($evaluationExistante): ?>
            <div class="alert alert-info py-4 text-center">
                <h4 class="alert-heading">Évaluation déjà soumise</h4>
                <p>Vous avez déjà évalué le déménageur **<?= htmlspecialchars($demenageur_nom) ?>** pour cette annonce.</p>
                <p class="mb-0">Note : <span class="badge bg-primary fs-5"><?= htmlspecialchars($evaluationExistante['note']) ?>/5</span></p>
                <p>Commentaire : <em>"<?= nl2br(htmlspecialchars($evaluationExistante['commentaire'])) ?>"</em></p>
            </div>
        <?php else: ?>

            <div class="card shadow-lg p-4">
                <h4 class="mb-4">Évaluer : <span class="text-success"><?= htmlspecialchars($demenageur_nom) ?></span></h4>
                <form method="POST" action="evaluer.php?annonce_id=<?= $annonce_id ?>">
                    
                    <div class="mb-4">
                        <label for="note" class="form-label fw-bold">Note de service (1 à 5 étoiles) <span class="text-danger">*</span></label>
                        <div id="rating-stars" class="d-flex" style="font-size: 2.5rem; cursor: pointer;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star-fill text-warning me-1" data-value="<?= $i ?>" style="opacity: <?= $i <= $note ? 1 : 0.3 ?>;"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="note" id="note-input" value="<?= htmlspecialchars($note) ?>" required>
                        <?php if (isset($errors['note'])): ?><div class="text-danger small mt-1"><?= htmlspecialchars($errors['note']) ?></div><?php endif; ?>
                    </div>
                    
                    <div class="mb-4">
                        <label for="commentaire" class="form-label fw-bold">Commentaire <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="commentaire" name="commentaire" rows="5" required><?= htmlspecialchars($commentaire) ?></textarea>
                        <?php if (isset($errors['commentaire'])): ?><div class="text-danger small mt-1"><?= htmlspecialchars($errors['commentaire']) ?></div><?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                        <i class="bi bi-send-fill me-2"></i> Soumettre l'évaluation
                    </button>
                </form>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const starsContainer = document.getElementById('rating-stars');
                    const noteInput = document.getElementById('note-input');
                    const stars = starsContainer.querySelectorAll('i');

                    function updateStars(value) {
                        stars.forEach((star, index) => {
                            star.style.opacity = index < value ? 1 : 0.3;
                        });
                        noteInput.value = value;
                    }

                    stars.forEach(star => {
                        star.addEventListener('click', function() {
                            const value = parseInt(this.getAttribute('data-value'));
                            updateStars(value);
                        });
                    });
                    
                    // Assurez-vous que l'état initial est bien affiché
                    updateStars(parseFloat(noteInput.value));
                });
            </script>

        <?php endif; ?>
    </div>
    <?php
}

include '../footer.php';
?>