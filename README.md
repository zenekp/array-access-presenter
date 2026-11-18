# ArrayAccess Presenter

A small PHP base presenter for reading arrays and `ArrayAccess` objects. It converts method names to snake_case and provides helpers for single and collection relations.

## Requirements

- PHP 8.0 or newer

## Installation

```bash
composer require zenekp/array-access-presenter
```

## Basic usage

Extend `AbstractPresenter` and use magic methods to read values:

```php
use Zenekp\ArrayAccessPresenter\AbstractPresenter;
use Zenekp\ArrayAccessPresenter\Interfaces\PresenterInterface;

class UserPresenter extends AbstractPresenter implements PresenterInterface
{
}

$presenter = new UserPresenter([
    'display_name' => 'Ada',
    'active' => true,
]);

$presenter->displayName(); // 'Ada'
$presenter->active();      // true
$presenter->getData();     // Original array
```

Method names are converted to snake_case. The same lookup works with `ArrayAccess` implementations such as `ArrayObject`.

```php
use ArrayObject;

$presenter = new UserPresenter(new ArrayObject([
    'display_name' => 'Ada',
]));

$presenter->displayName(); // 'Ada'
```

Calling a method for a key that does not exist throws a `RuntimeException`.

## Checking values

Prefix a method with `has` to check whether a key exists and is non-empty:

```php
$presenter = new UserPresenter([
    'name' => 'Ada',
    'nickname' => '',
]);

$presenter->hasName();     // true
$presenter->hasNickname(); // false
$presenter->hasMissing();  // false
```

## Relations

Define typed properties and expose methods that initialize related presenters:

```php
use Zenekp\ArrayAccessPresenter\AbstractPresenter;
use Zenekp\ArrayAccessPresenter\Interfaces\PresenterInterface;

class AuthorPresenter extends AbstractPresenter implements PresenterInterface
{
}

class CommentPresenter extends AbstractPresenter implements PresenterInterface
{
}

class ArticlePresenter extends AbstractPresenter implements PresenterInterface
{
    protected ?AuthorPresenter $author = null;

    protected array $comments = [];

    public function author(): ?AuthorPresenter
    {
        $this->setRelationPresenter(__FUNCTION__, AuthorPresenter::class);

        return $this->author;
    }

    public function comments(): array
    {
        $this->setCollectionRelationPresenters(__FUNCTION__, CommentPresenter::class);

        return $this->comments;
    }
}
```

Relations accept array or `ArrayAccess` data. Collection relation values that are not arrays or `ArrayAccess` objects are skipped.

Concrete relation classes must extend `AbstractPresenter` and implement `PresenterInterface`. Abstract classes and classes that omit the interface throw an `InvalidArgumentException`.

```php
$article = new ArticlePresenter([
    'author' => ['display_name' => 'Ada'],
    'comments' => [
        ['display_name' => 'Grace'],
        ['display_name' => 'Linus'],
    ],
]);

$article->author()->displayName(); // 'Ada'
$article->comments()[0]->displayName(); // 'Grace'
```

Both the original property name and its snake_case equivalent are checked. For example, `primaryAuthor` checks `primaryAuthor` and `primary_author`.

## Testing

```bash
composer test
```

## License

MIT
