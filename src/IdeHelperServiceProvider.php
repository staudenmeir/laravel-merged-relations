<?php

namespace Staudenmeir\LaravelMergedRelations;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Staudenmeir\LaravelMergedRelations\IdeHelper\MergedRelationsHook;

class IdeHelperServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $this->app->get('config');

        $config->set(
            'ide-helper.model_hooks',
            array_merge(
                [MergedRelationsHook::class],
                (array) $config->get('ide-helper.model_hooks', [])
            )
        );
    }

    /**
     * @return list<class-string>
     */
    public function provides(): array
    {
        return [
            ModelsCommand::class,
        ];
    }
}
