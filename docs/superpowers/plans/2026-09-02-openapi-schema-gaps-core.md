# cowegis-core – OpenAPI schema gaps Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the dynamically generated OpenAPI document describe what the API actually returns — controls, presets, the `{map,assets}` / `{data,assets}` envelopes, the Overpass layer, and error responses.

**Architecture:** Mirror the existing `LayerSchemaDescriber` pattern for controls (abstract base + `ControlSchema` component + concrete per-type describers under `src/Schema/Control/`). Extend `MapSchemaDescriber` to emit `presets`, an `assets`-carrying response envelope, and a reusable `Error` schema. Add one `OverpassLayerSchemaDescriber`. All new describers are plain classes; wiring/tagging happens in the api-bundle and contao-bundle repos.

**Tech Stack:** PHP `^8.2`, `goldspecdigital/oooas` `^2.8` (OpenAPI 3.0.2 object model), phpspec `^7.4`, phpcq QA chain.

**Spec:** `2026-09-02-openapi-schema-gaps-findings.md` (same directory)

## Global Constraints

- `declare(strict_types=1);` in every new file.
- `#[Override]` on every method that implements/overrides an interface or parent.
- New classes `final` unless designed for extension (the abstract `ControlSchemaDescriber` and `GeoJsonLayerDescriber` are the existing non-final exceptions).
- Doctrine Coding Standard, 120-column limit (`vendor/bin/phpcq run phpcs`).
- Psalm must stay green (`vendor/bin/phpcq run psalm`).
- Do **not** bump `goldspecdigital/oooas`. No `discriminator`, no OpenAPI 3.1 (see spec "Out of scope").
- Tests: phpspec specs under `spec/`, namespace `spec\Cowegis\Core\...`, style per `spec/Schema/GeoDataSchemaDescriberSpec.php` (build a `SchemaBuilder`, call `describe()`, assert on `->components()->build()->toArray()` / `$builder->build()->toArray()`).
- Full local check before "done": `vendor/bin/phpspec run` and `vendor/bin/phpcq run`.

---

## File Structure

| File | Responsibility |
|---|---|
| `src/Schema/Error/ErrorSchema.php` (new) | `Schema` subclass: the `Error` component (`{message, code}`) + `SHORT_REF`/`FULL_REF` constants. |
| `src/Schema/Error/ErrorSchemaDescriber.php` (new) | `SchemaDescriber` that registers `ErrorSchema` as a component. Tagged downstream. |
| `src/Schema/Error/ProblemResponses.php` (new) | Static factory: `notFound()` / `badRequest()` → `Response` referencing `ErrorSchema::FULL_REF`. Reused by every describer that needs error responses. |
| `src/Schema/ControlSchema.php` (new) | `Schema` subclass mirroring `LayerSchema`: base control props (`controlId`, `name`, `type`, `options`) + `SHORT_REF`/`FULL_REF`. |
| `src/Schema/ControlSchemaDescriber.php` (rewrite) | Abstract base mirroring `LayerSchemaDescriber`: ctor takes `$controlType`, `describe()` returns an `AllOf('ControlType'.ucfirst($type))` of the `ControlSchema` ref + a per-type object; `requiredProperties()` / `optionalProperties()` hooks. |
| `src/Schema/Control/{Zoom,Scale,Attribution,Fullscreen,Geocoder,Layers,Loading}ControlSchemaDescriber.php` (new, 7) | One concrete describer per core control definition. |
| `src/Schema/Layer/OverpassLayerSchemaDescriber.php` (new) | `LayerSchemaDescriber` for `type: overpass`. |
| `src/Schema/MapSchemaDescriber.php` (modify) | Register `ControlSchema` base; defensive non-empty `controls` items; add `presets`; drop inline `assets`; wrap `200` in a `MapResponse` envelope with `assets`; add `404`; rename `{definitionId}` → `{mapId}`. |
| `src/Schema/Layer/MarkerLayerSchemaDescriber.php` (modify) | Rename `{definitionId}` → `{mapId}`; wrap data response in `{data,assets}` envelope; add `404`. |
| `src/Schema/AssetSchema.php` (new) | `Schema` subclass: `Asset` component (`{type: enum, url}`) + refs. Reused by Map + Marker envelopes. |
| `spec/Schema/**` | One spec per new/changed describer. |

---

## Task 1: `Error` schema, describer and response factory

**Files:**
- Create: `src/Schema/Error/ErrorSchema.php`
- Create: `src/Schema/Error/ErrorSchemaDescriber.php`
- Create: `src/Schema/Error/ProblemResponses.php`
- Test: `spec/Schema/Error/ErrorSchemaDescriberSpec.php`

**Interfaces:**
- Produces:
  - `ErrorSchema::SHORT_REF = 'Error'`, `ErrorSchema::FULL_REF = '#/components/schemas/Error'`
  - `ErrorSchemaDescriber implements Cowegis\Core\Schema\SchemaDescriber` — `describe(SchemaBuilder $b): void` registers `new ErrorSchema()` as a component.
  - `ProblemResponses::notFound(): GoldSpecDigital\ObjectOrientedOAS\Objects\Response` — a `404` response, `->content(MediaType::json()->schema(Schema::ref(ErrorSchema::FULL_REF)))`.
  - `ProblemResponses::badRequest(): Response` — same shape, `400`.

- [ ] **Step 1: Write the failing spec**

```php
<?php

declare(strict_types=1);

namespace spec\Cowegis\Core\Schema\Error;

use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Info;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use function expect;

final class ErrorSchemaDescriberSpec extends ObjectBehavior
{
    public function it_registers_the_error_component(Info $info, Schema $idSchema, Schema $objectId): void
    {
        $idSchema->objectId(Argument::any())->willReturn($objectId->getWrappedObject());
        $idSchema->toArray()->willReturn(['type' => 'string']);
        $objectId->toArray()->willReturn(['type' => 'string']);

        $builder = SchemaBuilder::create($info->getWrappedObject(), $idSchema->getWrappedObject());
        $this->describe($builder);

        $schemas = $builder->components()->build()->toArray()['schemas'];

        expect($schemas)->shouldHaveKey('Error');
        expect($schemas['Error'])->shouldHaveKey('properties');
        expect($schemas['Error']['properties'])->shouldHaveKey('message');
        expect($schemas['Error']['properties'])->shouldHaveKey('code');
    }
}
```

