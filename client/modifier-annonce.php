<?php
session_start();
include '../header.php';
require_once '../Config.php';

// Vérifier que l'utilisateur est connecté et client
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: ../connexion.php');
    exit();
}

$pageTitle = 'Modifier votre Annonce';
$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;
$annonce = null;
$photos = [];
$annonce_id = $_GET['id'] ?? null;
$upload_dir = '../uploads/annonces/'; 

// 1. VÉRIFICATION ET CHARGEMENT DE L'ANNONCE
if ($annonce_id) {
    try {
        // Charger l'annonce et vérifier si elle appartient bien à l'utilisateur
        $stmt = $pdo->prepare("SELECT * FROM annonces WHERE id = ? AND utilisateur_id = ?");
        $stmt->execute([$annonce_id, $user_id]);
        $annonce = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$annonce) {
            die("<div class='alert alert-danger container mt-5'>Annonce introuvable ou vous n'avez pas les droits de modification.</div>");
        }

        // Charger les photos existantes
        $stmt_photos = $pdo->prepare("SELECT id, chemin_photo FROM photos_annonces WHERE annonce_id = ?");
        $stmt_photos->execute([$annonce_id]);
        $photos = $stmt_photos->fetchAll(PDO::FETCH_ASSOC);
        
        // Simuler la récupération des champs manquants non présents dans la BDD (mais dans le formulaire)
        // NOTE: Si vous ajoutez ces colonnes à la table 'annonces', vous devrez les récupérer ici.
        $heure_debut = '09:00'; // Simulation
        $details_depart = 'Détails de départ existants...'; // Simulation
        $details_arrivee = 'Détails d\'arrivée existants...'; // Simulation
        $nb_demenageurs = 2; // Simulation
        $poids = 500; // Simulation
        
    } catch (PDOException $e) {
        $errors['load'] = "Erreur lors du chargement de l'annonce : " . $e->getMessage();
        error_log("Load Error: " . $e->getMessage());
    }
} else {
    die("<div class='alert alert-warning container mt-5'>Aucun identifiant d'annonce spécifié.</div>");
}


// 2. TRAITEMENT DU FORMULAIRE DE MISE À JOUR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération des données POST
    $titre = trim($_POST['titre'] ?? $annonce['titre']);
    $description = trim($_POST['description'] ?? $annonce['description']);
    $date_depot = $_POST['date_depot'] ?? $annonce['date_depot'];
    $heure_debut = $_POST['heure_debut'] ?? $heure_debut; // Utilisation de la simulation si non en BDD
    $ville_depart = trim($_POST['ville_depart'] ?? $annonce['ville_depart']);
    $ville_arrivee = trim($_POST['ville_arrivee'] ?? $annonce['ville_arrivee']);
    $volume = $_POST['volume'] ?? $annonce['volume'];
    $nb_demenageurs = $_POST['nb_demenageurs'] ?? $nb_demenageurs;
    $details_depart = $_POST['details_depart'] ?? $details_depart;
    $details_arrivee = $_POST['details_arrivee'] ?? $details_arrivee;
    $poids = $_POST['poids'] ?? $poids;

    // Suppression de photos existantes
    $photos_a_supprimer = $_POST['delete_photos'] ?? [];

    // Validation
    if (empty($titre)) { $errors['titre'] = "Le titre est obligatoire."; }
    // (Ajouter ici toutes les validations des champs modifiables...)

    if (empty($errors)) {
        try {
            // A. MISE À JOUR DE LA TABLE ANNONCES
            $stmt = $pdo->prepare("
                UPDATE annonces 
                SET titre = ?, description = ?, date_depot = ?, ville_depart = ?, ville_arrivee = ?, volume = ?
                WHERE id = ? AND utilisateur_id = ?
            ");
            $stmt->execute([
                $titre, $description, $date_depot, $ville_depart, $ville_arrivee, $volume,
                $annonce_id, $user_id
            ]);

            // B. GESTION DE LA SUPPRESSION DES PHOTOS
            if (!empty($photos_a_supprimer)) {
                $stmt_delete = $pdo->prepare("DELETE FROM photos_annonces WHERE id = ? AND annonce_id = ?");
                foreach ($photos_a_supprimer as $photo_id) {
                    // Supprimer le fichier physique d'abord
                    $stmt_path = $pdo->prepare("SELECT chemin_photo FROM photos_annonces WHERE id = ?");
                    $stmt_path->execute([$photo_id]);
                    $path = $stmt_path->fetchColumn();
                    if ($path && file_exists($path)) {
                        unlink($path);
                    }
                    // Supprimer l'entrée en BDD
                    $stmt_delete->execute([$photo_id, $annonce_id]);
                }
            }
            
            // C. GESTION DE L'AJOUT DE NOUVELLES PHOTOS (Logique similaire à creer-annonce.php)
            if (!empty($_FILES['photos']['name'][0]) && empty($errors['photos'])) {
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                $stmt_photo = $pdo->prepare("INSERT INTO photos_annonces (annonce_id, chemin_photo) VALUES (?, ?)");

                foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = uniqid('photo_') . '.' . pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
                        $file_path = $upload_dir . $file_name;

                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $stmt_photo->execute([$annonce_id, $file_path]);
                        } else {
                            error_log("Erreur lors du déplacement du fichier: " . $tmp_name);
                        }
                    }
                }
            }
            
            $success = true;
            // Recharger l'annonce après mise à jour pour pré-remplir le formulaire
            header("Location: modifier-annonce.php?id=" . $annonce_id . "&status=updated");
            exit();

        } catch (PDOException $e) {
            $errors['submit'] = "Erreur lors de la mise à jour : " . $e->getMessage();
            error_log("Update Error: " . $e->getMessage());
        }
    }
    
    // Si la soumission a échoué (erreurs de validation), on utilise les valeurs POST pour pré-remplir
    // Sinon, le formulaire utilise les valeurs chargées de $annonce
}
?>

