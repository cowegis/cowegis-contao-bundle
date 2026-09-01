# XML → YAML Service-Konfiguration + Autokonfiguration – Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Die Symfony-DI-Konfiguration des Bundles vollständig von XML auf YAML umstellen, `autoconfigure` aktivieren (Autowiring bleibt aus), redundante Marker-Tags durch `registerForAutoconfiguration()` / PHP-Attribute ersetzen und mit einem PHPUnit-Container-Kompilierungstest absichern.

**Architecture:** `CowegisContaoExtension` lädt künftig `*.yaml` statt `*.xml`. Fünf Bundle-Marker-Interfaces (`Hydrator`, `LayerType`, `ControlType`, `IconType`, `StyleType`) werden per `registerForAutoconfiguration()` in der Extension getaggt; Contao-/Symfony-Tags (`contao.callback`, `kernel.event_listener`, Contao-Hook) wandern in `#[AsCallback]` / `#[AsEventListener]` / `#[AsHook]` an den Listener-Klassen. Attribut-tragende Fremd-Tags (`Serializer` `key`, `repository` `model`, `LayerDataProvider` `type`, core `Provider`/`SchemaDescriber`/`IdFormat`/`IdSchema`) bleiben explizite `tags:` im YAML. Konstruktor-Argumente werden weiterhin von Hand verdrahtet.

**Tech Stack:** PHP 8.2+, Symfony DependencyInjection/Config 6.4|7.4, Contao 5.3+, phpcq (psalm L3, phpcs Doctrine, rector), phpspec (bestehend), PHPUnit (neu, via companion/phpcq), `companion` CLI.

**Spec:** `docs/superpowers/specs/2026-09-01-xml-to-yaml-service-config-design.md`

## Global Constraints

- **Autowiring bleibt aus.** `_defaults: { autowire: false }` in jeder Service-Datei; jedes Konstruktor-Argument explizit in `arguments:`.
- **`autoconfigure: true`** ist der Default in jeder Service-Datei – **außer** bei diesen Services/Dateien, die `autoconfigure: false` bekommen (sonst Doppel-Tags / Selbst-Referenz):
  - `Cowegis\Bundle\Contao\Hydrator\Hydrator` (der `DelegatingHydrator`-Sammel-Service in `services.yaml`)
  - `Cowegis\Bundle\Contao\Map\Options\LocateOptionsHydrator`, `…\BoundsOptionsHydrator`, `Cowegis\Bundle\Contao\Hydrator\EventDispatchingHydrator` (Prioritäts-Hydratoren in `hydrators.yaml`)
  - `Cowegis\Bundle\Contao\ConsentBridge\Plugin` (in `services.yaml`)
  - gesamte `repositories.yaml` (`_defaults: autoconfigure: false`)
- **Service-IDs bleiben unverändert** (FQCN bzw. `cowegis_contao.*`). **Argument-Reihenfolge bleibt exakt erhalten.**
- **Versionsmatrix:** Contao `^5.3`, Symfony `^6.4 || ^7.4`, `netzmacht/contao-toolkit ^4.0`. Kein Contao 4.13 / Symfony 5.
- **`declare(strict_types=1)`** in jeder neuen PHP-Datei; neue Klassen `final`; `#[Override]` auf überschriebenen Methoden (rector-enforced).
- **phpspec bleibt unverändert** und in der QA-Chain; PHPUnit kommt **zusätzlich**.
- **Neue Test-Klassen:** Namespace `Cowegis\Bundle\Contao\Test\`, Verzeichnis `tests/`. Keine neuen `require`/`require-dev` außer `phpunit/phpunit` (von companion gesetzt).
- **YAML-Einrückung:** 4 Leerzeichen (wie bestehende XML-Struktur-Tiefe), UTF-8, keine Tabs.
- Nach **jeder** Task: `vendor/bin/phpcq run phpunit` und `vendor/bin/phpcq run psalm` müssen grün sein, bevor committet wird.

---

## File Structure

**Neu:**
- `tests/DependencyInjection/StubContainerFactory.php` – baut einen `ContainerBuilder` mit gestubbten Fremd-Services + Parametern, lädt `CowegisContaoExtension`, kompiliert ohne Removing-Passes.
- `tests/DependencyInjection/CowegisContaoExtensionTest.php` – Container kompiliert; Kern-Services existieren/Klasse/public-Flag; `registerForAutoconfiguration`-Tags; explizite YAML-Tags; Argument-Anzahl.
- `tests/EventListener/ListenerAttributeCoverageTest.php` – Reflection-Scan aller Listener-Klassen; `#[AsCallback]` / `#[AsEventListener]` / `#[AsHook]`-Mengen == Goldliste aus dem alten XML.
- `src/Resources/config/*.yaml` (11 Dateien) – ersetzen die `*.xml`.
- `phpunit.xml.dist` – falls `companion` keine erzeugt.

**Geändert:**
- `composer.json` – Versions-Constraints, `autoload-dev`.
- `companion.json` – `phpunit: true`, `directories`.
- `.phpcq.yaml.dist` – (via `companion project:configure`) phpunit-Plugin + Task.
- `src/DependencyInjection/CowegisContaoExtension.php` – `YamlFileLoader`, `registerForAutoconfiguration`, `.yaml`-Dateinamen.
- `src/ContaoManager/Plugin.php` – `routing.xml` → `routing.yaml` (2 Stellen).
- `src/EventListener/**/*Listener.php`, `src/EventListener/Dca/AliasGenerator.php`, `src/EventListener/Dca/Validator.php`, `src/EventListener/Hook/LanguageFileListener.php` – PHP-Attribute.
- `CLAUDE.md` – Konventionen, Versionszeile, Test-Kommandos.

**Gelöscht (am Ende):** `src/Resources/config/*.xml` (alle 11).

---

## Task 1: Versions-Constraints senken

**Files:**
- Modify: `composer.json`
- Modify: `CLAUDE.md:18`

- [ ] **Step 1: `composer.json` `require` anpassen**

Ersetze exakt diese Zeilen:

```json
    "contao/core-bundle": "^4.13 || ^5.3",
```
→
```json
    "contao/core-bundle": "^5.3",
```

```json
    "netzmacht/contao-toolkit": "^3.8 || ^4.0",
```
→
```json
    "netzmacht/contao-toolkit": "^4.0",
```

Und jede dieser fünf Zeilen (`symfony/config`, `symfony/dependency-injection`, `symfony/event-dispatcher`, `symfony/http-foundation`, `symfony/http-kernel`, `symfony/routing`):

```json
    "symfony/<name>": "^5.4 || ^6.4 || ^7.4",
```
→
```json
    "symfony/<name>": "^6.4 || ^7.4",
```

`symfony/translation-contracts`:
```json
    "symfony/translation-contracts": "^1.0 || ^2.0 || ^3.0",
```
→
```json
    "symfony/translation-contracts": "^2.0 || ^3.0",
```

- [ ] **Step 2: `CLAUDE.md` Versionszeile anpassen**

`CLAUDE.md` Zeile 18:
```
PHP `^8.2`; supports Contao `^4.13 || ^5.3` and Symfony `5.4 / 6.4 / 7.4`. CI runs on PHP 8.2, 8.3, 8.4.
```
→
```
PHP `^8.2`; supports Contao `^5.3` and Symfony `6.4 / 7.4`. CI runs on PHP 8.2, 8.3, 8.4.
```

- [ ] **Step 3: Abhängigkeiten aktualisieren**

Run: `composer update --with-all-dependencies 2>&1 | tail -20`
Expected: erfolgreich, keine Konflikte. Falls `psr/container`-Konflikt: `psr/container` unverändert lassen (Contao 5 verträgt `^1.1 || ^2.0`).

- [ ] **Step 4: composer-require-checker**

Run: `vendor/bin/phpcq run composer-require-checker 2>&1 | tail -20`
Expected: PASS (keine neuen unbekannten Symbole – es wurde nur eingeschränkt).

- [ ] **Step 5: Voller Bestandslauf als Referenz**

Run: `vendor/bin/phpcq run 2>&1 | tail -30`
Expected: grün (Baseline vor Umbau). Falls rector/psalm durch neue Symfony-Version neue Findings zeigt: hier noch **nicht** fixen, nur notieren.

- [ ] **Step 6: Commit**

```bash
git add composer.json composer.lock CLAUDE.md
git commit -m "Drop Contao 4.13 / Symfony 5 support"
```

---

## Task 2: PHPUnit-Infrastruktur über companion/phpcq

**Files:**
- Modify: `companion.json`
- Modify: `composer.json` (`autoload-dev`)
- Modify: `.phpcq.yaml.dist` (via `companion`, ggf. manuell nachziehen)
- Create: `phpunit.xml.dist` (falls nicht von companion erzeugt)
- Create: `tests/SmokeTest.php`

**Interfaces:**
- Produces: lauffähiges `vendor/bin/phpcq run phpunit`; PSR-4 `Cowegis\Bundle\Contao\Test\` → `tests/`.

- [ ] **Step 1: `companion.json` anpassen**

In `companion.json`:
- `tools.phpcq.plugins.phpunit`: `false` → `true` (Zeile mit `"phpunit": false`).
- `config.directories`: `["spec"]` → `["spec", "tests"]`.

- [ ] **Step 2: companion-Rezept prüfen**

Run: `companion receipts 2>&1 | head -40` (oder `companion list-receipts` / `companion receipts:list` – die verfügbare Variante nutzen)
- Falls ein Contao-5-only-Rezept (z. B. `projects/contao-bundle/5.3`) existiert: in `companion.json` `receipts` den Eintrag `projects/contao-bundle/4.13-5.3` dadurch ersetzen.
- Falls nicht: Eintrag unverändert lassen.

- [ ] **Step 3: `companion project:configure` ausführen**

Run: `companion project:configure 2>&1 | tail -40`
Expected: regeneriert `.phpcq.yaml.dist` (u. a. `plugins.phpunit`-Block), ggf. `phpunit.xml.dist`, ggf. `composer.json` `require-dev` `phpunit/phpunit`.

- [ ] **Step 4: `.phpcq.yaml.dist` prüfen und Task ergänzen**

`git diff .phpcq.yaml.dist` ansehen. Sicherstellen, dass:
- unter `plugins:` ein `phpunit:`-Block existiert (analog zu `phpspec:`).
- unter `tasks.analyze:` der Eintrag `phpunit` **nach** `phpspec` steht. Falls `companion` ihn nicht eingetragen hat, manuell ergänzen:

```yaml
  analyze:
    - phpcpd
    - phploc
    - phpmd
    - psalm
    - rector
    - phpcs
    - phpspec
    - phpunit
```

- [ ] **Step 5: `composer.json` `autoload-dev` ergänzen**

```json
    "autoload-dev": {
        "psr-4": {
            "spec\\Cowegis\\Bundle\\Contao\\": "spec/",
            "Cowegis\\Bundle\\Contao\\Test\\": "tests/"
        }
    },
```

Run: `composer dump-autoload`

- [ ] **Step 6: `phpunit.xml.dist` sicherstellen**

Falls `companion` keine erzeugt hat, `phpunit.xml.dist` anlegen:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         failOnRisky="true"
         failOnWarning="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="unit">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

Falls `.phpunit.cache` neu ist: zu `.gitignore` hinzufügen (prüfen ob schon drin).

- [ ] **Step 7: phpcq-Toolchain installieren**

Run: `vendor/bin/phpcq update -v 2>&1 | tail -20`
Expected: phpunit-Plugin wird installiert.

- [ ] **Step 8: Smoke-Test schreiben**

`tests/SmokeTest.php`:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Test;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testPhpunitRuns(): void
    {
        self::assertTrue(true);
    }
}
```

- [ ] **Step 9: PHPUnit über phpcq laufen lassen**

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -20`
Expected: PASS (1 Test, 1 Assertion).

- [ ] **Step 10: Commit**

```bash
git add companion.json composer.json composer.lock .phpcq.yaml.dist .phpcq.lock phpunit.xml.dist .gitignore tests/SmokeTest.php
git commit -m "Add PHPUnit via companion/phpcq alongside phpspec"
```

---

## Task 3: Stub-Container + Baseline-Kompilierungstest (gegen XML)

**Files:**
- Create: `tests/DependencyInjection/StubContainerFactory.php`
- Create: `tests/DependencyInjection/CowegisContaoExtensionTest.php`

**Interfaces:**
- Produces:
  - `StubContainerFactory::create(): ContainerBuilder` – Extension geladen, **nicht** kompiliert.
  - `StubContainerFactory::compile(ContainerBuilder $c): void` – kompiliert ohne Removing-/AfterRemoving-Passes.
  - `StubContainerFactory::STUB_SERVICE_IDS: string[]` – Liste der gestubbten Fremd-Service-IDs.

- [ ] **Step 1: `StubContainerFactory` schreiben**

`tests/DependencyInjection/StubContainerFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Test\DependencyInjection;

