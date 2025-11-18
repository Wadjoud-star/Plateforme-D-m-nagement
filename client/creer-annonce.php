<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérifier que l'utilisateur est connecté et client
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Créer une Annonce - Client';
$user_id = $_SESSION['user_id'];

$errors = [];
$success = false;

// NOUVEAU CHEMIN : Séparation du chemin physique et du chemin BDD
$upload_destination = '../uploads/annonces/'; // Chemin pour PHP (déplacement)
$bdd_path_base = 'uploads/annonces/';          // Chemin pour la BDD (affichage)

// Initialisation des variables complètes pour pré-remplissage
$titre = $_POST['titre'] ?? '';
$description = $_POST['description'] ?? '';
$date_depot = $_POST['date_depot'] ?? date('Y-m-d');
$heure_debut = $_POST['heure_debut'] ?? '';
$ville_depart = $_POST['ville_depart'] ?? '';
$ville_arrivee = $_POST['ville_arrivee'] ?? '';
$volume = $_POST['volume'] ?? '';
$nb_demenageurs = $_POST['nb_demenageurs'] ?? 1;
$details_depart = $_POST['details_depart'] ?? '';
$details_arrivee = $_POST['details_arrivee'] ?? '';
$poids = $_POST['poids'] ?? '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validation des champs de base
    if (empty($titre)) { $errors['titre'] = "Le titre est obligatoire."; }
    if (empty($description)) { $errors['description'] = "La description est obligatoire."; }
    if (empty($date_depot)) { $errors['date_depot'] = "La date de déménagement est obligatoire."; }
    if (empty($heure_debut)) { $errors['heure_debut'] = "L'heure de début est obligatoire."; }
    if (empty($ville_depart)) { $errors['ville_depart'] = "La ville de départ est obligatoire."; }
    if (empty($ville_arrivee)) { $errors['ville_arrivee'] = "La ville d'arrivée est obligatoire."; }
    if (empty($volume) || !is_numeric($volume) || $volume <= 0) {
        $errors['volume'] = "Le volume (m³) est obligatoire et doit être positif.";
    }
    if ($nb_demenageurs <= 0) {
        $errors['nb_demenageurs'] = "Le nombre de déménageurs doit être au moins de 1.";
    }
    
    // Validation des photos (facultative mais sécurisée)
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['error'] as $key => $error) {
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                $errors['photos'] = "Une des photos est trop volumineuse.";
                break;
            }
            if ($error !== UPLOAD_ERR_OK && $error !== UPLOAD_ERR_NO_FILE) {
                $errors['photos'] = "Une erreur est survenue lors du téléchargement d'une photo.";
                break;
            }
            $file_extension = pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
            if (!in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png'])) {
                 $errors['photos'] = "Seuls les fichiers JPG, JPEG et PNG sont acceptés.";
                 break;
            }
        }
    }

    if (empty($errors)) {
        try {
            // 1. Insertion de l'annonce dans la table 'annonces'
            $stmt = $pdo->prepare("INSERT INTO annonces (utilisateur_id, titre, description, date_depot, ville_depart, ville_arrivee, volume) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$user_id, $titre, $description, $date_depot, $ville_depart, $ville_arrivee, $volume])) {
                
                $annonce_id = $pdo->lastInsertId();
                $success = true;

                // 2. Gestion des photos
                if (!empty($_FILES['photos']['name'][0])) {
                    if (!is_dir($upload_destination)) {
                        mkdir($upload_destination, 0777, true);
                    }
                    
                    $stmt_photo = $pdo->prepare("INSERT INTO photos_annonces (annonce_id, chemin_photo) VALUES (?, ?)");

                    foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_name = uniqid('photo_') . '.' . pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
                            
                            // Chemin COMPLET pour le déplacement physique
                            $full_destination_path = $upload_destination . $file_name; 
                            
                            if (move_uploaded_file($tmp_name, $full_destination_path)) {
                                // Chemin RELATIF AU ROOT DU PROJET (stockage pour l'affichage)
                                $file_path_bdd = $bdd_path_base . $file_name; 
                                
                                // Insertion du chemin propre dans la nouvelle table
                                $stmt_photo->execute([$annonce_id, $file_path_bdd]);
                            } else {
                                error_log("Erreur lors du déplacement du fichier: " . $tmp_name);
                            }
                        }
                    }
                }

                // Réinitialisation des champs pour un nouveau formulaire
                $titre = $description = $date_depot = $heure_debut = $ville_depart = $ville_arrivee = $details_depart = $details_arrivee = '';
                $volume = $nb_demenageurs = $poids = '';
                
            } else {
                $errors['submit'] = "Erreur lors de la création de l'annonce.";
            }
        } catch (PDOException $e) {
            $errors['submit'] = "Erreur de base de données : " . $e->getMessage();
            error_log("DB Error: " . $e->getMessage());
        }
    }
}
?>

