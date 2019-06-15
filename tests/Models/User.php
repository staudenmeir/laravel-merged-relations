<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;

class User extends Model
{
    use HasMergedRelationships;

    public function allComments()
    {
        return $this->mergedRelationWithModel(Comment::class, 'all_comments')->orderBy('id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function postComments()
    {
        return $this->hasManyThrough(Comment::class, Post::class);
    }
}
