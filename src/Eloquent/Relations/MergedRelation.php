<?php

namespace Staudenmeir\LaravelMergedRelations\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\HasMany<TRelatedModel, TDeclaringModel>
 */
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
     * @param list<string>|string $columns
     * @return \Illuminate\Database\Eloquent\Collection<int, TRelatedModel>
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
     * @param list<string>|string $columns
     * @return TRelatedModel|null
     */
    public function first($columns = ['*'])
    {
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Get a paginator for the "select" statement.
     *
     * @param int|\Closure|null $perPage
     * @param list<string>|string $columns
     * @param string $pageName
     * @param int|null $page
     * @param int|null|\Closure $total
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TRelatedModel>
     *
     * @throws \InvalidArgumentException
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        $this->query->addSelect(
            $this->shouldSelect((array) $columns)
        );

        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TRelatedModel> $paginator */
        $paginator = $this->query->paginate($perPage, $columns, $pageName, $page, $total);

        $this->hydratePivotRelations(
            $paginator->items()
        );

        return $paginator;
    }

    /**
     * Prepare the query builder for query execution.
     *
     * @param list<string>|string $columns
     * @return \Illuminate\Database\Eloquent\Builder<TRelatedModel>
     */
    protected function prepareQueryBuilder($columns = ['*'])
    {
        $builder = $this->query->applyScopes();

        $columns = $builder->getQuery()->columns ? [] : (array) $columns;

        $builder->addSelect(
            $this->shouldSelect($columns)
        );

        return $builder;
    }

    /**
     * Get the select columns for the relation query.
     *
     * @param list<string> $columns
     * @return list<string>
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
     * @param list<TRelatedModel> $models
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
     * @param list<TRelatedModel> $models
     * @return array<string, array{columns: list<string>, table: string}>
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
     * @param list<TDeclaringModel> $models
     * @param \Illuminate\Database\Eloquent\Collection<int, TRelatedModel> $results
     * @param string $relation
     * @return list<TDeclaringModel>
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
