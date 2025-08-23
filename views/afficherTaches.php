<?php
// Fichier de sauvegarde des ta^ches
$dataFile = __DIR__ . '/../data.json';
// Instanciation de l'objet "ListeDeTaches"
$liste = new ListeDeTaches();

// Catégories pour les tâches et sous tâches dans le "select"
$categoriesDisponibles = ['Travail', 'Personnel', 'Urgent', 'Autre'];

$couleursCategories = [
    'Travail' => 'primary',
    'Personnel' => 'success',
    'Urgent' => 'warning',
    'Autre' => 'secondary',
    'Aucune' => 'dark'
];

// Crée un objet "Tâche" à partir d'un tableau JSON
function creerTacheDepuisArray(array $data): Tache {
    // Création d'une nouvelle tache 
    $tache = new Tache(
        $data['titre'] ?? '',
        $data['categorie'] ?? 'Aucune',
        $data['terminee'] ?? false,
        !empty($data['dateEcheance']) ? new DateTime($data['dateEcheance']) : null,
        $data['prioritaire'] ?? false,
        $data['urgente'] ?? false
    );

    // Vérifier si la tâche est archivéee
    if (isset($data['archivee'])) {
        // On applique la valeur via la fonction "setArchivee"
        $tache->setArchivee($data['archivee']);
    }

    // Vérifier si il y a des sous-tâches liées à la tâche
    if (!empty($data['sousTaches'])) {
        // On leur parcours toutes et on les ajoute à la tâche
        foreach ($data['sousTaches'] as $st) {
            $tache->ajouterSousTache(creerTacheDepuisArray($st));
        }
    }

    // Retourner les informations de la tâche à afficher
    return $tache;
}

// Charger les tâches depuis le fichier JSON

// Vérifier si le fichier existe 
if (file_exists($dataFile)) {
    // Lire le contenu complet du fichier JSON 
    $json = file_get_contents($dataFile);
    // Décoder le tableau JSON en tableau associatif PHP 
    $tachesArray = json_decode($json, true);

    // Vérifier que le résultat du décodage est bien un tableau 
    if (is_array($tachesArray)) {
        // Parcourt de chaque élément du tableau, chaque élément représentant une tâche
        foreach ($tachesArray as $tacheData) {
            // Créer un objet "Tache" à partir du tableau et l'ajouter à la liste 
            $liste->ajouterTache(creerTacheDepuisArray($tacheData));
        }
    }
}