- [ ] **Step 2: Run it, verify it fails**

Run: `vendor/bin/phpspec run spec/Schema/Error/ErrorSchemaDescriberSpec.php`
Expected: FAIL — `class ... not found`.

- [ ] **Step 3: Implement `ErrorSchema`**

```php
<?php

declare(strict_types=1);

namespace Cowegis\Core\Schema\Error;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

final class ErrorSchema extends Schema
{
    public const SHORT_REF = 'Error';

    public const FULL_REF = '#/components/schemas/' . self::SHORT_REF;

    public function __construct(string|null $objectId = self::SHORT_REF)
    {
        parent::__construct($objectId);

        $this->type        = 'object';
        $this->title       = 'Error';
        $this->description  = 'Error response payload';
        $this->required    = ['message'];
        $this->properties  = [
            Schema::string('message')
                ->description('Human readable error message')
                ->example('Map definition not found'),
            Schema::integer('code')
                ->description('Optional application error code')
                ->example(404),
        ];
    }
}
```

- [ ] **Step 4: Implement `ErrorSchemaDescriber`**

```php
<?php

declare(strict_types=1);

namespace Cowegis\Core\Schema\Error;

use Cowegis\Core\Schema\SchemaBuilder;
use Cowegis\Core\Schema\SchemaDescriber;
use Override;

final class ErrorSchemaDescriber implements SchemaDescriber
{
    #[Override]
    public function describe(SchemaBuilder $builder): void
    {
        $builder->components()->withSchema(new ErrorSchema());
    }
}
```

- [ ] **Step 5: Implement `ProblemResponses`**

```php
<?php

declare(strict_types=1);

namespace Cowegis\Core\Schema\Error;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

final class ProblemResponses
{
    public static function notFound(): Response
    {
        return Response::create()
            ->statusCode(404)
            ->description('The requested resource does not exist')
            ->content(MediaType::json()->schema(Schema::ref(ErrorSchema::FULL_REF)));
    }

    public static function badRequest(): Response
    {
        return Response::create()
            ->statusCode(400)
            ->description('The request parameters are invalid')
            ->content(MediaType::json()->schema(Schema::ref(ErrorSchema::FULL_REF)));
    }
}
```

- [ ] **Step 6: Run spec, verify PASS**

Run: `vendor/bin/phpspec run spec/Schema/Error/ErrorSchemaDescriberSpec.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add src/Schema/Error spec/Schema/Error
git commit -m "feat(schema): add reusable Error schema, describer and response factory"
```

---

## Task 2: `ControlSchema` component + abstract `ControlSchemaDescriber` rewrite

**Files:**
- Create: `src/Schema/ControlSchema.php`
- Modify: `src/Schema/ControlSchemaDescriber.php` (full rewrite — no concrete subclasses exist yet)
- Test: `spec/Schema/ControlSchemaDescriberSpec.php`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `ControlSchema::SHORT_REF = 'ControlType'`, `ControlSchema::FULL_REF = '#/components/schemas/ControlType'`. Constructor sets base props: `controlId` (ref `IdSchema::FULL_REF`), `name` (string), `type` (string), `options` (`HashMap`). Required: `controlId`, `name`, `type`.
  - `abstract class ControlSchemaDescriber` — `__construct(string $controlType)`; `final describe(SchemaBuilder $builder): SchemaContract` returns
    `AllOf::create('ControlType' . ucfirst($this->controlType))->schemas(Schema::ref(ControlSchema::FULL_REF), $perTypeObject)`.
    Protected hooks `requiredProperties(SchemaBuilder): array` and `optionalProperties(SchemaBuilder): array` (both default `[]`), plus `registerRequirements(SchemaBuilder, Schema): void` (default no-op), matching `LayerSchemaDescriber`.

> Note: `MapSchemaDescriber::buildControlSchemas()` already calls
> `$builder->components()->withSchema($describer->describe($builder))` and expects a
> `SchemaContract`. Keeping the same return contract as `LayerSchemaDescriber` means
> `MapSchemaDescriber` needs no change for this task.

- [ ] **Step 1: Write the failing spec** (use a local anonymous subclass)

```php
<?php

declare(strict_types=1);

namespace spec\Cowegis\Core\Schema;

use Cowegis\Core\Schema\ControlSchema;
use Cowegis\Core\Schema\ControlSchemaDescriber;
use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Info;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use function expect;

final class ControlSchemaDescriberSpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beAnInstanceOf(TestControlSchemaDescriber::class);
        $this->beConstructedWith('zoom');
    }

    public function it_builds_an_allof_named_after_the_control_type(Info $info, Schema $idSchema, Schema $objectId): void
    {
        $idSchema->objectId(Argument::any())->willReturn($objectId->getWrappedObject());
        $idSchema->toArray()->willReturn(['type' => 'string']);
        $objectId->toArray()->willReturn(['type' => 'string']);

        $builder = SchemaBuilder::create($info->getWrappedObject(), $idSchema->getWrappedObject());
        $result  = $this->describe($builder);

        $array = $result->getWrappedObject()->toArray();

        expect($array)->shouldHaveKey('allOf');
        expect($array['allOf'][0])->shouldHaveKey('$ref');
        expect($array['allOf'][0]['$ref'])->toBe(ControlSchema::FULL_REF);
    }
}

// phpcs:ignore
final class TestControlSchemaDescriber extends ControlSchemaDescriber
{
}
```

- [ ] **Step 2: Run it, verify it fails**

Run: `vendor/bin/phpspec run spec/Schema/ControlSchemaDescriberSpec.php`
Expected: FAIL — `ControlSchema` not found / constructor signature mismatch.

