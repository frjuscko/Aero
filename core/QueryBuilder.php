<?php

namespace Desinova\Aero;

class QueryBuilder
{
    protected string $modelClass;
    protected string $table;
    
    // On stocke les morceaux de la requête SQL ici
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $withRelations = [];
    protected ?int $limit = null;
    protected $orderBy = '';
    protected array $columns = ['*']; // Par défaut on prend tout
    protected string $groupBy = '';    // Stocke le groupement

    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
        $this->table = (new $modelClass())->getTable();
    }

    /**
     * Choisir les colonnes à sélectionner
     */
    public function select(...$columns): self
    {
        $this->columns = is_array($columns[0]) ? $columns[0] : $columns;
        return $this;
    }

    /**
     * Ajouter une sélection brute (ex: SUM, DATE, etc.)
     */
    public function selectRaw(string $expression): self
    {
        // Si c'est le '*' par défaut, on le vide pour mettre notre expression brute
        if ($this->columns === ['*']) {
            $this->columns = [];
        }
        $this->columns[] = $expression;
        return $this;
    }

    /**
     * Regrouper les résultats par une ou plusieurs colonnes
     */
    public function groupBy(string $expression): self
    {
        $this->groupBy = " GROUP BY {$expression}";
        return $this;
    }

    /**
     * Amélioration de ta méthode whereRaw existante pour accepter des variables sécurisées (bindings)
     */
    public function whereRaw(string $sqlCondition, array $bindings = []): self
    {
        $this->wheres[] = $sqlCondition;
        if (!empty($bindings)) {
            $this->bindings = array_merge($this->bindings, $bindings);
        }
        return $this;
    }                               

    /**
     * Ajouter un filtre WHERE
     */
    public function where(string $column, $operator, $value = null): self
    {
        // Si on ne passe que 2 paramètres (ex: where('status', 'active'))
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = "$column $operator ?";
        $this->bindings[] = $value;

        return $this; // Permet le chaînage
    }

    /**
     * Ajouter un filtre WHERE IN (ex: id IN (1, 2, 3))
     */
    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) return $this;

        // Crée autant de "?" que de valeurs (ex: "?, ?, ?")
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        
        $this->wheres[] = "$column IN ($placeholders)";
        
        // On fusionne les nouvelles valeurs dans nos bindings globaux
        $this->bindings = array_merge($this->bindings, array_values($values));

        return $this;
    }

    /**
     * Filtre conditionnel
     */
    public function when($condition, callable $callback): self
    {
        if ($condition) {
            $callback($this, $condition);
        }
        return $this;
    }

    /**
     * Définir les relations à charger d'un coup (Eager Loading)
     */
    public function with($relations): self
    {
        $this->withRelations = is_array($relations) ? $relations : [$relations];
        return $this;
    }

    /**
     * Définir la limite maximale d'enregistrements à récupérer
     */
    public function limit(int $value): self
    {
        $this->limit = $value;
        return $this;
    }

    /**
     * Récupérer le premier résultat
     */
    public function first()
    {
        $this->limit = 1;
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Trouver par ID
     */
    public function find($id)
    {
        return $this->where('id', $id)->first();
    }

    /**
     * Ordonner les résultats
     */
    public function orderBy($column, $direction = 'ASC'): self
    {
        $this->orderBy = " ORDER BY {$column} " . strtoupper($direction);
        return $this; // Permet de continuer à chaîner les méthodes
    }

    /**
     * Compter le nombre total d'enregistrements correspondant aux filtres applicables
     */
    public function count(): int
    {
        $pdo = Application::getInstance()->db->pdo();

        // Si un GROUP BY est défini, un simple COUNT(*) renverrait le décompte du premier groupe.
        // On utilise donc une sous-requête pour compter fidèlement le nombre total de lignes regroupées.
        if (!empty($this->groupBy)) {
            $columnsSelector = implode(', ', $this->columns);
            $subSql = "SELECT {$columnsSelector} FROM {$this->table}";

            if (!empty($this->wheres)) {
                $subSql .= " WHERE " . implode(' AND ', $this->wheres);
            }
            $subSql .= $this->groupBy;

            $sql = "SELECT COUNT(*) as total FROM ($subSql) as sub_query";
        } else {
            // Requête classique optimisée sans sous-requête
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";

            if (!empty($this->wheres)) {
                $sql .= " WHERE " . implode(' AND ', $this->wheres);
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($this->bindings);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int)($result['total'] ?? 0);
    }

    /**
     * Assembler et exécuter la requête pour récupérer la liste
     */
    public function get(): array
    {
        $pdo = Application::getInstance()->db->pdo();

        // 1. DYNAMISATION DU SELECT : On implode les colonnes choisies ou brutes
        $columnsSelector = implode(', ', $this->columns);
        $sql = "SELECT {$columnsSelector} FROM {$this->table}";

        // 2. On ajoute les conditions WHERE s'il y en a
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        // 3. NOUVEAU : On ajoute le GROUP BY juste après le WHERE s'il est défini
        if (!empty($this->groupBy)) {
            $sql .= $this->groupBy;
        }

        // 4. On ajoute l'ordre (Déjà présent dans ton code)
        if (!empty($this->orderBy)) {
            $sql .= $this->orderBy;
        }

        // 5. On ajoute la limite
        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }

        // 6. On exécute la requête
        $stmt = $pdo->prepare($sql);
        $stmt->execute($this->bindings);
        
        $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Si on a demandé des colonnes spécifiques qui ne sont pas des objets purs, 
        // ou si on veut juste manipuler un tableau de résultats bruts (comme des stats)
        if ($this->columns !== ['*'] && !isset($records[0]['id'])) {
            // On retourne le tableau associatif brut (comme ça, pas de bugs avec les alias magiques)
            return $records; 
        }

        // Sinon, comportement classique : conversion en objets Modèles
        $models = [];
        foreach ($records as $record) {
            $models[] = new $this->modelClass($record);
        }

        return $models;
    }

    /**
     * Filtrer les enregistrements en fonction de l'existence d'une relation
     */
    public function whereHas(string $relationName, ?callable $callback = null): self
    {
        // 1. On crée une instance vierge du modèle actuel pour inspecter sa relation
        $modelInstance = new $this->modelClass();
        
        if (!method_exists($modelInstance, $relationName)) {
            die("La relation [$relationName] n'existe pas sur le modèle " . $this->modelClass);
        }

        // 2. On extrait l'objet relation (ex: HasMany, HasOne)
        $relation = $modelInstance->$relationName();
        
        // 3. On crée un QueryBuilder pour le modèle lié (ex: Post)
        $relatedModelClass = (new \ReflectionClass($relation))->getProperty('relatedClass')->getValue($relation);
        $subQueryBuilder = new self($relatedModelClass);

        // 4. Si le dev a passé une fonction anonyme pour filtrer, on l'exécute sur la sous-requête
        if ($callback) {
            $callback($subQueryBuilder);
        }

        // 5. On extrait les infos de clés de la relation pour faire la liaison SQL (Constraint)
        $foreignKey = (new \ReflectionClass($relation))->getProperty('foreignKey')->getValue($relation);
        $localKey = (new \ReflectionClass($relation))->getProperty('localKey')->getValue($relation);
        
        // On lie la sous-requête au modèle parent : ex: posts.user_id = users.id
        $subQueryBuilder->whereRaw("{$subQueryBuilder->table}.$foreignKey = {$this->table}.$localKey");

        // 6. On compile la sous-requête
        $subSql = "SELECT * FROM {$subQueryBuilder->table}";
        if (!empty($subQueryBuilder->wheres)) {
            $subSql .= " WHERE " . implode(' AND ', $subQueryBuilder->wheres);
        }

        // 7. On injecte le EXISTS final dans notre QueryBuilder principal
        $this->wheres[] = "EXISTS ($subSql)";
        
        // On fusionne les paramètres (bindings) de la sous-requête dans la requête principale
        $this->bindings = array_merge($this->bindings, $subQueryBuilder->bindings);

        return $this;
    }

    /**
     * Mettre à jour plusieurs enregistrements d'un coup en fonction des filtres appliqués
     */
    public function update(array $values): bool
    {
        $pdo = Application::getInstance()->db->pdo();
        
        $fields = [];
        $updateBindings = [];
        
        // 1. On prépare la syntaxe SET colonne = ?
        foreach ($values as $column => $value) {
            $fields[] = "$column = ?";
            $updateBindings[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields);

        // 2. On injecte les contraintes WHERE s'il y en a
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        // 3. Attention à l'ordre des bindings : d'abord les valeurs du SET, ensuite celles du WHERE !
        $finalBindings = array_merge($updateBindings, $this->bindings);

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($finalBindings);
    }

    /**
     * Supprimer des enregistrements en fonction des filtres appliqués
     */
    public function delete(): bool
    {
        $pdo = Application::getInstance()->db->pdo();

        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($this->bindings);
    }
}