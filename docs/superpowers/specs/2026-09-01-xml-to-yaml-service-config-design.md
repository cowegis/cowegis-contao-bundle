# Design: Service-Konfiguration von XML auf YAML + Autokonfiguration

**Datum:** 2026-09-01
**Status:** Entwurf zur Review
**Betrifft:** `cowegis/cowegis-contao-bundle`

## 1. Überblick

Die Symfony-DI-Konfiguration des Bundles (`src/Resources/config/*.xml`) wird vollständig
auf YAML umgestellt. Dabei wird `autoconfigure` aktiviert (Autowiring bleibt **aus** —
jedes Konstruktor-Argument wird weiterhin von Hand verdrahtet). Marker-Interface-Tags
wandern in PHP-Attribute bzw. `_instanceof`; Attribut-tragende Tags
(`contao.callback`, `kernel.event_listener`, Contao-Hooks, `Serializer` mit `key`)
werden auf die passenden PHP-Attribute umgestellt.

Begleitend:

- Contao `^4.13` und Symfony `^5.4` werden als unterstützte Versionen entfernt.
- PHPUnit wird über phpcq/companion **zusätzlich** zu phpspec eingeführt; die neuen
  Service-Tests laufen unter PHPUnit (Container-Kompilierungstest).

Dies setzt eine bereits begonnene Migration fort: `styles.xml` und `fragments.xml`
nutzen schon `autoconfigure="true"`, einzelne Listener tragen bereits `#[AsCallback]` /
`#[AsEventListener]`, `MapContentElementAction`/`MapModuleAction` nutzen
`#[AsContentElement]` / `#[AsFrontendModule]`.

### Ziele

1. Alle `src/Resources/config/*.xml` → `*.yaml` (inkl. `routing.xml` → `routing.yaml`).
2. `autoconfigure: true` als Default in jeder Service-Datei; Autowiring bleibt aus.
3. Redundante Marker-Tags entfallen zugunsten von `#[AutoconfigureTag]` /
   `_instanceof` / dedizierter Attribute.
4. Container-Kompilierungstest unter PHPUnit als Regressionsnetz.
5. Contao/Symfony-Versionsmatrix bereinigt (kein 4.13 / 5.x).
6. `CLAUDE.md` an die neue Konvention angepasst.

### Nicht-Ziele

- Autowiring einführen.
- Umbau der Registry-/Hydrator-Architektur.
- Migration bestehender phpspec-Specs nach PHPUnit (phpspec bleibt unverändert).
- Neue Test-Abhängigkeiten (z. B. `matthiasnoback/symfony-dependency-injection-test`).
- Änderungen an `cowegis-core` / `cowegis-api-bundle`.

## 2. Versions-Support

`cowegis-core` und `cowegis-api-bundle` bieten **keine** Autokonfiguration für ihre
Tag-Familien. Die Entscheidung, Contao 4.13 / Symfony 5 zu streichen, ist zusätzlich
nötig, weil `#[AsEventListener]` erst ab `symfony/event-dispatcher` 6.1 existiert
(und im Baum bereits verwendet wird).

### `composer.json`

| Paket | vorher | nachher |
|---|---|---|
| `contao/core-bundle` | `^4.13 \|\| ^5.3` | `^5.3` |
| `symfony/config` | `^5.4 \|\| ^6.4 \|\| ^7.4` | `^6.4 \|\| ^7.4` |
| `symfony/dependency-injection` | `^5.4 \|\| ^6.4 \|\| ^7.4` | `^6.4 \|\| ^7.4` |
| `symfony/event-dispatcher` | `^5.4 \|\| ^6.4 \|\| ^7.4` | `^6.4 \|\| ^7.4` |
| `symfony/http-foundation` | `^5.4 \|\| ^6.4 \|\| ^7.4` | `^6.4 \|\| ^7.4` |
| `symfony/http-kernel` | `^5.4 \|\| ^6.4 \|\| ^7.4` | `^6.4 \|\| ^7.4` |
| `symfony/routing` | `^5.4 \|\| ^6.4 \|\| ^7.4` | `^6.4 \|\| ^7.4` |
| `symfony/translation-contracts` | `^1.0 \|\| ^2.0 \|\| ^3.0` | `^2.0 \|\| ^3.0` |
| `netzmacht/contao-toolkit` | `^3.8 \|\| ^4.0` | `^4.0` |

Restliche Constraints (`psr/container ^1.0 || ^2.0`, `contao/manager-plugin ^2.1`)
bleiben, sofern kein Konflikt entsteht; bei der Umsetzung `composer update` +
`composer-require-checker` als Kontrolle.

### CI / Sonstiges