- [ ] **Step 3: Implement `ControlSchema`**

```php
<?php

declare(strict_types=1);

namespace Cowegis\Core\Schema;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

final class ControlSchema extends Schema
{
    public const SHORT_REF = 'ControlType';

    public const FULL_REF = '#/components/schemas/' . self::SHORT_REF;

    public function __construct(string|null $objectId = null)
    {
        parent::__construct($objectId);

        $this->type        = 'object';
        $this->title       = 'Control type';
        $this->description  = 'Required properties of a control type';
        $this->required    = ['controlId', 'name', 'type'];
        $this->properties  = [
            Schema::ref(IdSchema::FULL_REF, 'controlId'),
            Schema::string('name')
                ->title('Control name')
                ->example('zoom')
                ->description('Unique name of the control'),
            Schema::string('type')
                ->title('Control type')
                ->example('zoom')
                ->description('Control type name'),
            HashMap::create('options')
                ->title('Control options')
                ->description('Key value map of control options'),
        ];
    }
}
```

- [ ] **Step 4: Rewrite `ControlSchemaDescriber`** (mirror `LayerSchemaDescriber` exactly)

```php
<?php

declare(strict_types=1);

namespace Cowegis\Core\Schema;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\AllOf;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

use function array_merge;
use function ucfirst;

abstract class ControlSchemaDescriber
{
    public function __construct(private readonly string $controlType)
    {
    }

    /**
     * @return Schema[]
     * @psalm-return list<Schema>
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function requiredProperties(SchemaBuilder $builder): array
    {
        return [];
    }

    /**
     * @return Schema[]
     * @psalm-return list<Schema>
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function optionalProperties(SchemaBuilder $builder): array
    {
        return [];
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    protected function registerRequirements(SchemaBuilder $builder, Schema $schema): void
    {
    }

    final public function describe(SchemaBuilder $builder): SchemaContract
    {
        $requiredProperties = $this->requiredProperties($builder);
        $optionalProperties = $this->optionalProperties($builder);
        $properties         = array_merge($requiredProperties, $optionalProperties);

        $schema = Schema::object()
            ->title('Control type ' . $this->controlType)
            ->description('Schema description of control type ' . $this->controlType)
            ->required(...$requiredProperties)
            ->properties(...$properties);

        $this->registerRequirements($builder, $schema);

        return AllOf::create('ControlType' . ucfirst($this->controlType))
            ->schemas(Schema::ref(ControlSchema::FULL_REF), $schema);
    }
}
```

- [ ] **Step 5: Run spec, verify PASS**

Run: `vendor/bin/phpspec run spec/Schema/ControlSchemaDescriberSpec.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Schema/ControlSchema.php src/Schema/ControlSchemaDescriber.php spec/Schema/ControlSchemaDescriberSpec.php
git commit -m "feat(schema): give ControlSchemaDescriber the LayerSchemaDescriber shape"
```

---

## Task 3: Seven concrete control describers

**Files:**
- Create: `src/Schema/Control/ZoomControlSchemaDescriber.php`
- Create: `src/Schema/Control/ScaleControlSchemaDescriber.php`
- Create: `src/Schema/Control/AttributionControlSchemaDescriber.php`
- Create: `src/Schema/Control/FullscreenControlSchemaDescriber.php`
- Create: `src/Schema/Control/GeocoderControlSchemaDescriber.php`
- Create: `src/Schema/Control/LayersControlSchemaDescriber.php`
- Create: `src/Schema/Control/LoadingControlSchemaDescriber.php`
- Test: `spec/Schema/Control/ControlSchemaDescribersSpec.php`

**Interfaces:**
- Consumes: `ControlSchemaDescriber` (Task 2), `Cowegis\Core\Definition\Control\*` serializer field lists (below).
- Produces: seven `final` classes extending `ControlSchemaDescriber`. Each has **no constructor of its own** — it is wired downstream with the type string, e.g. `Cowegis\Core\Schema\Control\ZoomControlSchemaDescriber: ['zoom']`. Extra properties per `Serializer/Control/*Serializer::serialize()`:
  - **zoom** → `replacesDefault` (bool)
  - **scale** → none
  - **attribution** → `attributions` (array of string), `replacesDefault` (bool)
  - **fullscreen** → none
  - **geocoder** → `geocoder` (string, provider id)
  - **layers** → `baseLayers` (object map), `overlays` (object map)
  - **loading** → none

- [ ] **Step 1: Write the failing spec**

```php
<?php

declare(strict_types=1);

namespace spec\Cowegis\Core\Schema\Control;

use Cowegis\Core\Schema\Control\AttributionControlSchemaDescriber;
use Cowegis\Core\Schema\Control\LayersControlSchemaDescriber;
use Cowegis\Core\Schema\Control\ZoomControlSchemaDescriber;
use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Info;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use function expect;

final class ControlSchemaDescribersSpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beAnInstanceOf(ZoomControlSchemaDescriber::class);
        $this->beConstructedWith('zoom');
    }

    public function it_names_the_zoom_variant(Info $info, Schema $idSchema, Schema $objectId): void
    {
        $builder = self::builder($info, $idSchema, $objectId);

        $array = $this->describe($builder)->getWrappedObject()->toArray();

        expect($array['allOf'][1]['properties'])->shouldHaveKey('replacesDefault');
    }

    public function it_describes_layers_control_collections(Info $info, Schema $idSchema, Schema $objectId): void
    {
        $describer = new LayersControlSchemaDescriber('layers');
        $builder   = self::builder($info, $idSchema, $objectId);

        $array = $describer->describe($builder)->toArray();

        expect($array['allOf'][1]['properties'])->shouldHaveKey('baseLayers');
        expect($array['allOf'][1]['properties'])->shouldHaveKey('overlays');
    }

    public function it_describes_attribution_control_lists(Info $info, Schema $idSchema, Schema $objectId): void
    {
        $describer = new AttributionControlSchemaDescriber('attribution');
        $builder   = self::builder($info, $idSchema, $objectId);

        $array = $describer->describe($builder)->toArray();

        expect($array['allOf'][1]['properties'])->shouldHaveKey('attributions');
    }

    private static function builder(Info $info, Schema $idSchema, Schema $objectId): SchemaBuilder
    {
        $idSchema->objectId(Argument::any())->willReturn($objectId->getWrappedObject());
        $idSchema->toArray()->willReturn(['type' => 'string']);
        $objectId->toArray()->willReturn(['type' => 'string']);

        return SchemaBuilder::create($info->getWrappedObject(), $idSchema->getWrappedObject());
    }
}
```

