<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as Base;
use Staudenmeir\LaravelMergedRelations\DatabaseServiceProvider;
use Staudenmeir\LaravelMergedRelations\Facades\Schema;
use Staudenmeir\LaravelMigrationViews\DatabaseServiceProvider as MigrationViewsDatabaseServiceProvider;
use Tests\Models\Comment;
use Tests\Models\Post;
use Tests\Models\Tag;
use Tests\Models\User;
use Tests\Models\Video;

abstract class TestCase extends Base
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        Schema::dropViewIfExists('all_comments');
        Schema::dropViewIfExists('all_posts');
        Schema::dropViewIfExists('all_taggables');
        Schema::dropViewIfExists('all_users');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->unsignedInteger('tag_id');
            $table->morphs('taggable');
            $table->string('label');
            $table->boolean('active');
        });

        Model::unguard();

        User::create();
        User::create();
        User::create();

        Post::create(['user_id' => 1]);
        Post::create(['user_id' => 2]);

        Comment::create(['post_id' => 1, 'user_id' => 1, 'parent_id' => null]);
        Comment::create(['post_id' => 1, 'user_id' => 2, 'parent_id' => 1]);
        Comment::create(['post_id' => 1, 'user_id' => 3, 'parent_id' => 1]);
        Comment::create(['post_id' => 2, 'user_id' => 1, 'parent_id' => null]);
        Comment::create(['post_id' => 2, 'user_id' => 2, 'parent_id' => 4]);

        Video::create();
        Video::create();

        Tag::create();
        Tag::create();

        DB::table('taggables')->insert([
            ['tag_id' => 1, 'taggable_type' => Post::class, 'taggable_id' => 1, 'label' => 'a', 'active' => true],
            ['tag_id' => 1, 'taggable_type' => Video::class, 'taggable_id' => 2, 'label' => 'b', 'active' => false],
            ['tag_id' => 2, 'taggable_type' => Post::class, 'taggable_id' => 2, 'label' => 'c', 'active' => true],
            ['tag_id' => 2, 'taggable_type' => Video::class, 'taggable_id' => 1, 'label' => 'd', 'active' => false],
        ]);

        Model::reguard();
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = require __DIR__.'/config/database.php';

        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', $config[getenv('DB_CONNECTION') ?: 'sqlite']);
    }

    protected function getPackageProviders($app)
    {
        return [
            DatabaseServiceProvider::class,
            MigrationViewsDatabaseServiceProvider::class,
        ];
    }
}
