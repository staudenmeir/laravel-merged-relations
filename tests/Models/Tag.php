<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;

class Tag extends Model
{
    use HasMergedRelationships;

    public function allTaggables()
    {
        return $this->mergedRelation('all_taggables')->orderBy('laravel_model');
    }

    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable')->with('comments');
    }

    public function videos()
    {
        return $this->morphedByMany(Video::class, 'taggable');
    }
}
