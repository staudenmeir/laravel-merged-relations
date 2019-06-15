<?php

namespace Staudenmeir\LaravelMergedRelations\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation;

trait HasMergedRelationships
{
    /**
     * Define a merged relationship.
     *
     * @param string $view
     * @param string|null $localKey
     * @return \Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation
     */
    public function mergedRelation($view, $localKey = null)
    {
        return $this->mergedRelationWithModel(static::class, $view, $localKey);
    }

    /**
     * Define a merged relationship with model.
     *
     * @param string $related
     * @param string $view
     * @param string|null $localKey
     * @return \Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation
     */
    public function mergedRelationWithModel($related, $view, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related)->setTable($view);

        $query = (new Builder($instance->getConnection()->query()))->setModel($instance);

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newMergedRelation($query, $this, $view.'.laravel_foreign_key', $localKey);
    }

    /**
     * Instantiate a new MergedRelation relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return \Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation
     */
    protected function newMergedRelation(EloquentBuilder $query, Model $parent, $foreignKey, $localKey)
    {
        return new MergedRelation($query, $parent, $foreignKey, $localKey);
    }
}
