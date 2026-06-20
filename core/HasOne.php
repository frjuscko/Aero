<?php

namespace Desinova\Aero;

class HasOne extends Relation
{
    public function newQuery(): QueryBuilder
    {
        return ($this->relatedClass)::query()
            ->where($this->foreignKey, $this->parent->getKey());
    }

    public function getResults()
    {
        return $this->newQuery()->first();
    }

    public function eagerLoad(array $models, string $relationName): array
    {
        $parentIds = array_map(fn($model) => $model->getKey(), $models);
        if (empty($parentIds)) return $models;

        $records = ($this->relatedClass)::query()
            ->whereIn($this->foreignKey, $parentIds)
            ->get();

        $dictionary = [];
        foreach ($records as $childModel) {
            $dictionary[$childModel->{$this->foreignKey}] = $childModel;
        }

        foreach ($models as $model) {
            $model->setRelation($relationName, $dictionary[$model->getKey()] ?? null);
        }

        return $models;
    }
}