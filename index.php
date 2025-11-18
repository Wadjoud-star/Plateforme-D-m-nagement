<?php
// On définit le titre de la page pour le <title>
$pageTitle = 'Accueil - Plateforme Déménagement';

// On inclut le header (qui gère la session, le début du HTML et le menu)
include 'demenagement/header.php'; 
require_once 'demenagement/Config.php';

// Les variables $isLoggedIn et $dashboardLink sont maintenant disponibles grâce à header.php

// Récupérer les 3 dernières annonces avec leur client
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.titre, a.description, a.ville_depart, a.ville_arrivee, a.date_depot, u.nom AS client_nom
        FROM annonces a
        INNER JOIN utilisateurs u ON a.utilisateur_id = u.id
        ORDER BY a.date_depot DESC
        LIMIT 3
    ");
    $stmt->execute();
    $annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la BDD est inaccessible, on affiche un message d'erreur (pour le développement)
    error_log("Erreur BDD index.php: " . $e->getMessage());
    $annonces = [];
}
?>

<section class="hero-banner position-relative mb-5" style="
    height: 70vh; 
    /* IMPORTANT : Ce chemin suppose que l'image est dans /demenagement/assets/hero_background.jpg */
    background-image: url('demenagement/assets/hero_background.jpg'); 
    background-position: center; /* Ajusté pour centrer l'image */
">
  <div class="hero-banner-overlay"></div>
  <div class="container hero-banner-content">
    <h1 class="display-2 fw-bolder">Votre Déménagement, Simplifié.</h1>
    <p class="lead fs-4">Connectez-vous à un réseau de déménageurs fiables et recevez des devis compétitifs en quelques clics.</p>
    <?php if (!$isLoggedIn): ?>
        <a href="inscription.php" class="btn btn-warning btn-lg fw-bold mt-3 shadow-lg">Recevoir des devis GRATUITS</a>
    <?php else: ?>
        <a href="<?= $dashboardLink ?>" class="btn btn-warning btn-lg fw-bold mt-3 shadow-lg">Accéder à mon tableau de bord</a>
    <?php endif; ?>
  </div>
</section>

<section class="container mb-5 py-5 text-center">
    <h2 class="mb-5 display-5 fw-bold text-primary">Comment ça marche ?</h2>
    <div class="row g-4"> 
        <div class="col-md-4">
            <div class="p-4 border rounded-3 shadow h-100 bg-light">
                <i class="bi bi-file-earmark-plus display-4 text-primary mb-3"></i>
                <h3 class="mt-3 fw-bold">1. Publiez votre annonce</h3>
                <p class="text-muted">Décrivez votre déménagement (volume, dates, adresses) et ajoutez quelques photos. C'est rapide et gratuit.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4 border rounded-3 shadow h-100 bg-light">
                <i class="bi bi-tag-fill display-4 text-success mb-3"></i>
                <h3 class="mt-3 fw-bold">2. Recevez des propositions</h3>
                <p class="text-muted">Des déménageurs qualifiés consultent votre annonce et vous proposent un prix fixe sur la plateforme.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4 border rounded-3 shadow h-100 bg-light">
                <i class="bi bi-check-circle-fill display-4 text-warning mb-3"></i>
                <h3 class="mt-3 fw-bold">3. Choisissez et déménagez</h3>
                <p class="text-muted">Comparez les offres, consultez les évaluations et choisissez le déménageur qui vous convient le mieux.</p>
            </div>
        </div>
    </div>
</section>

