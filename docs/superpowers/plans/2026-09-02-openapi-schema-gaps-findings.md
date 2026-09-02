# Cowegis dynamic OpenAPI schema – gap analysis (spec)

**Date:** 2026-09-02
**Verified against:** `https://cowegis.ddev.site/cowegis/docs/schema.json` (live, dynamically generated)

This is the shared spec for three implementation plans:

- `2026-09-02-openapi-schema-gaps-core.md` — `cowegis/cowegis-core`
- `2026-09-02-openapi-schema-gaps-api-bundle.md` — `cowegis/cowegis-api-bundle`
- `2026-09-02-openapi-schema-gaps-contao-bundle.md` — `cowegis/cowegis-contao-bundle`

Execute in that order: core → api-bundle → contao-bundle (later repos bump their
`cowegis/cowegis-core` / `cowegis/cowegis-api-bundle` constraint to pick up the new code).

## How the schema is assembled

`SchemaAction` (`cowegis-api-bundle`) builds an `Info`, creates a
`Cowegis\Core\Schema\SchemaBuilder`, then calls
`Cowegis\Core\Schema\DelegatingSchemaDescriber::describe($builder)`, which fans out to
every service tagged `Cowegis\Core\Schema\SchemaDescriber`. `MapSchemaDescriber`
additionally receives two `!tagged_iterator`s:
`Cowegis\Core\Schema\LayerSchemaDescriber` and
`Cowegis\Core\Schema\ControlSchemaDescriber`.

Concrete describers ship in **core** (`src/Schema/Layer/*`); downstream bundles only
*register + tag* them (with the type-name string as constructor arg) and can add their
own. `cowegis-contao-bundle` registers layer describers in
`src/Resources/config/layers.yaml` via an `_instanceof` block plus explicit
`SchemaDescriber`-tagged services (`LayersSchemaDescriber`, `ServersSchemaDescriber`).

## Confirmed gaps

### G1 — `controls` in the map schema is malformed
`MapSchemaDescriber::mapSchema()` builds
`Schema::array('controls')->items(OneOf::create()->schemas(...$this->buildControlSchemas($builder)))`.
`buildControlSchemas()` iterates the `ControlSchemaDescriber` tag — **nothing is tagged**
(core ships only the abstract `Cowegis\Core\Schema\ControlSchemaDescriber`, no concrete
classes; `cowegis-contao-bundle` registers none). `OneOf::create()->schemas()` with no
args serialises to an empty/again-filtered node. Live schema:
`"controls": { "description": "Map controls", "type": "array", "items": [] }` — an empty
`items` array is invalid.

### G2 — `presets` is emitted but not documented
`MapSerializer::serialize()` returns a top-level `presets` key
(`{icons, popups, styles, tooltips}`, from `PresetsSerializer`). `MapSchema` has **no**
`presets` property. Live schema confirms: no `presets` in `MapSchema.properties`.

### G3 — response envelope is not modelled; `assets` is misplaced
`MapAction::__invoke()` returns `{"map": <serialized Map>, "assets": [<Asset>...]}`.
`LayerDataAction::__invoke()` returns `{"data": <serialized data>, "assets": [...]}`.
The schema describes the **bare** map object as the `200` body and puts an `assets`
array *inside* `MapSchema.properties` (as a sibling of `layers`). `MapSerializer`
never emits `assets`. Same for the two layer-data paths (bare `GeoData` /
`FeatureCollection`, no envelope).

### G4 — Overpass layer type missing
`OverpassLayerType` (`cowegis-contao-bundle`) → core `OverpassLayer` definition →
`OverpassLayerSerializer` emits `type: "overpass"`. No `LayerSchemaDescriber` exists
for it (neither core nor bundle). Live `MapSchema.properties.layers` `oneOf` only
references `LayerTypeData, LayerTypeTileLayer, LayerTypeMarkers, LayerTypeFeatureGroup,
LayerTypeLayerGroup, LayerTypeMarkerCluster`. Overpass options (core `OverpassLayer`):
`query` (string), `endpoint` (string), `minZoom` (int), `onEachFeature` / `pointToLayer`
(callback references).

### G5 — Vector layer-data endpoint undocumented
`VectorsLayerType` registers a `LayerDataProvider` with `type: "vectors"`, giving a real
endpoint `GET /api/map/{mapId}/vectors/{layerId}`. Only `.../markers/{layerId}` (core
`MarkerLayerSchemaDescriber`) and the generic `.../data/{layerId}`
(`LayersSchemaDescriber`) are described. `file` and `vectors` both serialise as
`type: "data"` (both build a core `DataLayer`), so they are structurally covered by
`DataLayerSchemaDescriber`; only the dedicated `vectors` data path is missing.

### G6 — no error responses
Every operation defines only `200`. `components` has no `responses` section and no
error schema. Source has `// TODO error responses` at each describer.

### G7 — path parameter naming drift
Describers use `{definitionId}`; real routes use `{mapId}` (`cowegis_api_map`:
`/api/map/{mapId}`, `cowegis_api_layer_data`: `/api/map/{mapId}/{type}/{layerId}`).
The server URL (`{scheme}://{host}/{prefix}/api`) + describer path
(`/map/{definitionId}`) already resolves to the correct real URL, so this is a
cosmetic mismatch, not a wrong path.

### G8 — `info.version` is the literal string `"latest"`
`cowegis_api.api_version` defaults to `'latest'` (`CowegisApiExtension` +
`Configuration`) and is passed straight into `Info::version()`. Valid JSON string,
but not a usable version for tooling/codegen.

### G9 — Icons/Styles have no schema arm
`IconType`/`IconTypeRegistry` and `StyleType`/`StyleTypeRegistry` follow the same
Type+Registry pattern as layers/controls, feed `presets.icons` / `presets.styles` and
per-marker `icon`, but there is no `IconSchemaDescriber` / `StyleSchemaDescriber`
concept. Lower priority; addressed as a stretch task once G2 lands.

## Out of scope (documented, not fixed)

- **`discriminator` on the `layers` / `controls` `oneOf`.** `goldspecdigital/oooas`
  `^2.8` (core's pinned dep) models composition only through the standalone `OneOf`
  object, which has no `discriminator` support; `Schema` has no `oneOf()` method.
  Adding a real discriminator needs an oooas major upgrade or array post-processing.
  The `type` property on every layer/control already carries the information.
- **OpenAPI 3.1.** Same oooas constraint (`SchemaAction` hard-codes
  `OpenApi::OPENAPI_3_0_2`; 3.1 needs oooas `^3`).
- **`/js/callbacks/...` routes.** Intentionally undocumented (internal asset URLs).
