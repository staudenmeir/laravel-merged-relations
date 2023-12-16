<?php

namespace Tests\IdeHelper\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;
use Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation;

class User extends Model
{
    use HasMergedRelationships;

    public function allComments(): MergedRelation
    {
        return $this->mergedRelationWithModel(Comment::class, 'all_comments');
    }
}
