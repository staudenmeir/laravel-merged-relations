<?php

namespace Staudenmeir\LaravelMergedRelations;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Staudenmeir\LaravelMergedRelations\IdeHelper\MergedRelationsHook;

class IdeHelperServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var string
     */
    const ModelsCommandAlias = __NAMESPACE__ . '\\' . ModelsCommand::class;

    public function register(): void
    {
        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app->get('config');

        $config->set(
            'ide-helper.model_hooks',
            array_merge(
                [MergedRelationsHook::class],
                $config->array('ide-helper.model_hooks', [])
            )
        );

        $this->app->alias(ModelsCommand::class, static::ModelsCommandAlias);
    }

    /**
     * @return list<string>
     */
    public function provides(): array
    {
        return [
            static::ModelsCommandAlias
        ];
    }
}
