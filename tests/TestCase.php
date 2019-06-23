<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as Base;
use Tests\Models\Comment;
use Tests\Models\Post;
use Tests\Models\Tag;
use Tests\Models\User;
use Tests\Models\Video;

abstract class TestCase extends Base
{
    protected function setUp()
    {
        parent::setUp();

        $config = require __DIR__.'/config/database.php';

        $db = new DB;
        $db->addConnection($config[getenv('DB') ?: 'sqlite']);
        $db->setAsGlobal();
        $db->bootEloquent();

        $this->migrate();

        $this->seed();

        Facade::setFacadeApplication(['db' => $db]);
    }

    /**
     * Migrate the database.
     *
     * @return void
     */
    protected function migrate()
    {
        DB::schema()->dropAllTables();
        DB::schema()->dropAllViews();

        DB::schema()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        DB::schema()->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
        });

        DB::schema()->create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->timestamps();
        });

        DB::schema()->create('videos', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        DB::schema()->create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        DB::schema()->create('taggables', function (Blueprint $table) {
            $table->unsignedInteger('tag_id');
            $table->morphs('taggable');
        });
    }

    /**
     * Seed the database.
     *
     * @return void
     */
    protected function seed()
    {
        Model::unguard();

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
            ['tag_id' => 1, 'taggable_type' => Post::class, 'taggable_id' => 1],
            ['tag_id' => 1, 'taggable_type' => Video::class, 'taggable_id' => 2],
            ['tag_id' => 2, 'taggable_type' => Post::class, 'taggable_id' => 2],
            ['tag_id' => 2, 'taggable_type' => Video::class, 'taggable_id' => 1],
        ]);

        Model::reguard();
    }
}