<div class="container py-5">
    <a href="client.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour au Tableau de Bord</a>
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="mb-4 display-6 fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i> Publier votre annonce</h1>
            <p class="lead text-muted mb-5">Veuillez remplir le formulaire ci-dessous avec le maximum de détails pour recevoir des propositions précises de nos déménageurs partenaires.</p>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Annonce créée avec succès ! Elle est maintenant visible par les déménageurs.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($errors['submit'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errors['submit']) ?></div>
            <?php endif; ?>

            <form method="post" action="creer-annonce.php" novalidate enctype="multipart/form-data">

                <div class="card shadow-sm mb-4 border-primary border-top-0 border-end-0 border-bottom-0 border-5">
                    <div class="card-header bg-light">
                        <h4 class="mb-0 text-primary">1. Détails de base du projet</h4>
                    </div>
                    <div class="card-body">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="titre" class="form-label">Titre de l'annonce <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['titre']) ? 'is-invalid' : '' ?>" id="titre" name="titre" value="<?= htmlspecialchars($titre) ?>" required>
                                <?php if (isset($errors['titre'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['titre']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_depot" class="form-label">Date souhaitée <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?= isset($errors['date_depot']) ? 'is-invalid' : '' ?>" id="date_depot" name="date_depot" value="<?= htmlspecialchars($date_depot) ?>" required>
                                <?php if (isset($errors['date_depot'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['date_depot']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="heure_debut" class="form-label">Heure de début approximative <span class="text-danger">*</span></label>
                                <input type="time" class="form-control <?= isset($errors['heure_debut']) ? 'is-invalid' : '' ?>" id="heure_debut" name="heure_debut" value="<?= htmlspecialchars($heure_debut) ?>" required>
                                <?php if (isset($errors['heure_debut'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['heure_debut']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nb_demenageurs" class="form-label">Nombre de déménageurs souhaité <span class="text-danger">*</span></label>
                                <input type="number" class="form-control <?= isset($errors['nb_demenageurs']) ? 'is-invalid' : '' ?>" id="nb_demenageurs" name="nb_demenageurs" min="1" value="<?= htmlspecialchars($nb_demenageurs) ?>" required>
                                <?php if (isset($errors['nb_demenageurs'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['nb_demenageurs']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description détaillée du contenu et des services <span class="text-danger">*</span></label>
                            <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="4" required><?= htmlspecialchars($description) ?></textarea>
                            <small class="form-text text-muted">Précisez s'il y a des objets lourds (piano, coffre-fort) ou des besoins spécifiques.</small>
                            <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['description']) ?></div><?php endif; ?>
                        </div>

                    </div>
                </div>
                
                <div class="card shadow-sm mb-4 border-success border-top-0 border-end-0 border-bottom-0 border-5">
                    <div class="card-header bg-light">
                        <h4 class="mb-0 text-success">2. Logistique et Volume</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ville_depart" class="form-label">Ville de départ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['ville_depart']) ? 'is-invalid' : '' ?>" id="ville_depart" name="ville_depart" value="<?= htmlspecialchars($ville_depart) ?>" required>
                                <?php if (isset($errors['ville_depart'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['ville_depart']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="ville_arrivee" class="form-label">Ville d'arrivée <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['ville_arrivee']) ? 'is-invalid' : '' ?>" id="ville_arrivee" name="ville_arrivee" value="<?= htmlspecialchars($ville_arrivee) ?>" required>
                                <?php if (isset($errors['ville_arrivee'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['ville_arrivee']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                             <div class="col-md-6 mb-3">
                                <label for="volume" class="form-label">Volume (m³) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control <?= isset($errors['volume']) ? 'is-invalid' : '' ?>" id="volume" name="volume" min="1" step="0.5" value="<?= htmlspecialchars($volume) ?>" required>
                                <?php if (isset($errors['volume'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['volume']) ?></div><?php endif; ?>
                            </div>
                             <div class="col-md-6 mb-3">
                                <label for="poids" class="form-label">Poids total estimé (kg) (Optionnel)</label>
                                <input type="number" class="form-control" id="poids" name="poids" min="0" value="<?= htmlspecialchars($poids) ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4 border-warning border-top-0 border-end-0 border-bottom-0 border-5">
                    <div class="card-header bg-light">
                        <h4 class="mb-0 text-warning">3. Configuration du Logement et Médias</h4>
                    </div>
                    <div class="card-body">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="details_depart" class="form-label">Détails Logement DÉPART (étage, ascenseur, etc.)</label>
                                <textarea class="form-control" id="details_depart" name="details_depart" rows="3"><?= htmlspecialchars($details_depart) ?></textarea>
                                <small class="form-text text-muted">Ex: "Appartement au 3ème étage sans ascenseur."</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="details_arrivee" class="form-label">Détails Logement ARRIVÉE (étage, ascenseur, etc.)</label>
                                <textarea class="form-control" id="details_arrivee" name="details_arrivee" rows="3"><?= htmlspecialchars($details_arrivee) ?></textarea>
                                <small class="form-text text-muted">Ex: "Maison de plain-pied avec accès facile."</small>
                            </div>
                        </div>
                        
                         <div class="mb-3">
                             <label for="photos" class="form-label">Photos des objets (max. 5 photos JPG/PNG)</label>
                            <input type="file" class="form-control <?= isset($errors['photos']) ? 'is-invalid' : '' ?>" id="photos" name="photos[]" multiple accept="image/jpeg, image/png">
                            <?php if (isset($errors['photos'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['photos']) ?></div><?php endif; ?>
                        </div>
                        
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold mt-3">
                    <i class="bi bi-box-arrow-up me-2"></i> Publier mon annonce maintenant
                </button>
            </form>
        </div>
    </div>
</div>

<?php
include '../footer.php';
?>