use Cowegis\Bundle\Contao\DependencyInjection\CowegisContaoExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class StubContainerFactory
{
    /** @var list<string> */
    public const STUB_SERVICE_IDS = [
        'twig',
        'router',
        'request_stack',
        'event_dispatcher',
        'translator',
        'database_connection',
        'contao.framework',
        'contao.insert_tag.parser',
        'contao.security.token_checker',
        'psr18.http_client',
        'netzmacht.contao_toolkit.repository_manager',
        'netzmacht.contao_toolkit.response_tagger',
        'netzmacht.contao_toolkit.csrf.token_provider',
        'netzmacht.contao_toolkit.dca.manager',
        'netzmacht.contao_toolkit.assets_manager',
        'netzmacht.contao_toolkit.routing.scope_matcher',
        'netzmacht.contao_toolkit.contao.backend_adapter',
        'netzmacht.contao_toolkit.contao.system_adapter',
        'netzmacht.contao_toolkit.contao.input_adapter',
        'netzmacht.contao_toolkit.callback_invoker',
        'netzmacht.contao_toolkit.template_renderer',
        'Cowegis\Core\Filter\FilterFactory',
        'Cowegis\Core\Serializer\Serializer',
    ];

    public static function create(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.debug', false);
        $container->setParameter('cowegis_api.api_base_uri', '/cowegis/api');

        foreach (self::STUB_SERVICE_IDS as $id) {
            $definition = new Definition(\stdClass::class);
            $definition->setPublic(true);
            $definition->setSynthetic(true);
            $container->setDefinition($id, $definition);
        }

        (new CowegisContaoExtension())->load([], $container);

        return $container;
    }

    public static function compile(ContainerBuilder $container): void
    {
        $passConfig = $container->getCompilerPassConfig();
        $passConfig->setRemovingPasses([]);
        $passConfig->setAfterRemovingPasses([]);

        $container->compile();
    }
}
```

- [ ] **Step 2: Baseline-Test schreiben**

`tests/DependencyInjection/CowegisContaoExtensionTest.php`:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Test\DependencyInjection;

use Cowegis\Bundle\Contao\Hydrator\DelegatingHydrator;
use Cowegis\Bundle\Contao\Provider\ContaoBackendProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CowegisContaoExtensionTest extends TestCase
{
    private const HYDRATOR_TAG = 'Cowegis\Bundle\Contao\Hydrator\Hydrator';
    private const LAYER_TYPE_TAG = 'Cowegis\Bundle\Contao\Map\Layer\LayerType';
    private const CONTROL_TYPE_TAG = 'Cowegis\Bundle\Contao\Map\Control\ControlType';
    private const ICON_TYPE_TAG = 'Cowegis\Bundle\Contao\Map\Icon\IconType';
    private const STYLE_TYPE_TAG = 'Cowegis\Bundle\Contao\Map\Style\StyleType';
    private const SERIALIZER_TAG = 'Cowegis\Core\Serializer\Serializer';
    private const LAYER_DATA_PROVIDER_TAG = 'Cowegis\Bundle\Contao\Provider\LayerDataProvider';
    private const REPOSITORY_TAG = 'netzmacht.contao_toolkit.repository';

    private static function compiledContainer(): ContainerBuilder
    {
        $container = StubContainerFactory::create();
        StubContainerFactory::compile($container);

        return $container;
    }

    public function testContainerCompiles(): void
    {
        $this->expectNotToPerformAssertions();
        self::compiledContainer();
    }

    public function testCoreServicesAreRegistered(): void
    {
        $container = self::compiledContainer();

        self::assertTrue($container->hasDefinition(ContaoBackendProvider::class));
        self::assertSame(
            DelegatingHydrator::class,
            $container->getDefinition(self::HYDRATOR_TAG)->getClass(),
        );
        self::assertTrue($container->hasDefinition('Cowegis\Bundle\Contao\Map\MapHydrator'));

        foreach ([
            'Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry',
            'Cowegis\Bundle\Contao\Map\Control\ControlTypeRegistry',
            'Cowegis\Bundle\Contao\Map\Icon\IconTypeRegistry',
            'Cowegis\Bundle\Contao\Map\Style\StyleTypeRegistry',
        ] as $id) {
            self::assertTrue($container->hasDefinition($id), $id);
        }
    }

    public function testPublicFlags(): void
    {
        $container = self::compiledContainer();

        self::assertTrue($container->getDefinition('Cowegis\Bundle\Contao\Action\Backend\DocsAction')->isPublic());
        self::assertTrue($container->getDefinition('Cowegis\Bundle\Contao\Action\Backend\MapLayerAction')->isPublic());
        self::assertTrue($container->getDefinition('Cowegis\Bundle\Contao\EventListener\Dca\LayerDcaListener')->isPublic());
        self::assertFalse($container->getDefinition('Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry')->isPublic());
    }

    public function testDelegatingHydratorIsNotItselfTagged(): void
    {
        $container = self::compiledContainer();

        self::assertSame(
            [],
            $container->getDefinition(self::HYDRATOR_TAG)->getTag(self::HYDRATOR_TAG),
        );
    }

    public function testHydratorTagCollection(): void
    {
        $container = self::compiledContainer();
        $ids = array_keys($container->findTaggedServiceIds(self::HYDRATOR_TAG));
        sort($ids);

        self::assertSame(self::expectedHydratorIds(), $ids);

        foreach ($container->findTaggedServiceIds(self::HYDRATOR_TAG) as $id => $tags) {
            self::assertCount(1, $tags, $id . ' must carry the Hydrator tag exactly once');
        }
    }

    public function testHydratorPriorities(): void
    {
        $container = self::compiledContainer();
        $tagged = $container->findTaggedServiceIds(self::HYDRATOR_TAG);

        self::assertSame(-32, $tagged['Cowegis\Bundle\Contao\Map\Options\LocateOptionsHydrator'][0]['priority'] ?? 0);
        self::assertSame(-32, $tagged['Cowegis\Bundle\Contao\Map\Options\BoundsOptionsHydrator'][0]['priority'] ?? 0);
        self::assertSame(-128, $tagged['Cowegis\Bundle\Contao\Hydrator\EventDispatchingHydrator'][0]['priority'] ?? 0);
    }

    public function testTypeTagCounts(): void
    {
        $container = self::compiledContainer();

        self::assertCount(8, $container->findTaggedServiceIds(self::LAYER_TYPE_TAG));
        self::assertCount(7, $container->findTaggedServiceIds(self::CONTROL_TYPE_TAG));
        self::assertCount(4, $container->findTaggedServiceIds(self::ICON_TYPE_TAG));
        self::assertCount(1, $container->findTaggedServiceIds(self::STYLE_TYPE_TAG));
    }

    public function testSerializerKeyTag(): void
    {
        $container = self::compiledContainer();
        $tags = $container->getDefinition('Cowegis\Core\Serializer\Layer\TileLayerSerializer')->getTag(self::SERIALIZER_TAG);

        self::assertSame('Cowegis\Core\Definition\Layer\TileLayer', $tags[0]['key'] ?? null);
    }

    public function testLayerDataProviderTypes(): void
    {
        $container = self::compiledContainer();
        $types = [];
        foreach ($container->findTaggedServiceIds(self::LAYER_DATA_PROVIDER_TAG) as $tags) {
            $types[] = $tags[0]['type'] ?? null;
        }
        sort($types);

        self::assertSame(['markers', 'reference', 'vectors'], $types);
    }

    public function testRepositoryTagCount(): void
    {
        $container = self::compiledContainer();

        self::assertCount(10, $container->findTaggedServiceIds(self::REPOSITORY_TAG));
    }

    /** @return list<string> */
    private static function expectedHydratorIds(): array
    {
        $ids = [
            'Cowegis\Bundle\Contao\Hydrator\EventDispatchingHydrator',
            'Cowegis\Bundle\Contao\Map\MapHydrator',
            'Cowegis\Bundle\Contao\Map\Options\MapOptionsHydrator',
            'Cowegis\Bundle\Contao\Map\Options\LocateOptionsHydrator',
            'Cowegis\Bundle\Contao\Map\Options\BoundsOptionsHydrator',
            'Cowegis\Bundle\Contao\Map\Options\ViewHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\LayerObjectOptionsHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\GridLayerOptionsHydrator',
            'Cowegis\Bundle\Contao\Map\Presets\PopupPresetHydrator',
            'Cowegis\Bundle\Contao\Map\Presets\TooltipPresetHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Tile\TileLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator\MarkerOptionsHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator\MarkersLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator\MarkerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\File\FileLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Group\GroupLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\MarkerCluster\MarkerClusterGroupHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Reference\ReferenceLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Overpass\OverpassLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Layer\Vector\VectorsLayerHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Attribution\AttributionControlHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Fullscreen\FullscreenControlTypeHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Layers\LayersControlHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Loading\LoadingControlHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Scale\ScaleControlHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Zoom\ZoomControlHydrator',
            'Cowegis\Bundle\Contao\Map\Control\Geocoder\GeocoderControlTypeHydrator',
            'Cowegis\Bundle\Contao\Map\Icon\Image\ImageIconHydrator',
            'Cowegis\Bundle\Contao\Map\Icon\Div\DivIconHydrator',
            'Cowegis\Bundle\Contao\Map\Icon\Svg\SvgIconHydrator',
            'Cowegis\Bundle\Contao\Map\Icon\FontAwesome\FontAwesomeIconHydrator',
            'Cowegis\Bundle\Contao\Map\Style\Fixed\FixedStyleTypeHydrator',
        ];
        sort($ids);

        return $ids;
    }
}
```

- [ ] **Step 3: Test gegen den aktuellen XML-Zustand laufen lassen**

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -40`
Expected: alle Tests **grün**. Falls `testHydratorTagCollection` fehlschlägt: die tatsächliche Liste aus der Fehlermeldung übernehmen (eine Klasse implementiert `Hydrator` evtl. nicht / zusätzlich) und `expectedHydratorIds()` korrigieren – **die XML-Realität ist die Wahrheit**, der Test wird darauf kalibriert. Analog `testTypeTagCounts` / `testRepositoryTagCount` an die XML-Realität anpassen.

- [ ] **Step 4: Auch direkt via PHPUnit-Binary gegenprüfen**

Run: `vendor/bin/phpunit --testdox tests/DependencyInjection 2>&1 | tail -40`
Expected: identisch grün.

- [ ] **Step 5: Commit**

```bash
git add tests/DependencyInjection/
git commit -m "Add container compilation test calibrated against current XML config"
```

---

## Task 4: registerForAutoconfiguration + styles.xml entschlacken

**Files:**
- Modify: `src/DependencyInjection/CowegisContaoExtension.php`
- Modify: `src/Resources/config/styles.xml`

**Interfaces:**
- Consumes: `StubContainerFactory` aus Task 3.
- Produces: fünf `registerForAutoconfiguration()`-Tags aktiv für alle Services mit `autoconfigure: true`.

- [ ] **Step 1: Extension um `registerForAutoconfiguration` erweitern**

`src/DependencyInjection/CowegisContaoExtension.php` – Importe ergänzen:

```php
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Control\ControlType;
use Cowegis\Bundle\Contao\Map\Icon\IconType;
use Cowegis\Bundle\Contao\Map\Layer\LayerType;
use Cowegis\Bundle\Contao\Map\Style\StyleType;
```

In `load()`, **direkt nach** `$loader = new XmlFileLoader(...);` und **vor** dem ersten `$loader->load(...)`:

```php
$container->registerForAutoconfiguration(Hydrator::class)
    ->addTag('Cowegis\Bundle\Contao\Hydrator\Hydrator');
$container->registerForAutoconfiguration(LayerType::class)
    ->addTag('Cowegis\Bundle\Contao\Map\Layer\LayerType');
$container->registerForAutoconfiguration(ControlType::class)
    ->addTag('Cowegis\Bundle\Contao\Map\Control\ControlType');
$container->registerForAutoconfiguration(IconType::class)
    ->addTag('Cowegis\Bundle\Contao\Map\Icon\IconType');
$container->registerForAutoconfiguration(StyleType::class)
    ->addTag('Cowegis\Bundle\Contao\Map\Style\StyleType');
```

- [ ] **Step 2: Verifizieren, dass die Interface-FQCN stimmen**

Run: `for i in Hydrator/Hydrator Map/Layer/LayerType Map/Control/ControlType Map/Icon/IconType Map/Style/StyleType; do echo "src/$i.php:"; grep -h "^interface\|^namespace" "src/$i.php"; done`
Expected: jede Datei ist ein `interface` im erwarteten Namespace. Falls eine ein `abstract class` ist: trotzdem `::class` verwendbar, `registerForAutoconfiguration` funktioniert auch mit abstrakten Klassen.

- [ ] **Step 3: `styles.xml` – redundante Tags entfernen**

In `src/Resources/config/styles.xml` die beiden `<tag .../>`-Zeilen löschen, sodass:

```xml
    <services>
        <defaults autowire="false" autoconfigure="true" public="false"/>

        <service id="Cowegis\Bundle\Contao\Map\Style\Fixed\FixedStyleType" />

        <service id="Cowegis\Bundle\Contao\Map\Style\Fixed\FixedStyleTypeHydrator" />
    </services>
```

- [ ] **Step 4: Test – Doppel-Tagging darf nicht auftreten**

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -40`
Expected: grün. Insbesondere `testHydratorTagCollection` (jeder Service **genau einmal**) und `testTypeTagCounts` (StyleType == 1). Wenn `FixedStyleTypeHydrator` jetzt doppelt getaggt wäre, schlägt `assertCount(1, $tags)` an – dann prüfen, ob wirklich beide `<tag>`-Zeilen aus `styles.xml` entfernt wurden.