- [ ] **Step 2: Run it, verify it fails**

Run: `vendor/bin/phpspec run spec/Schema/Control/ControlSchemaDescribersSpec.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Implement the five prop-less describers**

`ScaleControlSchemaDescriber`, `FullscreenControlSchemaDescriber`, `LoadingControlSchemaDescriber` — identical body:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Core\Schema\Control;

use Cowegis\Core\Schema\ControlSchemaDescriber;

final class ScaleControlSchemaDescriber extends ControlSchemaDescriber
{
}
```

(repeat verbatim for `FullscreenControlSchemaDescriber` and `LoadingControlSchemaDescriber`, changing only the class name).

- [ ] **Step 4: Implement `ZoomControlSchemaDescriber`**

```php
<?php

declare(strict_types=1);

namespace Cowegis\Core\Schema\Control;

use Cowegis\Core\Schema\ControlSchemaDescriber;
use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Override;

final class ZoomControlSchemaDescriber extends ControlSchemaDescriber
{
    /**
     * @return Schema[]
     * @psalm-return list<Schema>
     */
    #[Override]
    protected function optionalProperties(SchemaBuilder $builder): array
    {
        return [
            Schema::boolean('replacesDefault')
                ->description('Whether this control replaces the Leaflet default zoom control')
                ->default(false),
        ];
    }
}
```

- [ ] **Step 5: Implement `AttributionControlSchemaDescriber`**

```php
<?php

declare(strict_types=1);

namespace Cowegis\Core\Schema\Control;

use Cowegis\Core\Schema\ControlSchemaDescriber;
use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Override;

final class AttributionControlSchemaDescriber extends ControlSchemaDescriber
{
    /**
     * @return Schema[]
     * @psalm-return list<Schema>
     */
    #[Override]
    protected function optionalProperties(SchemaBuilder $builder): array
    {
        return [
            Schema::array('attributions')
                ->description('Static attribution strings rendered by the control')
                ->items(Schema::string()),
            Schema::boolean('replacesDefault')
                ->description('Whether this control replaces the Leaflet default attribution control')
                ->default(false),
        ];
    }
}
```

- [ ] **Step 6: Implement `GeocoderControlSchemaDescriber`**

```php
<?php

declare(strict_types=1);

namespace Cowegis\Core\Schema\Control;

use Cowegis\Core\Schema\ControlSchemaDescriber;
use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Override;

final class GeocoderControlSchemaDescriber extends ControlSchemaDescriber
{
    /**
     * @return Schema[]
     * @psalm-return list<Schema>
     */
    #[Override]
    protected function optionalProperties(SchemaBuilder $builder): array
    {
        return [
            Schema::string('geocoder')
                ->description('Identifier of the geocoder provider backing the control')
                ->example('nominatim'),
        ];
    }
}
```

- [ ] **Step 7: Implement `LayersControlSchemaDescriber`**

```php
<?php

declare(strict_types=1);

namespace Cowegis\Core\Schema\Control;

use Cowegis\Core\Schema\ControlSchemaDescriber;
use Cowegis\Core\Schema\HashMap;
use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Override;

final class LayersControlSchemaDescriber extends ControlSchemaDescriber
{
    /**
     * @return Schema[]
     * @psalm-return list<Schema>
     */
    #[Override]
    protected function optionalProperties(SchemaBuilder $builder): array
    {
        return [
            HashMap::create('baseLayers')
                ->description('Selectable base layers keyed by layer id'),
            HashMap::create('overlays')
                ->description('Toggleable overlay layers keyed by layer id'),
        ];
    }
}
```

> `HashMap` extends `GoldSpecDigital\ObjectOrientedOAS\Objects\Schema` (it is already
> used inside `->properties(...)` lists in `MapSchemaDescriber`), so it is a valid
> `optionalProperties()` return element.

- [ ] **Step 8: Run spec, verify PASS**

Run: `vendor/bin/phpspec run spec/Schema/Control/ControlSchemaDescribersSpec.php`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add src/Schema/Control spec/Schema/Control
git commit -m "feat(schema): add control schema describers for all seven core controls"
```

---

## Task 4: `MapSchemaDescriber` — register the `ControlSchema` base and stop emitting an empty `oneOf`

**Files:**
- Modify: `src/Schema/MapSchemaDescriber.php` (`buildControlSchemas()` + `mapSchema()` `controls` property)
- Test: `spec/Schema/MapSchemaDescriberSpec.php` (new)

**Interfaces:**
- Consumes: `ControlSchema` (Task 2).
- Produces: the built document always contains `components.schemas.ControlType`; `MapSchema.properties.controls.items` is a non-empty `oneOf` when describers exist, otherwise a single `$ref` to `ControlType` (never `[]`).

- [ ] **Step 1: Write the failing spec**

```php
<?php

declare(strict_types=1);

namespace spec\Cowegis\Core\Schema;

use Cowegis\Core\Schema\Control\ZoomControlSchemaDescriber;
use Cowegis\Core\Schema\MapSchemaDescriber;
use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Info;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use function expect;

