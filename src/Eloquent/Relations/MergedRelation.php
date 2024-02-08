<?php

namespace Staudenmeir\LaravelMergedRelations\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MergedRelation extends HasMany
{
    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        $results = !is_null($this->getParentKey())
            ? $this->get()
            : $this->related->newCollection();

        foreach ($results as $result) {
            unset($result->laravel_foreign_key);
        }

        return $results;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        $builder = $this->prepareQueryBuilder($columns);

        $models = $builder->getModels();

        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        $this->hydratePivotRelations($models);

        return $this->related->newCollection($models);
    }

    /**
     * Execute the query and get the first related model.
     *
     * @param array $columns
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        $results = $this->take(1)->get($columns);

        return count($results) > 0 ? $results->first() : null;
    }

    /**
     * Get a paginator for the "select" statement.
     *
     * @param int|null $perPage
     * @param array $columns
     * @param string $pageName
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->query->addSelect(
            $this->shouldSelect($columns)
        );

        $paginator = $this->query->paginate($perPage, $columns, $pageName, $page);

        $this->hydratePivotRelations(
            $paginator->items()
        );

        return $paginator;
    }

    /**
     * Prepare the query builder for query execution.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function prepareQueryBuilder($columns = ['*'])
    {
        $builder = $this->query->applyScopes();

        $columns = $builder->getQuery()->columns ? [] : $columns;

        return $builder->addSelect(
            $this->shouldSelect($columns)
        );
    }

    /**
     * Get the select columns for the relation query.
     *
     * @param array $columns
     * @return array
     */
    protected function shouldSelect(array $columns = ['*'])
    {
        if ($columns === ['*']) {
            return $columns;
        }

        return array_merge(
            $columns,
            ['laravel_foreign_key', 'laravel_model', 'laravel_placeholders', 'laravel_with']
        );
    }

    /**
     * Hydrate the pivot table relationships on the models.
     *
     * @param array $models
     * @return void
     */
    protected function hydratePivotRelations(array $models): void
    {
        if (!$models) {
            return;
        }

        $pivotTables = $this->getPivotTables($models);

        if (!$pivotTables) {
            return;
        }

        foreach ($models as $model) {
            $attributes = $model->getAttributes();

            foreach ($pivotTables as $accessor => $table) {
                $pivotAttributes = [];

                foreach ($table['columns'] as $column) {
                    $key = "__{$table['table']}__{$accessor}__$column";

                    $pivotAttributes[$column] = $attributes[$key];

                    unset($model->$key);
                }

                $relation = Pivot::fromAttributes($model, $pivotAttributes, $table['table'], true);

                $model->setRelation($accessor, $relation);
            }
        }
    }

    /**
     * Get the pivot tables from the models.
     *
     * @param array $models
     * @return array
     */
    protected function getPivotTables(array $models): array
    {
        $tables = [];

        foreach (array_keys($models[0]->getAttributes()) as $key) {
            if (str_starts_with($key, '__')) {
                [, $table, $accessor, $column] = explode('__', $key);

                if (isset($tables[$accessor])) {
                    $tables[$accessor]['columns'][] = $column;
                } else {
                    $tables[$accessor] = [
                        'columns' => [$column],
                        'table' => $table,
                    ];
                }
            }
        }

        return $tables;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array $models
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param string $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $models = parent::match($models, $results, $relation);

        foreach ($results as $result) {
            unset($result->laravel_foreign_key);
        }

        return $models;
    }
}
