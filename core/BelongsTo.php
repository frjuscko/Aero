<?php

namespace Desinova\Aero;

class BelongsTo extends Relation
{
    public function newQuery(): QueryBuilder
    {
        $foreignKeyValue = $this->parent->{$this->foreignKey};
        
        return ($this->relatedClass)::query()
            ->where($this->localKey, $foreignKeyValue);
    }

    public function getResults()
    {
        return $this->newQuery()->first();
    }

    public function eagerLoad(array $models, string $relationName): array
    {
        $parentIds = array_unique(array_filter(array_map(fn($model) => $model->{$this->foreignKey}, $models)));
        if (empty($parentIds)) return $models;

        $records = ($this->relatedClass)::query()
            ->whereIn($this->localKey, $parentIds)
            ->get();

        $dictionary = [];
        foreach ($records as $parentModel) {
            $dictionary[$parentModel->{$this->localKey}] = $parentModel;
        }

        foreach ($models as $model) {
            $parentKey = $model->{$this->foreignKey};
            $model->setRelation($relationName, $dictionary[$parentKey] ?? null);
        }

        return $models;
    }
}