final class MapSchemaDescriberSpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beConstructedWith([], [new ZoomControlSchemaDescriber('zoom')]);
    }

    public function it_registers_the_control_base_and_a_non_empty_controls_oneof(
        Info $info,
        Schema $idSchema,
        Schema $objectId,
    ): void {
        $idSchema->objectId(Argument::any())->willReturn($objectId->getWrappedObject());
        $idSchema->toArray()->willReturn(['type' => 'string']);
        $objectId->toArray()->willReturn(['type' => 'string']);

        $builder = SchemaBuilder::create($info->getWrappedObject(), $idSchema->getWrappedObject());
        $this->describe($builder);

        $doc = $builder->build()->toArray();

        expect($doc['components']['schemas'])->shouldHaveKey('ControlType');

        $controls = $doc['components']['schemas']['MapSchema']['properties']['controls'];
        expect($controls['type'])->toBe('array');
        expect($controls['items'])->shouldHaveKey('oneOf');
        expect($controls['items']['oneOf'])->shouldHaveCount(1);
    }
}
```

- [ ] **Step 2: Run it, verify it fails**

Run: `vendor/bin/phpspec run spec/Schema/MapSchemaDescriberSpec.php`
Expected: FAIL — `items` is `[]` / missing `oneOf` key, and `ControlType` not registered.

- [ ] **Step 3: Update `buildControlSchemas()`**

In `src/Schema/MapSchemaDescriber.php`, change `buildControlSchemas()` to register the base schema first (mirroring `buildLayerSchemas()`):

```php
    /** @return Schema[] */
    private function buildControlSchemas(SchemaBuilder $builder): array
    {
        $builder->components()->withSchema(new ControlSchema(), ControlSchema::SHORT_REF);

        $schemas = [];
        foreach ($this->controlSchemas as $describer) {
            $schemas[] = $builder->components()->withSchema($describer->describe($builder));
        }

        return $schemas;
    }
```

`ControlSchema` is in the same namespace as `MapSchemaDescriber` (`Cowegis\Core\Schema`), so no `use` statement is needed — reference it directly.

- [ ] **Step 4: Make the `controls` items robust in `mapSchema()`**

Replace:

```php
                Schema::array('controls')
                    ->description('Map controls')
                    ->items(OneOf::create()->schemas(...$this->buildControlSchemas($builder))),
```

with:

```php
                Schema::array('controls')
                    ->description('Map controls')
                    ->items($this->controlsItems($builder)),
```

and add the private helper:

```php
    private function controlsItems(SchemaBuilder $builder): Schema|OneOf
    {
        $schemas = $this->buildControlSchemas($builder);

        if ($schemas === []) {
            return Schema::ref(ControlSchema::FULL_REF);
        }

        return OneOf::create()->schemas(...$schemas);
    }
```

(`OneOf` is already imported in this file.)

- [ ] **Step 5: Run spec, verify PASS; run the existing suite**

Run: `vendor/bin/phpspec run spec/Schema/MapSchemaDescriberSpec.php`
Run: `vendor/bin/phpspec run spec/Schema`
Expected: PASS, no regressions.

- [ ] **Step 6: Commit**

```bash
git add src/Schema/MapSchemaDescriber.php spec/Schema/MapSchemaDescriberSpec.php
git commit -m "fix(schema): never emit an empty controls oneOf; register ControlType base"
```

---

## Task 5: `MapSchemaDescriber` — describe `presets`, drop the phantom `assets` property

**Files:**
- Modify: `src/Schema/MapSchemaDescriber.php` (`mapSchema()` — add `presets`, remove `assets`; add three private preset-schema helpers)
- Test: `spec/Schema/MapSchemaDescriberSpec.php` (extend)

**Interfaces:**
- Produces: `MapSchema.properties.presets` = object with `icons` / `popups` / `styles` / `tooltips`, each an object with `additionalProperties`. `MapSchema.properties.assets` no longer exists (it moves to the envelope in Task 6).

- [ ] **Step 1: Add a failing expectation to `MapSchemaDescriberSpec`**

```php
    public function it_describes_presets_and_drops_assets(Info $info, Schema $idSchema, Schema $objectId): void
    {
        $idSchema->objectId(Argument::any())->willReturn($objectId->getWrappedObject());
        $idSchema->toArray()->willReturn(['type' => 'string']);
        $objectId->toArray()->willReturn(['type' => 'string']);

        $builder = SchemaBuilder::create($info->getWrappedObject(), $idSchema->getWrappedObject());
        $this->describe($builder);

        $props = $builder->build()->toArray()['components']['schemas']['MapSchema']['properties'];

        expect($props)->shouldHaveKey('presets');
        expect($props['presets']['properties'])->shouldHaveKey('icons');
        expect($props['presets']['properties'])->shouldHaveKey('popups');
        expect($props['presets']['properties'])->shouldHaveKey('styles');
        expect($props['presets']['properties'])->shouldHaveKey('tooltips');
        expect($props)->shouldNotHaveKey('assets');
    }
```

- [ ] **Step 2: Run it, verify it fails**

Run: `vendor/bin/phpspec run spec/Schema/MapSchemaDescriberSpec.php`
Expected: FAIL — `presets` missing, `assets` present.

- [ ] **Step 3: In `mapSchema()`, delete the `assets` block**

Remove:

```php
                Schema::array('assets')->items(
                    Schema::object()
                        ->required('type', 'url')
                        ->properties(
                            Schema::string('type')
                                ->enum(Asset::TYPE_JAVASCRIPT, Asset::TYPE_STYLESHEET)
                                ->example(Asset::TYPE_JAVASCRIPT),
                            Schema::string('url')
                                ->format('url')
                                ->example('/cowegis/js/callbacks/123.js'),
                        ),
                ),
```

Leave the `use Cowegis\Core\Definition\Asset\Asset;` import for now — Task 6 re-uses it.

- [ ] **Step 4: Add a `presets` property in `mapSchema()`** (next to `events`)

```php
                Schema::object('presets')
                    ->description('Reusable icon, popup, tooltip and style presets keyed by preset id')
                    ->required('icons', 'popups', 'styles', 'tooltips')
                    ->properties(
                        Schema::object('icons')->additionalProperties($this->iconPresetSchema($builder)),
                        Schema::object('popups')->additionalProperties($this->popupPresetSchema($builder)),
                        Schema::object('styles')->additionalProperties(HashMap::create()),
                        Schema::object('tooltips')->additionalProperties($this->tooltipPresetSchema($builder)),
                    ),
