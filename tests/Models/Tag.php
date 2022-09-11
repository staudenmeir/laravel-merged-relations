<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;
use Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation;

class Tag extends Model
{
    use HasMergedRelationships;

    public function allTaggables(): MergedRelation
    {
        return $this->mergedRelation('all_taggables')->orderBy('laravel_model');
    }

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable')->with('comments');
    }

    public function videos(): MorphToMany
    {
        return $this->morphedByMany(Video::class, 'taggable');//->withPivot();
    }
}
