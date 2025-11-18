<?php
session_start();
include 'header.php';
$pageTitle = 'À Propos de Nous';
?>

<div class="container py-5">
    <h1 class="mb-4 display-5 fw-bold text-primary"><i class="bi bi-info-circle-fill me-3"></i> Qui sommes-nous ?</h1>
    <p class="lead text-muted">Notre mission est de simplifier l'expérience du déménagement pour tous.</p>
   
    <div class="row mt-5">
        <div class="col-md-8">
            <h2 class="fw-bold mb-3">Notre Vision</h2>
            <p>
                La plateforme Mon Déménagement est née de la conviction que trouver un déménageur de confiance ne devrait pas être une source de stress. Nous avons créé une plateforme collaborative qui met en relation directe les particuliers (clients) avec un réseau de professionnels du déménagement vérifiés et évalués.
            </p>
            <p>
                Nous garantissons la transparence des prix, la qualité du service et la facilité d'utilisation. Que vous ayez un petit volume ou une grande maison à déplacer, nous facilitons la soumission d'annonces, la réception de devis comparatifs, et le choix du meilleur prestataire.
            </p>
           
            <h2 class="fw-bold mt-5 mb-3">Notre Équipe</h2>
            <p>
                Nous sommes une petite équipe de développeurs passionnés par la logistique et l'expérience utilisateur, travaillant à rendre votre transition vers votre nouveau foyer aussi fluide que possible.
            </p>
        </div>
        <div class="col-md-4 text-center">
            <i class="bi bi-person-heart display-1 text-danger"></i>
            <h3 class="mt-3">Conçu avec soin</h3>
            <p class="small text-muted">Une plateforme pensée par des étudiants pour répondre à un besoin réel.</p>
        </div>
    </div>
   
    <div class="text-center mt-5">
        <a href="contact.php" class="btn btn-lg btn-primary"><i class="bi bi-envelope-fill me-2"></i> Contactez-nous</a>
    </div>
</div>

<?php

?>