- `.github/workflows/diagnostics.yml`: Matrix ist rein PHP-versionsbasiert
  (8.2/8.3/8.4), kein Contao-/Symfony-Leg — **keine Änderung nötig**.
- `companion.json` `receipts`: Eintrag `projects/contao-bundle/4.13-5.3` bei der
  Umsetzung prüfen. Falls `companion` ein Contao-5-only-Rezept anbietet, darauf
  umstellen; andernfalls Eintrag belassen und nur `phpunit` aktivieren.

## 3. Autokonfigurations-Strategie

Die Tag-Familien des Bundles werden nach Besitzer und Attributbedarf klassifiziert:

| Tag | Besitzer | # | Strategie |
|---|---|---|---|
| `Cowegis\Bundle\Contao\Hydrator\Hydrator` | Bundle-Interface | ~30 | `#[AutoconfigureTag]` am Interface |
| `Cowegis\Bundle\Contao\Map\Layer\LayerType` | Bundle-Interface | ~8 | `#[AutoconfigureTag]` am Interface |
| `Cowegis\Bundle\Contao\Map\Control\ControlType` | Bundle-Interface | ~7 | `#[AutoconfigureTag]` am Interface |
| `Cowegis\Bundle\Contao\Map\Icon\IconType` | Bundle-Interface | ~4 | `#[AutoconfigureTag]` am Interface |
| `Cowegis\Bundle\Contao\Map\Style\StyleType` | Bundle-Interface | ~1 | `#[AutoconfigureTag]` am Interface |
| `contao.callback` (`table`/`target`/`method`) | Contao | ~60 | `#[AsCallback]` an den Zielmethoden |
| `kernel.event_listener` (`event`/`method`/`priority`) | Symfony | 4 | `#[AsEventListener]` an der Klasse |
| Contao-Hook (`@Hook`) | Contao | 1 | `@Hook`-Annotation → `#[AsHook]`-Attribut |
| `Cowegis\Core\Serializer\Serializer` (`key`) | cowegis-core | ~13 | eigenes `#[AsSerializer(key: …)]` + `registerAttributeForAutoconfiguration()` |
| `Cowegis\Core\Schema\LayerSchemaDescriber` | cowegis-core | ~8 | `_instanceof` in `layers.yaml` (String-Ctor-Arg bleibt in `arguments`) |
| `Cowegis\Core\Schema\SchemaDescriber` | cowegis-core | 1 | expliziter `tags`-Eintrag |
| `Cowegis\Core\Provider\Provider` | cowegis-core | 1 | expliziter `tags`-Eintrag |
| `Cowegis\Core\IdFormat\IdFormat` | cowegis-core | 1 | expliziter `tags`-Eintrag |
| `Cowegis\Core\Schema\IdSchema` | cowegis-core | 1 | expliziter `tags`-Eintrag |
| `Cowegis\Bundle\Contao\Provider\LayerDataProvider` (`type`) | Bundle | 4 | expliziter `tags`-Eintrag (`type` bleibt) |
| `netzmacht.contao_toolkit.repository` (`model`) | toolkit | ~10 | expliziter `tags`-Eintrag (kein Attribut vorhanden) |
| `hofff_contao_consent_bridge.plugin` | hofff | 1 | expliziter `tags`-Eintrag |

**Grundregel:** Ein Service erhält seinen Marker-Tag entweder via Attribut/`_instanceof`
**oder** via expliziten `tags`-Eintrag — niemals beides (`tagged_iterator` würde den
Service sonst doppelt enthalten).

### Hydrator-Prioritäten

Drei Hydratoren tragen abweichende Prioritäten:

- `Map\Options\LocateOptionsHydrator` → `priority: -32`
- `Map\Options\BoundsOptionsHydrator` → `priority: -32`
- `Hydrator\EventDispatchingHydrator` → `priority: -128`

Diese drei Einträge im YAML mit `autoconfigure: false` **überschreiben** und den Tag
explizit mit Priorität setzen:

```yaml
Cowegis\Bundle\Contao\Map\Options\LocateOptionsHydrator:
    autoconfigure: false
    tags:
        - { name: 'Cowegis\Bundle\Contao\Hydrator\Hydrator', priority: -32 }
```

(Alternative `#[AsTaggedItem(priority: -32)]` am Konzern-Class wird bewusst nicht
gewählt — der explizite YAML-Override ist besser testbar und eindeutig.)

## 4. Neue PHP-Attribute

### `src/DependencyInjection/Attribute/AsSerializer.php`

```php
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsSerializer
{
    public function __construct(public string $key) {}
}
```

Registrierung in `CowegisContaoExtension::load()`:

