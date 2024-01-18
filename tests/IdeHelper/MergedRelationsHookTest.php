<?php

namespace Tests\IdeHelper;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Illuminate\Database\Capsule\Manager as DB;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase;
use Staudenmeir\LaravelMergedRelations\IdeHelper\MergedRelationsHook;
use Tests\IdeHelper\Models\User;

class MergedRelationsHookTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__.'/../config/database.php';

        $db = new DB();
        $db->addConnection($config[getenv('DB_CONNECTION') ?: 'sqlite']);
        $db->setAsGlobal();
        $db->bootEloquent();
    }

    public function testRun()
    {
        $command = Mockery::mock(ModelsCommand::class);
        $command->shouldReceive('setProperty')->once()->with(
            'allComments',
            '\Illuminate\Database\Eloquent\Collection|\Tests\IdeHelper\Models\Comment[]',
            true,
            false
        );
        $command->shouldReceive('setProperty')->once()->with(
            'all_comments_count',
            'int',
            true,
            false,
            null,
            true
        );

        $hook = new MergedRelationsHook();
        $hook->run($command, new User());
    }
}