```

- [ ] **Step 5: Add the three private helpers** (field lists mirror `IconSerializer`, `PopupSerializer`, `TooltipSerializer`)

```php
    private function iconPresetSchema(SchemaBuilder $builder): Schema
    {
        return Schema::object('IconPreset')
            ->required('iconId', 'type')
            ->properties(
                $builder->idSchemaRef('iconId'),
                Schema::string('type')->example('svg')->description('Icon type name'),
                HashMap::create('options')->description('Key value map of icon options'),
            );
    }

    private function popupPresetSchema(SchemaBuilder $builder): Schema
    {
        return Schema::object('PopupPreset')
            ->required('content')
            ->properties(
                Schema::string('content')->description('Rendered popup HTML'),
                $builder->idSchemaRef('presetId')->nullable(),
                HashMap::create('options')->description('Key value map of popup options'),
                Schema::object('events')->description('Event reference map'),
            );
    }

    private function tooltipPresetSchema(SchemaBuilder $builder): Schema
    {
        return Schema::object('TooltipPreset')
            ->required('content')
            ->properties(
                Schema::string('content')->description('Rendered tooltip HTML'),
                Schema::array('coordinates')
                    ->nullable()
                    ->minItems(2)
                    ->maxItems(3)
                    ->items(Schema::number()),
                HashMap::create('options')->description('Key value map of tooltip options'),
                $builder->idSchemaRef('presetId')->nullable(),
                Schema::object('events')->description('Event reference map'),
            );
    }
```

- [ ] **Step 6: Run spec, verify PASS**

Run: `vendor/bin/phpspec run spec/Schema/MapSchemaDescriberSpec.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add src/Schema/MapSchemaDescriber.php spec/Schema/MapSchemaDescriberSpec.php
git commit -m "fix(schema): document presets on MapSchema, drop the never-emitted assets property"
```

---

## Task 6: `Asset` schema + `{map,assets}` response envelope + `404`

**Files:**
- Create: `src/Schema/AssetSchema.php`
- Modify: `src/Schema/MapSchemaDescriber.php` (`describe()` — envelope + `404`)
- Test: `spec/Schema/MapSchemaDescriberSpec.php` (extend)

**Interfaces:**
- Consumes: `ProblemResponses` (Task 1).
- Produces:
  - `AssetSchema::SHORT_REF = 'Asset'`, `AssetSchema::FULL_REF = '#/components/schemas/Asset'`. `{type: enum(javascript,stylesheet), url: string}`, both required.
  - `#/components/schemas/MapResponse` = `{map: $ref MapSchema, assets: [$ref Asset]}`, both required.
  - `GET /map/{mapId}` operation: `200` → `MapResponse`; `404` → `ProblemResponses::notFound()`.

- [ ] **Step 1: Add failing expectations to `MapSchemaDescriberSpec`**

```php
    public function it_wraps_the_map_response_in_an_envelope_with_assets(
        Info $info,
        Schema $idSchema,
        Schema $objectId,
    ): void {
        $idSchema->objectId(Argument::any())->willReturn($objectId->getWrappedObject());
        $idSchema->toArray()->willReturn(['type' => 'string']);
        $objectId->toArray()->willReturn(['type' => 'string']);

        $builder = SchemaBuilder::create($info->getWrappedObject(), $idSchema->getWrappedObject());
        $this->describe($builder);

        $doc = $builder->build()->toArray();

        expect($doc['components']['schemas'])->shouldHaveKey('MapResponse');
        expect($doc['components']['schemas']['MapResponse']['properties'])->shouldHaveKey('map');
        expect($doc['components']['schemas']['MapResponse']['properties'])->shouldHaveKey('assets');
        expect($doc['components']['schemas'])->shouldHaveKey('Asset');

        $responses = $doc['paths']['/map/{mapId}']['get']['responses'];
        expect($responses)->shouldHaveKey(200);
        expect($responses)->shouldHaveKey(404);
    }
```

- [ ] **Step 2: Run it, verify it fails**

Run: `vendor/bin/phpspec run spec/Schema/MapSchemaDescriberSpec.php`
Expected: FAIL.

- [ ] **Step 3: Implement `AssetSchema`**

```php
<?php

declare(strict_types=1);

namespace Cowegis\Core\Schema;

use Cowegis\Core\Definition\Asset\Asset;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

final class AssetSchema extends Schema
{
    public const SHORT_REF = 'Asset';

    public const FULL_REF = '#/components/schemas/' . self::SHORT_REF;

    public function __construct(string|null $objectId = null)
    {
        parent::__construct($objectId);

        $this->type        = 'object';
        $this->title       = 'Asset';
        $this->description  = 'A javascript or stylesheet asset the client must load for this response';
        $this->required    = ['type', 'url'];
        $this->properties  = [
            Schema::string('type')
                ->enum(Asset::TYPE_JAVASCRIPT, Asset::TYPE_STYLESHEET)
                ->example(Asset::TYPE_JAVASCRIPT),
            Schema::string('url')
                ->format('uri')
                ->example('/cowegis/js/callbacks/123.js'),
        ];
    }
}
```

- [ ] **Step 4: Rewrite the `describe()` response in `MapSchemaDescriber`**

Replace:

```php
        $response = Response::ok('map')
            ->content(MediaType::json()->schema($builder->components()->withSchema($this->mapSchema($builder))));

        // TODO error responses
```

with:

```php
        $mapRef   = $builder->components()->withSchema($this->mapSchema($builder));
        $assetRef = $builder->components()->withSchema(new AssetSchema(), AssetSchema::SHORT_REF);

        $envelope = $builder->components()->withSchema(
            Schema::object('MapResponse')
                ->description('Full map definition plus the assets required to render it')
                ->required('map', 'assets')
                ->properties(
                    $mapRef->objectId('map'),
                    Schema::array('assets')->items($assetRef),
                ),
        );

        $response = Response::ok('Full map definition with assets')
            ->content(MediaType::json()->schema($envelope));
```

