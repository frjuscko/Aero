<?php

namespace Desinova\Aero;

abstract class Model
{
    protected array $attributes = [];

    // Stocke les instances de relations pré-chargées
    protected array $relatedModels = [];
    protected ?string $table = null;
    protected array $fillable = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    // Permet à l'Eager Loading d'injecter directement une relation chargée
    public function setRelation(string $relationName, $value)
    {
        $this->relatedModels[$relationName] = $value;
    }

    // deviner le nom de la table à partir du Model
    public function getTable(): string
    {
        if ($this->table) return $this->table;

        $className = (new \ReflectionClass($this))->getShortName();
        return strtolower($className) . 's';
    }

    // Récuprére la valeur de la clé primaire
    public function getKey()
    {
        return $this->attributes['id'] ?? null;
    }

    /**
     * Getter magique pour accéder aux colonnes ou aux relations comme des propriétés
     * Exemple: $user->posts au lieu de $user->posts()
     */

    public function __get($key)
    {
        // Si c'est une colonne de la BDD, on la renvoie
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        // Si la relation est déjà chargée en mémoire, on la renvoie ! ---
        if (array_key_exists($key, $this->relatedModels)) {
            return $this->relatedModels[$key];
        }

        // Si une méthode du même nom existe (une relation), on l'exécute
        if (method_exists($this, $key)) {
            $relation = $this->$key();
            if ($relation instanceof Relation) {
                return $relation->getResults(); // On résout la relation à la volée !
            }
        }

        return null;
    }

    // Point d'entrée pour démarrer une requête propre
    public static function query(): QueryBuilder
    {
        // static::class contient le nom de la classe enfant (ex: "App\Models\User")
        return new QueryBuilder(static::class);
    }

    // Le raccourci all()
    public static function all(): array
    {
        return self::query()->get();
    }

    /**
     * La magie de Laravel : intercepter les appels statiques comme User::where()
     * et les rediriger vers le QueryBuilder
     */
    public static function __callStatic($method, $parameters)
    {
        // On crée le QueryBuilder et on lui passe la méthode demandée
        return call_user_func_array([self::query(), $method], $parameters);
    }

    public function hasMany(string $relatedClass, ?string $foreignKey = null, ?string $localKey = 'id'): HasMany
    {
        // Si la clé étrangère n'est pas définie, on la devine : "user_id"
        $foreignKey = $foreignKey ?: strtolower((new \ReflectionClass($this))->getShortName()) . '_id';
        return new HasMany($this, $relatedClass, $foreignKey, $localKey);
    }

    public function hasOne(string $relatedClass, ?string $foreignKey = null, ?string $localKey = 'id'): HasOne
    {
        $foreignKey = $foreignKey ?: strtolower((new \ReflectionClass($this))->getShortName()) . '_id';
        return new HasOne($this, $relatedClass, $foreignKey, $localKey);
    }

    public function belongsTo(string $relatedClass, ?string $foreignKey = null, ?string $ownerKey = 'id'): BelongsTo
    {
        $foreignKey = $foreignKey ?: strtolower((new \ReflectionClass($relatedClass))->getShortName()) . '_id';
        return new BelongsTo($this, $relatedClass, $foreignKey, $ownerKey);
    }


    /**
     * Remplir le modèle de façon sécurisée (Mass Assignment)
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            // Si la colonne est dans la liste blanche $fillable, on l'accepte
            if (in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Créer et enregistrer instantanément un modèle sécurisé en BDD
     */
    public static function create(array $attributes): self
    {
        // 1. On crée une instance vide du modèle enfant
        $instance = new static();
        
        // 2. On la remplit UNIQUEMENT avec les champs autorisés
        $instance->fill($attributes);
        
        // 3. On la sauvegarde dans la base de données
        $instance->save();

        return $instance;
    }

    /**
     * Sauvegarder le modèle actuel (gère l'INSERT ou l'UPDATE)
     */
    public function save(): bool
    {
        $pdo = Application::getInstance()->db->pdo();
        $table = $this->getTable();
        $id = $this->getKey();

        // Si le modèle a déjà un ID, c'est une mise à jour (UPDATE)
        if ($id) {
            $fields = [];
            $bindings = [];
            foreach ($this->attributes as $key => $value) {
                if ($key === 'id') continue;
                $fields[] = "$key = ?";
                $bindings[] = $value;
            }
            $bindings[] = $id; // Pour le WHERE id = ?

            $sql = "UPDATE $table SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($bindings);
        } 
        
        // Sinon, c'est une nouvelle insertion (INSERT)
        else {
            $columns = array_keys($this->attributes);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute(array_values($this->attributes));

            if ($result) {
                // On récupère l'ID généré par MySQL pour le mettre à jour dans l'objet
                $this->attributes['id'] = $pdo->lastInsertId();
            }

            return $result;
        }
    }

    /**
     * Setter magique pour modifier une valeur à la volée
     * Exemple: $user->email = 'test@test.com';
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Supprimer l'instance actuelle de la base de données
     */
    public function delete(): bool
    {
        $id = $this->getKey();

        // On ne peut supprimer que si le modèle existe déjà en BDD (s'il a un ID)
        if ($id) {
            // On délègue proprement au QueryBuilder en le ciblant par son ID
            $deleted = static::query()->where('id', $id)->delete();

            if ($deleted) {
                // On vide les attributs de l'objet en mémoire PHP
                $this->attributes = [];
                return true;
            }
        }

        return false;
    }

}