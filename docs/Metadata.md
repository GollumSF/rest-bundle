# Declaring metadata in configuration

`#[Serialize]`, `#[Unserialize]`, `#[Validate]` and `#[ApiSortable]` can also be declared
under `gollum_sf_rest.metadata`, which is the only way to describe a controller or an
entity you cannot annotate — one coming from a third party bundle, for instance.

```yaml
gollum_sf_rest:
    metadata:

        controllers:
            App\Controller\BookController::post:
                serialize:   { code: 201, groups: [book_get] }
                unserialize: { name: book, groups: [book_post] }
                validate:    { groups: [book_post] }

            App\Controller\BookController::list:
                serialize: { groups: book_getc }
                sortable:
                    title: ~

        entities:
            App\Entity\Book:
                sortable:
                    title: ~
                    author:     { path: author.name }
                    popularity: { sorter: App\Sort\PopularitySorter, direction: DESC }
```

Controllers are keyed by `Fully\Qualified\Controller::action`, entities by class name.
Declare only the sections you need: an omitted one leaves the attributes in charge.

## Configuration wins

A manager stops on the first handler answering, and the configured handler is walked
before the attribute one. So a declaration in configuration **overrides** the attribute
carried by the code, for the same action or the same entity.

Beware that the override applies to a whole section: declaring `sortable` for an entity
replaces its entire catalogue, it does not add to it.

## The sections

`serialize` — `code` (default `200`), `groups`, `headers`.

`unserialize` — `name`, `groups`, `save` (default `true`), `type`. Without an explicit
`type`, the target class is the type of the action parameter named after `name`, exactly
like the attribute.

`validate` — `groups` (default `['Default']`).

`sortable` — keyed by sort key, each taking `path`, `sorter` and `direction`. Under
`entities` it declares the catalogue; under `controllers` it narrows the catalogue of the
entity down for that action, following the same rules as
[the attributes](Sorting.md#narrowing-per-endpoint).

`groups` accepts a single group as well as a list, like the attributes do.

## Adding a source of your own

Every manager collects its handlers from a tag, walked by ascending priority:

| Manager | Tag |
|---|---|
| Serialize | `gollumsf.rest.metadata.serialize_builder.handler` |
| Unserialize | `gollumsf.rest.metadata.unserialize_builder.handler` |
| Validate | `gollumsf.rest.metadata.validate_builder.handler` |
| Sort | `gollumsf.rest.metadata.sort_builder.handler` |

The shipped handlers sit at `-10` for the configuration and `0` for the attributes;
register yours below `-10` to take precedence over both.

> The validate tag used to be capitalised, `...metadata.Validate_builder.handler`.
> Services still carrying it are collected, but it is deprecated: prefer
> `MetadataValidateManagerInterface::HANDLER_TAG`.