- [ ] **Step 5: psalm**

Run: `vendor/bin/phpcq run psalm 2>&1 | tail -20`
Expected: grün (neue `use`-Importe werden genutzt).

- [ ] **Step 6: Commit**

```bash
git add src/DependencyInjection/CowegisContaoExtension.php src/Resources/config/styles.xml
git commit -m "Autoconfigure marker-interface tags via registerForAutoconfiguration"
```

---

## Task 5: amenities + config → YAML

**Files:**
- Create: `src/Resources/config/amenities.yaml`
- Create: `src/Resources/config/config.yaml`
- Delete: `src/Resources/config/amenities.xml`, `src/Resources/config/config.xml`
- Modify: `src/DependencyInjection/CowegisContaoExtension.php`

- [ ] **Step 1: `amenities.yaml` erzeugen**

Reine Parameter-Datei. Jeden `<parameter>WERT</parameter>` aus `amenities.xml` als `- WERT` übernehmen, Reihenfolge exakt erhalten (197 Einträge, `administration` … `youth_centre`):

```yaml
parameters:
    cowegis_contao.amenities:
        - administration
        - advertising
        - alm
        # … alle weiteren Einträge aus amenities.xml in unveränderter Reihenfolge …
        - youth_centre
```

Kontrolle der Anzahl: `grep -c '<parameter>' src/Resources/config/amenities.xml` muss der Anzahl `- ` Listenzeilen in `amenities.yaml` entsprechen.

- [ ] **Step 2: `config.yaml` erzeugen**

```yaml
parameters:
    cowegis_contao.file_formats:
        gpx: ['gpx']
        kml: ['kml']
        wkt: ['wkt']
        geojson: ['json', 'geojson']
        topojson: ['json', 'geojson']
```

- [ ] **Step 3: Extension auf YamlFileLoader umstellen (dual)**

`src/DependencyInjection/CowegisContaoExtension.php`:

Import ergänzen: `use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;`

Loader-Setup:

```php
$xmlLoader  = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
$yamlLoader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
```

`registerForAutoconfiguration`-Block bleibt (nach dem Loader-Setup).

Load-Aufrufe:

```php
$yamlLoader->load('amenities.yaml');
$yamlLoader->load('config.yaml');
$xmlLoader->load('controls.xml');
$xmlLoader->load('fragments.xml');
$xmlLoader->load('hydrators.xml');
$xmlLoader->load('icons.xml');
$xmlLoader->load('styles.xml');
$xmlLoader->load('layers.xml');
$xmlLoader->load('listeners.xml');
$xmlLoader->load('services.xml');
$xmlLoader->load('repositories.xml');
```

- [ ] **Step 4: alte XML löschen**

```bash
git rm src/Resources/config/amenities.xml src/Resources/config/config.xml
```

- [ ] **Step 5: Test**

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -20`
Expected: grün (Parameter `cowegis_contao.amenities` / `cowegis_contao.file_formats` werden vom Container weiterhin gesetzt – `testContainerCompiles` genügt; optional Assertion ergänzen).

- [ ] **Step 6: psalm + phpcs**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs 2>&1 | tail -20`
Expected: grün.

- [ ] **Step 7: Commit**

```bash
git add src/Resources/config/amenities.yaml src/Resources/config/config.yaml src/DependencyInjection/CowegisContaoExtension.php
git commit -m "Convert amenities + config parameters to YAML"
```

---

## Task 6: icons + styles → YAML

**Files:**
- Create: `src/Resources/config/icons.yaml`, `src/Resources/config/styles.yaml`
- Delete: `src/Resources/config/icons.xml`, `src/Resources/config/styles.xml`
- Modify: `src/DependencyInjection/CowegisContaoExtension.php`

- [ ] **Step 1: `icons.yaml`**

```yaml
services:
    _defaults:
        autowire: false
        autoconfigure: true
        public: false

    Cowegis\Bundle\Contao\Map\Icon\Image\ImageIconType: ~

    Cowegis\Bundle\Contao\Map\Icon\Image\ImageIconHydrator:
        arguments:
            - '@netzmacht.contao_toolkit.repository_manager'

    Cowegis\Bundle\Contao\Map\Icon\Div\DivIconType: ~

    Cowegis\Bundle\Contao\Map\Icon\Div\DivIconHydrator: ~

    Cowegis\Bundle\Contao\Map\Icon\Svg\SvgIconType: ~

    Cowegis\Bundle\Contao\Map\Icon\Svg\SvgIconHydrator: ~

    Cowegis\Bundle\Contao\Map\Icon\FontAwesome\FontAwesomeIconType: ~

    Cowegis\Bundle\Contao\Map\Icon\FontAwesome\FontAwesomeIconHydrator: ~
```

- [ ] **Step 2: `styles.yaml`**

```yaml
services:
    _defaults:
        autowire: false
        autoconfigure: true
        public: false

    Cowegis\Bundle\Contao\Map\Style\Fixed\FixedStyleType: ~

    Cowegis\Bundle\Contao\Map\Style\Fixed\FixedStyleTypeHydrator: ~
```

- [ ] **Step 3: Extension – zwei Zeilen umhängen**

`$xmlLoader->load('icons.xml');` → `$yamlLoader->load('icons.yaml');` (an der bisherigen Position in der Reihenfolge).
`$xmlLoader->load('styles.xml');` → `$yamlLoader->load('styles.yaml');`

- [ ] **Step 4: XML löschen**

```bash
git rm src/Resources/config/icons.xml src/Resources/config/styles.xml
```

- [ ] **Step 5: Test**

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -30`
Expected: grün. `testTypeTagCounts` (Icon == 4, Style == 1), `testHydratorTagCollection` (die 4 Icon-Hydratoren + `FixedStyleTypeHydrator` weiterhin genau einmal getaggt).

- [ ] **Step 6: psalm + phpcs**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs 2>&1 | tail -20`
Expected: grün.

- [ ] **Step 7: Commit**

```bash
git add src/Resources/config/icons.yaml src/Resources/config/styles.yaml src/DependencyInjection/CowegisContaoExtension.php
git commit -m "Convert icons + styles service config to YAML"
```

---

## Task 7: fragments → YAML

**Files:**
- Create: `src/Resources/config/fragments.yaml`
- Delete: `src/Resources/config/fragments.xml`
- Modify: `src/DependencyInjection/CowegisContaoExtension.php`

- [ ] **Step 1: `fragments.yaml`**

Beide Actions haben identische, 10-elementige Argumentliste (Reihenfolge exakt aus `fragments.xml`):

```yaml
services:
    _defaults:
        autowire: false
        autoconfigure: true
        public: false

    Cowegis\Bundle\Contao\Action\MapContentElementAction:
        arguments:
            - '@Cowegis\Core\Filter\FilterFactory'
            - '@psr18.http_client'
            - '@netzmacht.contao_toolkit.repository_manager'
            - '@netzmacht.contao_toolkit.template_renderer'
            - '@netzmacht.contao_toolkit.routing.scope_matcher'
            - '@netzmacht.contao_toolkit.response_tagger'
            - '@router'
            - '@translator'
            - '@contao.security.token_checker'
            - '@netzmacht.contao_toolkit.contao.input_adapter'

    Cowegis\Bundle\Contao\Action\MapModuleAction:
        arguments:
            - '@Cowegis\Core\Filter\FilterFactory'
            - '@psr18.http_client'
            - '@netzmacht.contao_toolkit.repository_manager'
            - '@netzmacht.contao_toolkit.template_renderer'
            - '@netzmacht.contao_toolkit.routing.scope_matcher'
            - '@netzmacht.contao_toolkit.response_tagger'
            - '@router'
            - '@translator'
            - '@contao.security.token_checker'
            - '@netzmacht.contao_toolkit.contao.input_adapter'
```

(`#[AsContentElement]` / `#[AsFrontendModule]` sind bereits an den Klassen; `autoconfigure: true` ist Voraussetzung dafür und war im XML schon gesetzt.)

- [ ] **Step 2: Extension – Zeile umhängen**

`$xmlLoader->load('fragments.xml');` → `$yamlLoader->load('fragments.yaml');`

- [ ] **Step 3: XML löschen**

```bash
git rm src/Resources/config/fragments.xml
```

- [ ] **Step 4: Test**

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -20`
Expected: grün (`testContainerCompiles` – 10 Argument-Referenzen auflösbar).

- [ ] **Step 5: Argument-Anzahl gegenprüfen**

`CowegisContaoExtensionTest` um Assertion ergänzen:

```php
    public function testMapActionArgumentCount(): void
    {
        $container = self::compiledContainer();

        self::assertCount(10, $container->getDefinition('Cowegis\Bundle\Contao\Action\MapContentElementAction')->getArguments());
        self::assertCount(10, $container->getDefinition('Cowegis\Bundle\Contao\Action\MapModuleAction')->getArguments());
    }
```

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -20` → grün.

- [ ] **Step 6: psalm + phpcs**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs 2>&1 | tail -20`
Expected: grün.

- [ ] **Step 7: Commit**

```bash
git add src/Resources/config/fragments.yaml src/DependencyInjection/CowegisContaoExtension.php tests/DependencyInjection/CowegisContaoExtensionTest.php
git commit -m "Convert fragments service config to YAML"
```

---

## Task 8: controls → YAML

**Files:**
- Create: `src/Resources/config/controls.yaml`
- Delete: `src/Resources/config/controls.xml`
- Modify: `src/DependencyInjection/CowegisContaoExtension.php`

- [ ] **Step 1: `controls.yaml`**

```yaml
services:
    _defaults:
        autowire: false
        autoconfigure: true
        public: false

    # Attribution control
    Cowegis\Bundle\Contao\Map\Control\Attribution\AttributionControlType: ~

    Cowegis\Bundle\Contao\Map\Control\Attribution\AttributionControlHydrator: ~

    Cowegis\Core\Serializer\Control\AttributionControlSerializer:
        arguments:
            - '@Cowegis\Core\Serializer\Serializer'
        tags:
            - { name: 'Cowegis\Core\Serializer\Serializer', key: 'Cowegis\Core\Definition\Control\AttributionControl' }

    # Fullscreen control
    Cowegis\Bundle\Contao\Map\Control\Fullscreen\FullscreenControlType: ~

    Cowegis\Bundle\Contao\Map\Control\Fullscreen\FullscreenControlTypeHydrator: ~

    Cowegis\Core\Serializer\Control\FullscreenControlSerializer:
        arguments:
            - '@Cowegis\Core\Serializer\Serializer'
        tags:
            - { name: 'Cowegis\Core\Serializer\Serializer', key: 'Cowegis\Core\Definition\Control\FullscreenControl' }

    # Layers control
    Cowegis\Bundle\Contao\Map\Control\Layers\LayersControlType: ~

    Cowegis\Bundle\Contao\Map\Control\Layers\LayersControlHydrator:
        arguments:
            - '@database_connection'

    # Loading control
    Cowegis\Bundle\Contao\Map\Control\Loading\LoadingControlType: ~

    Cowegis\Bundle\Contao\Map\Control\Loading\LoadingControlHydrator: ~

    Cowegis\Core\Serializer\Control\LoadingControlSerializer:
        arguments:
            - '@Cowegis\Core\Serializer\Serializer'
        tags:
            - { name: 'Cowegis\Core\Serializer\Serializer', key: 'Cowegis\Core\Definition\Control\LoadingControl' }

    # Scale control
    Cowegis\Bundle\Contao\Map\Control\Scale\ScaleControlType: ~

    Cowegis\Bundle\Contao\Map\Control\Scale\ScaleControlHydrator: ~

    Cowegis\Core\Serializer\Control\ScaleControlSerializer:
        arguments:
            - '@Cowegis\Core\Serializer\Serializer'
        tags:
            - { name: 'Cowegis\Core\Serializer\Serializer', key: 'Cowegis\Core\Definition\Control\ScaleControl' }

    # Zoom control
    Cowegis\Bundle\Contao\Map\Control\Zoom\ZoomControlType: ~

    Cowegis\Bundle\Contao\Map\Control\Zoom\ZoomControlHydrator: ~

    Cowegis\Core\Serializer\Control\ZoomControlSerializer:
        arguments:
            - '@Cowegis\Core\Serializer\Serializer'
        tags:
            - { name: 'Cowegis\Core\Serializer\Serializer', key: 'Cowegis\Core\Definition\Control\ZoomControl' }

    # Geocoder control
    Cowegis\Bundle\Contao\Map\Control\Geocoder\GeocoderControlType: ~

    Cowegis\Core\Serializer\Control\GeocoderControlSerializer:
        arguments:
            - '@Cowegis\Core\Serializer\Serializer'
        tags:
            - { name: 'Cowegis\Core\Serializer\Serializer', key: 'Cowegis\Core\Definition\Control\GeocoderControl' }

    Cowegis\Bundle\Contao\Map\Control\Geocoder\GeocoderControlTypeHydrator:
        arguments:
            - '@?Cowegis\ContaoGeocoder\Routing\SearchUrlGenerator'
