# Sorting

Collections returned by `ApiSearchInterface::apiFindBy()` are ordered through the `order`
query parameter.

## Query parameter

```
GET /api/books?order=title                  title ascending
GET /api/books?order=title:desc             title descending
GET /api/books?order=author:desc,title      several keys, applied in order
GET /api/books?order=author.name:desc       dots cross relations
```

Each key may be suffixed with `:asc` or `:desc`. Without a suffix the key uses its
declared default direction, ascending otherwise. An unreadable direction is ignored
rather than rejected.

> The separate `direction` parameter still works but is **deprecated**: it applies to
> every key carrying no suffix of its own. Use `order=field:direction` instead.

## Everything is sortable until you say otherwise

An entity declaring no `#[ApiSortable]` keeps the historical behaviour: any property
path is accepted, and an unknown one ends up as a `400`. Nothing to do to keep an
existing API working.

Declaring a single sortable turns the whitelist on for that entity: every other key is
then refused with a `400` listing what is allowed.

## Declaring sortables

On a property, the key and the path both default to the property name:

```php
use GollumSF\RestBundle\Attribute\ApiSortable;

class Book {

	#[ApiSortable]
	private string $title;                          // ?order=title

	#[ApiSortable(key: 'author', path: 'author.name')]
	private Author $author;                         // ?order=author  ->  LEFT JOIN + author.name

	private string $secret;                         // never sortable
}
```

`path` may cross several relations, `author.country.name`. The joins are generated after
the count query, and two keys pointing at the same relation share a single join. A
relation already joined by the `queryCallback` is reused rather than joined twice.

On the class, for keys backed by no property:

```php
#[ApiSortable('popularity', sorter: PopularitySorter::class)]
#[ApiSortable('createdAt', direction: Direction::DESC)]
class Book { /* ... */ }
```

## Narrowing per endpoint

The entity declares the catalogue; an action may restrict it. Naming a key alone keeps
the entity definition, while giving a `path`, a `sorter` or a `direction` overrides it:

```php
#[Route('', methods: ['GET'])]
#[ApiSortable('title')]
#[ApiSortable('author', path: 'author.country.name')]
public function list(ApiSearchInterface $apiSearch) {
	return $apiSearch->apiFindBy(Book::class);
}
```

An action declaring nothing inherits the whole catalogue of the entity.

## Custom sorting

When no property path expresses the ordering, write a sorter:

```php
use GollumSF\RestBundle\Sort\ApiSorterInterface;

class PopularitySorter implements ApiSorterInterface {

	public function apply(QueryBuilder $queryBuilder, string $rootAlias, Direction $direction): void {
		$queryBuilder
			->leftJoin($rootAlias.'.comments', 'c')
			->addSelect('COUNT(c.id) AS HIDDEN popularity')
			->groupBy($rootAlias.'.id')
			->addOrderBy('popularity', $direction->value)
		;
	}
}
```

Implementing the interface is enough, autoconfiguration tags it. Reference it by class
name from the attribute:

```php
#[ApiSortable('popularity', sorter: PopularitySorter::class)]
```

## Going further

The resolution itself is a chain of handlers, tagged
`gollumsf.rest.sort_resolver.handler` and tried by descending priority. Add your own to
resolve keys none of the shipped handlers know about:

```php
use GollumSF\RestBundle\Sort\Handler\HandlerInterface;

class MySortHandler implements HandlerInterface {
	public function getOrder(ApiSortContext $context, string $key, ?Direction $direction): ?ApiOrder {
		// return an ApiOrder to own the key, null to pass it on
	}
}
```

```yaml
services:
    App\Sort\MySortHandler:
        tags:
            - { name: gollumsf.rest.sort_resolver.handler, priority: 200 }
```

## Static lists

`ApiSearchInterface::staticArrayList()` answers the same `order` syntax, keys and
directions included, walking the property path through the `get`/`has`/`is` accessors:

```
GET /api/things?order=author.name:desc,id
```

A list built by hand, `new StaticArrayApiList($data, $request)`, understands the syntax
too; going through `staticArrayList()` additionally applies the `#[ApiSortable]`
declarations of the class of its items.

Its second argument replaces the comparison of two values of the same key, the third one
switches to replacing the whole comparison:

```php
// compares two values of the sorted key
$apiSearch->staticArrayList($data, function ($valueA, $valueB, $objA, $objB, $order) { ... });

// compares two items, key and direction in hand
$apiSearch->staticArrayList($data, function ($objA, $objB, $order, $direction) { ... }, true);
```

## Custom repositories

`ApiFinderRepositoryTrait` implements the ordering. A repository writing `apiFindBy()`
by hand keeps working but only receives the first key; implement
`ApiFinderOrderRepositoryInterface::apiFindByOrder()` to get the whole collection.
