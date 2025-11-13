<?php
// On démarre la session (déjà fait dans header.php si vous l'incluez, mais c'est une bonne pratique de le mettre ici aussi)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// On définit le titre de la page
$pageTitle = 'Connexion - Accès à votre espace';

// On inclut le header (pour le début du HTML, la session et le menu)
include 'header.php';
require_once 'Config.php'; // Assurez-vous que ce fichier gère la connexion $pdo

$errors = [];
$successMessage = '';

// Si l'utilisateur vient d'une inscription réussie
if (isset($_GET['registration_success'])) {
    $successMessage = "Inscription réussie ! Veuillez vous connecter avec vos identifiants.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "L'adresse email est invalide.";
    }
    if (empty($mot_de_passe)) {
        $errors['mot_de_passe'] = "Le mot de passe est obligatoire.";
    }

    if (empty($errors)) {
        try {
            // Récupération utilisateur par email
            $stmt = $pdo->prepare("SELECT id, nom, mot_de_passe, role FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
                // Authentification réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_nom'] = $user['nom'];

                // Redirection selon rôle
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } elseif ($user['role'] === 'demenageur') {
                    header('Location: demenageur/demenageur.php');
                } else { // client
                    header('Location: client/client.php');
                }
                exit();
            } else {
                $errors['connexion'] = "Email ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $errors['connexion'] = "Une erreur de base de données est survenue.";
            error_log("Erreur de connexion : " . $e->getMessage());
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0"><i class="bi bi-lock-fill me-2"></i> Se Connecter</h4>
                    <p class="small mb-0">Accédez à votre espace Client, Déménageur ou Administrateur.</p>
                </div>
                <div class="card-body p-4">

                    <?php if ($successMessage): ?>
                        <div class="alert alert-success" role="alert">
                            <?= htmlspecialchars($successMessage) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($errors['connexion'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($errors['connexion']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="connexion.php" novalidate>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                <input type="email" id="email" name="email" 
                                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="mot_de_passe" class="form-label">Mot de Passe</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" id="mot_de_passe" name="mot_de_passe" 
                                       class="form-control password-field <?= isset($errors['mot_de_passe']) ? 'is-invalid' : '' ?>" 
                                       required>
                                
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="mot_de_passe">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                                <?php if (isset($errors['mot_de_passe'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['mot_de_passe']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-end mb-4">
                            <a href="recuperation-mot-de-passe.php" class="small text-muted text-decoration-none">Mot de passe oublié ?</a>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
                        </button>
                    </form>

                </div>
                <div class="card-footer text-center bg-light">
                    <p class="mb-0 small">Pas encore inscrit ? <a href="inscription.php">Créez un compte ici</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Sélectionne tous les boutons de bascule avec la classe 'toggle-password'
        const toggleButtons = document.querySelectorAll('.toggle-password');

        toggleButtons.forEach(button => {
            button.addEventListener('click', function () {
                // Récupère l'ID du champ cible à partir de l'attribut data-target
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');

                // Basculer le type de champ (text/password)
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Basculer l'icône
                if (type === 'password') {
                    icon.classList.remove('bi-eye-slash-fill');
                    icon.classList.add('bi-eye-fill');
                } else {
                    icon.classList.remove('bi-eye-fill');
                    icon.classList.add('bi-eye-slash-fill');
                }
            });
        });
    });
</script>

<?php
// On inclut le footer
include 'footer.php';
?>