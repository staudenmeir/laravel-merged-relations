<?php

namespace Staudenmeir\LaravelMergedRelations\Schema\Builders;

use Staudenmeir\LaravelMigrationViews\Schema\Builders\PostgresBuilder as Base;

class PostgresBuilder extends Base
{
    use CreatesMergeViews;
}