<section class="container mb-5 py-5 text-center bg-light rounded-3 shadow-sm">
    <h2 class="mb-5 display-6 fw-bold text-dark">Notre plateforme en chiffres</h2>
    <div class="row">
        <div class="col-md-3 mb-4">
            <i class="bi bi-person-check-fill display-4 text-primary"></i>
            <p class="display-4 fw-bolder mt-2">1,500+</p>
            <p class="lead text-muted">Déménageurs Inscrits</p>
        </div>
        <div class="col-md-3 mb-4">
            <i class="bi bi-briefcase-fill display-4 text-success"></i>
            <p class="display-4 fw-bolder mt-2">5,000+</p>
            <p class="lead text-muted">Missions Réalisées</p>
        </div>
        <div class="col-md-3 mb-4">
            <i class="bi bi-geo-alt-fill display-4 text-warning"></i>
            <p class="display-4 fw-bolder mt-2">98%</p>
            <p class="lead text-muted">Avis Positifs</p>
        </div>
        <div class="col-md-3 mb-4">
            <i class="bi bi-tag-fill display-4 text-danger"></i>
            <p class="display-4 fw-bolder mt-2">24h</p>
            <p class="lead text-muted">Délai de Devis Moyen</p>
        </div>
    </div>
</section>

<section class="container mb-5">
  <h2 class="mb-4 display-6">Dernières annonces publiées</h2>
  <?php if (empty($annonces)): ?>
    <div class="alert alert-info text-center">
        <i class="bi bi-info-circle-fill"></i> Aucune annonce n'a encore été publiée.
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($annonces as $annonce): ?>
        <div class="col-md-4 mb-4">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title text-primary"><?= htmlspecialchars($annonce['titre']) ?></h5>
              <h6 class="card-subtitle mb-2 text-muted">Client : <?= htmlspecialchars($annonce['client_nom']) ?></h6>
              <p class="card-text card-description" style="height: 80px; overflow: hidden; text-overflow: ellipsis;"><?= nl2br(htmlspecialchars(mb_strimwidth($annonce['description'], 0, 100, "..."))) ?></p>
              <div class="mt-auto pt-2 border-top">
                <p class="mb-1"><small>Départ : **<?= htmlspecialchars($annonce['ville_depart']) ?>**</small></p>
                <p class="mb-1"><small>Arrivée : **<?= htmlspecialchars($annonce['ville_arrivee']) ?>**</small></p>
                
                <a href="demenagement/annonces-public.php?id=<?= $annonce['id'] ?>" class="btn btn-sm btn-outline-secondary mt-2">Voir et Proposer</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5 display-6 text-dark">Ce que disent nos utilisateurs</h2>
        <div class="row">
            <div class="col-md-6 mb-4">
                <blockquote class="blockquote p-4 bg-white rounded shadow-sm border-start border-5 border-primary h-100">
                    <p class="mb-0 fst-italic">"J'ai pu trouver un déménageur pour un petit volume en moins de 24 heures. Le système de propositions est génial pour comparer les prix !"</p>
                    <footer class="blockquote-footer mt-2">Marie D., <cite title="Source Title">Client satisfaite</cite></footer>
                </blockquote>
            </div>
            <div class="col-md-6 mb-4">
                <blockquote class="blockquote p-4 bg-white rounded shadow-sm border-start border-5 border-success h-100">
                    <p class="mb-0 fst-italic">"La plateforme nous apporte un flux constant de nouvelles opportunités. C'est le meilleur outil pour développer notre activité."</p>
                    <footer class="blockquote-footer mt-2">Transport Express S.A.S., <cite title="Source Title">Déménageur Partenaire</cite></footer>
                </blockquote>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-primary text-white text-center">
    <div class="container">
        <h2 class="display-5 fw-bold mb-3">Prêt à déménager sans stress ?</h2>
        <p class="lead mb-4">Créez votre annonce en quelques minutes et recevez vos premières propositions de prix.</p>
        <?php if (!$isLoggedIn): ?>
            <a href="inscription.php" class="btn btn-lg btn-warning fw-bold shadow">Je publie mon annonce maintenant <i class="bi bi-arrow-right-short"></i></a>
        <?php else: ?>
            <a href="<?= $dashboardLink ?>" class="btn btn-lg btn-warning fw-bold shadow">Accéder à mon tableau de bord <i class="bi bi-arrow-right-short"></i></a>
        <?php endif; ?>
    </div>
</section>

<?php
// On inclut le footer
include 'demenagement/footer.php';
?>