```php
$container->registerAttributeForAutoconfiguration(
    AsSerializer::class,
    static function (ChildDefinition $definition, AsSerializer $attribute): void {
        $definition->addTag('Cowegis\Core\Serializer\Serializer', ['key' => $attribute->key]);
    },
);
```

Das gemeinsame Konstruktor-Argument der Serializer (`Cowegis\Core\Serializer\Serializer`,
der delegierende Serializer) wird in `layers.yaml` / `controls.yaml` per
`_defaults` → `bind` gesetzt (Binds greifen auch ohne Autowiring):

```yaml
_defaults:
    autowire: false
    autoconfigure: true
    public: false
    bind:
        Cowegis\Core\Serializer\Serializer $serializer: '@Cowegis\Core\Serializer\Serializer'
```

Der exakte Parametername (`$serializer` o. ä.) wird bei der Umsetzung an den
Serializer-Konstruktoren verifiziert (TDD-Schritt).

Keine weiteren Attribute (`AsLayerType` etc.) — `#[AutoconfigureTag]` am Interface
genügt.

## 5. Interface-Änderungen

An folgende Interfaces wird `#[AutoconfigureTag('<FQCN des Interface>')]` gesetzt:

- `src/Hydrator/Hydrator.php`
- `src/Map/Layer/LayerType.php`
- `src/Map/Control/ControlType.php`
- `src/Map/Icon/IconType.php`
- `src/Map/Style/StyleType.php`

Import: `Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag`.
Der Tag greift nur bei `autoconfigure: true` im YAML-`_defaults` — was künftig überall
der Fall ist.

## 6. YAML-Konvertierung — allgemeine Regeln

Pro Datei:

```yaml
services:
    _defaults:
        autowire: false
        autoconfigure: true
        public: false
```

Übersetzungstabelle:

| XML | YAML |
|---|---|
| `<argument type="service" id="X"/>` | `arguments: ['@X']` |
| `<argument>%param%</argument>` | `arguments: ['%param%']` |
| `<argument type="service" id="X" on-invalid="ignore"/>` | `'@?X'` |
| `<argument type="tagged_iterator" tag="T"/>` | `!tagged_iterator T` |
| `<argument type="tagged_locator" tag="T" index-by="type"/>` | `!tagged_locator { tag: T, index_by: type }` |
| `<service id="I" class="C">` | `I: { class: C, … }` |
| `public="true"` | `public: true` |
| `<tag name="N" k="v"/>` | `tags: [{ name: N, k: v }]` |
| `<call method="m"><argument>a</argument></call>` | `calls: [[m, [a]]]` |
| `<instanceof id="I">…</instanceof>` | `_instanceof: { I: { … } }` |
| `<parameter key="k">v</parameter>` | `parameters: { k: v }` |
| `<parameter key="k" type="collection">…</parameter>` | `parameters: { k: [ … ] }` |

Argument-Reihenfolge exakt erhalten. Service-IDs bleiben unverändert (FQCN bzw.
`cowegis_contao.*`).

## 7. Datei-für-Datei-Plan

### `amenities.xml` → `amenities.yaml`
Reine Parameter-Datei (`cowegis_contao.amenities`, Liste). 1:1 nach `parameters:`.

### `config.xml` → `config.yaml`
Reine Parameter-Datei (`cowegis_contao.file_formats`, verschachtelte Collection).
1:1 nach `parameters:`.

### `styles.xml` → `styles.yaml`
Bereits `autoconfigure="true"`. `FixedStyleType` (Tag via Interface-Attribut →
Tag-Zeile entfällt), `FixedStyleTypeHydrator` (dito über `Hydrator`-Attribut).

### `icons.xml` → `icons.yaml`
Sauberste Datei. 4× `*IconType` + 4× `*Hydrator`, alle Tags entfallen (Interface-
Attribute). `ImageIconHydrator` behält sein `repository_manager`-Argument.

### `fragments.xml` → `fragments.yaml`
Bereits `autoconfigure="true"`. `MapContentElementAction` / `MapModuleAction` —
keine Tags im XML (Attribute `#[AsContentElement]` / `#[AsFrontendModule]` schon
vorhanden). Nur XML→YAML, lange `arguments`-Liste 1:1.

### `controls.xml` → `controls.yaml`
- `*ControlType` → Tag via Interface-Attribut, entfällt.
- `*ControlHydrator` / `*ControlTypeHydrator` → Tag via `Hydrator`-Attribut, entfällt.
  `LayersControlHydrator` behält `database_connection`;
  `GeocoderControlTypeHydrator` behält `'@?Cowegis\ContaoGeocoder\Routing\SearchUrlGenerator'`.
