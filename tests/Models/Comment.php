<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;
use Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation;

class Comment extends Model
{
    use HasRelationships;
    use HasMergedRelationships;

    public function allPosts(): MergedRelation
    {
        return $this->mergedRelation('all_posts');
    }

    public function allUsers(): MergedRelation
    {
        return $this->mergedRelationWithModel(User::class, 'all_users');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function postUser(): HasOneDeep
    {
        return $this->hasOneDeepFromRelations($this->post(), (new Post())->user());
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
