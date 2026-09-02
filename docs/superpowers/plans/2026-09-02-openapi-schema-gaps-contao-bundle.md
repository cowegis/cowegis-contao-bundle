# cowegis-contao-bundle – OpenAPI schema gaps Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Register the control schema describers (so `controls` stops being empty), add the Overpass layer describer, and correct this bundle's own `LayersSchemaDescriber` (envelope, `mapId` param, the missing `vectors` data path).

**Architecture:** This bundle contributes to the dynamic schema in two ways: (1) it *registers + tags* core's `LayerSchemaDescriber` / `ControlSchemaDescriber` classes with the type-name string, via `layers.yaml` / `controls.yaml`; (2) it ships its own `SchemaDescriber` services under `src/Schema/`. So: add a `_instanceof` + seven service entries in `controls.yaml`, one entry in `layers.yaml` for Overpass, and rework `src/Schema/LayersSchemaDescriber.php`. Wiring is verified with phpunit DI tests (the established pattern in `tests/DependencyInjection/`); the describer behaviour with a new phpunit test.

**Tech Stack:** PHP `^8.3`, Contao `^5.3`, Symfony `^6.4 || ^7.4`, `cowegis/cowegis-core`, `cowegis/cowegis-api-bundle`, `goldspecdigital/oooas` `^2.8`, phpspec + phpunit, phpcq (`.phpcq.yaml.dist`).

