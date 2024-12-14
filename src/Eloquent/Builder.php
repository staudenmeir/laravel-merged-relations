<?php

namespace Staudenmeir\LaravelMergedRelations\Eloquent;

use Illuminate\Database\Eloquent\Builder as Base;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModel>
 */
class Builder extends Base
{
    /**
     * Get the hydrated models without eager loading.
     *
     * @param list<string>|string $columns
     * @return array<int, TModel>
     */
    public function getModels($columns = ['*'])
    {
        /** @var list<object{laravel_model: class-string<TModel>, laravel_placeholders: string}> $items */
        $items = $this->query->get($columns)->all();

        $models = [];

        foreach ($items as $item) {
            /** @var class-string<TModel> $class */
            $class = Relation::getMorphedModel($item->laravel_model) ?? $item->laravel_model;

            $unset = ['laravel_model', 'laravel_placeholders'];

            if ($item->laravel_placeholders) {
                array_push($unset, ...explode(',', $item->laravel_placeholders));
            }

            foreach ($unset as $key) {
                unset($item->$key);
            }

            $models[] = (new $class())->newQuery()->hydrate([$item])[0];
        }

        return $models;
    }

    /** @inheritDoc */
    public function eagerLoadRelations(array $models)
    {
        collect($models)->groupBy(function ($model) {
            return get_class($model);
        })->each(function ($models) {
            $model = $models[0];

            /** @var string $with */
            $with = $model->getAttribute('laravel_with');

            /** @var array<int, string> $relations */
            $relations = array_merge(
                $this->eagerLoad,
                !empty($model->laravel_with) ? explode(',', $with) : []
            );

            (new Collection($models))->load($relations);
        });

        foreach ($models as $model) {
            unset($model->laravel_with);
        }

        return $models;
    }
}
