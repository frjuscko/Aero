<?php

namespace Desinova\Aero;

class HasMany extends Relation
{
    /**
     * Construit la requête de base pour la relation
     */
    public function newQuery(): QueryBuilder
    {
        // On démarre un QueryBuilder sur le modèle lié (ex: Post)
        // et on applique le filtre : WHERE user_id = {parent_id}
        return ($this->relatedClass)::query()
            ->where($this->foreignKey, $this->parent->getKey());
    }

    public function getResults()
    {
        // On exécute simplement la requête
        return $this->newQuery()->get();
    }

    public function eagerLoad(array $models, string $relationName): array
    {
        $parentIds = array_map(fn($model) => $model->getKey(), $models);
        if (empty($parentIds)) return $models;

        $relatedInstance = new $this->relatedClass();

        // PLUS DE SQL BRUT ! On utilise le QueryBuilder avec un tableau d'IDs
        // Note: On va devoir ajouter une méthode whereIn() dans notre QueryBuilder juste après
        $records = ($this->relatedClass)::query()
            ->whereIn($this->foreignKey, $parentIds)
            ->get();

        $dictionary = [];
        foreach ($records as $childModel) {
            $dictionary[$childModel->{$this->foreignKey}][] = $childModel;
        }

        foreach ($models as $model) {
            $model->setRelation($relationName, $dictionary[$model->getKey()] ?? []);
        }

        return $models;
    }
}