```

- [ ] **Step 2: Extension – Zeile umhängen**

`$xmlLoader->load('controls.xml');` → `$yamlLoader->load('controls.yaml');`

- [ ] **Step 3: XML löschen**

```bash
git rm src/Resources/config/controls.xml
```

- [ ] **Step 4: Test**

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -30`
Expected: grün. `testTypeTagCounts` (Control == 7), `testHydratorTagCollection` (7 Control-Hydratoren genau einmal getaggt), `testSerializerKeyTag`, `testContainerCompiles` (`@?…SearchUrlGenerator` bleibt null, kein Fehler).

- [ ] **Step 5: psalm + phpcs**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs 2>&1 | tail -20`
Expected: grün.

- [ ] **Step 6: Commit**

```bash
git add src/Resources/config/controls.yaml src/DependencyInjection/CowegisContaoExtension.php
git commit -m "Convert controls service config to YAML"
```

---

## Task 9: hydrators → YAML

**Files:**
- Create: `src/Resources/config/hydrators.yaml`
- Delete: `src/Resources/config/hydrators.xml`
- Modify: `src/DependencyInjection/CowegisContaoExtension.php`

- [ ] **Step 1: `hydrators.yaml`**

`<instanceof>` entfällt (steht jetzt in der Extension). Prioritäts-Hydratoren mit `autoconfigure: false` + explizitem Tag.

```yaml
services:
    _defaults:
        autowire: false
        autoconfigure: true
        public: false

    Cowegis\Bundle\Contao\Map\MapHydrator:
        arguments:
            - '@Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry'
            - '@Cowegis\Bundle\Contao\Map\Control\ControlTypeRegistry'
            - '@Cowegis\Bundle\Contao\Map\Icon\IconTypeRegistry'
            - '@netzmacht.contao_toolkit.repository_manager'

    Cowegis\Bundle\Contao\Map\Options\MapOptionsHydrator: ~

    Cowegis\Bundle\Contao\Map\Options\LocateOptionsHydrator:
        autoconfigure: false
        tags:
            - { name: 'Cowegis\Bundle\Contao\Hydrator\Hydrator', priority: -32 }

    Cowegis\Bundle\Contao\Map\Options\BoundsOptionsHydrator:
        autoconfigure: false
        tags:
            - { name: 'Cowegis\Bundle\Contao\Hydrator\Hydrator', priority: -32 }

    Cowegis\Bundle\Contao\Map\Options\ViewHydrator: ~

    Cowegis\Bundle\Contao\Map\Layer\LayerObjectOptionsHydrator: ~

    Cowegis\Bundle\Contao\Map\Layer\GridLayerOptionsHydrator: ~

    Cowegis\Bundle\Contao\Map\Presets\PopupPresetHydrator: ~

    Cowegis\Bundle\Contao\Map\Presets\TooltipPresetHydrator: ~

    Cowegis\Bundle\Contao\Hydrator\EventDispatchingHydrator:
        autoconfigure: false
        arguments:
            - '@event_dispatcher'
        tags:
            - { name: 'Cowegis\Bundle\Contao\Hydrator\Hydrator', priority: -128 }
```

- [ ] **Step 2: Extension – Zeile umhängen**

`$xmlLoader->load('hydrators.xml');` → `$yamlLoader->load('hydrators.yaml');`

- [ ] **Step 3: XML löschen**

```bash
git rm src/Resources/config/hydrators.xml
```

- [ ] **Step 4: Test**

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -40`
Expected: grün. Besonders `testHydratorTagCollection` (alle argumentlosen Hydratoren via `registerForAutoconfiguration` genau einmal getaggt), `testHydratorPriorities` (-32/-32/-128 aus den expliziten Tags), `testDelegatingHydratorIsNotItselfTagged`.

Falls ein argumentloser Hydrator (`MapOptionsHydrator`, `ViewHydrator`, …) **nicht** getaggt erscheint: prüfen, ob seine Klasse `Cowegis\Bundle\Contao\Hydrator\Hydrator` implementiert (`grep -l "implements.*Hydrator" src/Map/...`). Falls nicht implementiert, war er auch im alten `<instanceof>` nicht erfasst → dann in `expectedHydratorIds()` entfernen (XML-Realität aus Task 3 zählt).

- [ ] **Step 5: psalm + phpcs**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs 2>&1 | tail -20`
Expected: grün.

- [ ] **Step 6: Commit**

```bash
git add src/Resources/config/hydrators.yaml src/DependencyInjection/CowegisContaoExtension.php
git commit -m "Convert hydrators service config to YAML"
```

---

## Task 10: layers → YAML

**Files:**
- Create: `src/Resources/config/layers.yaml`
- Delete: `src/Resources/config/layers.xml`
- Modify: `src/DependencyInjection/CowegisContaoExtension.php`

- [ ] **Step 1: `layers.yaml`**

`_instanceof` für `Cowegis\Core\Schema\LayerSchemaDescriber`; Serializer behalten `key`-Tags; `LayerType`/`Hydrator`-Tags kommen aus der Extension.

```yaml
services:
    _defaults:
        autowire: false
        autoconfigure: true
        public: false

    _instanceof:
        Cowegis\Core\Schema\LayerSchemaDescriber:
            tags:
                - 'Cowegis\Core\Schema\LayerSchemaDescriber'

    # Data layer
    Cowegis\Core\Serializer\Layer\DataLayerSerializer:
        arguments:
            - '@Cowegis\Core\Serializer\Serializer'
        tags:
            - { name: 'Cowegis\Core\Serializer\Serializer', key: 'Cowegis\Core\Definition\Layer\DataLayer' }

    Cowegis\Core\Schema\Layer\DataLayerSchemaDescriber:
        arguments:
            - 'data'

    Cowegis\Bundle\Contao\Schema\LayersSchemaDescriber: ~

    # Tile layer
    Cowegis\Bundle\Contao\Map\Layer\Tile\TileLayerType: ~

    Cowegis\Core\Schema\Layer\TileLayerSchemaDescriber:
        arguments:
            - 'tileLayer'

    Cowegis\Bundle\Contao\Map\Layer\Tile\TileLayerHydrator:
        arguments:
            - '@netzmacht.contao_toolkit.response_tagger'

    Cowegis\Core\Serializer\Layer\TileLayerSerializer:
        arguments:
            - '@Cowegis\Core\Serializer\Serializer'
        tags:
            - { name: 'Cowegis\Core\Serializer\Serializer', key: 'Cowegis\Core\Definition\Layer\TileLayer' }

    # Marker layer
    Cowegis\Bundle\Contao\Map\Layer\Markers\MarkersLayerType:
        arguments:
            - '@Cowegis\Bundle\Contao\Model\MarkerRepository'
            - '@translator'

    Cowegis\Core\Schema\Layer\MarkerLayerSchemaDescriber:
        arguments:
            - 'markers'

    Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator\MarkerOptionsHydrator: ~

    Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator\MarkersLayerHydrator:
        arguments:
            - '@router'
            - '@Cowegis\Bundle\Contao\Map\Layer\Markers\MarkersLayerDataProvider'
            - '@Cowegis\Core\Serializer\Serializer'
            - '@netzmacht.contao_toolkit.response_tagger'

    Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator\MarkerHydrator:
        arguments:
            - '@Cowegis\Bundle\Contao\Map\Icon\IconTypeRegistry'
            - '@Cowegis\Bundle\Contao\Model\IconRepository'
            - '@contao.insert_tag.parser'

    Cowegis\Bundle\Contao\Map\Layer\Markers\MarkersLayerDataProvider:
        arguments:
            - '@Cowegis\Bundle\Contao\Model\MarkerRepository'
            - '@Cowegis\Bundle\Contao\Hydrator\Hydrator'
        tags:
            - { name: 'Cowegis\Bundle\Contao\Provider\LayerDataProvider', type: 'markers' }

    Cowegis\Core\Serializer\Layer\MarkersLayerDataSerializer:
        arguments:
            - '@Cowegis\Core\Serializer\Serializer'
        tags:
            - { name: 'Cowegis\Core\Serializer\Serializer', key: 'Cowegis\Core\Provider\LayerData\MarkersLayerData' }

    Cowegis\Core\Serializer\Layer\MarkerSerializer:
        arguments:
            - '@Cowegis\Core\Serializer\Serializer'
        tags:
            - { name: 'Cowegis\Core\Serializer\Serializer', key: 'Cowegis\Core\Definition\UI\Marker' }

    # File layer
    Cowegis\Bundle\Contao\Map\Layer\File\FileLayerType:
        arguments:
            - '@netzmacht.contao_toolkit.repository_manager'

    Cowegis\Bundle\Contao\Map\Layer\File\FileLayerHydrator:
        arguments:
            - '@netzmacht.contao_toolkit.repository_manager'
            - '@netzmacht.contao_toolkit.response_tagger'

    # Group layer
    Cowegis\Bundle\Contao\Map\Layer\Group\GroupLayerType: ~

    Cowegis\Bundle\Contao\Map\Layer\Group\GroupLayerHydrator:
        arguments:
            - '@Cowegis\Bundle\Contao\Model\Map\MapLayerRepository'
            - '@netzmacht.contao_toolkit.response_tagger'

    Cowegis\Core\Schema\Layer\FeatureGroupLayerSchemaDescriber:
        arguments:
            - 'featureGroup'

    Cowegis\Core\Schema\Layer\LayerGroupLayerSchemaDescriber:
        arguments:
            - 'layerGroup'

    # Marker cluster layer
    Cowegis\Bundle\Contao\Map\Layer\MarkerCluster\MarkerClusterGroupType: ~

    Cowegis\Bundle\Contao\Map\Layer\MarkerCluster\MarkerClusterGroupHydrator:
        arguments:
            - '@Cowegis\Bundle\Contao\Model\Map\MapLayerRepository'
            - '@netzmacht.contao_toolkit.response_tagger'

    Cowegis\Core\Schema\Layer\MarkerClusterLayerSchemaDescriber:
        arguments:
            - 'markerCluster'

    # Reference layer
    Cowegis\Bundle\Contao\Map\Layer\Reference\ReferenceLayerType:
        arguments:
            - '@Cowegis\Bundle\Contao\Model\LayerRepository'

    Cowegis\Bundle\Contao\Map\Layer\Reference\ReferenceLayerHydrator:
        arguments:
            - '@Cowegis\Bundle\Contao\Model\LayerRepository'
            - '@netzmacht.contao_toolkit.response_tagger'

    Cowegis\Bundle\Contao\Map\Layer\Reference\ReferenceLayerDataProvider:
        arguments:
            - '@Cowegis\Bundle\Contao\Model\LayerRepository'
            - !tagged_locator { tag: 'Cowegis\Bundle\Contao\Provider\LayerDataProvider', index_by: 'type' }
        tags:
            - { name: 'Cowegis\Bundle\Contao\Provider\LayerDataProvider', type: 'reference' }

    # Overpass layer
    Cowegis\Bundle\Contao\Map\Layer\Overpass\OverpassLayerType: ~

    Cowegis\Bundle\Contao\Map\Layer\Overpass\OverpassLayerHydrator:
        arguments:
            - '@netzmacht.contao_toolkit.response_tagger'

    # Vector layer
    Cowegis\Bundle\Contao\Map\Layer\Vector\VectorsLayerType: ~

    Cowegis\Bundle\Contao\Map\Layer\Vector\VectorsLayerHydrator:
        arguments:
            - '@router'
            - '@netzmacht.contao_toolkit.response_tagger'
            - '@Cowegis\Bundle\Contao\Map\Style\StyleTypeRegistry'

    Cowegis\Bundle\Contao\Map\Layer\Vector\VectorsDataLayerProvider:
        tags:
            - { name: 'Cowegis\Bundle\Contao\Provider\LayerDataProvider', type: 'vectors' }
```

- [ ] **Step 2: Extension – Zeile umhängen**

`$xmlLoader->load('layers.xml');` → `$yamlLoader->load('layers.yaml');`

- [ ] **Step 3: XML löschen**

```bash
git rm src/Resources/config/layers.xml
```

- [ ] **Step 4: LayerSchemaDescriber-Tag-Assertion ergänzen**

`CowegisContaoExtensionTest`:

```php
    public function testLayerSchemaDescriberInstanceofTag(): void
    {
        $container = self::compiledContainer();

        self::assertCount(
            8,
            $container->findTaggedServiceIds('Cowegis\Core\Schema\LayerSchemaDescriber'),
        );
    }
```

(7 aus `layers.yaml`; ggf. Zahl an XML-Realität anpassen, falls Task-3-Baseline anders zählt.)

- [ ] **Step 5: Test**

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -40`
Expected: grün. `testTypeTagCounts` (Layer == 8), `testHydratorTagCollection`, `testLayerDataProviderTypes` (`markers`/`reference`/`vectors`), `testLayerSchemaDescriberInstanceofTag`, `testContainerCompiles`.

Falls `_instanceof` nicht greift (Describer implementiert das Interface nicht): Fehlermeldung zeigt zu niedrige Zahl → prüfen mit `grep -rn "implements" vendor/cowegis/cowegis-core/src/Schema/Layer/`. Ggf. betroffene Describer explizit taggen statt via `_instanceof`.

