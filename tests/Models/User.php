<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;
use Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation;

class User extends Model
{
    use HasMergedRelationships;

    public function allComments(): MergedRelation
    {
        return $this->mergedRelationWithModel(Comment::class, 'all_comments')->orderBy('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function postComments(): HasManyThrough
    {
        return $this->hasManyThrough(Comment::class, Post::class);
    }
}
