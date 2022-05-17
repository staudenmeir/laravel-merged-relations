<?php

namespace Staudenmeir\LaravelMergedRelations\Eloquent;

use Illuminate\Database\Eloquent\Builder as Base;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class Builder extends Base
{
    /**
     * Get the hydrated models without eager loading.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model[]
     */
    public function getModels($columns = ['*'])
    {
        $items = $this->query->get($columns)->all();

        $models = [];

        foreach ($items as $item) {
            $class = Relation::getMorphedModel($item->laravel_model) ?? $item->laravel_model;

            $unset = ['laravel_model', 'laravel_placeholders'];

            if ($item->laravel_placeholders) {
                array_push($unset, ...explode(',', $item->laravel_placeholders));
            }

            foreach ($unset as $key) {
                unset($item->$key);
            }

            $models[] = (new $class())->hydrate([$item])[0];
        }

        return $models;
    }

    /**
     * Eager load the relationships for the models.
     *
     * @param array $models
     * @return array
     */
    public function eagerLoadRelations(array $models)
    {
        collect($models)->groupBy(function ($model) {
            return get_class($model);
        })->each(function ($models) {
            $model = $models[0];

            $relations = array_merge(
                $this->eagerLoad,
                $model->laravel_with ? explode(',', $model->laravel_with) : []
            );

            (new Collection($models))->load($relations);
        });

        foreach ($models as $model) {
            unset($model->laravel_with);
        }

        return $models;
    }
}
