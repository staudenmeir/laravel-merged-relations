# Laravel Merged Relations

[![CI](https://github.com/staudenmeir/laravel-merged-relations/actions/workflows/ci.yml/badge.svg)](https://github.com/staudenmeir/laravel-merged-relations/actions/workflows/ci.yml?query=branch%3Amain)
[![Code Coverage](https://codecov.io/gh/staudenmeir/laravel-merged-relations/graph/badge.svg?token=ZRYGD44QVX)](https://codecov.io/gh/staudenmeir/laravel-merged-relations)
[![Latest Stable Version](https://poser.pugx.org/staudenmeir/laravel-merged-relations/v/stable)](https://packagist.org/packages/staudenmeir/laravel-merged-relations)
[![Total Downloads](https://poser.pugx.org/staudenmeir/laravel-merged-relations/downloads)](https://packagist.org/packages/staudenmeir/laravel-merged-relations/stats)
[![License](https://poser.pugx.org/staudenmeir/laravel-merged-relations/license)](https://github.com/staudenmeir/laravel-merged-relations/blob/main/LICENSE)

This Laravel Eloquent extension allows merging multiple relationships using SQL views.  
The relationships can target the same or different related models.

Supports Laravel 5.5+.

## Installation

    composer require staudenmeir/laravel-merged-relations:"^1.0"

Use this command if you are in PowerShell on Windows (e.g. in VS Code):

    composer require staudenmeir/laravel-merged-relations:"^^^^1.0"

## Versions

| Laravel | Package |
|:--------|:--------|
| 11.x    | 1.9     |
| 10.x    | 1.6     |
| 9.x     | 1.5     |
| 8.x     | 1.4     |
| 7.x     | 1.3     |
| 6.x     | 1.2     |
| 5.8     | 1.1     |
| 5.5–5.7 | 1.0     |

## Usage

- [Use Cases](#use-cases)
- [Step 1: Creating Views](#step-1-creating-views)
- [Step 2: Defining Relationships](#step-2-defining-relationships)
- [Pivot Table Data](#pivot-table-data)
- [Limitations](#limitations)
- [Testing](#testing)

### Use Cases

Use the package to merge multiple polymorphic relationships:

```php
class Tag extends Model
{
    public function allTaggables()
    {
        // TODO
    }

    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    public function videos()
    {
        return $this->morphedByMany(Video::class, 'taggable');
    }
}
```

Or use it to merge relationships with different depths:

```php
class User extends Model
{
    public function allComments()
    {
        // TODO
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function postComments()
    {
        return $this->hasManyThrough(Comment::class, Post::class);
    }
}
```

### Step 1: Creating Views

Before you can define the new relationship, you need to create the merge view in a migration:

```php
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

Schema::createMergeView(
    'all_taggables',
    [(new Tag)->posts(), (new Tag)->videos()]
);
```

By default, the view doesn't remove duplicates. Use `createMergeViewWithoutDuplicates()` to get unique results:

```php
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

Schema::createMergeViewWithoutDuplicates(
    'all_comments',
    [(new User)->comments(), (new User)->postComments()]
);
```

You can also replace an existing view:

```php
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

Schema::createOrReplaceMergeView(
    'all_comments',
    [(new User)->comments(), (new User)->postComments()]
);
```

The package includes [staudenmeir/laravel-migration-views](https://github.com/staudenmeir/laravel-migration-views). You
can use its methods to rename and drop views:

```php
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

Schema::renameView('all_comments', 'user_comments');

Schema::dropView('all_comments');
```

If you are using `php artisan migrate:fresh`, you can drop all views with `--drop-views`.

### Step 2: Defining Relationships

With the view created, you can define the merged relationship.

Use the `HasMergedRelationships` trait in your model and provide the view name:

```php
class Tag extends Model
{
    use \Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;

    public function allTaggables(): \Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation
    {
        return $this->mergedRelation('all_taggables');
    }
}
```

If all original relationships target the same related model, you can use `mergedRelationWithModel()`. This allows you to
access local scopes and use methods like `whereHas()` or `withCount()`:

```php
class User extends Model
{
    use \Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;

    public function allComments(): \Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation
    {
        return $this->mergedRelationWithModel(Comment::class, 'all_comments');
    }
}
```

You can use the merged relationship like any other relationship:

```php
$taggables = Tag::find($id)->allTaggables()->latest()->paginate();

$users = User::with('allComments')->get();
```

### Pivot Table Data

You can retrieve additional pivot columns if your merge view consists of many-to-many relationships.

Add the desired pivot columns to _all_ relationships:

```php
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

Schema::createMergeView(
    'all_taggables',
    [
        (new Tag)->posts()->withPivot('tagged_at'),
        (new Tag)->videos()->withPivot('tagged_at'),
    ]
);

$taggables = Tag::find($id)->allTaggables;

foreach ($taggables as $taggable) {
    dump($taggable->pivot->tagged_at);
}
```

### Limitations

In the original relationships, it's currently not possible to limit the selected columns or apply `withCount()`.

In the merged relationships, it's not possible to remove global scopes like `SoftDeletes`. They can only be removed in
the original relationships.

### Testing

If you use PHPUnit or a similar tool to run tests, add this property to your base test class to ensure that database
views are dropped when the test database is cleaned up:

```php
protected bool $dropViews = true;
```

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CODE OF CONDUCT](.github/CODE_OF_CONDUCT.md) for details.
