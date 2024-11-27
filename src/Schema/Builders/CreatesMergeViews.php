<?php

namespace Staudenmeir\LaravelMergedRelations\Schema\Builders;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use RuntimeException;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

trait CreatesMergeViews
{
    /**
     * Create a view that merges relationships.
     *
     * @param string $name
     * @param non-empty-list<\Illuminate\Database\Eloquent\Relations\Relation<*, *, *>> $relations
     * @param bool $duplicates
     * @param bool $orReplace
     * @return void
     */
    public function createMergeView($name, array $relations, $duplicates = true, $orReplace = false)
    {
        $this->removeConstraints($relations);

        $union = $duplicates ? 'unionAll' : 'union';

        $query = $this->getQuery($relations, $union);

        $this->createView($name, $query, null, $orReplace);
    }

    /**
     * Create a view that merges relationships without duplicates.
     *
     * @param string $name
     * @param non-empty-list<\Illuminate\Database\Eloquent\Relations\Relation<*, *, *>> $relations
     * @return void
     */
    public function createMergeViewWithoutDuplicates($name, array $relations)
    {
        $this->createMergeView($name, $relations, false);
    }

    /**
     * Create a view that merges relationships or replace an existing one.
     *
     * @param string $name
     * @param non-empty-list<\Illuminate\Database\Eloquent\Relations\Relation<*, *, *>> $relations
     * @param bool $duplicates
     * @return void
     */
    public function createOrReplaceMergeView($name, array $relations, $duplicates = true)
    {
        $this->createMergeView($name, $relations, $duplicates, true);
    }

    /**
     * Create a view that merges relationships or replace an existing one without duplicates.
     *
     * @param string $name
     * @param non-empty-list<\Illuminate\Database\Eloquent\Relations\Relation<*, *, *>> $relations
     * @return void
     */
    public function createOrReplaceMergeViewWithoutDuplicates($name, array $relations)
    {
        $this->createOrReplaceMergeView($name, $relations, false);
    }

    /**
     * Remove the foreign key constraints from the relationships.
     *
     * @param non-empty-list<\Illuminate\Database\Eloquent\Relations\Relation<*, *, *>> $relations
     * @return void
     */
    protected function removeConstraints(array $relations)
    {
        foreach ($relations as $relation) {
            $foreignKey = $this->getOriginalForeignKey($relation);

            $relation->getQuery()->getQuery()->wheres = collect($relation->getQuery()->getQuery()->wheres)
                ->reject(function ($where) use ($foreignKey) {
                    return $where['column'] === $foreignKey;
                })->values()->all();
        }
    }

    /**
     * Get the foreign key of the original relationship.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation<*, *, *> $relation
     * @return string
     */
    protected function getOriginalForeignKey(Relation $relation)
    {
        if ($relation instanceof BelongsTo) {
            return $relation->getQualifiedOwnerKeyName();
        }

        if ($relation instanceof BelongsToMany) {
            return $relation->getQualifiedForeignPivotKeyName();
        }

        // TODO[L12]
        if ($relation instanceof HasManyThrough || $relation instanceof HasOneThrough) {
            return $relation->getQualifiedFirstKeyName();
        }

        if ($relation instanceof HasOneOrMany) {
            return $relation->getQualifiedForeignKeyName();
        }

        throw new RuntimeException('This type of relationship is not supported.'); // @codeCoverageIgnore
    }

    /**
     * Get the merge query.
     *
     * @param non-empty-list<\Illuminate\Database\Eloquent\Relations\Relation<*, *, *>> $relations
     * @param string $union
     * @return \Illuminate\Database\Eloquent\Builder<*>
     */
    protected function getQuery(array $relations, $union)
    {
        $grammar = $this->connection->getQueryGrammar();

        $pdo = $this->connection->getPdo();

        $columns = $this->getRelationshipColumns($relations);

        $pivotTables = $this->getPivotTables($relations);

        $allColumns = array_unique(array_merge(...array_values($columns)));

        $query = null;

        foreach ($relations as $relation) {
            $relationQuery = $relation->getQuery();

            /** @var string $from */
            $from = $relationQuery->getQuery()->from;

            $foreignKey = $this->getMergedForeignKey($relation);

            $model = $relation->getRelated()->getMorphClass();

            $placeholders = [];

            foreach ($allColumns as $column) {
                if (in_array($column, $columns[$from])) {
                    $relationQuery->addSelect($from.'.'.$column);
                } else {
                    $relationQuery->selectRaw('null as '.$grammar->wrap($column));

                    $placeholders[] = $column;
                }
            }

            foreach ($pivotTables as $pivotTable) {
                foreach ($pivotTable['columns'] as $column) {
                    $alias = "__{$pivotTable['table']}__{$pivotTable['accessor']}__$column";

                    $relationQuery->addSelect("{$pivotTable['table']}.$column as $alias");
                }
            }

            $with = array_keys($relationQuery->getEagerLoads());

            $relationQuery->selectRaw($grammar->wrap($foreignKey).' as laravel_foreign_key')
                ->selectRaw($pdo->quote($model).' as laravel_model')
                ->selectRaw($pdo->quote(implode(',', $placeholders)).' as laravel_placeholders')
                ->selectRaw($pdo->quote(implode(',', $with)).' as laravel_with');

            $this->addRelationQueryConstraints($relation);

            if (!$query) {
                $query = $relationQuery;
            } else {
                $query->$union($relationQuery);
            }
        }

        return $query;
    }

