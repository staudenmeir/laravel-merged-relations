<?php

namespace Tests;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Staudenmeir\LaravelMergedRelations\Facades\Schema;
use Tests\Models\Tag;

class PivotTableTest extends TestCase
{
    public function testLazyLoading()
    {
        Schema::createMergeView(
            'all_taggables',
            [
                (new Tag())->posts()->withPivot('label', 'active'),
                (new Tag())->videosAsHasManyDeep()->withPivot('taggables', ['label', 'active'], accessor: 'pivot')
            ]
        );

        $allTaggables = Tag::first()->allTaggables;

        $pivot = $allTaggables[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertEquals(['label' => 'a', 'active' => true], $pivot->getAttributes());
        $this->assertEquals(['label' => 'b', 'active' => false], $allTaggables[1]->pivot->getAttributes());
    }

    public function testLazyLoadingWithoutResults()
    {
        Schema::createMergeView(
            'all_taggables',
            [
                (new Tag())->posts()->withPivot('label', 'active'),
                (new Tag())->videosAsHasManyDeep()->withPivot('taggables', ['label', 'active'], accessor: 'pivot')
            ]
        );

        $allTaggables = Tag::first()->allTaggables()->where('id', 0)->get();

        $this->assertEmpty($allTaggables);
    }

    public function testEagerLoading()
    {
        Schema::createMergeView(
            'all_taggables',
            [
                (new Tag())->posts()->withPivot('label', 'active'),
                (new Tag())->videosAsHasManyDeep()->withPivot('taggables', ['label', 'active'], accessor: 'pivot')
            ]
        );

        $tags = Tag::with('allTaggables')->get();

        $this->assertTrue($tags[0]->allTaggables[0]->relationLoaded('pivot'));
    }

    public function testPaginate()
    {
        Schema::createMergeView(
            'all_taggables',
            [
                (new Tag())->posts()->withPivot('label', 'active'),
                (new Tag())->videosAsHasManyDeep()->withPivot('taggables', ['label', 'active'], accessor: 'pivot')
            ]
        );

        $allTaggables = Tag::first()->allTaggables()->paginate();

        $this->assertTrue($allTaggables[0]->relationLoaded('pivot'));
    }
}
