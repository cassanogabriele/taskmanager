<?php 
// Déclaration d'une classe servant à représenter une tâche individuelle dans le gestionnaire de tâches
class Tache {
    // Propriétés privée

    // Stockage du titre de la tâche
    private string $titre;
    // Stockage de la catégorie de la tâche
    private string $categorie;
    // Stockage de la date d'échéance
    private ?DateTime $dateEcheance; 
    // Indique si la tâche est terminée
    private bool $terminee;
    // Indique si la tâche est prioritaire 
    private bool $prioritaire;
    // Indique si la tâche est urgente 
    private bool $urgente = false;
    // Stockage l'ancienne catégorie d'une tâche avec la catégorie "Urgent"
    public ?string $ancienneCategorie = null;
    // Stockage des sous-taches 
    private array $sousTaches = [];
    // Indique la tâche ou sous-tâche est archivée 
    private bool $archivee = false;

    // Constructeur    
    public function __construct(
        string $titre,
        string $categorie = 'Aucune',
        bool $terminee = false,
        ?DateTime $dateEcheance = null,
        bool $prioritaire = false,
        bool $urgente = false,
        array $sousTaches = []
    ) {
       // Assignation du titre passé en paramètre à la propriété concernée
        $this->titre = $titre;  
        // Assignation de la catégorie de tâche 
        $this->categorie = $categorie;
        // Assignation de l'état de la tâche : terminée ou non
        $this->terminee = $terminee;
        // Assignation de la date d'échéance à la propriété concernée
        $this->dateEcheance = $dateEcheance;        
        // Assignation de la prorité de la tâche 
        $this->prioritaire = $prioritaire;
        // Si la catégorie est "Urgent", on marque la tâche comme urgente
        $this->urgente = $urgente || strtolower($categorie) === 'urgent';
       
        // Assignation des sous-tâches
        foreach ($sousTaches as $st) {
            $this->ajouterSousTache(new Tache(
                $st['titre'],
                $st['categorie'] ?? 'Aucune',
                $st['terminee'] ?? false,
                !empty($st['dateEcheance']) ? new DateTime($st['dateEcheance']) : null,
                $st['prioritaire'] ?? false,
                $st['urgente'] ?? false
            ));
        }
    }

    // Getters et setters 

    // Récupérer le titre de la tâche
    public function getTitre(): string
    {
        return $this->titre;
    }

    // Modifier le titre de la tâche 
    public function setTitre(string $nouveauTitre): void 
    {
        $this->titre = $nouveauTitre; 
    }

    // Récupérer la catégorie de la tâche 
    public function getCategorie(): string 
    {
        return $this->categorie;
    }

    // Modifier la catégorie
    public function setCategorie(string $categorie): void 
    {
        $this->categorie = $categorie;
    }

    // Récupérer la date d'échéance
    public function getDateEcheance(): ?DateTime
    {
        return $this->dateEcheance;
    }

    // Modifier la date d'échéance 
    public function setDateEcheance(?DateTime $nouvelleDate): void 
    {
        $this->dateEcheance = $nouvelleDate;
    }

    // Savoir si la tâche est terminée
    public function estTerminee(): bool 
    {
        return $this->terminee; 
    } 
    
    // Marquer la tâche comme terminée
    public function marquerCommeTerminee(): void
    {
        $this->terminee = true;
    }

    // Savoir si un tâche ou sous-tâche est prioritaire
    public function estPrioritaire(): bool 
    {
        return $this->prioritaire;
    }

    // Marquer une tâche comme "prioritaire"
    public function setPrioritaire(bool $valeur)
    {
        $this->prioritaire = $valeur;
    }

    // Calcul du délai restant d'une tâche
    public function delaiRestant(): ?string 
    {
        if(!$this->dateEcheance) return null;
        
        $now = new DateTime();

        if ($now > $this->dateEcheance) return "Dépassé";

        $diff = $now->diff($this->dateEcheance); 
        
        return "Il reste {$diff->d} jour(s) {$diff->h} heure(s) {$diff->i} minute(s)";
    }

    // Savoir si une tâche ou sous-tâche est urgente
    public function estUrgente(): bool 
    {
        return $this->urgente;
    }

    // Marque une tâche ou sous-tâche comme "urgente"
    public function setUrgente(bool $valeur)
    {
        $this->urgente = $valeur;
    }  

    // Ajouter une sous-tache 
    public function ajouterSousTache(Tache $tache): void 
    {
        $this->sousTaches[] = $tache;
    }

    // Récupérer les sous-tâches
    public function getSousTaches(): array
    {
        return $this->sousTaches;
    }

    // Supprimer une sous-tâche 
    public function supprimerSousTache(int $index): void 
    {
        if (isset($this->sousTaches[$index])) {
            array_splice($this->sousTaches, $index, 1);
        }
    }

    // Marquer une sous-tâche comme "prioritaire"
    public function togglePrioriteSousTache(int $index): void 
    {
        if (isset($this->sousTaches[$index])) {
            $this->sousTaches[$index]->setPrioritaire(!$this->sousTaches[$index]->estPrioritaire());
        }
    }
    
    // Savoir si une tâche ou une sous-tâche est "archivée"
    public function estArchivee(): bool
    {
        return $this->archivee;
    }

    // Archiver une tâche ou une sous-tâche
    public function setArchivee(bool $etat): void 
    {
        $this->archivee = $etat;
    }
    
    // Convertir la tâche en tableau associatif pour JSON
    public function toArray(): array 
    {
        return [
            'titre' => $this->titre,
            'categorie' => $this->categorie, 
            'terminee' => $this->terminee,
            'dateEcheance' => $this->dateEcheance?->format('d-m-Y'),
            'prioritaire' => $this->prioritaire,   
            'urgente' => $this->urgente,    
            'ancienneCategorie' => $this->ancienneCategorie ?? null,
            'archivee' => $this->archivee,
            'sousTaches' => array_map(fn($st) => $st->toArray(), $this->sousTaches)
        ];
    }      
}
