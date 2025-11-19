<?php
session_start();
include 'header.php';
require_once 'Config.php';

$pageTitle = 'Contactez-nous';
// Activer l'affichage des erreurs n'est pas nécessaire en production, mais utile en développement.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errors = [];
$success = false;

// Initialisation des variables pour pré-remplissage
$nom = $_POST['nom'] ?? '';
$email = $_POST['email'] ?? '';
$objet = $_POST['objet'] ?? '';
$message = $_POST['message'] ?? '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validation des champs
    if (empty(trim($nom))) $errors[] = "Le nom est obligatoire.";
    if (empty(trim($email)) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email est invalide.";
    if (empty(trim($objet))) $errors[] = "L'objet est obligatoire.";
    if (empty(trim($message))) $errors[] = "Le message est obligatoire.";

    if (empty($errors)) {
        try {
            // NOTE : Assurez-vous d'avoir une table 'contact_messages' dans votre BDD
            $stmt = $pdo->prepare("INSERT INTO contact_messages (nom, email, objet, message) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$nom, $email, $objet, $message])) {
                $success = true;
                // Réinitialisation des champs après succès
                $nom = $email = $objet = $message = ''; 
            } else {
                $errors[] = "Une erreur est survenue lors de l'envoi du message (exécution SQL).";
            }
        } catch (PDOException $e) {
             $errors[] = "Erreur de base de données : Impossible d'enregistrer le message.";
             error_log("Contact Form Error: " . $e->getMessage());
        }
    }
}
?>

<section class="hero-banner position-relative" style="
    height: 50vh; 
    background-image: url('assets/hero_background.jpg'); 
    background-position: bottom center; 
    margin-bottom: 3rem;
">
  <div class="hero-banner-overlay"></div>
  <div class="container hero-banner-content">
    <h1 class="display-3 fw-bolder">Contactez-nous</h1>
    <p class="lead fs-5">Nous sommes à votre écoute pour toutes vos questions et remarques.</p>
  </div>
</section>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="card shadow-lg border-0 form-container p-4">
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill me-2"></i> Merci pour votre message. Nous vous répondrons rapidement !
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($errors): ?>
                    <div class="alert alert-danger">
                        <h5 class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Erreur de soumission :</h5>
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="contact.php" novalidate>
                    <div class="mb-4">
                        <label for="nom" class="form-label fw-bold">Nom complet <span class="text-danger">*</span></label>
                        <input type="text" id="nom" name="nom" class="form-control" value="<?= htmlspecialchars($nom) ?>" required/>
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label fw-bold">Adresse email <span class="text-danger">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required/>
                    </div>

                    <div class="mb-4">
                        <label for="objet" class="form-label fw-bold">Objet <span class="text-danger">*</span></label>
                        <input type="text" id="objet" name="objet" class="form-control" value="<?= htmlspecialchars($objet) ?>" required/>
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label fw-bold">Message <span class="text-danger">*</span></label>
                        <textarea id="message" name="message" rows="6" class="form-control" required><?= htmlspecialchars($message) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="bi bi-send-fill me-2"></i> Envoyer le message
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <p class="small text-muted mb-0">Vous pouvez aussi nous contacter directement à **contact@demenagement.com**</p>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php
// On inclut le footer
include 'footer.php';
?>