**Spec:** `2026-09-02-openapi-schema-gaps-findings.md` (same directory) — this plan covers **G1** (registration side), **G4** (registration side), **G5**, **G7** (this bundle's describer), and the **G9** stretch.

## Global Constraints

- `declare(strict_types=1);` everywhere (rector-enforced); `#[Override]` on all interface/parent implementations; classes `final` unless a pattern says otherwise.
- Autowiring is **off** in every `src/Resources/config/*.yaml` (`_defaults: { autowire: false }`) — wire every constructor argument by hand. Autoconfiguration is **on**.
- The five marker-interface tags are attached via `registerForAutoconfiguration()` in `CowegisContaoExtension`. `Cowegis\Core\Schema\LayerSchemaDescriber` and `Cowegis\Core\Schema\ControlSchemaDescriber` are **not** among them — they are tagged with an `_instanceof` block inside the YAML file (see the existing `layers.yaml` block for `LayerSchemaDescriber`).
- phpcs: Doctrine Coding Standard, 120 cols. psalm errorLevel 3. `src/Resources/contao/{dca,languages}` are psalm/phpcs-excluded (not touched here).
- **Depends on** the `cowegis-core` and `cowegis-api-bundle` plans being merged/tagged first. This plan bumps both constraints.
- Full check before "done": `vendor/bin/phpcq run` (runs psalm, phpcs, rector, phpmd, phpcpd, phpspec, phpunit).

---

## File Structure

| File | Responsibility |
|---|---|
| `composer.json` (modify) | Bump `cowegis/cowegis-core` and `cowegis/cowegis-api-bundle`. |
| `src/Resources/config/controls.yaml` (modify) | `_instanceof` for `ControlSchemaDescriber` + 7 service entries (one per core control describer), each with its type-name string arg. |
| `src/Resources/config/layers.yaml` (modify) | One entry: `Cowegis\Core\Schema\Layer\OverpassLayerSchemaDescriber` with `['overpass']`. |
| `src/Schema/LayersSchemaDescriber.php` (modify) | `{definitionId}` → `{mapId}`; wrap the `200` body in `{data,assets}`; add the `/map/{mapId}/vectors/{layerId}` path; add `404`. |
| `tests/DependencyInjection/CowegisContaoExtensionTest.php` (modify) | Assertions: 7 control describers tagged + argument strings; Overpass describer tagged. |
| `tests/Schema/LayersSchemaDescriberTest.php` (new) | Behaviour: paths, envelope, params, responses. |
| `src/Schema/Preset/IconsSchemaDescriber.php` + `StylesSchemaDescriber.php` (new, **stretch** Task 6) | G9 — describe the `presets.icons` / `presets.styles` shapes from the registered `IconType` / `StyleType` names. |

---

## Task 1: Bump the upstream constraints

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Bump both packages**

- `"cowegis/cowegis-core"` → the release shipping `Cowegis\Core\Schema\ControlSchemaDescriber` (new shape), `Cowegis\Core\Schema\Control\*SchemaDescriber`, `Cowegis\Core\Schema\Layer\OverpassLayerSchemaDescriber`, `Cowegis\Core\Schema\AssetSchema`, `Cowegis\Core\Schema\Error\ProblemResponses`.
- `"cowegis/cowegis-api-bundle"` → the release that tags `ErrorSchemaDescriber` and resolves `info.version`.

While iterating locally, use path repositories for both.

- [ ] **Step 2: Update**

Run: `composer update cowegis/cowegis-core cowegis/cowegis-api-bundle --with-dependencies`

- [ ] **Step 3: Sanity check the new classes autoload**

Run:
```bash
php -r "require 'vendor/autoload.php'; foreach ([
  'Cowegis\\Core\\Schema\\Control\\ZoomControlSchemaDescriber',
  'Cowegis\\Core\\Schema\\Layer\\OverpassLayerSchemaDescriber',
  'Cowegis\\Core\\Schema\\AssetSchema',
  'Cowegis\\Core\\Schema\\Error\\ProblemResponses',
] as \$c) { echo \$c, ': ', class_exists(\$c) ? 'ok' : 'MISSING', PHP_EOL; }"
```
Expected: all `ok`.

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock
git commit -m "build: require the cowegis-core/api-bundle releases with the schema fixes"
```

---

## Task 2: Register the seven control schema describers

**Files:**
- Modify: `src/Resources/config/controls.yaml`
- Test: `tests/DependencyInjection/CowegisContaoExtensionTest.php`

**Interfaces:**
- Consumes: core's `Cowegis\Core\Schema\Control\{Zoom,Scale,Attribution,Fullscreen,Geocoder,Layers,Loading}ControlSchemaDescriber` — each extends the abstract `Cowegis\Core\Schema\ControlSchemaDescriber` and takes a single string constructor arg (the control type name).
- Produces: seven services tagged `Cowegis\Core\Schema\ControlSchemaDescriber`, which core's `MapSchemaDescriber` consumes via `!tagged_iterator { tag: Cowegis\Core\Schema\ControlSchemaDescriber }` (wired in `cowegis-api-bundle`'s `schema.yaml`).

- [ ] **Step 1: Write the failing DI assertions**

In `CowegisContaoExtensionTest.php`, add a tag constant next to `LAYER_SCHEMA_TAG`:

```php
    private const string CONTROL_SCHEMA_TAG = 'Cowegis\Core\Schema\ControlSchemaDescriber';
```

and a test:

```php
    public function testControlSchemaDescribersAreRegistered(): void
    {
        $container = self::compiledContainer();

        $tagged = array_keys($container->findTaggedServiceIds(self::CONTROL_SCHEMA_TAG));
        sort($tagged);

        self::assertSame(
            [
                'Cowegis\Core\Schema\Control\AttributionControlSchemaDescriber',
                'Cowegis\Core\Schema\Control\FullscreenControlSchemaDescriber',
                'Cowegis\Core\Schema\Control\GeocoderControlSchemaDescriber',
                'Cowegis\Core\Schema\Control\LayersControlSchemaDescriber',
                'Cowegis\Core\Schema\Control\LoadingControlSchemaDescriber',
                'Cowegis\Core\Schema\Control\ScaleControlSchemaDescriber',
                'Cowegis\Core\Schema\Control\ZoomControlSchemaDescriber',
            ],
            $tagged,
        );

        self::assertSame(
            ['zoom'],
            $container->getDefinition('Cowegis\Core\Schema\Control\ZoomControlSchemaDescriber')->getArguments(),
        );
        self::assertSame(
            ['layers'],
            $container->getDefinition('Cowegis\Core\Schema\Control\LayersControlSchemaDescriber')->getArguments(),
        );
    }
```

- [ ] **Step 2: Run it, verify it fails**

Run: `vendor/bin/phpunit --filter testControlSchemaDescribersAreRegistered`
Expected: FAIL — no tagged services.

- [ ] **Step 3: Add the `_instanceof` block to `controls.yaml`**

Directly under `_defaults:` in `src/Resources/config/controls.yaml`:

```yaml
    _instanceof:
        Cowegis\Core\Schema\ControlSchemaDescriber:
            tags:
                - 'Cowegis\Core\Schema\ControlSchemaDescriber'
```

- [ ] **Step 4: Add the seven service entries to `controls.yaml`**

Place each next to the matching control-type block (or grouped at the end under a `# Control schema describers` comment):

```yaml
    Cowegis\Core\Schema\Control\ZoomControlSchemaDescriber:
        arguments: ['zoom']

    Cowegis\Core\Schema\Control\ScaleControlSchemaDescriber:
        arguments: ['scale']

    Cowegis\Core\Schema\Control\AttributionControlSchemaDescriber:
        arguments: ['attribution']

    Cowegis\Core\Schema\Control\FullscreenControlSchemaDescriber:
        arguments: ['fullscreen']

    Cowegis\Core\Schema\Control\GeocoderControlSchemaDescriber:
        arguments: ['geocoder']

    Cowegis\Core\Schema\Control\LayersControlSchemaDescriber:
        arguments: ['layers']

    Cowegis\Core\Schema\Control\LoadingControlSchemaDescriber:
        arguments: ['loading']
```

- [ ] **Step 5: Run the test, verify PASS**

Run: `vendor/bin/phpunit --filter testControlSchemaDescribersAreRegistered`
Run: `vendor/bin/phpunit tests/DependencyInjection`
Expected: PASS, no regressions.

- [ ] **Step 6: Commit**

```bash
git add src/Resources/config/controls.yaml tests/DependencyInjection/CowegisContaoExtensionTest.php
git commit -m "feat(schema): register control schema describers so controls stop being empty"
```

---

## Task 3: Register the Overpass layer schema describer

**Files:**
- Modify: `src/Resources/config/layers.yaml`
- Test: `tests/DependencyInjection/CowegisContaoExtensionTest.php`

**Interfaces:**
- Consumes: core `Cowegis\Core\Schema\Layer\OverpassLayerSchemaDescriber` (extends `LayerSchemaDescriber`, one string arg).
- Produces: a service tagged `Cowegis\Core\Schema\LayerSchemaDescriber` (via the existing `_instanceof` block in `layers.yaml`).

- [ ] **Step 1: Add the failing assertion**

In `CowegisContaoExtensionTest.php` (`LAYER_SCHEMA_TAG` already exists):

```php
    public function testOverpassLayerSchemaDescriberIsRegistered(): void
    {
        $container = self::compiledContainer();

        $tagged = array_keys($container->findTaggedServiceIds(self::LAYER_SCHEMA_TAG));

        self::assertContains('Cowegis\Core\Schema\Layer\OverpassLayerSchemaDescriber', $tagged);
        self::assertSame(
            ['overpass'],
            $container->getDefinition('Cowegis\Core\Schema\Layer\OverpassLayerSchemaDescriber')->getArguments(),
        );
    }
```

- [ ] **Step 2: Run it, verify it fails**

Run: `vendor/bin/phpunit --filter testOverpassLayerSchemaDescriberIsRegistered`
Expected: FAIL.

- [ ] **Step 3: Add the entry to `layers.yaml`**

In the `# Overpass layer` section of `src/Resources/config/layers.yaml`, next to `OverpassLayerType` / `OverpassLayerHydrator`:

```yaml
    Cowegis\Core\Schema\Layer\OverpassLayerSchemaDescriber:
        arguments:
            - 'overpass'
```

- [ ] **Step 4: Run the test + suite, verify PASS**

Run: `vendor/bin/phpunit --filter testOverpassLayerSchemaDescriberIsRegistered`
Run: `vendor/bin/phpunit tests/DependencyInjection`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Resources/config/layers.yaml tests/DependencyInjection/CowegisContaoExtensionTest.php
git commit -m "feat(schema): register OverpassLayerSchemaDescriber"
```

---

## Task 4: Fix `LayersSchemaDescriber` — envelope, `mapId`, the `vectors` path, `404`

**Files:**
- Modify: `src/Schema/LayersSchemaDescriber.php`
- Test: `tests/Schema/LayersSchemaDescriberTest.php` (new; phpunit — this bundle has no `phpspec-expect` helper)

**Interfaces:**
- Consumes: `Cowegis\Core\Schema\SchemaBuilder`, `Cowegis\Core\Schema\GeoData\GeoDataSchema::FULL_REF`, `Cowegis\Core\Schema\AssetSchema::FULL_REF` (registered by core `MapSchemaDescriber`), `Cowegis\Core\Schema\Error\ProblemResponses::notFound()` (its `Error` component is registered by `ErrorSchemaDescriber`, wired in the api-bundle).
- Produces: two enveloped paths — `GET /map/{mapId}/data/{layerId}` and `GET /map/{mapId}/vectors/{layerId}` — `200` body `{data: $ref GeoData, assets: [$ref Asset]}` (component `LayerDataResponse`), `404` from `ProblemResponses::notFound()`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Test\Schema;

use Cowegis\Bundle\Contao\Schema\LayersSchemaDescriber;
use Cowegis\Core\Schema\Id\IntegerIdSchema;
use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Info;
use PHPUnit\Framework\TestCase;

final class LayersSchemaDescriberTest extends TestCase
{
    private static function build(): array
    {
        $builder = SchemaBuilder::create(
            Info::create()->title('t')->version('1'),
            new IntegerIdSchema(),
        );

        (new LayersSchemaDescriber())->describe($builder);

        return $builder->build()->toArray();
    }

    public function testDescribesBothDataPathsWithMapIdParam(): void
    {
        $paths = self::build()['paths'];

        self::assertArrayHasKey('/map/{mapId}/data/{layerId}', $paths);
        self::assertArrayHasKey('/map/{mapId}/vectors/{layerId}', $paths);
        self::assertArrayNotHasKey('/map/{definitionId}/data/{layerId}', $paths);

        $params = array_column($paths['/map/{mapId}/data/{layerId}']['get']['parameters'], 'name');
        self::assertSame(['mapId', 'layerId'], $params);
    }

    public function testWrapsDataInAnAssetsEnvelopeAndDefinesA404(): void
    {
        $doc = self::build();

        self::assertArrayHasKey('LayerDataResponse', $doc['components']['schemas']);
        $props = $doc['components']['schemas']['LayerDataResponse']['properties'];
        self::assertArrayHasKey('data', $props);
        self::assertArrayHasKey('assets', $props);

        $responses = $doc['paths']['/map/{mapId}/data/{layerId}']['get']['responses'];
        self::assertArrayHasKey(200, $responses);
        self::assertArrayHasKey(404, $responses);
    }
}
```

- [ ] **Step 2: Run it, verify it fails**

Run: `vendor/bin/phpunit tests/Schema/LayersSchemaDescriberTest.php`
Expected: FAIL — path is `/map/{definitionId}/data/{layerId}`, no envelope, no `404`.

- [ ] **Step 3: Rewrite `LayersSchemaDescriber::describe()`**

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Schema;

use Cowegis\Core\Schema\AssetSchema;
use Cowegis\Core\Schema\Error\ProblemResponses;
use Cowegis\Core\Schema\GeoData\GeoDataSchema;
use Cowegis\Core\Schema\SchemaBuilder;
use Cowegis\Core\Schema\SchemaDescriber;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Tag;
use Override;

final class LayersSchemaDescriber implements SchemaDescriber
{
    private const array DATA_TYPES = ['data', 'vectors'];

    #[Override]
    public function describe(SchemaBuilder $builder): void
    {
        $envelope = $builder->components()->withSchema(
            Schema::object('LayerDataResponse')
                ->description('Deferred layer data plus the assets required to render it')
                ->required('data', 'assets')
                ->properties(
                    Schema::ref(GeoDataSchema::FULL_REF, 'data'),
                    Schema::array('assets')->items(Schema::ref(AssetSchema::FULL_REF)),
                ),
        );

        $response = Response::ok('Deferred layer data')
            ->content(MediaType::json()->schema($envelope));

        $tag = Tag::create()->name('Layer data');

        foreach (self::DATA_TYPES as $type) {
            $operation = Operation::get()
                ->description('Deferred feature data for a single layer of a map')
                ->summary('Show layer data')
                ->parameters(
                    Parameter::path()->name('mapId')->schema($builder->idSchemaRef())->required(),
                    Parameter::path()->name('layerId')->schema($builder->idSchemaRef())->required(),
                )
                ->tags($tag)
                ->responses($response, ProblemResponses::notFound());

            $builder->withPathItem(
                (new PathItem())
                    ->route('/map/{mapId}/' . $type . '/{layerId}')
                    ->operations($operation),
            );
        }
    }
}
```

> `private const array` needs PHP 8.3 — this bundle already requires `^8.3`, and
> `StubContainerFactory` uses the same syntax.

- [ ] **Step 4: Run the test + suite, verify PASS**

Run: `vendor/bin/phpunit tests/Schema/LayersSchemaDescriberTest.php`
Run: `vendor/bin/phpunit`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Schema/LayersSchemaDescriber.php tests/Schema/LayersSchemaDescriberTest.php
git commit -m "fix(schema): envelope + mapId param + vectors path + 404 for layer data"
```

---

## Task 5: Full QA + verify against the live schema

**Files:** none (verification only) — plus `CHANGELOG.md` if kept.

- [ ] **Step 1: Run the whole QA chain**

Run: `vendor/bin/phpcq run`
Expected: psalm / phpcs / rector / phpmd / phpcpd / phpspec / phpunit all green. Likely fixes: `#[Override]` on `LayersSchemaDescriber::describe()` (already present), import order, `array_column`/`array_keys` imports in the test (`use function array_column; use function array_keys;`).

- [ ] **Step 2: Regenerate and eyeball the schema**

With the ddev site running:

```bash
curl -s https://cowegis.ddev.site/cowegis/docs/schema.json | python3 -m json.tool | less
```

Confirm:
- `components.schemas.MapSchema.properties.controls.items` is a non-empty `oneOf` (7 `ControlType*` refs).
- `components.schemas.ControlType` exists; `ControlTypeZoom` … `ControlTypeLoading` exist.
- `components.schemas.MapSchema.properties.presets` exists; no `assets` under `MapSchema`.
- `components.schemas.MapResponse` = `{map, assets}`; `paths./map/{mapId}` has `200` + `404`.
- `layers` `oneOf` includes `LayerTypeOverpass`.
- `paths` has `/map/{mapId}/data/{layerId}` **and** `/map/{mapId}/vectors/{layerId}`, both enveloped.
- `info.version` is not `"latest"`.
- Load it in the Swagger UI at `/cowegis/docs` and in the backend module (`tl_cowegis_map` `api_docs`) — no console/validation errors.

- [ ] **Step 3: CHANGELOG entry**

```markdown
## Unreleased

### Added
- Control schema describers registered for all seven controls — the OpenAPI
  `controls` list is no longer an empty `oneOf`.
- `OverpassLayerSchemaDescriber` registered — the Overpass layer now appears in the
  schema.
- `GET /map/{mapId}/vectors/{layerId}` documented.

### Changed
- `LayersSchemaDescriber` now models the real `{data,assets}` envelope, uses the
  `mapId` path parameter, and defines a `404` response.

### Requires
- `cowegis/cowegis-core` `^<new>`, `cowegis/cowegis-api-bundle` `^<new>`.
```

- [ ] **Step 4: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs: changelog for schema completeness fixes"
```

---

## Task 6 (STRETCH — G9): describe `presets.icons` / `presets.styles` from the registries

Only do this if Task 1–5 are merged and there is appetite for it. It needs a small
core addition (a `PresetSchemaDescriber`-style extension point) *or* can be done
crudely here by enriching core's generic `presets` schema through a `SchemaDescriber`
that post-decorates `IconPreset`. Recommendation: **defer to a follow-up** and open an
issue — the generic `presets` shape from core Task 5 is already correct, just not
enumerating icon/style type names.

If proceeding:

**Files:**
- Create: `src/Schema/Preset/IconTypesSchemaDescriber.php` — a `SchemaDescriber` that
  reads the tagged `Cowegis\Bundle\Contao\Map\Icon\IconType` services' `name()` and
  registers an `IconType` enum / per-type `oneOf` component.
- Modify: `src/Resources/config/icons.yaml` — register it with the `SchemaDescriber` tag
  and a `!tagged_iterator 'Cowegis\Bundle\Contao\Map\Icon\IconType'` argument.
- Test: `tests/DependencyInjection/CowegisContaoExtensionTest.php` + a phpunit behaviour test.

Mirror Task 2/3 structure (failing DI test → wire → behaviour test → commit).

---

## Self-Review checklist

- [ ] G1 registration side (Task 2) — 7 describers tagged `Cowegis\Core\Schema\ControlSchemaDescriber`; arg strings match core's `type` values (`attribution`, `fullscreen`, `geocoder`, `layers`, `loading`, `scale`, `zoom`).
- [ ] G4 registration side (Task 3) — `OverpassLayerSchemaDescriber` arg `'overpass'` matches `OverpassLayerSerializer`'s `type`.
- [ ] G5 (Task 4) — `/map/{mapId}/vectors/{layerId}` added.
- [ ] G7 (Task 4) — `{definitionId}` gone from this bundle's describer. (Core plan Tasks 6/7 remove it from `MapSchemaDescriber` / `MarkerLayerSchemaDescriber`.)
- [ ] `LayerDataResponse` (this plan) vs `MapResponse` (core T6) vs `MarkerDataResponse` (core T7) — three distinct component names, intentional, no collision.
- [ ] `AssetSchema::FULL_REF` string identical to core's definition (`#/components/schemas/Asset`).
- [ ] `composer.json` bumps both upstreams; DI tests only pass once the new core classes autoload.