- [ ] **Step 6: psalm + phpcs**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs 2>&1 | tail -20`
Expected: grün.

- [ ] **Step 7: Commit**

```bash
git add src/Resources/config/layers.yaml src/DependencyInjection/CowegisContaoExtension.php tests/DependencyInjection/CowegisContaoExtensionTest.php
git commit -m "Convert layers service config to YAML"
```

---

## Task 11: services → YAML

**Files:**
- Create: `src/Resources/config/services.yaml`
- Delete: `src/Resources/config/services.xml`
- Modify: `src/DependencyInjection/CowegisContaoExtension.php`

- [ ] **Step 1: `services.yaml`**

```yaml
services:
    _defaults:
        autowire: false
        autoconfigure: true
        public: false

    Cowegis\Bundle\Contao\Action\Backend\DocsAction:
        public: true
        arguments:
            - '@twig'
            - '@router'

    Cowegis\Bundle\Contao\Action\Backend\MapLayerAction:
        public: true
        arguments:
            - '@contao.framework'
            - '@netzmacht.contao_toolkit.repository_manager'
            - '@router'
            - '@netzmacht.contao_toolkit.csrf.token_provider'

    Cowegis\Bundle\Contao\ConsentBridge\Plugin:
        autoconfigure: false
        tags:
            - { name: hofff_contao_consent_bridge.plugin }

    Cowegis\Core\IdFormat\IntegerIdFormat:
        tags:
            - 'Cowegis\Core\IdFormat\IdFormat'

    Cowegis\Core\Schema\Id\IntegerIdSchema:
        tags:
            - 'Cowegis\Core\Schema\IdSchema'

    Cowegis\Bundle\Contao\Hydrator\Hydrator:
        class: Cowegis\Bundle\Contao\Hydrator\DelegatingHydrator
        autoconfigure: false
        arguments:
            - !tagged_iterator 'Cowegis\Bundle\Contao\Hydrator\Hydrator'
            - '@netzmacht.contao_toolkit.response_tagger'

    Cowegis\Bundle\Contao\Provider\ContaoBackendProvider:
        arguments:
            - '@contao.framework'
            - '@Cowegis\Bundle\Contao\Model\Map\MapRepository'
            - '@Cowegis\Bundle\Contao\Model\Map\MapLayerRepository'
            - '@Cowegis\Bundle\Contao\Hydrator\Hydrator'
            - !tagged_locator { tag: 'Cowegis\Bundle\Contao\Provider\LayerDataProvider', index_by: 'type' }
            - '@Cowegis\Core\IdFormat\IntegerIdFormat'
        tags:
            - 'Cowegis\Core\Provider\Provider'

    cowegis_contao.slug_generator.options:
        class: Ausi\SlugGenerator\SlugOptions
        calls:
            - [setValidChars, ['a-z0-9_']]
            - [setDelimiter, ['_']]

    cowegis_contao.slug_generator:
        class: Ausi\SlugGenerator\SlugGenerator
        arguments:
            - '@cowegis_contao.slug_generator.options'

    Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry:
        arguments:
            - !tagged_iterator 'Cowegis\Bundle\Contao\Map\Layer\LayerType'

    Cowegis\Bundle\Contao\Map\Control\ControlTypeRegistry:
        arguments:
            - !tagged_iterator 'Cowegis\Bundle\Contao\Map\Control\ControlType'

    Cowegis\Bundle\Contao\Map\Icon\IconTypeRegistry:
        arguments:
            - !tagged_iterator 'Cowegis\Bundle\Contao\Map\Icon\IconType'

    Cowegis\Bundle\Contao\Map\Style\StyleTypeRegistry:
        arguments:
            - !tagged_iterator 'Cowegis\Bundle\Contao\Map\Style\StyleType'

    Cowegis\Bundle\Contao\Schema\ServersSchemaDescriber:
        arguments:
            - '@netzmacht.contao_toolkit.repository_manager'
            - '@request_stack'
            - '%cowegis_api.api_base_uri%'
        tags:
            - 'Cowegis\Core\Schema\SchemaDescriber'
```

- [ ] **Step 2: Extension – Zeile umhängen**

`$xmlLoader->load('services.xml');` → `$yamlLoader->load('services.yaml');`

- [ ] **Step 3: XML löschen**

```bash
git rm src/Resources/config/services.xml
```

- [ ] **Step 4: ServersSchemaDescriber-Tag prüfen (kein `_instanceof`-Konflikt)**

Falls `Cowegis\Bundle\Contao\Schema\ServersSchemaDescriber` ebenfalls `Cowegis\Core\Schema\LayerSchemaDescriber` implementieren sollte (unwahrscheinlich – anderer Tag), würde es durch `layers.yaml` `_instanceof` mitgetaggt. `services.yaml` lädt **nach** `layers.yaml`, aber `_instanceof` gilt nur innerhalb der definierenden Datei → kein Effekt. Sicherstellen per Test (Step 5).

- [ ] **Step 5: Assertions ergänzen**

`CowegisContaoExtensionTest`:

```php
    public function testProviderTagPresent(): void
    {
        $container = self::compiledContainer();

        self::assertNotSame(
            [],
            $container->getDefinition('Cowegis\Bundle\Contao\Provider\ContaoBackendProvider')->getTag('Cowegis\Core\Provider\Provider'),
        );
    }

    public function testSlugGeneratorCalls(): void
    {
        $container = self::compiledContainer();
        $calls = $container->getDefinition('cowegis_contao.slug_generator.options')->getMethodCalls();

        self::assertSame('setValidChars', $calls[0][0]);
        self::assertSame(['a-z0-9_'], $calls[0][1]);
        self::assertSame('setDelimiter', $calls[1][0]);
        self::assertSame(['_'], $calls[1][1]);
    }
```

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -40`
Expected: grün. Alle bisherigen Tests + neue. `testDelegatingHydratorIsNotItselfTagged` weiterhin grün (autoconfigure: false).

- [ ] **Step 6: psalm + phpcs**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs 2>&1 | tail -20`
Expected: grün.

- [ ] **Step 7: Commit**

```bash
git add src/Resources/config/services.yaml src/DependencyInjection/CowegisContaoExtension.php tests/DependencyInjection/CowegisContaoExtensionTest.php
git commit -m "Convert core services config to YAML"
```

---

## Task 12: repositories → YAML

**Files:**
- Create: `src/Resources/config/repositories.yaml`
- Delete: `src/Resources/config/repositories.xml`
- Modify: `src/DependencyInjection/CowegisContaoExtension.php`

- [ ] **Step 1: `repositories.yaml`**

`_defaults` hier mit `autoconfigure: false` – Repositories gewinnen nichts durch Autokonfiguration, und ein Toolkit-`_instanceof` auf `Repository` würde sonst den `netzmacht.contao_toolkit.repository`-Tag doppeln.

```yaml
parameters:
    cowegis_contao.model.tl_cowegis_marker: Cowegis\Bundle\Contao\Model\MarkerModel
    cowegis_contao.model.tl_cowegis_layer: Cowegis\Bundle\Contao\Model\LayerModel
    cowegis_contao.model.tl_cowegis_control: Cowegis\Bundle\Contao\Model\ControlModel
    cowegis_contao.model.tl_cowegis_icon: Cowegis\Bundle\Contao\Model\IconModel

services:
    _defaults:
        autowire: false
        autoconfigure: false
        public: false

    Cowegis\Bundle\Contao\Model\Map\MapRepository:
        tags:
            - { name: netzmacht.contao_toolkit.repository, model: 'Cowegis\Bundle\Contao\Model\Map\MapModel' }

    Cowegis\Bundle\Contao\Model\Map\MapLayerRepository:
        arguments:
            - '@database_connection'
        tags:
            - { name: netzmacht.contao_toolkit.repository, model: 'Cowegis\Bundle\Contao\Model\Map\MapLayerModel' }

    Cowegis\Bundle\Contao\Model\Map\MapPaneRepository:
        tags:
            - { name: netzmacht.contao_toolkit.repository, model: 'Cowegis\Bundle\Contao\Model\Map\MapPaneModel' }

    Cowegis\Bundle\Contao\Model\IconRepository:
        arguments:
            - '%cowegis_contao.model.tl_cowegis_icon%'
        tags:
            - { name: netzmacht.contao_toolkit.repository, model: 'Cowegis\Bundle\Contao\Model\IconModel' }

    Cowegis\Bundle\Contao\Model\MarkerRepository:
        arguments:
            - '%cowegis_contao.model.tl_cowegis_marker%'
            - '@event_dispatcher'
        tags:
            - { name: netzmacht.contao_toolkit.repository, model: 'Cowegis\Bundle\Contao\Model\MarkerModel' }

    Cowegis\Bundle\Contao\Model\LayerRepository:
        arguments:
            - '%cowegis_contao.model.tl_cowegis_layer%'
        tags:
            - { name: netzmacht.contao_toolkit.repository, model: 'Cowegis\Bundle\Contao\Model\LayerModel' }

    Cowegis\Bundle\Contao\Model\PopupRepository:
        tags:
            - { name: netzmacht.contao_toolkit.repository, model: 'Cowegis\Bundle\Contao\Model\PopupModel' }

    Cowegis\Bundle\Contao\Model\ControlRepository:
        arguments:
            - '%cowegis_contao.model.tl_cowegis_control%'
        tags:
            - { name: netzmacht.contao_toolkit.repository, model: 'Cowegis\Bundle\Contao\Model\ControlModel' }

    Cowegis\Bundle\Contao\Model\TooltipRepository:
        tags:
            - { name: netzmacht.contao_toolkit.repository, model: 'Cowegis\Bundle\Contao\Model\TooltipModel' }

    Cowegis\Bundle\Contao\Model\StyleRepository:
        tags:
            - { name: netzmacht.contao_toolkit.repository, model: 'Cowegis\Bundle\Contao\Model\StyleModel' }
```

- [ ] **Step 2: Extension – Zeile umhängen**

`$xmlLoader->load('repositories.xml');` → `$yamlLoader->load('repositories.yaml');`

- [ ] **Step 3: XML löschen**

```bash
git rm src/Resources/config/repositories.xml
```

- [ ] **Step 4: Test**

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -30`
Expected: grün. `testRepositoryTagCount` (== 10, jeweils genau ein `model`-Attribut).

- [ ] **Step 5: psalm + phpcs**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs 2>&1 | tail -20`
Expected: grün.

- [ ] **Step 6: Commit**

```bash
git add src/Resources/config/repositories.yaml src/DependencyInjection/CowegisContaoExtension.php
git commit -m "Convert repositories service config to YAML"
```

---

## Task 13: Event-Listener + Hook-Attribute (dormant)

**Files:**
- Modify: `src/EventListener/BackendMenuListener.php`
- Modify: `src/EventListener/LayerResponseListener.php`
- Modify: `src/EventListener/MapResponseListener.php`
- Modify: `src/EventListener/Filter/ApplyFilterRuleMarkerListener.php`
- Modify: `src/EventListener/Hook/LanguageFileListener.php`
- Create: `tests/EventListener/ListenerAttributeCoverageTest.php`

**Interfaces:**
- Produces: `#[AsEventListener]` an 4 Listenern, `#[AsHook]` an `LanguageFileListener`. Solange `listeners.xml` noch geladen wird (autoconfigure=false), bleiben die Attribute wirkungslos; die expliziten XML-Tags tragen weiter.

- [ ] **Step 1: `BackendMenuListener`**

Import: `use Symfony\Component\EventDispatcher\Attribute\AsEventListener;`
Über der Klasse:
```php
#[AsEventListener(event: 'contao.backend_menu_build', method: 'onBuild', priority: -255)]
```

- [ ] **Step 2: `LayerResponseListener`**

Import: `use Symfony\Component\EventDispatcher\Attribute\AsEventListener;`
Über der Klasse (Event-Klasse ist bereits importiert – Namen aus `use` der Datei verwenden):
```php
#[AsEventListener(event: LayerResponseEvent::class)]
```

- [ ] **Step 3: `MapResponseListener`**

```php
#[AsEventListener(event: MapResponseEvent::class)]
```

- [ ] **Step 4: `ApplyFilterRuleMarkerListener`**

```php
#[AsEventListener(event: ApplyFilterRuleEvent::class)]
```

- [ ] **Step 5: `LanguageFileListener` – `@Hook` → `#[AsHook]`**

Ersetze:
```php
use Contao\CoreBundle\ServiceAnnotation\Hook;
```
durch:
```php
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
```
Ersetze den Docblock `/** @Hook("loadLanguageFile") */` über der Klasse durch:
```php
#[AsHook('loadLanguageFile')]
```

- [ ] **Step 6: Golden-List-Test schreiben**

`tests/EventListener/ListenerAttributeCoverageTest.php`:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Test\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

use function array_map;
use function sort;

