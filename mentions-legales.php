<?php
session_start();
include 'header.php';
$pageTitle = 'Mentions Légales';
?>

<div class="container py-5">
    <h1 class="mb-4 display-5 fw-bold text-primary"><i class="bi bi-gavel me-3"></i> Mentions Légales</h1>
    <p class="lead text-muted mb-5">Informations requises par la loi française pour l'identification de l'éditeur.</p>

    <div class="card shadow-sm p-4 mb-4">
        <h2 class="fw-bold mb-3">1. Éditeur du Site</h2>
        <p>
            Le présent site est édité par :
        </p>
        <ul class="list-unstyled">
            <li>**Nom de la structure :** Projet Étudiant (Université/École XXXXXX)</li>
            <li>**Statut :** Projet universitaire, non commercial.</li>
            <li>**Adresse :** [Votre Adresse ou Adresse de l'École, Ex: 1 Rue des Études, 75000 Paris]</li>
            <li>**Téléphone :** [Votre numéro de téléphone]</li>
            <li>**Contact Email :** contact@demenagement.com</li>
        </ul>
    </div>
   
    <div class="card shadow-sm p-4">
        <h2 class="fw-bold mb-3">2. Hébergement</h2>
        <p>
            Le site est hébergé sur les serveurs de :
        </p>
        <ul class="list-unstyled">
            <li>**Nom de l'hébergeur :** Serveur de l'Établissement (ou de l'école/université)</li>
            <li>**Adresse de l'hébergeur :** [Adresse du serveur si connue, sinon : Adresse de l'École]</li>
        </ul>
    </div>
   
</div>

<?php

?>