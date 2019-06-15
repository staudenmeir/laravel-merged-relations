<?php

namespace Staudenmeir\LaravelMergedRelations\Schema\Builders;

use Staudenmeir\LaravelMigrationViews\Schema\Builders\SQLiteBuilder as Base;

class SQLiteBuilder extends Base
{
    use CreatesMergeViews;
}
