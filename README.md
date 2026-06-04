# FilterQ for Symfony / Doctrine ORM

FilterQ allows advanced filtering in Symfony APIs using Doctrine ORM. You can accept a single-line expression from your users like:

```
name=starter&(type=image|type=video)
```

And FilterQ will convert it to DQL WHERE conditions in your Doctrine QueryBuilder.

---

FilterQ was built for [Hyvor Blogs](https://blogs.hyvor.com)' Data API.

---

## Features

- Easy-to-write, single or multi-line expressions.
- Logical operators (`&` and `|`) and nesting/grouping (with `()`)
- Secure. FilterQ only gives access to the columns and operators you define.
- Supports joining related entities. Users can filter by joined entity fields.
- Supports "type hinting" for keys.
- Extensible. You can add your own operators easily (e.g., SQL `LIKE`).

# FilterQ Expressions

Example: `(published_at > 1639665890 & published_at < 1639695890) | is_featured=true`

A **FilterQ Expression** is a combination of **conditions**, connected and grouped using one or more of the following.

- `&` - AND
- `|` - OR
- `()` - to group logic

A condition has three parts:

- `key`
- `operator`
- `value`

### Key

Usually, a key maps to a DQL column reference (e.g., `p.id`, `a.name`). It should match `[a-zA-Z0-9_.]+`.

### Operators

By default, the following operators are supported.

- `=` - equals
- `!=` - not equal
- `>` - greater than
- `<` - less than
- `>=` - greater than or equals
- `<=` - less than or equals

### Values

- null: `null`
- boolean: `true` or `false`
- strings: `'hey'` or `hey`
- number: `250`, `-250`, or `2.5`

# Basic Usage

```
composer require hyvor/doctrine-filterq
```

```php
use Hyvor\FilterQ\FilterQ;

$qb = $entityManager->createQueryBuilder()
    ->select('p')
    ->from(Post::class, 'p');

$qb = FilterQ::expression('id=100|slug=hello')
    ->queryBuilder($qb)
    ->keys(function ($keys) {
        $keys->add('id')->column('p.id');
        $keys->add('slug')->column('p.slug');
    })
    ->addWhere();

$posts = $qb->getQuery()->getResult();
```

The `FilterQ::expression()` static method is the entry point. The chain must end with `addWhere()`, which adds WHERE conditions to the QueryBuilder and returns it.

### 1. Setting the Expression and QueryBuilder

```php
$qb = $em->createQueryBuilder()->select('p')->from(Post::class, 'p');

FilterQ::expression($request->query->get('filter'))
    ->queryBuilder($qb)
    // ...
```

`addWhere()` returns the Doctrine ORM `QueryBuilder` so you can continue chaining:

```php
$posts = FilterQ::expression(...)
    ->queryBuilder($qb)
    ->keys(...)
    ->addWhere()
    ->setMaxResults(25)
    ->addOrderBy('p.id', 'DESC')
    ->getQuery()
    ->getResult();
```

### 2. Set Keys

Define all keys the user is allowed to filter on. This prevents SQL injection — only the columns you explicitly allow can be used in expressions.

```php
->keys(function ($keys) {
    $keys->add('id')->column('p.id');
    $keys->add('slug')->column('p.slug');
})
```

- `$keys->add($key)` registers a key and returns a `Key` object for further configuration.
- `Key::column()` sets the DQL column reference. Defaults to the key name if not set. **Always include the entity alias** (e.g., `p.id`, not just `id`).
- `Key::join()` sets a [join callback](#joins).
- `Key::operators()` sets [allowed operators](#key-operators).
- `Key::valueType()` defines [supported value types](#key-value-types).
- `Key::values()` defines [supported values](#key-values).

## Joins

To filter on a related entity's field, use a join callback. The callback receives the QueryBuilder.

```php
FilterQ::expression('author.name=hyvor')
    ->queryBuilder($qb)
    ->keys(function ($keys) {
        $keys->add('author.name')
            ->column('a.name')
            ->join(function ($qb) {
                $qb->leftJoin('p.author', 'a');
            });
    })
    ->addWhere();
```

> Even if the same key appears multiple times in the expression, the join callback is only called once.

## Key Operators

Restrict which operators are allowed for a key.

```php
->keys(function ($keys) {
    // only allow these operators
    $keys->add('id')->operators('=,>,<');

    // or use an array
    $keys->add('slug')->operators(['=', '!=']);

    // exclude specific operators
    $keys->add('age')->operators('>', true);
})
```

## Key Value Types

Define the expected type for a key's value. Highly recommended for security and data integrity.

```php
->keys(function ($keys) {
    $keys->add('id')->valueType('integer');
    $keys->add('name')->valueType('string');
    $keys->add('description')->valueType('string|null');
    $keys->add('created_at')->valueType('date');
})
```

Supported types:

Scalar: `int`, `float`, `string`, `null`, `bool`

Special:

- `numeric` — int, float, or numeric string
- `date` — a valid date/time string or Unix timestamp. Returns a `\DateTimeImmutable`. (Uses PHP's `strtotime()`, so relative dates like `"-7 days"` are supported.)

Multiple types can be combined with `|` or as an array:

```php
$keys->add('created_at')->valueType('date|null');
// or
$keys->add('created_at')->valueType(['date', 'null']);
```

## Key Values

Restrict a key to a specific set of allowed values. Useful for enum columns.

```php
->keys(function ($keys) {
    $keys->add('status')->values(['published', 'draft']);
    $keys->add('id')->values(200);
})
```

## Custom Operators

Add custom DQL operators. The callback receives the QueryBuilder, an auto-generated parameter name, and the value. It must bind the parameter and return the DQL expression string.

```php
FilterQ::expression("title~'Hello%'")
    ->queryBuilder($qb)
    ->keys(function ($keys) {
        $keys->add('title')->column('p.title');
    })
    ->operators(function ($operators) {
        $operators->add('~', 'LIKE');
    })
    ->addWhere();
```

For more complex cases, use a callback:

```php
->operators(function ($operators) {
    $operators->add('!', function ($qb, string $paramName, mixed $value): string {
        $qb->setParameter($paramName, $value);
        return 'MATCH (p.title) AGAINST (:' . $paramName . ')';
    });
})
```

The callback signature is `(QueryBuilder $qb, string $paramName, mixed $value): string`. It should:

1. Bind the value with `$qb->setParameter($paramName, $value)` if needed.
2. Return a DQL expression string.

## Removing an Operator

```php
->operators(function ($operators) {
    $operators->remove('>');
})
```

## Exception Handling

FilterQ throws three exceptions, all extending `FilterQException`:

- `Hyvor\FilterQ\Exceptions\FilterQException` — base exception
- `Hyvor\FilterQ\Exceptions\ParserException` — invalid expression syntax
- `Hyvor\FilterQ\Exceptions\InvalidValueException` — value fails key value/type validation

```php
use Hyvor\FilterQ\Exceptions\FilterQException;

try {
    $qb = FilterQ::expression($filter)
        ->queryBuilder($qb)
        ->keys(...)
        ->addWhere();
} catch (FilterQException $e) {
    // $e->getMessage() is safe to display to API consumers
}
```