final class ListenerAttributeCoverageTest extends TestCase
{
    /** @return array<string, list<array{string, string}>> table+target pairs, sorted */
    private static function goldenCallbacks(): array
    {
        return [
            'Cowegis\Bundle\Contao\EventListener\Dca\ContentDcaListener' => [
                ['tl_content', 'config.onload'],
                ['tl_content', 'fields.cowegis_client.options'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\ModuleDcaListener' => [
                ['tl_module', 'config.onload'],
                ['tl_module', 'fields.cowegis_client.options'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\LayerDcaListener' => [
                ['tl_cowegis_layer', 'fields.type.options'],
                ['tl_cowegis_layer', 'fields.fileFormat.options'],
                ['tl_cowegis_layer', 'fields.amenityIcons.eval.columnFields.amenity.options'],
                ['tl_cowegis_layer', 'fields.file.load'],
                ['tl_cowegis_layer', 'list.label.label'],
                ['tl_cowegis_layer', 'list.operations.data.button'],
                ['tl_cowegis_layer', 'list.sorting.paste_button'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MapLayerSelectionDcaListener' => [
                ['tl_cowegis_layer', 'config.onload'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MapDcaListener' => [
                ['tl_cowegis_map', 'fields.layers.eval.listCallback'],
                ['tl_cowegis_map', 'config.onload'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MapLayerDcaListener' => [
                ['tl_cowegis_map_layer', 'config.onload'],
                ['tl_cowegis_map_layer', 'list.sorting.child_record'],
                ['tl_cowegis_map_layer', 'fields.pane.options'],
                ['tl_cowegis_map_layer', 'fields.dataPane.options'],
                ['tl_cowegis_map_layer', 'fields.filterRules.options'],
                ['tl_cowegis_map_layer', 'fields.layerId.input_field'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MapPaneDcaListener' => [
                ['tl_cowegis_map_pane', 'list.sorting.child_record'],
                ['tl_cowegis_map_pane', 'fields.name.save'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\MarkerDcaListener' => [
                ['tl_cowegis_marker', 'list.sorting.child_record'],
                ['tl_cowegis_marker', 'fields.coordinates.save'],
                ['tl_cowegis_marker', 'fields.coordinates.load'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\IconDcaListener' => [
                ['tl_cowegis_icon', 'fields.type.options'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\ControlDcaListener' => [
                ['tl_cowegis_control', 'list.sorting.child_record'],
                ['tl_cowegis_control', 'fields.type.options'],
                ['tl_cowegis_control', 'fields.layers.load'],
                ['tl_cowegis_control', 'fields.layers.save'],
                ['tl_cowegis_control', 'fields.layers.eval.columnFields.layer.options'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\OptionsListener' => [
                ['tl_cowegis_map', 'fields.zoom.options'],
                ['tl_cowegis_map', 'fields.minZoom.options'],
                ['tl_cowegis_map', 'fields.maxZoom.options'],
                ['tl_cowegis_map', 'fields.locateMaxZoom.options'],
                ['tl_cowegis_layer', 'fields.minZoom.options'],
                ['tl_cowegis_layer', 'fields.maxZoom.options'],
                ['tl_cowegis_layer', 'fields.maxNativeZoom.options'],
                ['tl_cowegis_layer', 'fields.disableClusteringAtZoom.options'],
                ['tl_cowegis_control', 'fields.zoomControl.options'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\StyleDcaListener' => [
                ['tl_cowegis_style', 'fields.type.options'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\AliasGenerator' => [
                ['tl_cowegis_map', 'fields.alias.save'],
                ['tl_cowegis_marker', 'fields.alias.save'],
                ['tl_cowegis_layer', 'fields.alias.save'],
                ['tl_cowegis_icon', 'fields.alias.save'],
                ['tl_cowegis_popup', 'fields.alias.save'],
                ['tl_cowegis_control', 'fields.alias.save'],
                ['tl_cowegis_style', 'fields.alias.save'],
            ],
            'Cowegis\Bundle\Contao\EventListener\Dca\Validator' => [
                ['tl_cowegis_marker', 'fields.coordinates.save'],
                ['tl_cowegis_map', 'fields.center.save'],
                ['tl_cowegis_popup', 'fields.offset.save'],
                ['tl_cowegis_map', 'fields.alias.save'],
                ['tl_cowegis_marker', 'fields.alias.save'],
                ['tl_cowegis_layer', 'fields.alias.save'],
                ['tl_cowegis_icon', 'fields.alias.save'],
                ['tl_cowegis_popup', 'fields.alias.save'],
                ['tl_cowegis_control', 'fields.alias.save'],
                ['tl_cowegis_style', 'fields.alias.save'],
            ],
        ];
    }

    /**
     * @dataProvider callbackClassProvider
     *
     * @param list<array{string, string}> $expected
     */
    public function testCallbackAttributesMatchGoldenList(string $class, array $expected): void
    {
        $actual = [];
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getAttributes(AsCallback::class) as $attribute) {
                /** @var AsCallback $instance */
                $instance = $attribute->newInstance();
                $actual[] = $instance->table . '::' . $instance->target;
            }
        }
        $expectedFlat = array_map(static fn (array $p): string => $p[0] . '::' . $p[1], $expected);

        sort($actual);
        sort($expectedFlat);

        self::assertSame($expectedFlat, $actual, $class);
    }

    /** @return iterable<string, array{string, list<array{string, string}>}> */
    public static function callbackClassProvider(): iterable
    {
        foreach (self::goldenCallbacks() as $class => $pairs) {
            yield $class => [$class, $pairs];
        }
    }

    public function testEventListenerAttributes(): void
    {
        $expected = [
            'Cowegis\Bundle\Contao\EventListener\BackendMenuListener' => 'contao.backend_menu_build',
            'Cowegis\Bundle\Contao\EventListener\BackendStyleListener' => null,
            'Cowegis\Bundle\Contao\EventListener\LayerResponseListener' => 'Cowegis\Bundle\Api\Event\LayerResponseEvent',
            'Cowegis\Bundle\Contao\EventListener\MapResponseListener' => 'Cowegis\Bundle\Api\Event\MapResponseEvent',
            'Cowegis\Bundle\Contao\EventListener\Filter\ApplyFilterRuleMarkerListener' => 'Cowegis\Bundle\Contao\Event\ApplyFilterRuleEvent',
        ];

        foreach ($expected as $class => $event) {
            $attributes = (new ReflectionClass($class))->getAttributes(AsEventListener::class);
            self::assertCount(1, $attributes, $class);
            self::assertSame($event, $attributes[0]->newInstance()->event, $class);
        }
    }

    public function testHookAttribute(): void
    {
        $attributes = (new ReflectionClass('Cowegis\Bundle\Contao\EventListener\Hook\LanguageFileListener'))
            ->getAttributes(AsHook::class);

        self::assertCount(1, $attributes);
        self::assertSame('loadLanguageFile', $attributes[0]->newInstance()->hook);
    }
}
```

- [ ] **Step 7: Test – Event/Hook grün, Callbacks noch rot**

Run: `vendor/bin/phpunit tests/EventListener 2>&1 | tail -40`
Expected: `testEventListenerAttributes` + `testHookAttribute` PASS. `testCallbackAttributesMatchGoldenList` schlägt für **alle DCA-Klassen außer `ModuleDcaListener`/`StyleDcaListener`** fehl (Attribute noch nicht gesetzt – kommt in Task 14/15). Das ist an dieser Stelle **erwartet**.

- [ ] **Step 8: Callback-Fälle temporär als incomplete markieren**

Damit die Suite bis Task 15 grün bleibt, in `testCallbackAttributesMatchGoldenList` **vorerst** am Anfang:

```php
        if (! in_array($class, [
            'Cowegis\Bundle\Contao\EventListener\Dca\ModuleDcaListener',
            'Cowegis\Bundle\Contao\EventListener\Dca\StyleDcaListener',
        ], true)) {
            self::markTestIncomplete('AsCallback attributes added in later task');
        }
```

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -20` → grün (mit incomplete).

- [ ] **Step 9: psalm + phpcs + rector**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs && vendor/bin/phpcq run rector 2>&1 | tail -30`
Expected: grün. (`#[AsEventListener]`/`#[AsHook]` sind bereits anderswo im Baum genutzt.)

- [ ] **Step 10: Commit**

```bash
git add src/EventListener/BackendMenuListener.php src/EventListener/LayerResponseListener.php src/EventListener/MapResponseListener.php src/EventListener/Filter/ApplyFilterRuleMarkerListener.php src/EventListener/Hook/LanguageFileListener.php tests/EventListener/ListenerAttributeCoverageTest.php
git commit -m "Add AsEventListener / AsHook attributes to listeners (dormant)"
```

---

## Task 14: AsCallback-Attribute – DCA-Listener Gruppe A (dormant)

**Files:**
- Modify: `src/EventListener/Dca/ContentDcaListener.php`, `LayerDcaListener.php`, `MapLayerSelectionDcaListener.php`, `MapDcaListener.php`, `MapLayerDcaListener.php`, `MapPaneDcaListener.php`

**Interfaces:**
- Consumes: Goldliste aus `ListenerAttributeCoverageTest::goldenCallbacks()`.

- [ ] **Step 1: Attribute setzen – pro Klasse**

Für jede der sechs Klassen: `use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;` ergänzen (falls nicht vorhanden), und über **jeder** Zielmethode das Attribut nach folgendem Muster (Werte 1:1 aus der Goldliste bzw. dem gelöschten `listeners.xml`-Tag; `method` aus dem alten `method="…"`, sonst `__invoke`):

`ContentDcaListener`:
```php
#[AsCallback('tl_content', 'config.onload')]
public function onLoad(...) // vorhandene Methode

#[AsCallback('tl_content', 'fields.cowegis_client.options')]
public function clientOptions(...)
```

`LayerDcaListener`:
```php
#[AsCallback('tl_cowegis_layer', 'fields.type.options')]                                     public function typeOptions(...)
#[AsCallback('tl_cowegis_layer', 'fields.fileFormat.options')]                               public function fileFormatOptions(...)
#[AsCallback('tl_cowegis_layer', 'fields.amenityIcons.eval.columnFields.amenity.options')]   public function amenitiesOptions(...)
#[AsCallback('tl_cowegis_layer', 'fields.file.load')]                                        public function prepareFileWidget(...)
#[AsCallback('tl_cowegis_layer', 'list.label.label')]                                        public function rowLabel(...)
#[AsCallback('tl_cowegis_layer', 'list.operations.data.button')]                             public function editDataButton(...)
#[AsCallback('tl_cowegis_layer', 'list.sorting.paste_button')]                               public function pasteButtons(...)
```

`MapLayerSelectionDcaListener`:
```php
#[AsCallback('tl_cowegis_layer', 'config.onload')]   public function initializeMapView(...)
```

`MapDcaListener`:
```php
#[AsCallback('tl_cowegis_map', 'fields.layers.eval.listCallback')]   public function layerList(...)
#[AsCallback('tl_cowegis_map', 'config.onload')]                     public function showIncompleteConfigurationWarning(...)
```

`MapLayerDcaListener`:
```php
#[AsCallback('tl_cowegis_map_layer', 'config.onload')]              public function initializePalette(...)
#[AsCallback('tl_cowegis_map_layer', 'list.sorting.child_record')]  public function rowLabel(...)
#[AsCallback('tl_cowegis_map_layer', 'fields.pane.options')]        public function paneOptions(...)
#[AsCallback('tl_cowegis_map_layer', 'fields.dataPane.options')]    public function paneOptions(...)   // zweites Attribut an derselben Methode
#[AsCallback('tl_cowegis_map_layer', 'fields.filterRules.options')] public function fileRuleOptions(...)
#[AsCallback('tl_cowegis_map_layer', 'fields.layerId.input_field')] public function layerFieldLabel(...)
```
(Bei `paneOptions`: zwei `#[AsCallback]`-Zeilen übereinander an derselben Methode – Attribut ist wiederholbar.)

`MapPaneDcaListener`:
```php
#[AsCallback('tl_cowegis_map_pane', 'list.sorting.child_record')]   public function rowLabel(...)
#[AsCallback('tl_cowegis_map_pane', 'fields.name.save')]            public function onSaveName(...)
```

- [ ] **Step 2: Verifizieren, dass jede benannte Methode existiert**

Run: `for c in ContentDcaListener LayerDcaListener MapLayerSelectionDcaListener MapDcaListener MapLayerDcaListener MapPaneDcaListener; do echo "== $c =="; grep -n "public function" "src/EventListener/Dca/$c.php"; done`
Expected: alle im Muster genannten Methodennamen kommen vor. Bei Abweichung: den tatsächlichen Namen verwenden (die Goldliste `table::target` bleibt maßgeblich, nur die Methode variiert).

- [ ] **Step 3: Test – Gruppe A grün schalten**

In `ListenerAttributeCoverageTest::testCallbackAttributesMatchGoldenList` die `markTestIncomplete`-Ausnahmeliste um die 6 Klassen dieser Task erweitern (sie sollen jetzt real geprüft werden – also aus der „noch incomplete"-Logik entfernen). Konkret: die Whitelist der **fertigen** Klassen führen statt der unfertigen:

```php
        $done = [
            'Cowegis\Bundle\Contao\EventListener\Dca\ModuleDcaListener',
            'Cowegis\Bundle\Contao\EventListener\Dca\StyleDcaListener',
            'Cowegis\Bundle\Contao\EventListener\Dca\ContentDcaListener',
            'Cowegis\Bundle\Contao\EventListener\Dca\LayerDcaListener',
            'Cowegis\Bundle\Contao\EventListener\Dca\MapLayerSelectionDcaListener',
            'Cowegis\Bundle\Contao\EventListener\Dca\MapDcaListener',
            'Cowegis\Bundle\Contao\EventListener\Dca\MapLayerDcaListener',
            'Cowegis\Bundle\Contao\EventListener\Dca\MapPaneDcaListener',
        ];
        if (! in_array($class, $done, true)) {
            self::markTestIncomplete('AsCallback attributes added in later task');
        }
```

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -30`
Expected: die 6 Klassen-Fälle PASS, Rest incomplete, insgesamt grün.

- [ ] **Step 4: psalm + phpcs + rector**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs && vendor/bin/phpcq run rector 2>&1 | tail -30`
Expected: grün.

- [ ] **Step 5: Commit**

```bash
git add src/EventListener/Dca/ContentDcaListener.php src/EventListener/Dca/LayerDcaListener.php src/EventListener/Dca/MapLayerSelectionDcaListener.php src/EventListener/Dca/MapDcaListener.php src/EventListener/Dca/MapLayerDcaListener.php src/EventListener/Dca/MapPaneDcaListener.php tests/EventListener/ListenerAttributeCoverageTest.php
git commit -m "Add AsCallback attributes to DCA listeners (group A, dormant)"
```

---

## Task 15: AsCallback-Attribute – DCA-Listener Gruppe B (dormant)

**Files:**
- Modify: `src/EventListener/Dca/MarkerDcaListener.php`, `IconDcaListener.php`, `ControlDcaListener.php`, `OptionsListener.php`, `AliasGenerator.php`, `Validator.php`

- [ ] **Step 1: Attribute setzen**

`MarkerDcaListener` (`use` ergänzen):
```php
#[AsCallback('tl_cowegis_marker', 'list.sorting.child_record')]        public function rowLabel(...)
#[AsCallback('tl_cowegis_marker', 'fields.coordinates.save')]          public function saveCoordinates(...)
#[AsCallback('tl_cowegis_marker', 'fields.coordinates.load', priority: 128)] public function loadCoordinates(...)
```

`IconDcaListener`:
```php
#[AsCallback('tl_cowegis_icon', 'fields.type.options')]   public function iconOptions(...)
```

`ControlDcaListener`:
```php
#[AsCallback('tl_cowegis_control', 'list.sorting.child_record')]                          public function rowLabel(...)
#[AsCallback('tl_cowegis_control', 'fields.type.options')]                                public function typeOptions(...)
#[AsCallback('tl_cowegis_control', 'fields.layers.load')]                                 public function loadLayerRelations(...)
#[AsCallback('tl_cowegis_control', 'fields.layers.save')]                                 public function saveLayerRelations(...)
#[AsCallback('tl_cowegis_control', 'fields.layers.eval.columnFields.layer.options')]      public function layerOptions(...)
```

`OptionsListener` – neun `#[AsCallback]`-Zeilen an der einen Methode `zoomOptions()` (Attribut wiederholbar):
```php
#[AsCallback('tl_cowegis_map', 'fields.zoom.options')]
#[AsCallback('tl_cowegis_map', 'fields.minZoom.options')]
#[AsCallback('tl_cowegis_map', 'fields.maxZoom.options')]
#[AsCallback('tl_cowegis_map', 'fields.locateMaxZoom.options')]
#[AsCallback('tl_cowegis_layer', 'fields.minZoom.options')]
#[AsCallback('tl_cowegis_layer', 'fields.maxZoom.options')]
#[AsCallback('tl_cowegis_layer', 'fields.maxNativeZoom.options')]
#[AsCallback('tl_cowegis_layer', 'fields.disableClusteringAtZoom.options')]
#[AsCallback('tl_cowegis_control', 'fields.zoomControl.options')]
public function zoomOptions(): array
```

`AliasGenerator` – sieben `#[AsCallback(..., priority: 128)]` an `__invoke`:
```php
#[AsCallback('tl_cowegis_map', 'fields.alias.save', priority: 128)]
#[AsCallback('tl_cowegis_marker', 'fields.alias.save', priority: 128)]
#[AsCallback('tl_cowegis_layer', 'fields.alias.save', priority: 128)]
#[AsCallback('tl_cowegis_icon', 'fields.alias.save', priority: 128)]
#[AsCallback('tl_cowegis_popup', 'fields.alias.save', priority: 128)]
#[AsCallback('tl_cowegis_control', 'fields.alias.save', priority: 128)]
#[AsCallback('tl_cowegis_style', 'fields.alias.save', priority: 128)]
public function __invoke(mixed $value, DataContainer $dataContainer): string
```

`Validator`:
```php
#[AsCallback('tl_cowegis_marker', 'fields.coordinates.save', priority: 128)]
#[AsCallback('tl_cowegis_map', 'fields.center.save', priority: 128)]
#[AsCallback('tl_cowegis_popup', 'fields.offset.save', priority: 128)]
public function validateCoordinates(...)

#[AsCallback('tl_cowegis_map', 'fields.alias.save')]
#[AsCallback('tl_cowegis_marker', 'fields.alias.save')]
#[AsCallback('tl_cowegis_layer', 'fields.alias.save')]
#[AsCallback('tl_cowegis_icon', 'fields.alias.save')]
#[AsCallback('tl_cowegis_popup', 'fields.alias.save')]
#[AsCallback('tl_cowegis_control', 'fields.alias.save')]
#[AsCallback('tl_cowegis_style', 'fields.alias.save')]
public function validateAlias(...)
```

- [ ] **Step 2: `markTestIncomplete`-Logik entfernen**

In `ListenerAttributeCoverageTest::testCallbackAttributesMatchGoldenList` den kompletten `$done`/`markTestIncomplete`-Block **löschen** – ab jetzt werden alle Klassen der Goldliste real geprüft.

- [ ] **Step 3: Methodennamen verifizieren**

Run: `for c in MarkerDcaListener IconDcaListener ControlDcaListener OptionsListener AliasGenerator Validator; do echo "== $c =="; grep -n "public function" "src/EventListener/Dca/$c.php"; done`
Expected: alle genannten Methodennamen existieren.

- [ ] **Step 4: Test – gesamte Goldliste grün**

Run: `vendor/bin/phpunit tests/EventListener --testdox 2>&1 | tail -40`
Expected: **alle** `testCallbackAttributesMatchGoldenList`-Fälle PASS, `testEventListenerAttributes`, `testHookAttribute` PASS.

- [ ] **Step 5: psalm + phpcs + rector + phpmd**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs && vendor/bin/phpcq run rector && vendor/bin/phpcq run phpmd 2>&1 | tail -30`
Expected: grün.

- [ ] **Step 6: Commit**

```bash
git add src/EventListener/Dca/MarkerDcaListener.php src/EventListener/Dca/IconDcaListener.php src/EventListener/Dca/ControlDcaListener.php src/EventListener/Dca/OptionsListener.php src/EventListener/Dca/AliasGenerator.php src/EventListener/Dca/Validator.php tests/EventListener/ListenerAttributeCoverageTest.php
git commit -m "Add AsCallback attributes to DCA listeners (group B, dormant)"
```

---

## Task 16: listeners → YAML + XmlFileLoader entfernen

**Files:**
- Create: `src/Resources/config/listeners.yaml`
- Delete: `src/Resources/config/listeners.xml`
- Modify: `src/DependencyInjection/CowegisContaoExtension.php`

**Interfaces:**
- Consumes: alle Attribute aus Task 13–15.
- Produces: `listeners.yaml` **ohne** `contao.callback` / `kernel.event_listener`-Tags; die Tags entstehen jetzt aus den PHP-Attributen (autoconfigure).

- [ ] **Step 1: `listeners.yaml`**

`_defaults` mit `autoconfigure: true`. Alle Services behalten `arguments` und (bei DCA-Listenern) `public: true`; **keine** `tags:` mehr für `contao.callback` / `kernel.event_listener`.

```yaml
services:
    _defaults:
        autowire: false
        autoconfigure: true
        public: false

    Cowegis\Bundle\Contao\EventListener\BackendMenuListener:
        arguments:
            - '@request_stack'

    Cowegis\Bundle\Contao\EventListener\BackendStyleListener:
        arguments:
            - '@netzmacht.contao_toolkit.assets_manager'
            - '@netzmacht.contao_toolkit.routing.scope_matcher'

    Cowegis\Bundle\Contao\EventListener\LayerResponseListener:
        arguments:
            - '@Cowegis\Bundle\Contao\Model\LayerRepository'

    Cowegis\Bundle\Contao\EventListener\MapResponseListener:
        arguments:
            - '@Cowegis\Bundle\Contao\Model\Map\MapRepository'

    Cowegis\Bundle\Contao\EventListener\Filter\ApplyFilterRuleMarkerListener: ~

    Cowegis\Bundle\Contao\EventListener\Dca\ContentDcaListener:
        public: true
        arguments:
            - '@netzmacht.contao_toolkit.dca.manager'
            - '%cowegis_contao.client_bundle%'

    Cowegis\Bundle\Contao\EventListener\Dca\ModuleDcaListener:
        arguments:
            - '@netzmacht.contao_toolkit.dca.manager'
            - '%cowegis_contao.client_bundle%'

    Cowegis\Bundle\Contao\EventListener\Dca\LayerDcaListener:
        public: true
        arguments:
            - '@netzmacht.contao_toolkit.dca.manager'
            - '@Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry'
            - '@translator'
            - '@netzmacht.contao_toolkit.contao.backend_adapter'
            - '%cowegis_contao.file_formats%'
            - '%cowegis_contao.amenities%'

    Cowegis\Bundle\Contao\EventListener\Dca\MapLayerSelectionDcaListener:
        public: true
        arguments:
            - '@netzmacht.contao_toolkit.dca.manager'
            - '@Cowegis\Bundle\Contao\Model\Map\MapRepository'
            - '@database_connection'
            - '@router'
            - '@translator'
            - '@netzmacht.contao_toolkit.csrf.token_provider'
            - '@netzmacht.contao_toolkit.callback_invoker'

    Cowegis\Bundle\Contao\EventListener\Dca\MapDcaListener:
        public: true
        arguments:
            - '@netzmacht.contao_toolkit.dca.manager'
            - '@Cowegis\Bundle\Contao\Model\Map\MapRepository'
            - '@translator'
            - '@request_stack'

    Cowegis\Bundle\Contao\EventListener\Dca\MapLayerDcaListener:
        public: true
        arguments:
            - '@netzmacht.contao_toolkit.dca.manager'
            - '@Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry'
            - '@Cowegis\Bundle\Contao\Model\Map\MapLayerRepository'
            - '@Cowegis\Bundle\Contao\Model\LayerRepository'
            - '@Cowegis\Bundle\Contao\Model\Map\MapPaneRepository'
            - '@Cowegis\Core\Filter\FilterFactory'

    Cowegis\Bundle\Contao\EventListener\Dca\MapPaneDcaListener:
        public: true
        arguments:
            - '@netzmacht.contao_toolkit.dca.manager'

    Cowegis\Bundle\Contao\EventListener\Dca\MarkerDcaListener:
        public: true
        arguments:
            - '@netzmacht.contao_toolkit.dca.manager'
            - '@database_connection'

    Cowegis\Bundle\Contao\EventListener\Dca\IconDcaListener:
        public: true
        arguments:
            - '@Cowegis\Bundle\Contao\Map\Icon\IconTypeRegistry'

    Cowegis\Bundle\Contao\EventListener\Dca\ControlDcaListener:
        public: true
        arguments:
            - '@netzmacht.contao_toolkit.dca.manager'
            - '@Cowegis\Bundle\Contao\Map\Control\ControlTypeRegistry'
            - '@database_connection'
            - '@Cowegis\Bundle\Contao\Model\LayerRepository'
            - '@?Cowegis\ContaoGeocoder\Provider\Geocoder'

    Cowegis\Bundle\Contao\EventListener\Dca\OptionsListener:
        public: true

    Cowegis\Bundle\Contao\EventListener\Dca\StyleDcaListener:
        arguments:
            - '@netzmacht.contao_toolkit.dca.manager'
            - '@Cowegis\Bundle\Contao\Map\Style\StyleTypeRegistry'

    Cowegis\Bundle\Contao\EventListener\Dca\AliasGenerator:
        arguments:
            - '@cowegis_contao.slug_generator'
            - '@database_connection'

    Cowegis\Bundle\Contao\EventListener\Dca\Validator:
        public: true
        arguments:
            - '@netzmacht.contao_toolkit.dca.manager'
            - '@translator'

    Cowegis\Bundle\Contao\EventListener\Hook\LanguageFileListener:
        arguments:
            - '@netzmacht.contao_toolkit.contao.system_adapter'
```

> **Achtung – `public`-Flags exakt aus `listeners.xml` übernehmen.** Im XML haben `ModuleDcaListener`, `StyleDcaListener`, `AliasGenerator`, `BackendStyleListener` **kein** `public="true"` (sie standen dort auf `autoconfigure="true"` bzw. Default). Alle anderen DCA-Listener haben `public="true"`. Vor dem Schreiben `git show HEAD~N:src/Resources/config/listeners.xml` (bzw. den letzten Commit mit der Datei) gegenlesen und jede `public: true`-Zeile abgleichen.

- [ ] **Step 2: Extension – letzte Zeile umhängen + `XmlFileLoader` entfernen**

`$xmlLoader->load('listeners.xml');` → `$yamlLoader->load('listeners.yaml');`

Danach wird `$xmlLoader` nirgends mehr benutzt: Zeile `$xmlLoader = new XmlFileLoader(...);` und den Import `use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;` entfernen. `FileLocator`-Import bleibt (vom `YamlFileLoader` genutzt).

Ziel-`load()`-Block (nur noch YAML):

```php
$yamlLoader->load('amenities.yaml');
$yamlLoader->load('config.yaml');
$yamlLoader->load('controls.yaml');
$yamlLoader->load('fragments.yaml');
$yamlLoader->load('hydrators.yaml');
$yamlLoader->load('icons.yaml');
$yamlLoader->load('styles.yaml');
$yamlLoader->load('layers.yaml');
$yamlLoader->load('listeners.yaml');
$yamlLoader->load('services.yaml');
$yamlLoader->load('repositories.yaml');
```

- [ ] **Step 3: XML löschen**

```bash
git rm src/Resources/config/listeners.xml
```

- [ ] **Step 4: Container-Test um Listener-Existenz erweitern**

`CowegisContaoExtensionTest`:

```php
    public function testListenerServicesRegistered(): void
    {
        $container = self::compiledContainer();

        foreach ([
            'Cowegis\Bundle\Contao\EventListener\BackendMenuListener',
            'Cowegis\Bundle\Contao\EventListener\LayerResponseListener',
            'Cowegis\Bundle\Contao\EventListener\MapResponseListener',
            'Cowegis\Bundle\Contao\EventListener\Dca\LayerDcaListener',
            'Cowegis\Bundle\Contao\EventListener\Dca\ControlDcaListener',
            'Cowegis\Bundle\Contao\EventListener\Hook\LanguageFileListener',
        ] as $id) {
            self::assertTrue($container->hasDefinition($id), $id);
        }

        self::assertTrue($container->getDefinition('Cowegis\Bundle\Contao\EventListener\Dca\LayerDcaListener')->isPublic());
        self::assertFalse($container->getDefinition('Cowegis\Bundle\Contao\EventListener\Dca\StyleDcaListener')->isPublic());
    }
```

- [ ] **Step 5: Voller Testlauf**

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -40`
Expected: grün – Container kompiliert weiterhin ohne `listeners.xml`; `ListenerAttributeCoverageTest` unverändert grün.

- [ ] **Step 6: psalm + phpcs**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs 2>&1 | tail -20`
Expected: grün. `CowegisContaoExtension` hat keinen `XmlFileLoader`-Import mehr (psalm `UnusedImport` würde sonst greifen).

- [ ] **Step 7: Commit**

```bash
git add src/Resources/config/listeners.yaml src/DependencyInjection/CowegisContaoExtension.php tests/DependencyInjection/CowegisContaoExtensionTest.php
git commit -m "Convert listeners service config to YAML; drop XmlFileLoader"
```

---

## Task 17: routing.xml → routing.yaml

**Files:**
- Create: `src/Resources/config/routing.yaml`
- Delete: `src/Resources/config/routing.xml`
- Modify: `src/ContaoManager/Plugin.php`

- [ ] **Step 1: `routing.yaml`**

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

- [ ] **Step 2: `Plugin.php` anpassen**

In `src/ContaoManager/Plugin.php`, `getRouteCollection()`: die **zwei** Vorkommen von
```php
__DIR__ . '/../Resources/config/routing.xml'
```
ersetzen durch
```php
__DIR__ . '/../Resources/config/routing.yaml'
```
(Es sind genau zwei – `$resolver->resolve(...)` und `$loader->load(...)`. Der Verweis auf `$apiPath . '/Resources/config/routing.xml'` **bleibt** – das ist die Datei des api-bundle.)

- [ ] **Step 3: XML löschen**

```bash
git rm src/Resources/config/routing.xml
```

- [ ] **Step 4: Regression-Test für Plugin-Routing**

`tests/ContaoManager/PluginRoutingTest.php`:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Test\ContaoManager;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

final class PluginRoutingTest extends TestCase
{
    public function testBundleRoutingYamlLoads(): void
    {
        $dir = \dirname(__DIR__, 2) . '/src/Resources/config';
        $loader = new YamlFileLoader(new FileLocator([$dir]));
        $collection = $loader->load('routing.yaml');

        self::assertInstanceOf(RouteCollection::class, $collection);
        self::assertNotNull($collection->get('cowegis_contao_backend_api_docs'));
        self::assertNotNull($collection->get('cowegis_contao_backend_map_layer_actions'));
        self::assertSame('/contao/cowegis/docs', $collection->get('cowegis_contao_backend_api_docs')->getPath());
        self::assertSame(['GET'], $collection->get('cowegis_contao_backend_api_docs')->getMethods());
        self::assertSame('backend', $collection->get('cowegis_contao_backend_api_docs')->getDefault('_scope'));
    }
}
```

- [ ] **Step 5: Test**

Run: `vendor/bin/phpcq run phpunit 2>&1 | tail -20`
Expected: grün.

- [ ] **Step 6: psalm + phpcs + rector**

Run: `vendor/bin/phpcq run psalm && vendor/bin/phpcq run phpcs && vendor/bin/phpcq run rector 2>&1 | tail -20`
Expected: grün.

- [ ] **Step 7: Commit**

```bash
git add src/Resources/config/routing.yaml src/ContaoManager/Plugin.php tests/ContaoManager/PluginRoutingTest.php
git commit -m "Convert routing config to YAML"
```

---

## Task 18: CLAUDE.md aktualisieren

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Abschnitt „Commands" – phpspec-Block ergänzen**

Nach dem `vendor/bin/phpspec run …`-Block einfügen:

```markdown
PHPUnit runs alongside phpspec (config service tests etc.), specs in `tests/`,
namespace `Cowegis\Bundle\Contao\Test`:

\```bash
vendor/bin/phpcq run phpunit
vendor/bin/phpunit tests/DependencyInjection        # single suite
\```
```

- [ ] **Step 2: Abschnitt „Service wiring" umschreiben**

Den Absatz, der mit „`CowegisContaoExtension` loads `src/Resources/config/*.xml`" beginnt, ersetzen durch:

```markdown
`CowegisContaoExtension` loads `src/Resources/config/*.yaml` **explicitly and in
order** via `YamlFileLoader`. Autowiring is **off** in every file
(`_defaults: { autowire: false }`) — wire every constructor argument by hand.
Autoconfiguration is **on** by default; the five marker-interface tags
(`Hydrator`, `LayerType`, `ControlType`, `IconType`, `StyleType`) are attached via
`registerForAutoconfiguration()` calls at the top of `CowegisContaoExtension::load()`.
`autoconfigure: false` is set locally where it would double-tag or self-reference:
the `DelegatingHydrator` collector service, the priority hydrators
(`LocateOptionsHydrator`, `BoundsOptionsHydrator`, `EventDispatchingHydrator`),
`ConsentBridge\Plugin`, and all of `repositories.yaml`.

Contao/Symfony behaviour tags come from PHP attributes on the classes:
`#[AsCallback]` (DCA listeners in `src/EventListener/Dca/`), `#[AsEventListener]`
(response/menu/filter listeners), `#[AsHook]` (`LanguageFileListener`),
`#[AsContentElement]` / `#[AsFrontendModule]` (fragment actions).

Attribute-carrying tags that stay explicit in YAML: `Cowegis\Core\Serializer\Serializer`
(`key`), `netzmacht.contao_toolkit.repository` (`model`),
`Cowegis\Bundle\Contao\Provider\LayerDataProvider` (`type`),
`Cowegis\Core\Schema\LayerSchemaDescriber` (via `_instanceof` in `layers.yaml`),
and the singletons `Cowegis\Core\Provider\Provider` / `…\Schema\SchemaDescriber` /
`…\IdFormat\IdFormat` / `…\Schema\IdSchema`.
```

- [ ] **Step 3: Abschnitt „Adding a layer type" anpassen**

```markdown
**Adding a layer type**: new `FooLayerType` + `FooLayerHydrator` in
`src/Map/Layer/Foo/`, register **both** in `src/Resources/config/layers.yaml`
(the `LayerType` / `Hydrator` tags come automatically from
`registerForAutoconfiguration`; only wire the constructor arguments). Add the
matching `cowegis-core` `SchemaDescriber` (string arg + `_instanceof` tag) /
`Serializer` (`arguments: ['@Cowegis\Core\Serializer\Serializer']` + explicit
`key` tag) entries in the same file.
```

- [ ] **Step 4: Abschnitt „Conventions" – XML-Zeile**

Falls dort noch von `.xml`-Service-Konfiguration die Rede ist: auf `.yaml` ändern. Die Zeile zu `src/Resources/contao/dca` (psalm-Ausschluss) bleibt.

- [ ] **Step 5: Commit**

```bash
git add CLAUDE.md
git commit -m "Document YAML service config + autoconfigure conventions"
```

---

## Task 19: Voller QA-Lauf + Abschluss

**Files:** keine (nur Verifikation), ggf. kleine Fixes.

- [ ] **Step 1: Kompletten phpcq-Default-Lauf**

Run: `vendor/bin/phpcq run 2>&1 | tail -60`
Expected: **alle** Tools grün – `composer-normalize`, `composer-require-checker`, `phpcpd`, `phploc`, `phpmd`, `psalm`, `rector`, `phpcs`, `phpspec`, `phpunit`.

- [ ] **Step 2: Falls `composer-normalize` meckert**

Run: `vendor/bin/phpcq run fix 2>&1 | tail -20` → dann erneut `vendor/bin/phpcq run`.

- [ ] **Step 3: Prüfen, dass keine `.xml`-Service-Datei übrig ist**

Run: `ls src/Resources/config/`
Expected: nur `*.yaml` (11 Dateien: amenities, config, controls, fragments, hydrators, icons, layers, listeners, repositories, routing, services). Kein `.xml`.

- [ ] **Step 4: Grep nach XML-Loader-Rückständen**

Run: `grep -rn "XmlFileLoader\|routing.xml\|\.xml'" src/DependencyInjection/ src/ContaoManager/`
Expected: keine Treffer für den bundle-eigenen Pfad. (`$apiPath . '/Resources/config/routing.xml'` in `Plugin.php` ist erlaubt – api-bundle.)

- [ ] **Step 5: Manueller Funktions-Smoke-Test (falls Contao-5-Testinstanz verfügbar)**

In einer Contao-5-App mit installiertem Bundle:
```bash
bin/console debug:container --tag=Cowegis\\Bundle\\Contao\\Hydrator\\Hydrator
bin/console debug:container Cowegis\\Bundle\\Contao\\Provider\\ContaoBackendProvider
bin/console debug:router | grep cowegis_contao
bin/console cache:clear
```
Expected: Hydrator-Chain vollständig, Provider auflösbar, beide Backend-Routen vorhanden, Cache-Clear ohne Fehler. Backend öffnen, ein Karten-Element im Frontend rendern.

Falls keine Instanz verfügbar: Schritt überspringen und im Abschluss vermerken.

- [ ] **Step 6: Abschluss-Commit (nur falls Fixes in Step 2 nötig waren)**

```bash
git add -A
git commit -m "QA fixes after XML to YAML migration"
```

- [ ] **Step 7: Branch-Übersicht**

Run: `git log --oneline master..HEAD`
Expected: ~19 fokussierte Commits, jeder für sich grün.

---

## Self-Review (durchgeführt)

**Spec-Abdeckung:**
- §2 Versionen → Task 1 ✓
- §3 Tag-Strategie → Tasks 4 (Interfaces), 8–12 (explizite Tags), 13–15 (Attribute) ✓
- §4 kein `#[AsSerializer]` → Serializer als explizite `key`-Tags in Tasks 8/10 ✓
- §5 `registerForAutoconfiguration` → Task 4 ✓
- §6 Übersetzungsregeln → in jeder YAML-Task angewandt ✓
- §7 Datei-für-Datei → Tasks 5–12, 16, 17 ✓
- §8 Extension → Tasks 4, 5, 16 ✓
- §9 PHPUnit/companion → Task 2 ✓
- §10 Container-Test → Task 3 (+ Erweiterungen in Folgetasks); §10-Fall 8 (`#[AsCallback]`-Solllisten) als eigener Reflection-Test `ListenerAttributeCoverageTest` in Tasks 13–15 ✓
- §11 CLAUDE.md → Task 18 ✓
- §12 Reihenfolge → Task-Nummerierung folgt ihr ✓
- §13 Risiken → Doppel-Tag-Test (Task 4/6/9), Priorität-Test (Task 3/9), DelegatingHydrator (Task 11), routing (Task 17), companion-Diff (Task 2) ✓
- §14 QA-Gates → Task 19 ✓

**Platzhalter-Scan:** Amenities-Liste = mechanische 1:1-Übernahme aus vorhandener Datei mit Anzahl-Kontrolle (kein Platzhalter). Alle Test- und YAML-Blöcke ausgeschrieben. companion-`project:configure`-Output ist umgebungsabhängig → mit expliziten „falls X, dann Y"-Zweigen abgedeckt.

**Typ-Konsistenz:** `StubContainerFactory::create()/compile()/STUB_SERVICE_IDS`, `expectedHydratorIds()`, Tag-Konstanten und Golden-List-Struktur (`table::target`) durchgängig identisch über Tasks 3, 7, 10–16 verwendet.
