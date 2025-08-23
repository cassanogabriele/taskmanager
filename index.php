<?php 
// Point de démarrage de l'application

/*
Chargement automatique des fichiers des classes quand on les utilise, sans devoir les inclure partout manuellement. 

- spl_autoload_register : fonction de PHP, qui enregistre une fonction anonyme, qu'il appellera chaque fois qu'il rencontrera une classe inconue. 
- $class : contient de le nom de la classe qu'on va charger 
- __DIR__ : chemin absolue du dossier courant, on l'utilise pour le concaténer
*/
spl_autoload_register(function($class) {
    require __DIR__ . '/classes/' . $class . '.php';
});

// Charger les données 
$taches = [];

if(file_exists("data.json")) {
    $json = file_get_contents("data.json");
    $donnees = json_decode($json, true) ?? [];

    foreach($donnees as $donnee) {
        $titre = $donnee['titre'];
        $categorie = !empty($donnee['categorie']) ? (string)$donnee['categorie'] : '';
        $terminee = $donnee['terminee'] ?? false;
        $dateEcheance = !empty($donnee['dateEcheance']) ? new DateTime($donnee['dateEcheance']) : null;

        $taches[] = new Tache($titre, $categorie, $terminee, $dateEcheance);
    }
}

// Inclure le fichier qui affiche les tâches et le formulaire
include "views/afficherTaches.php";

