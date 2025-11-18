<?php
include 'header.php';
require_once 'Config.php';

// Activation affichage erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $objet = trim($_POST['objet'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation des champs
    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email est invalide.";
    if (empty($objet)) $errors[] = "L'objet est obligatoire.";
    if (empty($message)) $errors[] = "Le message est obligatoire.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (nom, email, objet, message) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$nom, $email, $objet, $message])) {
            $success = true;
            $nom = $email = $objet = $message = '';
        } else {
            $errors[] = "Une erreur est survenue lors de l'envoi du message.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Contact - Mon Déménagement</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
    /* Nouvelle image de fond professionnelle */
    .hero {
      background-image: url('https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1470&q=80');
      background-size: cover;
      background-position: center center;
      height: 50vh;
      position: relative;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-weight: 700;
      font-size: 3rem;
      text-shadow: 2px 2px 6px rgba(0,0,0,0.7);
      margin-bottom: 3rem;
    }
    .hero-overlay {
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.5);
      z-index: 1;
    }
    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 90%;
    }

    .form-container {
      max-width: 700px;
      margin: 0 auto 5rem auto;
      background: white;
      padding: 2rem 3rem;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    button[type=submit] {
      background: linear-gradient(45deg, #007bff, #00c6ff);
      border: none;
      font-weight: bold;
      box-shadow: 0 4px 15px rgba(0,198,255,0.5);
      transition: background 0.3s ease;
    }
    button[type=submit]:hover {
      background: linear-gradient(45deg, #0056b3, #0099cc);
    }
  </style>
</head>
<body>

<section class="hero">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1>Contactez-nous</h1>
    <p>Nous sommes à votre écoute pour toutes vos questions.</p>
  </div>
</section>

<div class="container form-container">
  <?php if ($success): ?>
    <div class="alert alert-success">Merci pour votre message. Nous vous répondrons rapidement !</div>
  <?php elseif ($errors): ?>
    <div class="alert alert-danger">
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="contact.php" novalidate>
    <div class="mb-4">
      <label for="nom" class="form-label">Nom complet</label>
      <input type="text" id="nom" name="nom" class="form-control" value="<?= htmlspecialchars($nom ?? '') ?>" required/>
    </div>

    <div class="mb-4">
      <label for="email" class="form-label">Adresse email</label>
      <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email ?? '') ?>" required/>
    </div>

    <div class="mb-4">
      <label for="objet" class="form-label">Objet</label>
      <input type="text" id="objet" name="objet" class="form-control" value="<?= htmlspecialchars($objet ?? '') ?>" required/>
    </div>

    <div class="mb-4">
      <label for="message" class="form-label">Message</label>
      <textarea id="message" name="message" rows="6" class="form-control" required><?= htmlspecialchars($message ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary w-100">Envoyer</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php
// On inclut le footer
include 'footer.php';
?>

</body>
</html>
