<?php

namespace Staudenmeir\LaravelMergedRelations\Schema\Builders;

use Staudenmeir\LaravelMigrationViews\Schema\Builders\SqlServerBuilder as Base;

class SqlServerBuilder extends Base
{
    use CreatesMergeViews;
}
