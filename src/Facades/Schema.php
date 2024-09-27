<?php

namespace Staudenmeir\LaravelMergedRelations\Facades;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Facade;
use RuntimeException;
use Staudenmeir\LaravelMergedRelations\Schema\Builders\MariaDbBuilder;
use Staudenmeir\LaravelMergedRelations\Schema\Builders\MySqlBuilder;
use Staudenmeir\LaravelMergedRelations\Schema\Builders\PostgresBuilder;
use Staudenmeir\LaravelMergedRelations\Schema\Builders\SQLiteBuilder;
use Staudenmeir\LaravelMergedRelations\Schema\Builders\SqlServerBuilder;
use Staudenmeir\LaravelMigrationViews\Schema\Grammars\MariaDbGrammar;
use Staudenmeir\LaravelMigrationViews\Schema\Grammars\MySqlGrammar;
use Staudenmeir\LaravelMigrationViews\Schema\Grammars\PostgresGrammar;
use Staudenmeir\LaravelMigrationViews\Schema\Grammars\SQLiteGrammar;
use Staudenmeir\LaravelMigrationViews\Schema\Grammars\SqlServerGrammar;

/**
 * @method static void createMergeView(string $name, array $relations, bool $duplicates = true, bool $orReplace = false)
 * @method static void createMergeViewWithoutDuplicates(string $name, array $relations)
 * @method static void createOrReplaceMergeView(string $name, array $relations, bool $duplicates = true)
 * @method static void createOrReplaceMergeViewWithoutDuplicates(string $name, array $relations)
 *
 * @mixin \Staudenmeir\LaravelMigrationViews\Facades\Schema
 */
class Schema extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return static::class;
    }

    /**
     * Get a schema builder instance for a connection.
     *
     * @param string $name
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function connection($name)
    {
        /** @var array{db: \Illuminate\Database\DatabaseManager} $app */
        $app = static::$app;

        return static::getSchemaBuilder(
            $app['db']->connection($name)
        );
    }

    /**
     * Get the schema builder.
     *
     * @param \Illuminate\Database\Connection $connection
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function getSchemaBuilder(Connection $connection)
    {
        return match ($connection->getDriverName()) {
            'mysql' => new MySqlBuilder(
                $connection->setSchemaGrammar(
                    $connection->withTablePrefix(new MySqlGrammar())
                )
            ),
            'mariadb' => new MariaDbBuilder(
                $connection->setSchemaGrammar(
                    $connection->withTablePrefix(new MariaDbGrammar())
                )
            ),
            'pgsql' => new PostgresBuilder(
                $connection->setSchemaGrammar(
                    $connection->withTablePrefix(new PostgresGrammar())
                )
            ),
            'sqlite' => new SQLiteBuilder(
                $connection->setSchemaGrammar(
                    $connection->withTablePrefix(new SQLiteGrammar())
                )
            ),
            'sqlsrv' => new SqlServerBuilder(
                $connection->setSchemaGrammar(
                    $connection->withTablePrefix(new SqlServerGrammar())
                )
            ),
            default => throw new RuntimeException('This database is not supported.'), // @codeCoverageIgnore
        };
    }
}