- `*ControlSerializer` (core, `key=`) → `#[AsSerializer(key: …ControlFqcn)]` an der
  Serializer-Klasse; `arguments` nur noch, was `bind` nicht abdeckt (ggf. leer).

### Serializer-Bind (`controls.yaml`, `layers.yaml`)
`_defaults.bind` für `Cowegis\Core\Serializer\Serializer $…` nur in den Dateien mit
Serializer-Services — das sind `controls.yaml` und `layers.yaml`.

### `layers.xml` → `layers.yaml`
- `*LayerType` → Interface-Attribut. `MarkersLayerType` (`MarkerRepository`,
  `translator`), `FileLayerType` (`repository_manager`), `ReferenceLayerType`
  (`LayerRepository`) behalten Argumente.
- `*LayerHydrator` / `*OptionsHydrator` → `Hydrator`-Attribut. Argumente
  (`router`, `response_tagger`, Repositories, `StyleTypeRegistry`, `Serializer`)
  bleiben.
- `*LayerSerializer` / `Marker*Serializer` (core, `key=`) → `#[AsSerializer]`.
- `*SchemaDescriber` (core, String-Ctor-Arg `data`/`tileLayer`/…):
  `_instanceof: { 'Cowegis\Core\Schema\LayerSchemaDescriber': { tags: ['Cowegis\Core\Schema\LayerSchemaDescriber'] } }`;
  String-Argument bleibt in `arguments`. `LayersSchemaDescriber` (Bundle-Klasse,
  kein Arg) fällt ebenfalls unter `_instanceof`.
- `*LayerDataProvider` (`MarkersLayerDataProvider`, `ReferenceLayerDataProvider`,
  `VectorsDataLayerProvider`) → expliziter `tags`-Eintrag mit `type: …`.
  `ReferenceLayerDataProvider` behält `!tagged_locator`.

### `hydrators.xml` → `hydrators.yaml`
- `<instanceof>` entfällt (ersetzt durch `#[AutoconfigureTag]` am `Hydrator`-Interface).
- `MapHydrator` (4 Registry-Argumente), `EventDispatchingHydrator`
  (`event_dispatcher`) behalten Argumente.
- Prioritäts-Hydratoren (`LocateOptionsHydrator`, `BoundsOptionsHydrator` = `-32`;
  `EventDispatchingHydrator` = `-128`): `autoconfigure: false` + expliziter
  Tag mit `priority` (siehe §3).
- Argumentlose Hydratoren (`MapOptionsHydrator`, `ViewHydrator`,
  `LayerObjectOptionsHydrator`, `GridLayerOptionsHydrator`, `PopupPresetHydrator`,
  `TooltipPresetHydrator`) → nur `Klasse: ~` bzw. leerer Eintrag; Tag via Attribut.

### `services.xml` → `services.yaml`
- `Action\Backend\DocsAction` (`public: true`, `twig`, `router`),
  `Action\Backend\MapLayerAction` (`public: true`, 4 Args) — 1:1.
- `ConsentBridge\Plugin` → `tags: [{ name: hofff_contao_consent_bridge.plugin }]`.
- `Cowegis\Core\IdFormat\IntegerIdFormat` → `tags: ['Cowegis\Core\IdFormat\IdFormat']`.
- `Cowegis\Core\Schema\Id\IntegerIdSchema` → `tags: ['Cowegis\Core\Schema\IdSchema']`.
- `Cowegis\Bundle\Contao\Hydrator\Hydrator` (ID = Interface, `class: …DelegatingHydrator`,
  `!tagged_iterator`, `response_tagger`) — 1:1, **kein** `Hydrator`-Tag hier
  (das ist die Sammelstelle, nicht ein Hydrator).
  Achtung: `autoconfigure: true` + `#[AutoconfigureTag]` am Interface ⇒
  `DelegatingHydrator implements Hydrator` würde sich selbst taggen und in den
  eigenen `tagged_iterator` geraten. **Daher hier `autoconfigure: false` setzen.**
- `Provider\ContaoBackendProvider` (`!tagged_locator index_by: type`,
  `tags: ['Cowegis\Core\Provider\Provider']`, mehrere Args) — 1:1.
- `cowegis_contao.slug_generator.options` (`class: Ausi\SlugGenerator\SlugOptions`,
  `calls: [[setValidChars, ['a-z0-9_']], [setDelimiter, ['_']]]`).
- `cowegis_contao.slug_generator` (`class: Ausi\SlugGenerator\SlugGenerator`, 1 Arg).
- 4× `*TypeRegistry` (`!tagged_iterator …LayerType|ControlType|IconType|StyleType`) — 1:1.
- `Schema\ServersSchemaDescriber` (`repository_manager`, `request_stack`,
  `%cowegis_api.api_base_uri%`, `tags: ['Cowegis\Core\Schema\SchemaDescriber']`) — 1:1.

