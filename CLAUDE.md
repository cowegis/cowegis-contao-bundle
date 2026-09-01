# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this bundle is

`cowegis/cowegis-contao-bundle` is the Contao integration for the Cowegis ("Content Web GIS") mapping
stack. It is **not** self-contained:

- `cowegis/cowegis-core` – framework-agnostic map *definition* model (`Map`, `Layer`, `Control`,
  `Pane`, presets) plus the `Provider`, `Hydrator` (core), `Serializer` and `Schema` contracts.
- `cowegis/cowegis-api-bundle` – serves the map/layer JSON API and dispatches response events. The
  ContaoManager `Plugin` registers this bundle and re-exposes the api-bundle routes under the
  `cowegis_api.route_prefix` parameter.
- This bundle supplies the Contao backend UI (DCA, backend modules), the Doctrine/Contao data models,
  and the code that turns those models into `cowegis-core` definitions.

PHP `^8.2`; supports Contao `^5.3` and Symfony `6.4 / 7.4`. CI runs on PHP 8.2, 8.3, 8.4.

## Commands

QA is orchestrated by **phpcq** (`.phpcq.yaml.dist`), not composer scripts. First run needs the tool
chain installed:

```bash
vendor/bin/phpcq update          # install/update the phpcq plugin toolchain (once / after config change)
vendor/bin/phpcq run             # full default chain: verify + analyze
vendor/bin/phpcq run -o default  # same, explicit (what CI runs)
vendor/bin/phpcq run psalm       # single tool: psalm | phpcs | rector | phpmd | phpcpd | phpspec | ...
vendor/bin/phpcq run fix         # auto-fix: composer-normalize + rector + phpcbf
```

Use the `phpcq` skill for more details.

Tools in the chain: `psalm` (errorLevel 3), `phpcs` (Doctrine Coding Standard, 120-col limit),
`rector` (dead code / type declarations / privatization / strict types – see `rector.php`), `phpmd`,
`phpcpd`, `phploc`, `composer-require-checker`, `composer-normalize`, `phpspec`, `phpunit`.

Tests use **phpspec** (specs in `spec/`, namespace `spec\Cowegis\Bundle\Contao`):

```bash
vendor/bin/phpspec run                               # all specs
vendor/bin/phpspec run spec/CowegisContaoBundleSpec.php   # single spec
```

PHPUnit runs alongside phpspec (DI / service-config tests), tests in `tests/`,
namespace `Cowegis\Bundle\Contao\Test`:

```bash
vendor/bin/phpcq run phpunit
vendor/bin/phpunit tests/DependencyInjection        # single suite
```

## Architecture

### Model → definition flow (the core of the bundle)

`cowegis-api-bundle` asks `ContaoBackendProvider` (`src/Provider/`, implements
`Cowegis\Core\Provider\Provider`) for a map. It:

1. Loads the `MapModel` (`tl_cowegis_map`), creates an empty core `Map` definition.
2. Runs the **hydrator chain** to populate it.
3. For layer data (marker features etc.) resolves a tagged `LayerDataProvider` by layer type.

### Hydrator chain

`DelegatingHydrator` (`src/Hydrator/`) holds all services tagged
`Cowegis\Bundle\Contao\Hydrator\Hydrator`. For each `(data, definition)` pair it calls every
hydrator whose `supports()` returns true, then `hydrate($data, $definition, $context, $hydrator)` –
the last arg is the delegating hydrator itself, so hydrators recurse (e.g. `MapHydrator` hydrates the
map, then hands each layer/control/preset back to the chain).

- `MapHydrator` – entry point for `MapModel` → `Map`: panes, layers, controls, locate, icon/popup/
  tooltip presets, assets, view.
- `EventDispatchingHydrator` (tag priority `-128`, runs last) dispatches `HydrateEvent` so other
  bundles / listeners can extend hydration without a new service.
- Hydrators that produce cacheable output pull in `netzmacht.contao_toolkit.response_tagger` (via
  `ResponseTaggerPlugin`) to tag the HTTP response for cache invalidation.

### Type + Registry + Hydrator pattern

Layers, controls, icons and styles each follow the same three-part shape:

| Concept | Interface | Registry | Example dir |
|---|---|---|---|
| Layer | `Map\Layer\LayerType` | `LayerTypeRegistry` | `src/Map/Layer/Tile/` |
| Control | `Map\Control\ControlType` | `ControlTypeRegistry` | `src/Map/Control/Zoom/` |
| Icon | `Map\Icon\IconType` | `IconTypeRegistry` | `src/Map/Icon/Svg/` |
| Style | `Map\Style\StyleType` | `StyleTypeRegistry` | `src/Map/Style/Fixed/` |

A `*Type` is tagged (e.g. `Cowegis\Bundle\Contao\Map\Layer\LayerType`), collected into its registry
keyed by `name()`, and builds an empty core definition from the model (`createDefinition()`); a
sibling `*Hydrator` (tagged as a `Hydrator`) fills that definition in. The `*Type` also drives the
Contao backend (`label()`, `iconUrl()`, option lists via the DCA listeners).