Then add the `404` to the operation:

```php
        $mapDetails = Operation::get()
            ->description('This entrypoint provides all information to render a map with cowegis.')
            ->summary('Show full map details')
            ->parameters(
                Parameter::path()
                    ->name('mapId')
                    ->schema($builder->idSchemaRef())
                    ->required(),
            )
            ->tags($tag)
            ->responses($response, ProblemResponses::notFound());
```

Add imports: `use Cowegis\Core\Schema\Error\ProblemResponses;` (and `AssetSchema` is same-namespace).

> `Response::ok(...)` in oooas sets `statusCode` `200`; `ProblemResponses::notFound()`
> sets `404`. `Operation::responses(...)` keys them by their `statusCode`.

- [ ] **Step 5: Rename the path template**

In `describe()`, change:

```php
        $path = (new PathItem())
            ->route('/map/{mapId}')
            ->operations($mapDetails);
```

- [ ] **Step 6: Run spec + full schema suite**

Run: `vendor/bin/phpspec run spec/Schema/MapSchemaDescriberSpec.php`
Run: `vendor/bin/phpspec run spec/Schema`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add src/Schema/AssetSchema.php src/Schema/MapSchemaDescriber.php spec/Schema/MapSchemaDescriberSpec.php
git commit -m "fix(schema): model the {map,assets} response envelope and a 404 for GET /map/{mapId}"
```

---

## Task 7: `MarkerLayerSchemaDescriber` — envelope + param rename + `404`

**Files:**
- Modify: `src/Schema/Layer/MarkerLayerSchemaDescriber.php` (`registerRequirements()`)
- Test: `spec/Schema/Layer/MarkerLayerSchemaDescriberSpec.php` (new)

**Interfaces:**
- Consumes: `ProblemResponses` (Task 1), `Cowegis\Core\Schema\GeoJson\FeatureCollectionSchema`.
- Produces: `GET /map/{mapId}/markers/{layerId}` — `200` body `{data: $ref FeatureCollection, assets: [$ref Asset]}`; `404` from `ProblemResponses::notFound()`. Component `#/components/schemas/MarkerDataResponse`.

- [ ] **Step 1: Write the failing spec**

```php
<?php

declare(strict_types=1);

namespace spec\Cowegis\Core\Schema\Layer;

use Cowegis\Core\Schema\Layer\MarkerLayerSchemaDescriber;
use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Info;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use function expect;

final class MarkerLayerSchemaDescriberSpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beConstructedWith('markers');
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(MarkerLayerSchemaDescriber::class);
    }

    public function it_describes_an_enveloped_marker_data_path(Info $info, Schema $idSchema, Schema $objectId): void
    {
        $idSchema->objectId(Argument::any())->willReturn($objectId->getWrappedObject());
        $idSchema->toArray()->willReturn(['type' => 'string']);
        $objectId->toArray()->willReturn(['type' => 'string']);

        $builder = SchemaBuilder::create($info->getWrappedObject(), $idSchema->getWrappedObject());
        $this->describe($builder);

        $doc  = $builder->build()->toArray();
        $path = $doc['paths']['/map/{mapId}/markers/{layerId}']['get'];

        expect($path['responses'])->shouldHaveKey(200);
        expect($path['responses'])->shouldHaveKey(404);
        expect($doc['components']['schemas'])->shouldHaveKey('MarkerDataResponse');
        expect($doc['components']['schemas']['MarkerDataResponse']['properties'])->shouldHaveKey('data');
        expect($doc['components']['schemas']['MarkerDataResponse']['properties'])->shouldHaveKey('assets');
    }
}
```

- [ ] **Step 2: Run it, verify it fails**

Run: `vendor/bin/phpspec run spec/Schema/Layer/MarkerLayerSchemaDescriberSpec.php`
Expected: FAIL — path is `/map/{definitionId}/markers/{layerId}`, no `404`, no envelope.

- [ ] **Step 3: Rewrite `registerRequirements()`**

```php
    protected function registerRequirements(SchemaBuilder $builder, Schema $schema): void
    {
        parent::registerRequirements($builder, $schema);

        $envelope = $builder->components()->withSchema(
            Schema::object('MarkerDataResponse')
                ->description('Marker feature collection plus required assets')
                ->required('data', 'assets')
                ->properties(
                    Schema::ref(FeatureCollectionSchema::FULL_REF, 'data'),
                    Schema::array('assets')->items(Schema::ref(AssetSchema::FULL_REF)),
                ),
        );

        $response = Response::ok('Marker layer data')
            ->content(MediaType::json()->schema($envelope));

        $layerDetails = Operation::get()
            ->description('Deferred marker features for a single layer of a map')
            ->summary('Show marker layer data')
            ->parameters(
                Parameter::path()
                    ->name('mapId')
                    ->schema($builder->idSchemaRef())
                    ->required(),
                Parameter::path()
                    ->name('layerId')
                    ->schema($builder->idSchemaRef())
                    ->required(),
            )
            ->tags(Tag::create()->name('Layer data'))
            ->responses($response, ProblemResponses::notFound());

        $builder->withPathItem(
            (new PathItem())
                ->route('/map/{mapId}/markers/{layerId}')
                ->operations($layerDetails),
        );
    }
```

Add imports: `use Cowegis\Core\Schema\AssetSchema;` and `use Cowegis\Core\Schema\Error\ProblemResponses;`.

- [ ] **Step 4: Run spec + suite**