### `listeners.xml` → `listeners.yaml`
Alle Services bleiben als YAML-Einträge (Argumente werden nicht autowired), aber
**alle `contao.callback`- und `kernel.event_listener`-Tag-Zeilen entfallen**
zugunsten von Attributen. `public: true` der DCA-Listener bleibt.

DCA-Listener (`src/EventListener/Dca/`) — je Callback ein `#[AsCallback(table, target)]`
bzw. `#[AsCallback(table, target)]` mit passender Zielmethode (Muster:
`ModuleDcaListener`, `StyleDcaListener`, die das bereits so machen):

- `ContentDcaListener` (`onLoad`, `clientOptions`) — Arg `%cowegis_contao.client_bundle%` bleibt.
- `LayerDcaListener` (7 Callbacks: `typeOptions`, `fileFormatOptions`,
  `amenitiesOptions`, `prepareFileWidget`, `rowLabel`, `editDataButton`, `pasteButtons`).
- `MapLayerSelectionDcaListener` (`initializeMapView`).
- `MapDcaListener` (`layerList`, `showIncompleteConfigurationWarning`).
- `MapLayerDcaListener` (6 Callbacks).
- `MapPaneDcaListener` (`rowLabel`, `onSaveName`).
- `MarkerDcaListener` (`rowLabel`, `saveCoordinates`, `loadCoordinates` — `priority: 128`).
- `IconDcaListener` (`iconOptions`).
- `ControlDcaListener` (5 Callbacks) — Arg `'@?Cowegis\ContaoGeocoder\Provider\Geocoder'` bleibt.
- `OptionsListener` (viele `zoomOptions`-Callbacks über mehrere Tabellen/Felder —
  ein `#[AsCallback]` pro Ziel an derselben Methode).
- `AliasGenerator` (7× `fields.alias.save`, `priority: 128`, teils ohne `method` →
  `__invoke`).
- `Validator` (viele `validateCoordinates` / `validateAlias`, teils `priority: 128`).

`#[AsCallback]` mit `priority` wird unterstützt: `#[AsCallback(table: '…', target: '…', priority: 128)]`.

Event-Listener → `#[AsEventListener]`:

- `BackendMenuListener` — `#[AsEventListener(event: 'contao.backend_menu_build', method: 'onBuild', priority: -255)]`, Arg `request_stack` bleibt.
- `LayerResponseListener` — `#[AsEventListener(event: Cowegis\Bundle\Api\Event\LayerResponseEvent::class)]`, Arg `LayerRepository` bleibt.
- `MapResponseListener` — `#[AsEventListener(event: Cowegis\Bundle\Api\Event\MapResponseEvent::class)]`.
- `Filter\ApplyFilterRuleMarkerListener` — `#[AsEventListener(event: Cowegis\Bundle\Contao\Event\ApplyFilterRuleEvent::class)]`.

`BackendStyleListener`, `ModuleDcaListener`, `StyleDcaListener` tragen die Attribute
bereits — nur XML→YAML.

Hook-Listener:

- `Hook\LanguageFileListener` — `@Hook("loadLanguageFile")`-Annotation →
  `#[AsHook('loadLanguageFile')]`-Attribut
  (`Contao\CoreBundle\DependencyInjection\Attribute\AsHook`). Arg `system_adapter` bleibt.
  Der Service braucht dann `autoconfigure: true` (Default) — greift automatisch.

### `repositories.xml` → `repositories.yaml`
Parameter (`cowegis_contao.model.tl_cowegis_*`) 1:1. 10× Repository, jeweils
`tags: [{ name: netzmacht.contao_toolkit.repository, model: '…Model' }]` bleibt
explizit (kein Toolkit-Attribut vorhanden). Argumente
(`%cowegis_contao.model.*%`, `database_connection`, `event_dispatcher`) bleiben.

### `routing.xml` → `routing.yaml`
2 Routen nach YAML-Routing-Syntax:

```yaml
cowegis_contao_backend_api_docs:
    path: /contao/cowegis/docs
    controller: Cowegis\Bundle\Contao\Action\Backend\DocsAction
    methods: [GET]
    defaults:
        _scope: backend
        _backend_module: cowegis-api-docs

cowegis_contao_backend_map_layer_actions:
    path: /contao/cowegis/map/{mapId}/layer/{layerId}
    controller: Cowegis\Bundle\Contao\Action\Backend\MapLayerAction
    methods: [POST]
    defaults:
        _scope: backend
```