**Adding a layer type**: new `FooLayerType` + `FooLayerHydrator` in `src/Map/Layer/Foo/`, register
both in `src/Resources/config/layers.yaml` (the `LayerType` / `Hydrator` tags come automatically from
`registerForAutoconfiguration` — just wire the constructor arguments), and usually add the matching
`cowegis-core` `SchemaDescriber` (string arg + `_instanceof` tag) / `Serializer`
(`@Cowegis\Core\Serializer\Serializer` arg + explicit `key` tag) service entries there too.

### Service wiring

`CowegisContaoExtension` loads `src/Resources/config/*.yaml` **explicitly and in order** via
`YamlFileLoader`. Autowiring is **off** in every file (`_defaults: { autowire: false }`) — wire every
constructor argument by hand. Autoconfiguration is **on** by default; the five marker-interface tags
(`Hydrator`, `LayerType`, `ControlType`, `IconType`, `StyleType`) are attached via
`registerForAutoconfiguration()` calls at the top of `CowegisContaoExtension::load()`.
`autoconfigure: false` is set locally where it would double-tag or self-reference: the
`DelegatingHydrator` collector service, the priority hydrators (`LocateOptionsHydrator`,
`BoundsOptionsHydrator`, `EventDispatchingHydrator`), `ConsentBridge\Plugin`, and all of
`repositories.yaml`.

Contao/Symfony behaviour tags come from PHP attributes on the classes: `#[AsCallback]` (DCA listeners
in `src/EventListener/Dca/`), `#[AsEventListener]` (menu / response / filter listeners), `#[AsHook]`
(`LanguageFileListener`), `#[AsContentElement]` / `#[AsFrontendModule]` (fragment actions).

Tags that stay explicit in YAML: `Cowegis\Core\Serializer\Serializer` (`key`),
`netzmacht.contao_toolkit.repository` (`model`), `Cowegis\Bundle\Contao\Provider\LayerDataProvider`
(`type`), `Cowegis\Core\Schema\LayerSchemaDescriber` (via `_instanceof` in `layers.yaml`), and the
singletons `Cowegis\Core\Provider\Provider` / `…\Schema\SchemaDescriber` / `…\IdFormat\IdFormat` /
`…\Schema\IdSchema`.

### Contao backend

- DCA definitions: `src/Resources/contao/dca/tl_cowegis_*.php` (`map`, `map_layer`, `map_pane`,
  `layer`, `marker`, `control`, `control_layer`, `icon`, `style`, `popup`, `tooltip`); the bundle
  also extends `tl_content` and `tl_module`.
- DCA callbacks live in `src/EventListener/Dca/*Listener` (Toolkit `AbstractListener`; `getName()`
  returns the table it binds to). Registered via `#[AsCallback]` on the callback methods; the
  services are wired in `src/Resources/config/listeners.yaml`.
- Backend modules are injected into `$GLOBALS['BE_MOD']` in `src/Resources/contao/config/config.php`
  (`cowegis_map`, `cowegis_layer`, `cowegis_presets`).
- `tl_cowegis_map_layer` is the junction between a map and a reusable `tl_cowegis_layer`, carrying
  per-map settings (pane, initial visibility). `MapLayerModel::layerModel()` gives the underlying
  layer; `MapLayerContext` wraps the core `Context` with that per-map state.

### Frontend rendering

`MapFragmentAction` (abstract, extends Toolkit `AbstractHybridController`) renders the map container
and a `mapUri` pointing at the `cowegis_api_map` route (filters derived from the request). Concrete
entry points: `MapContentElementAction` (`#[AsContentElement('cowegis_map')]`) and
`MapModuleAction`. Client JS/CSS is chosen from `cowegis_client` (`bundles/cowegisclient/...` or a
custom file).

### Response caching

`MapResponseListener` / `LayerResponseListener` subscribe to `cowegis-api-bundle` response events and
set `setPublic()` / `setMaxAge()` from the model's `cache` / `cacheLifeTime` fields.

## Conventions

- `declare(strict_types=1)` everywhere (rector-enforced); `#[Override]` on all interface/parent
  implementations; most classes are `final`.
- Contao model magic-property access (`$model->cowegis_map`) trips phpcs
  `Squiz.NamingConventions.ValidVariableName` and psalm — the codebase uses targeted
  `// phpcs:disable ...` / `@psalm-suppress` / `psalm.xml` `referencedProperty` entries rather than
  loosening the rules. Follow the existing local-suppression style.
- `src/Resources/contao/dca` and `.../languages` are excluded from psalm and (languages) from phpcs.
- `minimum-stability: dev` — some dev tooling (`netzmacht/phpspec-phpcq-plugin`) is pulled as `@dev`.
