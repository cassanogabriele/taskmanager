<?php 
// Déclaration d'une classe pour gérer une liste de tâches 
class ListeDeTaches {
    // Propriétés privées

    // Tableau privé qui stocke toutes les tâches 
    private array $taches = [];
    // Chemin vers le fichier JSON qui stocke les tâches
    private string $fichier = __DIR__ . '/../data/data.json';
    // Tableau privé qui stocke toutes les sous-tâches
    private array $sousTaches = [];

    // Constructeur 
    public function __construct(array $taches = [])
    {
        $this->taches = $taches;
    }

    // Fonctions

    // 1. Ajouter une tâche à la liste des tâches 
    public function ajouterTache(Tache $tache): void 
    {
        // Ajouter la tâche à la fin du tableau 
        $this->taches[] = $tache;
        // Mettre à jour le fichier JSON qui stocke la liste des ta^ches
        $this->sauvegarderData();
    }

    // 2. Supprimer une tâche de la liste des tâches par son index 
    public function supprimerTache(int $ind): void 
    {
        // Vérifier que l'index existe dans le tableau 
        if(isset($this->taches[$ind]))
        {
            // Supprimer la tâche 
            unset($this->taches[$ind]);
            // Réindexer le tableau 
            $this->taches = array_values($this->taches);

            $this->sauvegarderData();
        }        
    }

    // 3. Récupérer toutes les tâches 
    public function getTaches(): array 
    {
        return $this->taches;
    }

    // 4. Convertir toute la liste en tableau associatif pour le JSON
    public function toArray(): array 
    {
        // On appelle la méthode "toArray()" de chaque tâche
        return array_map(fn($tache) => $tache->toArray(), $this->taches);
    }

    // 5. Marquer une tâche comme terminée 
    public function marquerCommeTerminee(int $ind): void 
    {
        if(isset($this->taches[$ind]))        {
            $this->taches[$ind]->marquerCommeTerminee();         
            $this->sauvegarderData();
        }
    }

    // 6. Mettre à jour le fichier "data.json" avec l'état actuel des tâches   
    private function sauvegarderData(): void 
    {
        // Charger tout le fichier JSON existant
        $data = file_exists($this->fichier) ? json_decode(file_get_contents($this->fichier), true) : ['users' => []];

        // Identifier l’utilisateur connecté
        $username = $_SESSION['user'] ?? null;
        if ($username) {
            // Sauvegarder les tâches de CE user uniquement
            $data['users'][$username]['tasks'] = $this->toArray();
        }

        // Réécrire le fichier
        file_put_contents($this->fichier, json_encode($data, JSON_PRETTY_PRINT));
    }



    // 7. Modifier une tâche existante 
    public function modifierTache(string $nouveauTitre, ?DateTime $nouvelleDate = null, ?string $nouvelleCategorie = null): void 
    {
        if (isset($this->taches[$ind])) {
            $this->taches[$ind]->setTitre($nouveauTitre);
            $this->taches[$ind]->setDateEcheance($nouvelleDate);

            if ($nouvelleCategorie !== null) {
                $this->taches[$ind]->setCategorie($nouvelleCategorie);
            }

            $this->sauvegarderData();
        }
    }

    // 8. Ajouter une sous-tâches
    public function ajouterSousTache(int $indexTache, Tache $sousTache): void 
    {
        if(isset($this->taches[$indexTache])) {
            $this->taches[$indexTache]->ajouterSousTache($sousTache);
            $this->sauvegarderData();
        }
    }

    // 9. Supprimer une sous-tâche 
    public function supprimerSousTache(int $indexTache, int $indexSousTache): void
    {
        if(isset($this->taches[$indexTache])) {
            $this->taches[$indexTache]->supprimerSousTache($indexSousTache);
            $this->sauvegarderData();
        }
    }

    // 10. Marquer une sous-tâche comme terminée
    public function terminerSousTache(int $index): void
    {
        if (isset($this->sousTaches[$index])) {
            $this->sousTaches[$index]->marquerCommeTerminee();
        }
    } 

    // 11. Prioriser ou déprioriser une sous-tâche
    public function togglePrioriteSousTache(int $index): void
    {
        if (isset($this->sousTaches[$index])) {
            // On récupère l'état de la tâche actuelle et on inverse son état
            $actuelle = $this->sousTaches[$index]->estPrioritaire();
            $this->sousTaches[$index]->setPrioritaire(!$actuelle);
        }
    }

    // 12. Urgentiser ou désurgentiser une sous-tâche
    public function toggleUrgenceSousTache(int $index): void
    {
        if (isset($this->sousTaches[$index])) {
            $actuelle = $this->sousTaches[$index]->estUrgente();
            $this->sousTaches[$index]->setUrgente(!$actuelle);
        }
    }

    // 13. Modifier les informations d'une sous-tâche
    public function modifierSousTache(int $index, string $nouveauTitre, ?DateTime $nouvelleDate, string $categorie): void
    {
        if (isset($this->sousTaches[$index])) {
            $this->sousTaches[$index]->setTitre($nouveauTitre);
            $this->sousTaches[$index]->setDateEcheance($nouvelleDate);
            $this->sousTaches[$index]->setCategorie($categorie);

            // Si la catégorie est "Urgent", on peut rendre la sous-tâche urgente automatiquement
            if ($categorie === 'Urgent') {
                $this->sousTaches[$index]->setUrgente(true);
            }
        }
    }

    // 14. Mettre à jour une tâche
    public function setTaches(array $taches): void 
    {
        $this->taches = $taches;
    }    
}