**`src/ContaoManager/Plugin.php` anpassen:** beide Vorkommen von
`__DIR__ . '/../Resources/config/routing.xml'` → `routing.yaml`. Der
`LoaderResolver` wählt dann automatisch den `YamlFileLoader`. Der Verweis auf die
`routing.xml` des **api-bundle** bleibt unverändert.

## 8. Extension-Klasse

`src/DependencyInjection/CowegisContaoExtension.php`:

- `use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;`
  → `YamlFileLoader`.
- `new XmlFileLoader(...)` → `new YamlFileLoader(...)`.
- alle `$loader->load('*.xml')` → `'*.yaml'` (Reihenfolge unverändert:
  amenities, config, controls, fragments, hydrators, icons, styles, layers,
  listeners, services, repositories).
- Neu vor den `load()`-Aufrufen:
  `$container->registerAttributeForAutoconfiguration(AsSerializer::class, …)`
  (Closure siehe §4).
- `kernel.bundles` / `cowegis_contao.client_bundle`-Logik am Ende unverändert.
- `#[Override]` / `declare(strict_types=1)` bleiben.

## 9. PHPUnit über phpcq / companion

1. `companion.json`:
   - `tools.phpcq.plugins.phpunit`: `false` → `true` (phpspec bleibt `true`).
   - `config.directories`: `["spec"]` → `["spec", "tests"]`.
   - `receipts` ggf. auf Contao-5-Rezept umstellen (siehe §2).
2. `companion project:configure` ausführen → regeneriert `.phpcq.yaml.dist`
   (phpunit-Plugin-Block + `phpunit` in der `analyze`-Task-Liste), ggf.
   `phpunit.xml.dist`, `composer.json` `require-dev` (`phpunit/phpunit`).
3. `vendor/bin/phpcq update` → Toolchain installieren.
4. Falls `companion` `phpunit` **nicht** in `tasks.analyze` einträgt: manuell
   ergänzen (nach `phpspec`).
5. Falls kein `phpunit.xml.dist` erzeugt wird: manuell anlegen — Testsuite `tests/`,
   Bootstrap `vendor/autoload.php`, `colors=true`, `failOnWarning`/`failOnDeprecation`
   nach Projektüblichkeit.
6. `composer.json` `autoload-dev`: PSR-4 `"Cowegis\\Bundle\\Contao\\Test\\": "tests/"`
   ergänzen (neben dem bestehenden `spec\…`). `composer dump-autoload`.
7. Kontrolllauf: `vendor/bin/phpcq run phpunit`, danach `vendor/bin/phpcq run`.

Alle konkreten Nachjustierungen (Task-Liste, `phpunit.xml.dist`) hängen vom Output
von `companion project:configure` ab und werden bei der Umsetzung final festgelegt.

## 10. Container-Kompilierungstest (PHPUnit, handgeschrieben)

