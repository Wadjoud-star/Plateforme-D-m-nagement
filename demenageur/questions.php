<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérification du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'demenageur') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Demander un Complément';
$userId = $_SESSION['user_id'];
$demenageurNom = htmlspecialchars($_SESSION['user_nom']);
$message = '';
$error = '';
$annonce = null;
$client_id = $_GET['client_id'] ?? null;
$annonce_id = $_GET['annonce_id'] ?? null;
$question_texte = $_POST['question_texte'] ?? '';


// --- LOGIQUE DE VÉRIFICATION ET CHARGEMENT DE L'ANNONCE ---
if (!$annonce_id || !$client_id) {
    header('Location: voir-annonces.php');
    exit();
}

// Assurer que les IDs sont des entiers pour la sécurité
$annonce_id = intval($annonce_id);
$client_id = intval($client_id);


try {
    // 1. Charger l'annonce et vérifier la liaison Client/Annonce
    $stmt = $pdo->prepare("
        SELECT a.titre, a.ville_depart, a.ville_arrivee, a.description, u.nom AS client_nom
        FROM annonces a
        JOIN utilisateurs u ON a.utilisateur_id = u.id
        WHERE a.id = ? AND a.utilisateur_id = ?
    ");
    $stmt->execute([$annonce_id, $client_id]);
    $annonce = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$annonce) {
        $error = "Annonce ou Client cible introuvable.";
    }
    
} catch (PDOException $e) {
    $error = "Erreur lors du chargement des données de l'annonce.";
    error_log("Questions Load Error: " . $e->getMessage());
}


// --- TRAITEMENT DU FORMULAIRE POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    
    if (empty(trim($question_texte))) {
        $error = "Veuillez saisir votre question.";
    } else {
        try {
            // Insertion de la question dans la BDD
            $stmt = $pdo->prepare("
                INSERT INTO questions_demenageurs (annonce_id, demenageur_id, client_id, question_texte) 
                VALUES (?, ?, ?, ?)
            ");
            // Le $userId (déménageur), $annonce_id et $client_id sont vérifiés pour la clé étrangère.
            $stmt->execute([$annonce_id, $userId, $client_id, trim($question_texte)]);
            
            $message = "Votre demande de complément d'information a été transmise au client (" . htmlspecialchars($annonce['client_nom']) . ").";
            $question_texte = ''; // Vider le champ après succès
            
        } catch (PDOException $e) {
            // Cette erreur est maintenant plus ciblée pour les problèmes de clé étrangère
            $error = "Erreur : La question n'a pas pu être enregistrée. (Problème de BDD ou clé manquante).";
            error_log("Question Submit Error (PDO): " . $e->getMessage());
        } catch (Exception $e) {
            $error = "Une erreur inattendue est survenue lors de l'envoi.";
            error_log("Question Submit Error: " . $e->getMessage());
        }
    }
}
?>

<div class="container py-5">
    <a href="voir-annonces.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour aux Annonces Disponibles</a>
    
    <h1 class="mb-4 display-6 fw-bold text-primary"><i class="bi bi-chat-dots-fill me-2"></i> Demander un Complément d'Info</h1>
    <p class="lead text-muted mb-4">Annonce : <strong><?= htmlspecialchars($annonce['titre'] ?? 'Chargement...') ?></strong> (Client : <?= htmlspecialchars($annonce['client_nom'] ?? 'Chargement...') ?>)</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-5 border-0">
        <div class="card-header bg-light fw-bold">Contexte de l'Annonce</div>
        <div class="card-body">
            <p class="mb-1"><strong>Trajet :</strong> <?= htmlspecialchars($annonce['ville_depart'] ?? '') ?> → <?= htmlspecialchars($annonce['ville_arrivee'] ?? '') ?></p>
            <p><strong>Description :</strong> <?= nl2br(htmlspecialchars($annonce['description'] ?? '')) ?></p>
            <hr>
            <p class="small text-muted mb-0">Utilisez ce formulaire uniquement si l'annonce ne contient pas assez d'informations pour établir un devis précis.</p>
        </div>
    </div>


    <div class="card shadow-lg p-4">
        <h4 class="mb-4">Votre Question</h4>
        <form method="POST" action="questions.php?client_id=<?= $client_id ?>&annonce_id=<?= $annonce_id ?>">
            
            <div class="mb-4">
                <label for="question_texte" class="form-label fw-bold">Votre question au client <span class="text-danger">*</span></label>
                <textarea class="form-control" id="question_texte" name="question_texte" rows="6" required><?= htmlspecialchars($question_texte) ?></textarea>
                <small class="form-text text-muted">Soyez précis : étage sans ascenseur, besoin d'emballage spécifique, etc.</small>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                <i class="bi bi-send-fill me-2"></i> Envoyer la demande d'information
            </button>
        </form>
    </div>

</div>

<?php
include '../footer.php';
?>