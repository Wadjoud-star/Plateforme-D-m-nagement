<?php
// On définit le titre de la page
$pageTitle = 'Inscription - Rejoignez la plateforme';

// On inclut le header (pour le début du HTML, la session et le menu)
include 'header.php';
require_once 'Config.php'; // Assurez-vous que ce fichier gère la connexion $pdo

$error = '';
$success = '';
$role = 'client'; // Valeur par défaut pour pré-sélectionner

// Si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Récupération et nettoyage des données
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $mot_de_passe_confirm = $_POST['mot_de_passe_confirm'] ?? ''; // NOUVEAU : Récupération du champ de confirmation
    $role = $_POST['role'] ?? 'client'; 

    // 2. Validation simple
    if (empty($nom) || empty($email) || empty($mot_de_passe) || empty($mot_de_passe_confirm)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } elseif (strlen($mot_de_passe) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($mot_de_passe !== $mot_de_passe_confirm) { // NOUVEAU : Vérification de la correspondance
        $error = "Les mots de passe ne correspondent pas.";
    }
    // Correction de sécurité : on vérifie que le rôle est bien 'client' ou 'demenageur'
    elseif (!in_array($role, ['client', 'demenageur'])) {
        $role = 'client'; 
    } else {
        try {
            // 3. Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Cette adresse email est déjà utilisée.";
            } else {
                // 4. Hacher le mot de passe (Sécurité ESSENTIELLE)
                $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

                // 5. Insertion du nouvel utilisateur
                $stmt = $pdo->prepare("
                    INSERT INTO utilisateurs (nom, email, mot_de_passe, role) 
                    VALUES (:nom, :email, :mot_de_passe, :role)
                ");
                $stmt->execute([
                    'nom' => $nom,
                    'email' => $email,
                    'mot_de_passe' => $mot_de_passe_hash,
                    'role' => $role
                ]);

                // Succès et redirection vers la page de connexion
                header("Location: connexion.php?registration_success=1");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Une erreur de base de données est survenue. Veuillez réessayer.";
            error_log("Erreur d'inscription : " . $e->getMessage());
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 d-none d-md-block">
            <div class="h-100 p-5 bg-light rounded-3 text-center shadow-sm border border-primary">
                <i class="bi bi-box-seam-fill display-1 text-primary"></i>
                <h2 class="mt-4 fw-bold">Déménagez Facilement</h2>
                <p class="lead">Que vous ayez besoin d'aide ou que vous souhaitiez proposer vos services, notre plateforme vous met en relation.</p>
                <ul class="list-unstyled text-start small mt-4">
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Recevez des devis compétitifs.</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Gérez vos offres et propositions.</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Évaluations pour plus de confiance.</li>
                </ul>
            </div>
        </div>

        <div class="col-md-7 col-lg-6">
            <div class="card shadow-lg border-0 h-100">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i> Créer votre Compte</h4>
                    <p class="small mb-0">Rejoignez-nous en tant que Client ou Déménageur.</p>
                </div>
                <div class="card-body p-4">

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Je m'inscris en tant que :</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="role" id="roleClient" value="client" autocomplete="off" <?= ($role === 'client' || !isset($_POST['role'])) ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-primary w-100 py-2" for="roleClient">
                                        <i class="bi bi-person-fill me-1"></i> Client
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="role" id="roleDemenageur" value="demenageur" autocomplete="off" <?= $role === 'demenageur' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-primary w-100 py-2" for="roleDemenageur">
                                        <i class="bi bi-truck me-1"></i> Déménageur
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom Complet</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                <input type="text" class="form-control" id="nom" name="nom" required value="<?= htmlspecialchars($nom ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="mot_de_passe" class="form-label">Mot de Passe</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control password-field" id="mot_de_passe" name="mot_de_passe" required>
                                
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="mot_de_passe">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Minimum 6 caractères.</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="mot_de_passe_confirm" class="form-label">Confirmer le Mot de Passe</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control password-field" id="mot_de_passe_confirm" name="mot_de_passe_confirm" required>
                                
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="mot_de_passe_confirm">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            <i class="bi bi-arrow-right-circle-fill me-1"></i> M'inscrire
                        </button>
                    </form>

                </div>
                <div class="card-footer text-center bg-light">
                    <p class="mb-0 small">Déjà un compte ? <a href="connexion.php">Connectez-vous ici</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Sélectionne tous les boutons de bascule
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