Verzeichnis `tests/`, Namespace `Cowegis\Bundle\Contao\Test\`.

### `tests/DependencyInjection/StubContainerFactory.php` (Basis-Helfer)

Baut einen `ContainerBuilder`, in dem alle **externen** Service-IDs, die die
YAML-Dateien referenzieren, als synthetische bzw. `stdClass`-Definitionen
registriert sind, dazu die benötigten Parameter. Danach lädt der Test die Extension
und ruft `compile()`.

Zu stubbende IDs (aus der Gesamtschau der Config-Dateien):

- Contao: `contao.framework`, `contao.insert_tag.parser`,
  `contao.security.token_checker`, `database_connection`
- Symfony: `twig`, `router`, `request_stack`, `event_dispatcher`, `translator`,
  `psr18.http_client`
- Toolkit: `netzmacht.contao_toolkit.repository_manager`,
  `netzmacht.contao_toolkit.response_tagger`,
  `netzmacht.contao_toolkit.csrf.token_provider`,
  `netzmacht.contao_toolkit.dca.manager`,
  `netzmacht.contao_toolkit.assets_manager`,
  `netzmacht.contao_toolkit.routing.scope_matcher`,
  `netzmacht.contao_toolkit.contao.backend_adapter`,
  `netzmacht.contao_toolkit.contao.system_adapter`,
  `netzmacht.contao_toolkit.contao.input_adapter`,
  `netzmacht.contao_toolkit.callback_invoker`,
  `netzmacht.contao_toolkit.template_renderer`
- cowegis-core / api-bundle: `Cowegis\Core\Filter\FilterFactory`,
  `Cowegis\Core\Serializer\Serializer`
- Parameter: `cowegis_api.api_base_uri`, `kernel.bundles` (`[]`), `kernel.debug`,
  weitere von der Extension erwartete (`kernel.*`)

Optionale Services (`@?…Geocoder`, `@?…SearchUrlGenerator`) werden **nicht**
registriert — der Test prüft damit auch das `on-invalid: ignore`-Verhalten.

### `tests/DependencyInjection/CowegisContaoExtensionTest.php`

Fälle:

1. `test_container_compiles` — `compile()` wirft nicht.
2. `test_core_services_registered` — Existenz + `getClass()` für:
   `Provider\ContaoBackendProvider`, `Hydrator\Hydrator`
   (Klasse `DelegatingHydrator`), `Map\MapHydrator`, die vier `*TypeRegistry`.
3. `test_public_flags` — `DocsAction`, `MapLayerAction`, DCA-Listener sind `public`;
   Registries/Hydratoren sind `private`.
4. `test_hydrator_tag_collection` — Anzahl der Services mit Tag
   `Cowegis\Bundle\Contao\Hydrator\Hydrator` entspricht erwarteter Liste; jeder
   Service genau **einmal** getaggt.
5. `test_hydrator_priorities` — `LocateOptionsHydrator` / `BoundsOptionsHydrator`
   haben `priority -32`, `EventDispatchingHydrator` `priority -128`;
   `DelegatingHydrator` trägt **keinen** `Hydrator`-Tag.
6. `test_layer_control_icon_style_type_tags` — je erwartete Menge an
   `LayerType` / `ControlType` / `IconType` / `StyleType`-getaggten Services.
7. `test_serializer_tags` — mind. `TileLayerSerializer` trägt
   `Cowegis\Core\Serializer\Serializer` mit korrektem `key`.
8. `test_contao_callback_tags` — je Tabelle (`tl_cowegis_map`, `tl_cowegis_layer`,
   `tl_cowegis_map_layer`, …) die erwartete Anzahl `contao.callback`-Tags gegen
   eine im Test hinterlegte Sollliste (fängt vertippte `#[AsCallback]`-Ziele/-Methoden).
9. `test_event_listener_tags` — `BackendMenuListener` etc. tragen
   `kernel.event_listener` mit erwartetem `event`/`priority`.
10. `test_layer_data_provider_locator` — `ContaoBackendProvider` bekommt einen
    `index-by="type"`-Locator; `markers`/`reference`/`vectors` sind enthalten.

Zum Prüfen der Tags **vor** dem vollständigen `compile()` (das Tags entfernt):
zweiter `ContainerBuilder`, nur `load()` + `AttributeAutoconfigurationPass`-relevante
Verarbeitung, dann `findTaggedServiceIds()`. Alternativ Tags direkt nach `load()` +
manуellem `registerForAutoconfiguration`-Durchlauf inspizieren. Konkrete Mechanik
wird im ersten TDD-Schritt festgeklopft (Test zuerst gegen den **XML-Zustand**
grün bekommen).

## 11. CLAUDE.md-Anpassungen

- Kopf: „supports Contao `^4.13 || ^5.3` and Symfony `5.4 / 6.4 / 7.4`" →
  „supports Contao `^5.3` and Symfony `6.4 / 7.4`".
- Abschnitt **Commands / Tests**: PHPUnit ergänzen —
  `vendor/bin/phpcq run phpunit`; Tests in `tests/`, Namespace
  `Cowegis\Bundle\Contao\Test`.
- Abschnitt **Service wiring**: „Autowiring and autoconfiguration are **off** in
  every file" → „Autowiring is **off**; autoconfiguration is **on**. Marker-interface
  tags come from `#[AutoconfigureTag]` on the interface (`Hydrator`, `LayerType`,
  `ControlType`, `IconType`, `StyleType`), from `_instanceof`
  (`Cowegis\Core\Schema\LayerSchemaDescriber`), or from `#[AsSerializer]` /
  `#[AsCallback]` / `#[AsEventListener]` / `#[AsHook]`. Every constructor argument is
  still wired by hand. Attribute-carrying non-autoconfigurable tags
  (`netzmacht.contao_toolkit.repository`, `Cowegis\...\Provider\LayerDataProvider`,
  core `Provider` / `SchemaDescriber` / `IdFormat` / `IdSchema`) stay as explicit
  `tags:` in YAML."
- Abschnitt **Type + Registry + Hydrator pattern** / **Adding a layer type**:
  `layers.xml` → `layers.yaml`; „register both in `layers.yaml`" — der
  `LayerType`-Tag kommt jetzt automatisch übers Interface, nur `LayerHydrator`-Tag
  bzw. Argumente müssen gesetzt werden; Serializer via `#[AsSerializer]`.
- Konvention zu `.xml`-Config-Dateien → `.yaml` (Config-Format YAML).