    /**
     * Get the columns of all relationship tables.
     *
     * @param non-empty-list<\Illuminate\Database\Eloquent\Relations\Relation<*, *, *>> $relations
     * @return array<string, list<string>>
     */
    protected function getRelationshipColumns(array $relations)
    {
        $columns = [];

        foreach ($relations as $relation) {
            /** @var string $table */
            $table = $relation->getQuery()->getQuery()->from;

            if (!isset($columns[$table])) {
                /** @var list<string> $listing */
                $listing = $relation->getRelated()->getConnection()->getSchemaBuilder()->getColumnListing($table);

                $columns[$table] = $listing;
            }
        }

        return $columns;
    }

    /**
     * Get the pivot tables that are requested by all relationships.
     *
     * @param non-empty-list<\Illuminate\Database\Eloquent\Relations\Relation<*, *, *>> $relations
     * @return list<array{accessor: string, columns: array<int, string>, table: string}>
     */
    protected function getPivotTables(array $relations): array
    {
        $tables = [];

        foreach ($relations as $i => $relation) {
            if ($relation instanceof BelongsToMany) {
                $pivotColumns = $relation->getPivotColumns();

                if ($pivotColumns) {
                    $tables[$i][] = [
                        'accessor' => $relation->getPivotAccessor(),
                        'columns' => $pivotColumns,
                        'table' => $relation->getTable(),
                    ];
                }
            } elseif($relation instanceof HasManyDeep) {
                $intermediateTables = $relation->getIntermediateTables();

                foreach ($intermediateTables as $accessor => $table) {
                    $tables[$i][] = [
                        'accessor' => $accessor,
                        'columns' => $table['columns'],
                        'table' => $table['table'],
                    ];
                }
            }
        }

        if (count($tables) === count($relations)) {
            $hashes = array_map(
                fn (array $table) => serialize($table),
                $tables
            );

            $uniqueHashes = array_unique($hashes);

            if (count($uniqueHashes) === 1) {
                return $tables[0];
            }
        }

        return [];
    }

    /**
     * Get the foreign key for the merged relationship.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation<*, *, *> $relation
     * @return string
     */
    protected function getMergedForeignKey(Relation $relation)
    {
        if ($relation instanceof BelongsTo) {
            return $relation->getQualifiedParentKeyName();
        }

        if ($this->isHasManyDeepRelationWithLeadingBelongsTo($relation)) {
            /** @var \Staudenmeir\EloquentHasManyDeep\HasManyDeep<*, *> $relation */
            return $relation->getFarParent()->getQualifiedKeyName();
        }

        return $this->getOriginalForeignKey($relation);
    }

    /**
     * Add relation-specific constraints to the query.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation<*, *, *> $relation
     * @return void
     */
    protected function addRelationQueryConstraints(Relation $relation)
    {
        if ($relation instanceof BelongsTo) {
            $relation->getQuery()->distinct()
                          ->join(
                              $relation->getParent()->getTable(),
                              $relation->getQualifiedForeignKeyName(),
                              '=',
                              $relation->getQualifiedOwnerKeyName()
                          );
        }

        if ($this->isHasManyDeepRelationWithLeadingBelongsTo($relation)) {
            /** @var \Staudenmeir\EloquentHasManyDeep\HasManyDeep<*, *> $relation */
            $relation->getQuery()
                     ->join(
                         $relation->getFarParent()->getTable(),
                         $relation->getQualifiedLocalKeyName(),
                         '=',
                         $relation->getQualifiedFirstKeyName()
                     );
        }
    }

    /**
     * Determine if the relationship is a HasManyDeep relationship that starts with a BelongsTo relationship.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation<*, *, *> $relation
     * @return bool
     */
    protected function isHasManyDeepRelationWithLeadingBelongsTo(Relation $relation): bool
    {
        return $relation instanceof HasManyDeep
            && $relation->getFirstKeyName() === $relation->getParent()->getKeyName();
    }
}
