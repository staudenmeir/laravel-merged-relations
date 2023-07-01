<?php

namespace Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Staudenmeir\LaravelMergedRelations\Facades\Schema;
use Tests\Models\Comment;
use Tests\Models\Post;
use Tests\Models\Tag;
use Tests\Models\User;
use Tests\Models\Video;

class EloquentTest extends TestCase
{
    public function testLazyLoading()
    {
        Schema::createMergeView('all_taggables', [(new Tag())->posts(), (new Tag())->videos()]);

        $allTaggables = Tag::first()->allTaggables;
        $this->assertInstanceOf(Post::class, $allTaggables[0]);
        $this->assertInstanceOf(Video::class, $allTaggables[1]);
        $this->assertArrayHasKey('user_id', $allTaggables[0]);
        $this->assertArrayNotHasKey('created_at', $allTaggables[0]->getAttributes());
        $this->assertArrayNotHasKey('updated_at', $allTaggables[0]->getAttributes());
        $this->assertArrayHasKey('created_at', $allTaggables[1]);
        $this->assertArrayHasKey('updated_at', $allTaggables[1]);
        $this->assertArrayNotHasKey('user_id', $allTaggables[1]->getAttributes());
        $this->assertArrayNotHasKey('laravel_foreign_key', $allTaggables[0]->getAttributes());
        $this->assertArrayNotHasKey('laravel_model', $allTaggables[0]->getAttributes());
        $this->assertArrayNotHasKey('laravel_placeholders', $allTaggables[0]->getAttributes());
        $this->assertArrayNotHasKey('laravel_with', $allTaggables[0]->getAttributes());
        $this->assertTrue($allTaggables[0]->relationLoaded('comments'));
        $this->assertTrue($allTaggables[0]->relationLoaded('user'));
    }

    public function testEmptyLazyLoading()
    {
        Schema::createMergeView('all_taggables', [(new Tag())->posts(), (new Tag())->videos()]);

        DB::enableQueryLog();

        $allTaggables = (new Tag())->allTaggables;

        $this->assertInstanceOf(Collection::class, $allTaggables);
        $this->assertEmpty(DB::getQueryLog());
    }

    public function testEagerLoading()
    {
        Schema::createMergeView('all_taggables', [(new Tag())->posts(), (new Tag())->videos()]);

        $tags = Tag::with('allTaggables')->get();

        $this->assertArrayNotHasKey('laravel_foreign_key', $tags[0]->allTaggables[0]);
    }

    public function testCustomLocalKey()
    {
        Schema::createMergeView('all_comments', [(new User())->comments(), (new User())->postComments()]);

        $user = (new User())->setAttribute('local_key', 1);
        $allComments = $user->mergedRelation('all_comments', 'local_key')->orderBy('id')->get();

        $this->assertEquals([1, 1, 2, 3, 4], $allComments->pluck('id')->all());
    }

    public function testWithModel()
    {
        Schema::createMergeView('all_comments', [(new User())->comments(), (new User())->postComments()]);

        $allComments = User::first()->allComments()->withCount('replies')->with('post')->get();

        $this->assertEquals(2, $allComments[0]->replies_count);
        $this->assertTrue($allComments[0]->relationLoaded('post'));
    }

    public function testGet()
    {
        Schema::createMergeView('all_taggables', [(new Tag())->posts(), (new Tag())->videos()]);

        $allTaggables = Tag::first()->allTaggables()->get(['id']);

        $this->assertEquals([1, 2], $allTaggables->pluck('id')->all());
        $this->assertArrayHasKey('laravel_foreign_key', $allTaggables[1]);
        $this->assertArrayNotHasKey('user_id', $allTaggables[1]->getAttributes());
    }

    public function testFirst()
    {
        Schema::createMergeView('all_taggables', [(new Tag())->posts(), (new Tag())->videos()]);

        $taggable = Tag::first()->allTaggables()->first(['id']);

        $this->assertEquals(1, $taggable->id);
        $this->assertArrayHasKey('laravel_foreign_key', $taggable);
        $this->assertArrayNotHasKey('user_id', $taggable->getAttributes());
    }

    public function testPaginate()
    {
        Schema::createMergeView('all_taggables', [(new Tag())->posts(), (new Tag())->videos()]);

        $allTaggables = Tag::first()->allTaggables()->paginate(null, ['id']);

        $this->assertEquals([1, 2], $allTaggables->pluck('id')->all());
        $this->assertArrayHasKey('laravel_foreign_key', $allTaggables[1]);
        $this->assertArrayNotHasKey('user_id', $allTaggables[1]->getAttributes());
    }

    public function testHasManyDeepRelationWithLeadingBelongsTo()
    {
        Schema::createMergeView('all_users', [(new Comment())->postUser(), (new Comment())->user()]);

        $allUsers = Comment::find(2)->allUsers;

        $this->assertEquals([1, 2], $allUsers->pluck('id')->all());
    }
}
