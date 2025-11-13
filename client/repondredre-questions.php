<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérification du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Questions Déménageurs';
$userId = $_SESSION['user_id'];
$errors = [];
$message = '';

// --- LOGIQUE DE TRAITEMENT DE LA RÉPONSE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_id'])) {
    $question_id = intval($_POST['question_id']);
    $reponse_texte = trim($_POST['reponse_texte'] ?? '');

    if (empty($reponse_texte)) {
        $errors['reponse'] = "La réponse ne peut pas être vide.";
    } else {
        try {
            // Mise à jour de la réponse dans la BDD
            $stmt = $pdo->prepare("
                UPDATE questions_demenageurs 
                SET reponse_texte = ?, date_reponse = NOW()
                WHERE id = ? AND client_id = ? AND reponse_texte IS NULL
            ");
            $stmt->execute([$reponse_texte, $question_id, $userId]);
            
            $message = "Votre réponse a été envoyée avec succès au déménageur.";

        } catch (Exception $e) {
            $errors['submit'] = "Erreur lors de l'envoi de la réponse.";
            error_log("Response Submit Error: " . $e->getMessage());
        }
    }
}


// --- LOGIQUE DE CHARGEMENT DES QUESTIONS EN ATTENTE DE RÉPONSE ---
$sql = "
    SELECT 
        q.id AS question_id, 
        q.question_texte, 
        q.date_question, 
        a.titre AS annonce_titre,
        u_dem.nom AS demenageur_nom
    FROM questions_demenageurs q
    JOIN annonces a ON q.annonce_id = a.id
    JOIN utilisateurs u_dem ON q.demenageur_id = u_dem.id
    WHERE q.client_id = ? AND q.reponse_texte IS NULL -- Seulement les questions sans réponse
    ORDER BY q.date_question DESC
";

$questions = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors['load'] = "Erreur lors du chargement des questions.";
    error_log("Questions Client Load Error: " . $e->getMessage());
}
?>

<div class="container py-5">
    <a href="client.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour au Tableau de Bord</a>
    
    <h1 class="mb-4 display-6 fw-bold text-primary"><i class="bi bi-question-square-fill me-2"></i> Questions des Déménageurs</h1>
    <p class="lead text-muted mb-4">Veuillez répondre aux questions ci-dessous pour permettre aux déménageurs de vous fournir un devis précis.</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error_msg): ?>
                <p class="mb-0"><?= htmlspecialchars($error_msg) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($questions)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="bi bi-info-circle-fill me-2"></i> Aucune question en attente de réponse.
        </div>
    <?php else: ?>
        
        <?php foreach ($questions as $question): ?>
        <div class="card shadow-sm mb-4 border-primary border-start border-5">
            <div class="card-header bg-light d-flex justify-content-between">
                <span class="fw-bold">Déménageur : <?= htmlspecialchars($question['demenageur_nom']) ?></span>
                <span class="small text-muted">Annonce : <?= htmlspecialchars($question['annonce_titre']) ?></span>
            </div>
            <div class="card-body">
                <p class="fst-italic">**Question posée le <?= date('d/m/Y H:i', strtotime($question['date_question'])) ?> :**</p>
                <p class="lead"><?= nl2br(htmlspecialchars($question['question_texte'])) ?></p>
                
                <hr>
                
                <form method="POST" action="repondre-questions.php">
                    <input type="hidden" name="question_id" value="<?= $question['question_id'] ?>">
                    <div class="mb-3">
                        <label for="reponse_texte_<?= $question['question_id'] ?>" class="form-label fw-bold">Votre Réponse <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reponse_texte_<?= $question['question_id'] ?>" name="reponse_texte" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-reply-fill me-1"></i> Envoyer la Réponse</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<?php
include '../footer.php';
?>