// Traitements des actions : si la méthode est "POST" et qu'elle contient le mot "action"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Récupérer l'action demandée par le formulaire 
    $action = $_POST['action'];
    // Récupérer l'index de la ^tache, sinon on la défini à "-1" si elle n'est pas définie
    $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;

    // 1. Ajouter unen ouvelle tâche
    if ($action === 'ajouter') {
        // Récupération des informations de la tâche 
        $titre = htmlspecialchars($_POST['titre'] ?? '');
        $categorie = $_POST['categorie'] ?? 'Aucune';
        $dateEcheance = !empty($_POST['dateEcheance']) ? new DateTime($_POST['dateEcheance']) : null;
        
        // Ajouter la nouvelle tâche à la liste des tâche 
        $liste->ajouterTache(new Tache($titre, $categorie, false, $dateEcheance));
    } elseif ($action === 'modifier' && $index >= 0 && isset($liste->getTaches()[$index])) {
        //  2. Modifier une tâche existante
        
        // Récupérer la tâche à modifier 
        $tache = $liste->getTaches()[$index];
        // Récupérer les informations à modifier
        $tache->setTitre(htmlspecialchars($_POST['titre'] ?? ''));
        $tache->setCategorie($_POST['categorie'] ?? 'Aucune');       
        $tache->setDateEcheance(!empty($_POST['dateEcheance']) ? new DateTime($_POST['dateEcheance']) : null);
    } elseif ($action === 'terminer' && $index >= 0 && isset($liste->getTaches()[$index])) {
        // 3. Marque une tâche comme "terminée"
        $liste->getTaches()[$index]->marquerCommeTerminee();
    } elseif ($action === 'supprimer' && $index >= 0 && isset($liste->getTaches()[$index])) {
        // 4. Supprimer une tâche
        $liste->supprimerTache($index);
    } elseif ($action === 'prioriser' && $index >= 0 && isset($liste->getTaches()[$index])) {
        // 5. Prioriser une tâche 

        // Récupérer la tâche à "prioriser"
        $tache = $liste->getTaches()[$index];
        $tache->setPrioritaire(!$tache->estPrioritaire());
    } elseif ($action === 'prioriser_toutes') {
        // 6. Prioriser toutes les tâches
        foreach ($liste->getTaches() as $t) $t->setPrioritaire(true);
    } elseif ($action === 'deprioriser_toutes') {
        // 7. Déprioriser toutes les tâches
        foreach ($liste->getTaches() as $t) $t->setPrioritaire(false);
    } elseif ($action === 'urgentiser' && $index >= 0 && isset($liste->getTaches()[$index])) {       
        // 8. Urgentiser une tâche 

        // Récupérer la tâche à "urgentiser"
        $tache = $liste->getTaches()[$index];

        // Inverser l'état d'urgence 
        $etat = !$tache->estUrgente(); 
        $tache->setUrgente($etat);

        // Si la tâche devient urgente, mémoriser l'ancienne catégorie
        if ($etat) {
            if (!isset($tache->ancienneCategorie)) {
                $tache->ancienneCategorie = $tache->getCategorie();
            }
            $tache->setCategorie('Urgent');
        } else {
            // Si la tâche n'est plus urgent, restaurer l'ancienne catégorie 
            $tache->setCategorie($tache->ancienneCategorie ?? 'Aucune');
            unset($tache->ancienneCategorie);
        }
    } elseif ($action === 'ajouterSousTache' && $index >= 0 && isset($liste->getTaches()[$index])) {  
        // 9. Ajouter une sous-tache
        
        // Récupérer les infos de la sous-taches
        $titre = $_POST['titreSousTache'] ?? '';
        $categorie = $_POST['categorieSousTache'] ?: 'Aucune';

        // Récupérer la tache parent de la sous-tâche à ajouter
        $tacheParent = $liste->getTaches()[$index] ?? null;

        // Si il y en a une, on ajoute la sous-tâche
        if ($tacheParent) {
            $tacheParent->ajouterSousTache(new Tache($titre, $categorie));
        }
    } elseif ($action === 'terminer_sous_tache' && $index >= 0 && isset($liste->getTaches()[$index])) {
        // 10. Terminer une sous-tâche 

        // Récupèrer l'index de la sous-tâche
        $sousIndex = isset($_POST['sousIndex']) ? (int)$_POST['sousIndex'] : -1;
        // Récupère la tâche parente
        $tacheParent = $liste->getTaches()[$index];

        // Vérifie que la sous-tâche existe et la marque comme terminée
        if ($sousIndex >= 0 && isset($tacheParent->getSousTaches()[$sousIndex])) {
            $tacheParent->getSousTaches()[$sousIndex]->marquerCommeTerminee();
        }
    } elseif ($action === 'supprimer_sous_tache') {
        // 11. Supprimer une sous-tâche 

        // Récupèrer l'index de la sous-tâche
        $sousIndex = isset($_POST['sousIndex']) ? (int)$_POST['sousIndex'] : -1;
        // Récupèrer la tâche parente
        $tacheParent = $liste->getTaches()[$index];

        // Si l'index est valide, supprimer la sous-tâche
        if ($sousIndex >= 0) {
            $tacheParent->supprimerSousTache($sousIndex);
        }
    } elseif ($action === 'prioriser_sous_tache' && isset($_POST['index'], $_POST['sousIndex'])) {
        // 12. Prioriser la sous-tâche 

        // Récupèrer l'index de la tâche et de la sous-tâche
        $index = (int)$_POST['index'];       
        $sousIndex = (int)$_POST['sousIndex']; 

        // Vérifier que la tâche existe
        if (isset($liste->getTaches()[$index])) {
            $tacheParent = $liste->getTaches()[$index];

            // Vérifier que la sous-tâche existe
            if (isset($tacheParent->getSousTaches()[$sousIndex])) {
                // Récupèrer la sous-tâche
                $sousTache = $tacheParent->getSousTaches()[$sousIndex];
                // Inverser son état de priorité
                $sousTache->setPrioritaire(!$sousTache->estPrioritaire());
            }
        }
    } elseif ($action === 'urgentiser_sous_tache' && $index >= 0 && isset($liste->getTaches()[$index])) {
        // 13. Urgentiser une sous-tâche 

        $sousIndex = isset($_POST['sousIndex']) ? (int)$_POST['sousIndex'] : -1;
        $tacheParent = $liste->getTaches()[$index];

        // Si la sous-tâche existe
        if ($sousIndex >= 0 && isset($tacheParent->getSousTaches()[$sousIndex])) {
            $sousTache = $tacheParent->getSousTaches()[$sousIndex];
            // Détermine le nouvel état urgent
            $etat = !$sousTache->estUrgente();
            // Appliquer ce nouvel état
            $sousTache->setUrgente($etat);

            // Si elle devient urgente
            if ($etat){
                // Vérifier si la propriété "ancienneCategorie" n’est pas déjà définie
                if (!isset($sousTache->ancienneCategorie)) {
                    // Sauvegarder la catégorie actuelle de la sous-tâche avant de la modifier
                    $sousTache->ancienneCategorie = $sousTache->getCategorie();
                }

                // Changer la catégorie de la sous-tâche en "Urgent"
                $sousTache->setCategorie('Urgent');
            } else {
                // Si la sous-tâche n’est plus urgente, restaurer sa catégorie d’origine
                $sousTache->setCategorie($sousTache->ancienneCategorie ?? 'Aucune');
                // Supprimer la propriété temporaire "ancienneCategorie"
                unset($sousTache->ancienneCategorie);
            }
        }
    } elseif ($action === 'modifier_sous_tache' && $index >= 0 && isset($_POST['sousIndex'])) {
        // 14. Modifier une sous-tâche 

        $sousIndex = (int)$_POST['sousIndex'];
        $tacheParent = $liste->getTaches()[$index] ?? null;

        // Si la sous-tâche existe, on met à jour ses infos
        if ($tacheParent && isset($tacheParent->getSousTaches()[$sousIndex])) {
            $sousTache = $tacheParent->getSousTaches()[$sousIndex];
            $sousTache->setTitre(htmlspecialchars($_POST['titre'] ?? ''));
            $sousTache->setCategorie($_POST['categorie'] ?? 'Aucune');
            $sousTache->setDateEcheance(!empty($_POST['dateEcheance']) ? new DateTime($_POST['dateEcheance']) : null);
        }
    } elseif (isset($_POST['terminerSousTaches'])) {
        // 14. Marque les sous-tâches comme "terminées"

        // Récupèrer l'index de la tâche
        $index = (int)$_POST['index']; 

        // Vérifier que la tâche existe
        if (isset($liste->getTaches()[$index])) {
            $tacheParent = $liste->getTaches()[$index];
            
            // On parcours toutes les sous-tâches de la tâche parent et on les marque comme "terminées"
            foreach ($tacheParent->getSousTaches() as $sousTache) {
                $sousTache->marquerCommeTerminee();
            }
        }
    } elseif ($action === 'archiverSousTache' || ($action === 'desarchiverSousTache')&& isset($_POST['index'], $_POST['sousIndex'])) {
        // 15. Archiver une sous-tache 

        $index = (int)$_POST['index'];       
        $sousIndex = (int)$_POST['sousIndex']; 

        // Si latâche existe
        if (isset($liste->getTaches()[$index])) {
            $tacheParent = $liste->getTaches()[$index];

            // On récupère la sous-tâche de la tâche parent pour l'archiver
            if (isset($tacheParent->getSousTaches()[$sousIndex])) {
                $sousTache = $tacheParent->getSousTaches()[$sousIndex];
                $sousTache->setArchivee(!$sousTache->estArchivee());
            }
        }
    } 

    /// Sauvegarde toutes les tâches dans le fichier JSON avec mise en forme
    file_put_contents($dataFile, json_encode($liste->toArray(), JSON_PRETTY_PRINT));
    // Redirige vers la même page pour éviter le resoumission du formulaire
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Filtrage par recherche et état
$recherche = $_GET['recherche'] ?? '';
$filtre = $_GET['filtre'] ?? 'toutes';
$tachesAffichees = $liste->getTaches();