Run: `vendor/bin/phpspec run spec/Schema/Layer/MarkerLayerSchemaDescriberSpec.php`
Run: `vendor/bin/phpspec run spec/Schema`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Schema/Layer/MarkerLayerSchemaDescriber.php spec/Schema/Layer/MarkerLayerSchemaDescriberSpec.php
git commit -m "fix(schema): envelope + 404 + mapId param for the marker data path"
```

---

## Task 8: `OverpassLayerSchemaDescriber`

**Files:**
- Create: `src/Schema/Layer/OverpassLayerSchemaDescriber.php`
- Test: `spec/Schema/Layer/OverpassLayerSchemaDescriberSpec.php`

**Interfaces:**
- Consumes: `LayerSchemaDescriber` base.
- Produces: `final class OverpassLayerSchemaDescriber extends LayerSchemaDescriber` — wired downstream as `['overpass']`. Emits `AllOf('LayerTypeOverpass')` referencing `LayerSchema::FULL_REF` + an object describing the Overpass options (`query`, `endpoint`, `minZoom`; `onEachFeature` / `pointToLayer` are callback references left as free-form strings). No extra path item (Overpass is client-side).

- [ ] **Step 1: Write the failing spec**

```php
<?php

declare(strict_types=1);

namespace spec\Cowegis\Core\Schema\Layer;

use Cowegis\Core\Schema\Layer\OverpassLayerSchemaDescriber;
use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Info;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use function expect;

final class OverpassLayerSchemaDescriberSpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beConstructedWith('overpass');
    }

    public function it_describes_the_overpass_layer_variant(Info $info, Schema $idSchema, Schema $objectId): void
    {
        $idSchema->objectId(Argument::any())->willReturn($objectId->getWrappedObject());
        $idSchema->toArray()->willReturn(['type' => 'string']);
        $objectId->toArray()->willReturn(['type' => 'string']);

        $builder = SchemaBuilder::create($info->getWrappedObject(), $idSchema->getWrappedObject());
        $array   = $this->describe($builder)->getWrappedObject()->toArray();

        expect($array)->shouldHaveKey('allOf');
        expect($array['allOf'][1]['properties'])->shouldHaveKey('query');
        expect($array['allOf'][1]['properties'])->shouldHaveKey('endpoint');
        expect($array['allOf'][1]['properties'])->shouldHaveKey('minZoom');
    }
}
```

- [ ] **Step 2: Run it, verify it fails**

Run: `vendor/bin/phpspec run spec/Schema/Layer/OverpassLayerSchemaDescriberSpec.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Cowegis\Core\Schema\Layer;

use Cowegis\Core\Schema\LayerSchemaDescriber;
use Cowegis\Core\Schema\SchemaBuilder;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Override;

final class OverpassLayerSchemaDescriber extends LayerSchemaDescriber
{
    /**
     * @return Schema[]
     * @psalm-return list<Schema>
     */
    #[Override]
    protected function optionalProperties(SchemaBuilder $builder): array
    {
        return [
            Schema::string('query')
                ->description('Overpass QL query; the token BBOX is replaced with the current map bounds')
                ->example('(node(BBOX)[amenity];);out qt;'),
            Schema::string('endpoint')
                ->description('Base URL of the Overpass API instance')
                ->example('https://overpass-api.de/api/'),
            Schema::integer('minZoom')
                ->description('Minimum zoom level at which the query is executed')
                ->default(15),
            Schema::string('onEachFeature')
                ->description('Client callback reference invoked per feature')
                ->nullable(),
            Schema::string('pointToLayer')
                ->description('Client callback reference turning a point into a layer')
                ->nullable(),
        ];
    }
}
```

- [ ] **Step 4: Run spec, verify PASS**

Run: `vendor/bin/phpspec run spec/Schema/Layer/OverpassLayerSchemaDescriberSpec.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Schema/Layer/OverpassLayerSchemaDescriber.php spec/Schema/Layer/OverpassLayerSchemaDescriberSpec.php
git commit -m "feat(schema): add OverpassLayerSchemaDescriber"
```

---

## Task 9: Full QA + CHANGELOG

**Files:**
- Modify: `CHANGELOG.md` (if the repo keeps one — add an "Unreleased" entry)

- [ ] **Step 1: Run the whole test suite**

Run: `vendor/bin/phpspec run`
Expected: all green.

- [ ] **Step 2: Run the QA chain**

Run: `vendor/bin/phpcq run`
Expected: psalm / phpcs / rector / phpmd clean. Fix findings (most likely: missing `#[Override]`, import ordering, line length in the preset helpers).

- [ ] **Step 3: Add a CHANGELOG entry**

```markdown
## Unreleased

### Added
- `ControlSchema` + `ControlSchemaDescriber` base and concrete describers for all
  seven core controls (`zoom`, `scale`, `attribution`, `fullscreen`, `geocoder`,
  `layers`, `loading`).
- `OverpassLayerSchemaDescriber`.
- Reusable `Error` schema, `ErrorSchemaDescriber` and `ProblemResponses` factory;
  `404` responses on the map and marker-data operations.
- `presets` (`icons`/`popups`/`styles`/`tooltips`) on `MapSchema`.

### Changed
- The `GET /map/{mapId}` and `GET /map/{mapId}/markers/{layerId}` responses are now
  modelled as the real `{map,assets}` / `{data,assets}` envelopes.
- Path parameters renamed `definitionId` → `mapId` to match the routes.

### Fixed
- `MapSchema.controls` no longer emits an invalid empty `oneOf`.
- Removed the `assets` property that `MapSchema` described but the API never returned.
```

- [ ] **Step 4: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs: changelog for OpenAPI schema fixes"
```

---

## Self-Review checklist (run before handing off)

- [ ] Every spec gap G1–G7 + G4 has a task: G1→T2/3/4, G2→T5, G3→T6/7, G4→T8, G6→T1/6/7, G7→T6/7. G5 and G9 are handled in the contao-bundle plan; G8 in the api-bundle plan. ✅
- [ ] `ControlSchema::SHORT_REF` / `FULL_REF` used identically in Tasks 2, 4.
- [ ] `AssetSchema::SHORT_REF` / `FULL_REF` used identically in Tasks 6, 7.
- [ ] `ProblemResponses::notFound()` signature identical in Tasks 1, 6, 7.
- [ ] `MapResponse` (T6) vs `MarkerDataResponse` (T7) — distinct component names, intentional.
- [ ] No `discriminator` / oooas bump anywhere.
