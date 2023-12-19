<?php

namespace Staudenmeir\LaravelMergedRelations\IdeHelper;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;
use Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation;
use Throwable;

class MergedRelationsHook implements ModelHookInterface
{
    public function run(ModelsCommand $command, Model $model): void
    {
        $traits = class_uses_recursive($model);

        if (!in_array(HasMergedRelationships::class, $traits)) {
            return;
        }

        $methods = (new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->isAbstract() || $method->isStatic() || !$method->isPublic()
                || $method->getNumberOfParameters() > 0 || $method->getDeclaringClass()->getName() === Model::class) {
                continue;
            }

            try {
                $relationship = $method->invoke($model);
            } catch (Throwable) {
                continue;
            }

            if ($relationship instanceof MergedRelation) {
                $this->addRelationship($command, $method, $relationship);
            }
        }
    }

    protected function addRelationship(ModelsCommand $command, ReflectionMethod $method, Relation $relationship): void
    {
        $type = '\\' . Collection::class . '|\\' . $relationship->getRelated()::class . '[]';

        $command->setProperty(
            $method->getName(),
            $type,
            true,
            false
        );

        $command->setProperty(
            Str::snake($method->getName()) . '_count',
            'int',
            true,
            false,
            null,
            true
        );
    }
}
