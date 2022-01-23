<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;

class Comment extends Model
{
    use HasMergedRelationships;

    public function allPosts()
    {
        return $this->mergedRelation('all_posts');
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
