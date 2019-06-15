<?php

namespace Tests;

use Staudenmeir\LaravelMergedRelations\Facades\Schema;
use Tests\Models\Comment;
use Tests\Models\Post;
use Tests\Models\User;

class SchemaTest extends TestCase
{
    public function testCreateMergeView()
    {
        Schema::createMergeView('all_comments', [(new User)->comments(), (new User)->postComments()]);

        $allComments = User::first()->allComments;
        $this->assertEquals([1, 1, 2, 3, 4], $allComments->pluck('id')->all());
    }

    public function testCreateMergeViewWithoutDuplicates()
    {
        Schema::createMergeViewWithoutDuplicates('all_comments', [(new User)->comments(), (new User)->postComments()]);

        $allComments = User::first()->allComments;
        $this->assertEquals([1, 2, 3, 4], $allComments->pluck('id')->all());
    }

    public function testCreateOrReplaceMergeView()
    {
        Schema::connection('default')->createView('all_posts', Post::query());

        Schema::createOrReplaceMergeView('all_posts', [(new Comment)->post()]);

        $allPosts = Comment::first()->allPosts;
        $this->assertEquals([1], $allPosts->pluck('id')->all());
    }

    public function testCreateOrReplaceMergeViewWithoutDuplicates()
    {
        Schema::connection('default')->createView('all_posts', Comment::query());

        Schema::createOrReplaceMergeViewWithoutDuplicates('all_comments', [(new User)->comments(), (new User)->postComments()]);

        $allComments = User::first()->allComments;
        $this->assertEquals([1, 2, 3, 4], $allComments->pluck('id')->all());
    }
}