if ($filtre === 'terminees') {
    $tachesAffichees = array_filter($tachesAffichees, fn($t) => $t->estTerminee());
} elseif ($filtre === 'non-terminees') {
    $tachesAffichees = array_filter($tachesAffichees, fn($t) => !$t->estTerminee());
}

if ($recherche !== '') {
    $tachesAffichees = array_filter($tachesAffichees, fn($t) => stripos($t->getTitre(), $recherche) !== false);
}

// Détection alertes 48h
$alertTaches = [];
$now = new DateTime();
foreach ($liste->getTaches() as $tache) {
    if ($tache->getDateEcheance()) {
        $diff = $now->diff($tache->getDateEcheance());
        $hours = ($diff->days * 24) + $diff->h + ($diff->i / 60);
        if ($hours >= 0 && $hours <= 48 && !$tache->estTerminee()) {
            $alertTaches[] = $tache;
        }
    }
}
?>

<!DOCTYPE html>
    <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>📝 Gestionnaire de Tâches</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
            <link href="css/style.css" rel="stylesheet">
        </head>

        <body>
            <div class="container mt-5">
                <div class="title"><h1 class="text-info">📝 Task Manager</h1></div>           

                <form method="GET" class="mt-4 mb-3 recherche">
                    <div class="input-group">
                        <input type="text" name="recherche" class="form-control" placeholder="Rechercher une tâche" value="<?= htmlspecialchars($_GET['recherche'] ?? '') ?>">
                        <button type="submit" class="btn btn-primary ms-2"><i class="bi bi-search mt-2"></i></button>
                    </div>
                </form>
            
                <form method="post" class="mb-3 d-flex align-items-center gap-2 mt-5 mb-3">
                    <input type="hidden" name="action" value="ajouter">

                    <input type="text" name="titre" class="form-control" placeholder="Nouvelle tâche" required style="flex:2; min-width:150px;">
                    <input type="date" name="dateEcheance" class="form-control" style="flex:1; max-width:140px;">

                    <select name="categorie" class="form-select" style="flex:1; max-width:130px;">
                        <?php foreach ($categoriesDisponibles as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus"></i> Ajouter</button>
                </form>

               <div class="d-flex justify-content-between align-items-center mb-5 mt-4">
                    <!-- Filtres à gauche -->
                    <form method="GET" class="d-flex gap-2">
                        <button type="submit" name="filtre" value="toutes" class="btn btn-outline-primary <?= ($_GET['filtre'] ?? 'toutes') === 'toutes' ? 'active' : '' ?>">Toutes</button>
                        <button type="submit" name="filtre" value="terminees" class="btn btn-outline-success <?= ($_GET['filtre'] ?? '') === 'terminees' ? 'active' : '' ?>">Terminées</button>
                        <button type="submit" name="filtre" value="non-terminees" class="btn btn-outline-secondary <?= ($_GET['filtre'] ?? '') === 'non-terminees' ? 'active' : '' ?>">Non terminées</button>
                    </form>

                    <!-- Boutons priorisation à droite -->
                    <div class="d-flex gap-2">
                        <form method="post">
                            <input type="hidden" name="action" value="prioriser_toutes">
                            <button type="submit" class="btn btn-primary">Prioriser toutes</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="action" value="deprioriser_toutes">
                            <button type="submit" class="btn btn-secondary">Déprioriser toutes</button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($alertTaches)): ?>
                    <div id="alert-taches" class="alert alert-info alert-dismissible fade show mt-3 text-center mb-4" role="alert">
                        <?php if (count($alertTaches) === 1): ?> 
                            <strong>Il y a <?= count($alertTaches) ?> tâche proche de l'échéance :</strong>
                            <p>
                                <?= htmlspecialchars($alertTaches[0]->getTitre()) ?> 
                                (Échéance : <?= $alertTaches[0]->getDateEcheance()->format('d/m/Y') ?>)
                            </p>
                        <?php else: ?>                            
                            <strong>Il y a (<?= count($alertTaches) ?>) tâches proches de l'échéance :</strong>
                            <ul class="mb-0">
                                <?php foreach ($alertTaches as $tache): ?>
                                    <li><?= htmlspecialchars($tache->getTitre()) ?> (Échéance : <?= $tache->getDateEcheance()->format('d/m/Y') ?>)</li>
                                <?php endforeach; ?>
                            </ul>                           
                        <?php endif; ?>

                        <button type="button" class="btn-close" aria-label="Close" id="close-alert-taches"></button>
                    </div>

                    <script>
                        // Vérifier si l'alerte a déjà été fermée
                        if (localStorage.getItem('alertTachesClosed') === 'true') {
                            document.getElementById('alert-taches').style.display = 'none';
                        }

                        // Au clic sur la croix, cacher et mémoriser
                        document.getElementById('close-alert-taches').addEventListener('click', function() {
                            document.getElementById('alert-taches').style.display = 'none';
                            localStorage.setItem('alertTachesClosed', 'true');
                        });
                    </script>
                <?php endif; ?>    
                
                <?php $numeroTache = 0; ?>

                <ul class="list-group mt-3 mb-5">                                         
                    <?php foreach ($tachesAffichees as $index => $tache): ?>
                        <?php
                        $numeroTache++;
                        $categorie = $tache->getCategorie() ?: 'Aucune';
                        $couleurBadge = $couleursCategories[$categorie] ?? 'light';
                        $prioritaire = $tache->estPrioritaire() ? 'bg-warning' : '';
                        $categorieUrgent = $categorie == "Urgent" ? 'bg-danger' : '';
                        $urgente = $tache->estUrgente() ? 'bg-danger' : '';
                        $delai = $tache->estUrgente() ? 'text-white' : '';
                        $terminee = $tache->estTerminee() ? 'text-decoration-line-through text-success' : '';   
                        $desurgentiser =  'background-color: #4AA3A2; color: #fff;';                         
                        
                        if ($tache->estUrgente() || $categorie == "Urgent") {
                            $btClassSupprimer = 'btn';
                            $btStyleSupprimer = 'background-color: #7AA95C; color: #fff;'; 

                            $btClassUrgentiser = 'btn';
                            $btStyleUrgentiser = 'background-color: #955149; color: #fff;';                             
                        } else {
                            $btClassSupprimer = 'btn btn-danger';
                            $btStyleSupprimer = '';

                            $btClassUrgentiser = 'btn';
                            $btStyleUrgentiser = 'background-color: #A7001E; color: #fff;'; 
                        }

                        if($categorie == "Urgent")
                        {
                            $couleurBadge = 'primary';
                            $delai = "text-white";                            
                        }
                        ?>

                        <div class="alert alert-primary mt-3" role="alert">
                        Tâche n° : <?= $numeroTache; ?>
                        </div>

                        <li class="list-group-item d-flex justify-content-between align-items-start <?= $prioritaire; ?> <?= $urgente; ?> <?= $categorieUrgent; ?>">                       
                            <div>
                                <?php 
                                $delaiTexte = $tache->delaiRestant(); 
                                $classeDelaiTitre = ($delaiTexte === 'Dépassé') ? 'text-danger' : 'text-success';
                                ?>                              

                                <p><?= $tache->estTerminee() ? '✅' : '❌' ?> <pan class="<?= $delai; ?> <?= $terminee; ?> <?= $classeDelaiTitre; ?>"><?= htmlspecialchars($tache->getTitre()) ?></span></p>

                                <?php if ($tache->getDateEcheance()): ?>
                                    <?php 
                                    $classeDelai = ($delaiTexte === 'Dépassé') ? 'bg-danger' : 'bg-primary';
                                    ?>

                                    <p class="badge <?= $classeDelai ?> text-wrap fs-6 fst-italic">
                                        <span><?= $delaiTexte ?></span>
                                    </p>

                                    <p class="text-muted"><span class="fw-bold  <?= $delai; ?>">Date d'échéance :</span> <span class="<?= $delai; ?>"><?= $tache->getDateEcheance()->format('d-m-Y') ?></span></p>
                                <?php endif; ?>

                                <p class="mt-1">
                                    <span class="badge bg-<?= $couleurBadge ?> text-wrap fs-6 fw-bold"><?= htmlspecialchars($categorie) ?></span>
                                </p>

                               <form method="POST" class="mt-3 d-flex gap-2 align-items-center flex-nowrap">
                                    <input type="hidden" name="action" value="ajouterSousTache">
                                    <input type="hidden" name="index" value="<?= $index ?>">

                                    <input type="text" name="titreSousTache" class="form-control form-control-sm" 
                                        placeholder="Nouvelle sous-tâche..." required style="max-width: 200px;">

                                    <select name="categorieSousTache" class="form-select form-select-sm" style="width: 140px;">
                                        <option value="">Catégorie</option>
                                        <option value="personnel">Personnel</option>
                                        <option value="travail">Travail</option>
                                        <option value="projets">Projets</option>
                                    </select>

                                    <button type="submit" name="ajouterSousTache" class="btn btn-sm btn-outline-primary">➕</button>
                                </form>                    
                            </div>

                            <div class="d-flex gap-1">
                                <?php if (!$tache->estTerminee()): ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="terminer">
                                        <input type="hidden" name="index" value="<?= $index ?>">
                                        <button type="submit" class="btn btn-success btn-sm" title="Terminer"><i class="bi bi-check"></i></button>
                                    </form>
                                <?php endif; ?>

                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="index" value="<?= $index ?>">
                                    <button class="<?= $btClassSupprimer ?> btn-sm" style="<?= $btStyleSupprimer ?>" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>

                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="prioriser">
                                    <input type="hidden" name="index" value="<?= $index ?>">

                                    <?php if(!($tache->estPrioritaire())): ?>
                                        <button type="submit" class="btn btn-primary btn-sm text-white" title="Prioriser">
                                            <i class="bi bi-star text-white"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-secondary btn-sm text-white" title="Déprioriser">
                                            <i class="bi bi-star text-white"></i> 
                                        </button>
                                    <?php endif; ?>
                                </form>

                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="urgentiser">
                                    <input type="hidden" name="index" value="<?= $index ?>">                                  

                                    <?php if ($tache->estUrgente()): ?>
                                        <button type="submit" name="action" value="urgentiser" class="btn btn-sm" style="<?= $desurgentiser ?>" title="Désurgentiser">
                                            <i class="bi bi-bell-slash-fill"></i> 
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="urgentiser" class="<?= $btClassUrgentiser ?> btn-sm" style="<?= $btStyleUrgentiser ?>" title="Urgentiser">
                                            <i class="bi bi-bell-fill"></i> 
                                        </button>
                                    <?php endif; ?>
                                </form>                      

                                <form method="post" style="display:inline-flex; gap:2px; align-items:center;">
                                    <input type="hidden" name="action" value="modifier">
                                    <input type="hidden" name="index" value="<?= $index ?>">
                                    <input type="text" name="titre" value="<?= htmlspecialchars($tache->getTitre()) ?>" class="form-control form-control-sm" style="min-width:120px;">
                                    <input type="date" name="dateEcheance" value="<?= $tache->getDateEcheance() ? $tache->getDateEcheance()->format('Y-m-d') : '' ?>" class="form-control form-control-sm" style="min-width:130px;">
                                    <select name="categorie" class="form-select form-select-sm" style="min-width:100px;">
                                        <?php foreach ($categoriesDisponibles as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>" <?= $tache->getCategorie() === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-info btn-sm"><i class="bi bi-pencil text-white"></i></button>
                                </form>
                            </div>
                        </li>

                        <?php if($tache->getSousTaches()): ?>
                            <div class="alert alert-info mt-3" role="alert">
                                Sous tâche(s) de la tache n° <?= $numeroTache; ?>
                            </div>
                                    
                            <ul class="list-group mt-2">
                                <?php foreach ($tache->getSousTaches() as $sousIndex => $sousTache): ?>      
                                    <?php
                                    $sousCategorie = $sousTache->getCategorie() ?: 'Aucune';    
                                    $sousCategorieNormalisee = ucfirst(strtolower($sousCategorie));                                
                                    $couleurBadgeSous = $couleursCategories[$sousCategorieNormalisee] ?? 'light';
                                    $termineeSous = $sousTache->estTerminee() ? 'text-decoration-line-through text-success' : '';
                                    $urgentSous = $sousTache->estUrgente() ? 'bg-danger text-white' : '';
                                    $prioritaireSous = $sousTache->estPrioritaire() ? 'bg-warning' : '';
                                    $estArchivee = $sousTache->estArchivee();
                                    $archiveeSous =$sousTache->estArchivee() ? 'bg-dark text-white' : '';
                                    ?>                                         

                                    <li class="list-group-item d-flex justify-content-between align-items-start ps-4 <?= $prioritaireSous ?> <?= $urgentSous ?> <?= $termineeSous ?> <?= $archiveeSous ?>">
                                            <div>
                                            <span class="<?= $termineeSous ?> <?= $urgentSous ?>">
                                                <?= $sousTache->estTerminee() ? '✅' : '❌' ?> <?= htmlspecialchars($sousTache->getTitre()) ?>
                                            </span>

                                            <?php if ($estArchivee): ?>
                                                <div class="mt-1">
                                                    <span class="badge bg-primary"><?= htmlspecialchars($sousCategorieNormalisee) ?></span>
                                                </div>
                                            <?php else: ?>
                                                <div class="mt-1">
                                                    <span class="badge bg-<?= $couleurBadgeSous ?>"><?= htmlspecialchars($sousCategorieNormalisee) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex gap-1 ms-5">
                                            <?php if(!$sousTache->estArchivee()): ?>    
                                                <?php if (!$sousTache->estTerminee()): ?>
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="terminer_sous_tache">
                                                        <input type="hidden" name="index" value="<?= $index ?>">
                                                        <input type="hidden" name="sousIndex" value="<?= $sousIndex ?>">

                                                        <button type="submit" class="btn btn-success btn-sm" title="Terminer"><i class="bi bi-check"></i></button>
                                                    </form>
                                                <?php endif; ?>   
                                            <?php endif; ?>                                               

                                            <form method="post">
                                                <input type="hidden" name="action" value="supprimer_sous_tache">
                                                <input type="hidden" name="index" value="<?= $index ?>">
                                                <input type="hidden" name="sousIndex" value="<?= $sousIndex ?>">

                                                <button class="<?= $btClassSupprimer ?> btn-sm" style="<?= $btStyleSupprimer ?>" title="Supprimer"><i class="bi bi-trash"></i></button>
                                            </form>        
                                            
                                            <?php if(!$sousTache->estArchivee()): ?>     
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="prioriser_sous_tache">
                                                    <input type="hidden" name="index" value="<?= $index ?>">
                                                    <input type="hidden" name="sousIndex" value="<?= $sousIndex ?>">

                                                    <?php if(!($sousTache->estPrioritaire())): ?>
                                                        <button type="submit" class="btn btn-primary btn-sm text-white" title="Prioriser">
                                                            <i class="bi bi-star text-white"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-secondary btn-sm text-white" title="Déprioriser">
                                                            <i class="bi bi-star text-white"></i> 
                                                        </button>
                                                    <?php endif; ?>
                                                </form>  

                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="urgentiser_sous_tache">
                                                    <input type="hidden" name="index" value="<?= $index ?>">
                                                    <input type="hidden" name="sousIndex" value="<?= $sousIndex ?>">

                                                    <?php if ($sousTache->estUrgente()): ?>
                                                        <button type="submit" class="btn btn-sm" style="<?= $desurgentiser ?>" title="Désurgentiser">
                                                            <i class="bi bi-bell-slash-fill"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="<?= $btClassUrgentiser ?> btn-sm" style="<?= $btStyleUrgentiser ?>" title="Urgentiser">
                                                            <i class="bi bi-bell-fill"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </form>  
                                            <?php endif; ?> 
                                            
                                            <?php if (!$estArchivee): ?>
                                                <form method="post" class="mb-0" style="display: inline;">
                                                    <input type="hidden" name="action" value="archiverSousTache">
                                                    <input type="hidden" name="index" value="<?= $index ?>">
                                                    <input type="hidden" name="sousIndex" value="<?= $sousIndex ?>">
                                                    
                                                    <button type="submit" name="archiverSousTache" class="btn btn-sm btn-outline-secondary" title="Archiver">
                                                        🗄️ 
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" class="mb-0" style="display: inline;">
                                                    <input type="hidden" name="action" value="desarchiverSousTache">
                                                    <input type="hidden" name="index" value="<?= $index ?>">
                                                    <input type="hidden" name="sousIndex" value="<?= $sousIndex ?>">

                                                    <button type="submit" name="desarchiverSousTache" class="btn btn-sm btn-outline-primary" title="Désarchiver">
                                                        ↩️ 
                                                    </button>
                                                </form>
                                            <?php endif; ?>      
                                            
                                            <?php if(!$sousTache->estArchivee()): ?>   
                                                <form method="post" style="display:inline-flex; gap:2px; align-items:center;">
                                                    <input type="hidden" name="action" value="modifier_sous_tache">
                                                    <input type="hidden" name="index" value="<?= $index ?>">
                                                    <input type="hidden" name="sousIndex" value="<?= $sousIndex ?>">


                                                    <input type="text" name="titre" value="<?= htmlspecialchars($sousTache->getTitre()) ?>" class="form-control form-control-sm" style="min-width:120px;">
                                                    <input type="date" name="dateEcheance" value="<?= $sousTache->getDateEcheance() ? $tache->getDateEcheance()->format('Y-m-d') : '' ?>" class="form-control form-control-sm" style="min-width:130px;">
                                                
                                                    <select name="categorie" class="form-select form-select-sm" style="min-width:100px;">
                                                        <?php foreach ($categoriesDisponibles as $cat): ?>
                                                            <option value="<?= htmlspecialchars($cat) ?>" <?= $sousTache->getCategorie() === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-info btn-sm"><i class="bi bi-pencil text-white"></i></button>
                                                </form>
                                            <?php endif; ?>   
                                        </div>                                               
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <form method="post" class="mt-3">
                                <input type="hidden" name="action" value="terminerSousTaches">
                                <input type="hidden" name="index" value="<?= $index ?>">

                                <button type="submit" name="terminerSousTaches" class="btn btn-sm btn-outline-success">
                                    ✅ Terminer toutes les sous-tâches
                                </button>
                            </form>
                       <?php endif; ?>   
                    <?php endforeach; ?>
                </ul>
            </div>
        </body>
    </html>
