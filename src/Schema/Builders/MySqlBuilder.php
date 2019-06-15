<?php

namespace Staudenmeir\LaravelMergedRelations\Schema\Builders;

use Staudenmeir\LaravelMigrationViews\Schema\Builders\MySqlBuilder as Base;

class MySqlBuilder extends Base
{
    use CreatesMergeViews;
}
