<?php

namespace Staudenmeir\LaravelMergedRelations\Schema\Builders;

use Staudenmeir\LaravelMigrationViews\Schema\Builders\MariaDbBuilder as Base;

class MariaDbBuilder extends Base
{
    use CreatesMergeViews;
}