<div class="container py-5">
    <a href="client.php" class="btn btn-outline-secondary btn-sm mb-4"><i class="bi bi-arrow-left me-2"></i> Retour au Tableau de Bord</a>
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="mb-4 display-6 fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i> Modification de l'annonce : <?= htmlspecialchars($annonce['titre'] ?? 'Chargement...') ?></h1>
            <p class="lead text-muted mb-5">Modifiez les détails de votre annonce et mettez à jour les photos si nécessaire.</p>
            
            <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Annonce mise à jour avec succès !
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($errors['submit'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errors['submit']) ?></div>
            <?php endif; ?>

            <form method="post" action="modifier-annonce.php?id=<?= $annonce_id ?>" novalidate enctype="multipart/form-data">
                
                <div class="card shadow-sm mb-4 border-primary border-top-0 border-end-0 border-bottom-0 border-5">
                    <div class="card-header bg-light">
                        <h4 class="mb-0 text-primary">1. Détails de base du projet</h4>
                    </div>
                    <div class="card-body">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="titre" class="form-label">Titre de l'annonce <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['titre']) ? 'is-invalid' : '' ?>" id="titre" name="titre" value="<?= htmlspecialchars($titre ?? $annonce['titre']) ?>" required>
                                <?php if (isset($errors['titre'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['titre']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_depot" class="form-label">Date souhaitée <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?= isset($errors['date_depot']) ? 'is-invalid' : '' ?>" id="date_depot" name="date_depot" value="<?= htmlspecialchars($date_depot ?? $annonce['date_depot']) ?>" required>
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
                            <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="4" required><?= htmlspecialchars($description ?? $annonce['description']) ?></textarea>
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
                                <input type="text" class="form-control <?= isset($errors['ville_depart']) ? 'is-invalid' : '' ?>" id="ville_depart" name="ville_depart" value="<?= htmlspecialchars($ville_depart ?? $annonce['ville_depart']) ?>" required>
                                <?php if (isset($errors['ville_depart'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['ville_depart']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="ville_arrivee" class="form-label">Ville d'arrivée <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['ville_arrivee']) ? 'is-invalid' : '' ?>" id="ville_arrivee" name="ville_arrivee" value="<?= htmlspecialchars($ville_arrivee ?? $annonce['ville_arrivee']) ?>" required>
                                <?php if (isset($errors['ville_arrivee'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['ville_arrivee']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                             <div class="col-md-6 mb-3">
                                <label for="volume" class="form-label">Volume (m³) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control <?= isset($errors['volume']) ? 'is-invalid' : '' ?>" id="volume" name="volume" min="1" step="0.5" value="<?= htmlspecialchars($volume ?? $annonce['volume']) ?>" required>
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
                        
                         <?php if (!empty($photos)): ?>
                            <h6 class="mt-3 mb-2">Photos actuelles (Cochez pour supprimer)</h6>
                            <div class="row mb-3">
                                <?php foreach ($photos as $photo): ?>
                                    <div class="col-4 col-md-3 mb-2">
                                        <div class="card shadow-sm">
                                            <img src="<?= htmlspecialchars($photo['chemin_photo']) ?>" class="card-img-top" style="height: 100px; object-fit: cover;" alt="Photo d'annonce">
                                            <div class="card-body p-1 text-center bg-light">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="delete_photos[]" value="<?= $photo['id'] ?>" id="photo-<?= $photo['id'] ?>">
                                                    <label class="form-check-label small text-danger" for="photo-<?= $photo['id'] ?>">Supprimer</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                         <?php endif; ?>
                         
                         <div class="mb-3">
                             <label for="photos" class="form-label">Ajouter de nouvelles photos (ou remplacer les anciennes)</label>
                            <input type="file" class="form-control <?= isset($errors['photos']) ? 'is-invalid' : '' ?>" id="photos" name="photos[]" multiple accept="image/jpeg, image/png">
                            <?php if (isset($errors['photos'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['photos']) ?></div><?php endif; ?>
                        </div>
                        
                    </div>
                </div>

                <button type="submit" class="btn btn-warning w-100 py-3 fw-bold mt-3">
                    <i class="bi bi-arrow-repeat me-2"></i> Mettre à jour l'annonce
                </button>
            </form>
        </div>
    </div>
</div>

<?php
include '../footer.php';
?>