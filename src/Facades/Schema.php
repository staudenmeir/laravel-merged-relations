<?php

namespace Staudenmeir\LaravelMergedRelations\Facades;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Facade;
use RuntimeException;
use Staudenmeir\LaravelMergedRelations\Schema\Builders\MySqlBuilder;
use Staudenmeir\LaravelMergedRelations\Schema\Builders\PostgresBuilder;
use Staudenmeir\LaravelMergedRelations\Schema\Builders\SQLiteBuilder;
use Staudenmeir\LaravelMergedRelations\Schema\Builders\SqlServerBuilder;
use Staudenmeir\LaravelMigrationViews\Schema\Grammars\MySqlGrammar;
use Staudenmeir\LaravelMigrationViews\Schema\Grammars\PostgresGrammar;
use Staudenmeir\LaravelMigrationViews\Schema\Grammars\SQLiteGrammar;
use Staudenmeir\LaravelMigrationViews\Schema\Grammars\SqlServerGrammar;

/**
 * @method static void createMergeView(string $name, array $relations, bool $duplicates = true, bool $orReplace = false)
 * @method static void createMergeViewWithoutDuplicates(string $name, array $relations)
 * @method static void createOrReplaceMergeView(string $name, array $relations, bool $duplicates = true)
 * @method static void createOrReplaceMergeViewWithoutDuplicates(string $name, array $relations)
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
        return static::getSchemaBuilder(
            static::$app['db']->connection($name)
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
        $driver = $connection->getDriverName();

        switch ($driver) {
            case 'mysql':
                $connection->setSchemaGrammar($connection->withTablePrefix(new MySqlGrammar()));

                return new MySqlBuilder($connection);
            case 'pgsql':
                $connection->setSchemaGrammar($connection->withTablePrefix(new PostgresGrammar()));

                return new PostgresBuilder($connection);
            case 'sqlite':
                $connection->setSchemaGrammar($connection->withTablePrefix(new SQLiteGrammar()));

                return new SQLiteBuilder($connection);
            case 'sqlsrv':
                $connection->setSchemaGrammar($connection->withTablePrefix(new SqlServerGrammar()));

                return new SqlServerBuilder($connection);
        }

        throw new RuntimeException('This database is not supported.'); // @codeCoverageIgnore
    }
}
