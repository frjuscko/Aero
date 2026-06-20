<?php

namespace Desinova\Aero;

abstract class Relation
{
    protected Model $parent;
    protected string $relatedClass;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(Model $parent, string $relatedClass, string $foreignKey, string $localKey)
    {
        $this->parent = $parent;
        $this->relatedClass = $relatedClass;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    /**
     * Magie : Si on appelle une méthode du QueryBuilder sur la Relation,
     * on la redirige vers le constructeur de requête de la relation.
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->newQuery(), $method], $parameters);
    }

    // Chaque relation doit implémenter sa propre façon de récupérer le résultat SQL
    abstract public function getResults();

    /**
     * Méthode clé pour l'Eager Loading
     * @param array $models Liste des modèles parents (ex: tous les Users)
     * @param string $relationName Nom de la relation (ex: 'posts')
     */
    abstract public function eagerLoad(array $models, string $relationName): array;

}