## 12. Umsetzungsreihenfolge (TDD)

1. **Versions-Constraints** in `composer.json` senken, `composer update`,
   `composer-require-checker` grün.
2. **PHPUnit-Infrastruktur**: `companion.json` anpassen,
   `companion project:configure`, `phpcq update`, `autoload-dev`, leerer
   Smoke-Test `tests/` grün (`phpcq run phpunit`).
3. **`AsSerializer`-Attribut** + Extension-`registerAttributeForAutoconfiguration`
   (noch ohne Wirkung, XML unverändert).
4. **Interface-Attribute** `#[AutoconfigureTag]` setzen.
5. **Container-Kompilierungstest** gegen den **noch-XML**-Zustand schreiben und
   grün bekommen (Regressionsnetz mit Solllisten für Tag-Zahlen).
6. **Datei für Datei** XML→YAML + zugehörige PHP-Attribute; nach jeder Datei
   `phpcq run phpunit` + `phpcq run psalm`. Reihenfolge: erst die einfachen
   (`amenities`, `config`, `icons`, `styles`, `fragments`), dann `controls`,
   `hydrators`, `layers`, `services`, `repositories`, zuletzt `listeners`.
7. **`routing.xml` → `routing.yaml`** + `Plugin.php` anpassen.
8. **XML-Dateien löschen**, `CowegisContaoExtension` auf `YamlFileLoader` +
   `.yaml`-Namen umstellen (falls nicht schon in Schritt 6 schrittweise erfolgt —
   empfohlen: pro Datei sofort im Loader umbenennen).
9. **Voller QA-Lauf** `vendor/bin/phpcq run` (psalm L3, phpcs Doctrine, rector,
   phpmd, phpcpd, composer-require-checker, phpspec, phpunit).
10. **`CLAUDE.md`** aktualisieren.

## 13. Risiken & Gegenmaßnahmen

| Risiko | Gegenmaßnahme |
|---|---|
| `#[AsCallback]`-Ziel/-Methode vertippt → stiller Callback-Verlust | Kompilierungstest mit Sollliste der `contao.callback`-Tag-Anzahl pro Tabelle (Schritt 5, gegen XML kalibriert) |
| Doppelte Marker-Tags (Attribut **und** `_instanceof`/expliziter Tag) → Service doppelt im `tagged_iterator` | Grundregel §3; Test „jeder Service genau einmal getaggt" |
| `DelegatingHydrator` taggt sich selbst (implementiert `Hydrator`) → Rekursion im eigenen Iterator | `autoconfigure: false` für den Sammel-Service `Cowegis\Bundle\Contao\Hydrator\Hydrator` in `services.yaml`; Test prüft „kein `Hydrator`-Tag" |
| Hydrator-Prioritäten (`-32`, `-128`) gehen verloren | expliziter YAML-Override + dedizierter Test |
| `bind` für `Cowegis\Core\Serializer\Serializer` trifft falschen Parameternamen | Parameternamen an den Serializer-Konstruktoren verifizieren; Kompilierungstest deckt fehlende Args auf |
| `routing.yaml` wird vom Manager-Plugin nicht geladen | `Plugin.php` beide Pfade umstellen; manueller Funktionstest der Backend-Routen bzw. `debug:router` |
| `companion project:configure` überschreibt manuelle `.phpcq.yaml.dist`-Anpassungen | zuerst `companion` laufen lassen, dann Diff prüfen und nur Ergänzungen (phpunit-Task) nachziehen |
| Symfony-6.4-Kompatibilität von `#[AsCallback(priority: …)]` / `#[AsHook]` | Contao 5.3 unterstützt beide; im vollen QA-Lauf abgesichert |
| `composer-require-checker` meckert neue `use` (`AutoconfigureTag`, `AsSerializer`, `AsHook`) | `AsCallback`/`AsEventListener`/`AsContentElement` bereits im Baum und erlaubt; ggf. `.composer-require-checker.json` ergänzen |

## 14. Verifikation / QA-Gates

- `vendor/bin/phpcq run` grün auf PHP 8.2 / 8.3 / 8.4 (CI).
- `vendor/bin/phpcq run phpunit` grün (neue Service-Tests).
- `vendor/bin/phpcq run psalm` — errorLevel 3, keine neuen Suppressions außer
  begründet.
- `vendor/bin/phpcq run rector` — `declare(strict_types=1)`, `#[Override]`,
  `final` weiterhin erfüllt.
- `composer-require-checker` grün nach Constraint-Senkung.
- Manuell: `debug:container` / `debug:router` in einer Contao-5-Testinstanz
  (Backend-Module + Map-Rendering rauchgetestet).
