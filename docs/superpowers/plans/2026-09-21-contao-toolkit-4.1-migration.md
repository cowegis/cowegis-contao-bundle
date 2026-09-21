# contao-toolkit 4.1 Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate `cowegis/cowegis-contao-bundle` from `netzmacht/contao-toolkit` `^4.0` to `^4.1@dev` (4.1.0 is not tagged on Packagist yet — its changes live on the `develop` branch), replacing every deprecated toolkit API this bundle uses with its 5.0-ready replacement, with no behavioural change for visitors or backend editors.

**Architecture:** Five independent, sequentially-committed migrations: (1) version constraints, (2) `ResponseTagger` → `Contao\CoreBundle\Cache\CacheTagManager`, (3) `RequestScopeMatcher` → `Contao\CoreBundle\Routing\ScopeMatcher`, (4) DCA color-picker wizard → native `colorpicker` eval, (5) the Hybrid-fragment-controller split into independent `MapContentElementAction`/`MapModuleAction` classes sharing a `MapFragmentDataTrait`. Each task leaves the container compilable and the test suite green — tasks 2–4 deliberately do not touch `src/Resources/config/fragments.yaml` so the still-untouched `MapFragmentAction` keeps working right up until task 5 replaces it atomically.

**Tech Stack:** PHP 8.2+, Symfony DI (YAML service config, `autowire: false` everywhere), Contao 5.7 fragment controllers, phpspec, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-21-contao-toolkit-4.1-migration-design.md`

## Global Constraints

- `netzmacht/contao-toolkit`: `^4.0` → `^4.1@dev` (no stable `4.1.0` tag exists yet; resolves to
  the `develop` branch, published as `dev-develop`/`4.1.x-dev`)
- `contao/core-bundle`: `^5.3` → `^5.7`
- No migration of the legacy `.html5` fragment templates to Twig — out of scope (Contao Core keeps rendering un-namespaced template names via the legacy engine).
- Every task must leave `vendor/bin/phpunit tests/DependencyInjection` green (container compiles, `testMapActionArgumentCount` etc. pass) before moving to the next task.
- `_defaults: { autowire: false }` convention: every new/changed constructor argument is wired explicitly in the matching `src/Resources/config/*.yaml` file — never rely on autowiring.
- `declare(strict_types=1)` and `#[Override]` on every interface/parent implementation, matching existing files.

---

### Task 1: Bump `composer.json` version constraints

**Files:**
- Modify: `composer.json:19` (`contao/core-bundle`), `composer.json:28` (`netzmacht/contao-toolkit`)

**Interfaces:** None — pure constraint change, no code depends on this task's output beyond the constraint itself.

- [ ] **Step 1: Edit the two constraints**

In `composer.json`, change:

```json
    "contao/core-bundle": "^5.3",
```
to:
```json
    "contao/core-bundle": "^5.7",
```

and change:
```json
    "netzmacht/contao-toolkit": "^4.0",
```
to:
```json
    "netzmacht/contao-toolkit": "^4.1@dev",
```

`4.1.0` is not tagged on Packagist yet (highest stable tag is `4.0.11`) — its changes live on the
`develop` branch. The `@dev` stability flag is required, or composer will fail to resolve any
version at all satisfying `^4.1`. This project's `minimum-stability` is already `dev` (see
`CLAUDE.md`), so the flag only needs to apply to this one package, not the whole project.

- [ ] **Step 2: Validate the composer manifest**

Run: `composer validate --no-check-publish`
Expected: `./composer.json is valid`

- [ ] **Step 3: Actually install the raised versions**

Editing `composer.json` alone does not touch `vendor/` — Task 5 needs the real `Netzmacht\Contao\Toolkit\Controller\Fragment\*` classes (new in 4.1.0) physically installed, not just the constraint string changed.

Run: `composer update netzmacht/contao-toolkit contao/core-bundle --with-all-dependencies --no-interaction`
Expected: exits 0, resolving `netzmacht/contao-toolkit` to `dev-develop` (verified in advance via
`composer require "netzmacht/contao-toolkit:^4.1@dev" --dry-run`, which reported
`Upgrading netzmacht/contao-toolkit (4.0.11 => dev-develop ...)`). If it fails on a conflicting
transitive constraint from another package (e.g. `cowegis/cowegis-api-bundle`), report BLOCKED
with the full composer output — do not attempt to loosen unrelated constraints yourself.

Then verify the installed versions:

Run: `composer show netzmacht/contao-toolkit` and `composer show contao/core-bundle`
Expected: `versions` line shows `* dev-develop` (or similar, marking the currently-installed dev
version) for `netzmacht/contao-toolkit`, and a `5.7.*` (or higher, still `<6.0`) release for
`contao/core-bundle`.

- [ ] **Step 4: Update the documented Contao floor in `CLAUDE.md`**

In `CLAUDE.md`, change:

```
PHP `^8.2`; supports Contao `^5.3` and Symfony `6.4 / 7.4`. CI runs on PHP 8.2, 8.3, 8.4.
```

to:

```
PHP `^8.2`; supports Contao `^5.7` and Symfony `6.4 / 7.4`. CI runs on PHP 8.2, 8.3, 8.4.
```

- [ ] **Step 5: Commit**

`composer.lock` is gitignored (`/composer.lock` in `.gitignore`) for this library — do not add it.

```bash
git add composer.json CLAUDE.md
git commit -m "chore: raise netzmacht/contao-toolkit and contao/core-bundle floors"
```

---

### Task 2: `ResponseTagger` → `Contao\CoreBundle\Cache\CacheTagManager`

**Files:**
- Modify: `src/Hydrator/ResponseTaggerPlugin.php`
- Modify: `src/Hydrator/DelegatingHydrator.php`
- Modify: `src/Map/Layer/Vector/VectorsLayerHydrator.php`
- Modify: `src/Map/Layer/Group/GroupLayerHydrator.php`
- Modify: `src/Map/Layer/Markers/Hydrator/MarkersLayerHydrator.php`
- Modify: `src/Map/Layer/Reference/ReferenceLayerHydrator.php`
- Modify: `src/Map/Layer/File/FileLayerHydrator.php`
- Modify: `src/Resources/config/layers.yaml` (8 references)
- Modify: `src/Resources/config/services.yaml:41`
- Modify: `tests/DependencyInjection/StubContainerFactory.php`

**Interfaces:**
- Consumes: `Contao\CoreBundle\Cache\CacheTagManager::tagWithModelInstance(Contao\Model $instance): void` (from `contao/core-bundle`, already vendored).
- Produces: `ResponseTaggerPlugin::__construct(CacheTagManager $cacheTagManager)` and `ResponseTaggerPlugin::tagResponseForData(mixed $data): void` — used unchanged by `LayerTypeHydrator` (via the trait) and `DelegatingHydrator`.

`Cowegis\Bundle\Contao\Model\Model extends Contao\Model`, so both the bundle's own models and plain Contao models can be tagged with the single `tagWithModelInstance()` call — the old `CowegisModel`/`ContaoModel` branching in `tagResponseForData()` collapses into one `instanceof Contao\Model` check.

`src/Resources/config/fragments.yaml` is **not** touched in this task — `MapFragmentAction` still injects `Netzmacht\Contao\Toolkit\Response\ResponseTagger` directly (not via this trait) and is rewired wholesale in Task 5.

- [ ] **Step 1: Rewrite `ResponseTaggerPlugin`**

Replace the full content of `src/Hydrator/ResponseTaggerPlugin.php` with:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator;

use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\Model;

trait ResponseTaggerPlugin
{
    public function __construct(private readonly CacheTagManager $cacheTagManager)
    {
    }

    protected function tagResponseForData(mixed $data): void
    {
        if ($data instanceof Model) {
            $this->cacheTagManager->tagWithModelInstance($data);
        }
    }
}
```

- [ ] **Step 2: Update `DelegatingHydrator`**

In `src/Hydrator/DelegatingHydrator.php`, change:

```php
use Cowegis\Core\Provider\Context;
use Netzmacht\Contao\Toolkit\Response\ResponseTagger;
use Override;

/** @psalm-suppress PropertyNotSetInConstructor - see https://github.com/vimeo/psalm/issues/5062 */
final class DelegatingHydrator implements Hydrator
{
    use ResponseTaggerPlugin {
        ResponseTaggerPlugin::__construct as private responseTaggerConstruct;
    }

    /** @param Hydrator[] $hydrators */
    public function __construct(private readonly iterable $hydrators, ResponseTagger $responseTagger)
    {
        $this->responseTaggerConstruct($responseTagger);
    }
```

to:

```php
use Contao\CoreBundle\Cache\CacheTagManager;
use Cowegis\Core\Provider\Context;
use Override;

/** @psalm-suppress PropertyNotSetInConstructor - see https://github.com/vimeo/psalm/issues/5062 */
final class DelegatingHydrator implements Hydrator
{
    use ResponseTaggerPlugin {
        ResponseTaggerPlugin::__construct as private responseTaggerConstruct;
    }

    /** @param Hydrator[] $hydrators */
    public function __construct(private readonly iterable $hydrators, CacheTagManager $cacheTagManager)
    {
        $this->responseTaggerConstruct($cacheTagManager);
    }
```

- [ ] **Step 3: Update the five hydrator constructors**

The `use` import block in each file is alphabetically sorted (phpcs `SlevomatCodingStandard.Namespaces.AlphabeticallySortedUses`) and `Contao\CoreBundle\Cache\CacheTagManager` sorts before every existing `Cowegis\...`/`Contao\FilesModel`/`Contao\Model` import in these files — it must be inserted at the top of the block, not left at the old `ResponseTagger` position. Replace the full content of each file:

`src/Map/Layer/Vector/VectorsLayerHydrator.php`:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Vector;

use Contao\CoreBundle\Cache\CacheTagManager;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\GeoData\RawGeoJsonGeoData;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Map\Style\StyleTypeRegistry;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\StyleModel;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Expression\InlineExpression;
use Cowegis\Core\Definition\GeoData\GeoData;
use Cowegis\Core\Definition\GeoData\UriData;
use Cowegis\Core\Definition\Layer\DataLayer;
use Cowegis\Core\Definition\Layer\Layer;
use Override;
use Symfony\Component\Routing\RouterInterface;

use function array_merge;
use function assert;

final class VectorsLayerHydrator extends LayerTypeHydrator
{
    public function __construct(
        private readonly RouterInterface $router,
        CacheTagManager $cacheTagManager,
        private readonly StyleTypeRegistry $styleTypes,
    ) {
        parent::__construct($cacheTagManager);
    }

    #[Override]
    protected function supportedType(): string
    {
        return 'vectors';
    }

    #[Override]
    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator,
    ): void {
        assert($layer instanceof DataLayer);
        $layer->options()->set('adjustBounds', (bool) $context->mapLayerModel()->adjustBounds);

        if ($layerModel->pointToLayer !== null) {
            $layer->options()->set(
                'pointToLayer',
                $context->callbacks()->add(new InlineExpression($layerModel->pointToLayer)),
            );
        }

        if ($layerModel->onEachFeature !== null) {
            $layer->options()->set(
                'onEachFeature',
                $context->callbacks()->add(new InlineExpression($layerModel->onEachFeature)),
            );
        }

        if ($layerModel->style) {
            $styleModel = StyleModel::findByPk($layerModel->style);
            if ($styleModel instanceof StyleModel) {
                $style = $this->styleTypes->get($styleModel->type)->createDefinition($styleModel);
                $hydrator->hydrate($styleModel, $style, $context, $hydrator);
                $style->apply($layer);
            }
        }

        if ((bool) $layerModel->deferred) {
            $layer->withData(
                new UriData(
                    $this->router->generate(
                        'cowegis_api_layer_data',
                        array_merge(
                            $context->filter()->toQuery()->toArray(),
                            [
                                'type'    => 'vectors',
                                'mapId'   => $context->mapId()->value(),
                                'layerId' => $layer->layerId()->value(),
                                '_locale' => $context->locale(),
                            ],
                        ),
                    ),
                    GeoData::FORMAT_GEOJSON,
                ),
            );

            return;
        }

        $layer->withData(new RawGeoJsonGeoData($layerModel->vectors));
    }
}
```

`src/Map/Layer/Group/GroupLayerHydrator.php`:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Group;

use Contao\CoreBundle\Cache\CacheTagManager;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerRepository;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Definition\Layer\LayerGroup;
use Override;

use function assert;

class GroupLayerHydrator extends LayerTypeHydrator
{
    public function __construct(private readonly MapLayerRepository $repository, CacheTagManager $cacheTagManager)
    {
        parent::__construct($cacheTagManager);
    }

    #[Override]
    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator,
    ): void {
        assert($layer instanceof LayerGroup);

        $children = $this->repository->findChildren((int) $layerModel->layerId()->value()) ?: [];
        foreach ($children as $child) {
            assert($child instanceof MapLayerModel);
            $layer->layers()->addLayer($child->layerId());
        }
    }

    #[Override]
    protected function supportedType(): string
    {
        return 'group';
    }
}
```

`src/Map/Layer/Markers/Hydrator/MarkersLayerHydrator.php`:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator;

use Contao\CoreBundle\Cache\CacheTagManager;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Provider\LayerDataProvider;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Expression\InlineExpression;
use Cowegis\Core\Definition\GeoData\InlineGeoJsonData;
use Cowegis\Core\Definition\GeoData\UriData;
use Cowegis\Core\Definition\Layer\DataLayer;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Provider\LayerData\MarkersLayerData;
use Cowegis\Core\Serializer\Serializer;
use Override;
use Symfony\Component\Routing\RouterInterface;

use function array_merge;
use function assert;

final class MarkersLayerHydrator extends LayerTypeHydrator
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly LayerDataProvider $dataProvider,
        private readonly Serializer $serializer,
        CacheTagManager $cacheTagManager,
    ) {
        parent::__construct($cacheTagManager);
    }

    #[Override]
    protected function supportedType(): string
    {
        return 'markers';
    }

    #[Override]
    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator,
    ): void {
        assert($layer instanceof DataLayer);

        if ($layerModel->pointToLayer !== null) {
            $layer->options()->set(
                'pointToLayer',
                $context->callbacks()->add(new InlineExpression($layerModel->pointToLayer)),
            );
        }

        $layer->options()->set('adjustBounds', (bool) $context->mapLayerModel()->adjustBounds);

        if ((bool) $layerModel->deferred) {
            $layer->withData(
                new UriData(
                    $this->router->generate(
                        'cowegis_api_layer_data',
                        array_merge(
                            $context->filter()->toQuery()->toArray(),
                            [
                                'type'    => 'markers',
                                'mapId'   => $context->mapId()->value(),
                                'layerId' => $layer->layerId()->value(),
                                '_locale' => $context->locale(),
                            ],
                        ),
                    ),
                    'geojson',
                ),
            );

            return;
        }

        $layerData = $this->dataProvider->findLayerData($layerModel, $context);
        if (! ($layerData instanceof MarkersLayerData)) {
            return;
        }

        $layer->withData(new InlineGeoJsonData($this->serializer->serialize($layerData)));
    }
}
```

`src/Map/Layer/Reference/ReferenceLayerHydrator.php`:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Reference;

use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\Model;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\LayerRepository;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Exception\LayerNotFound;
use Override;

final class ReferenceLayerHydrator extends LayerTypeHydrator
{
    public function __construct(private readonly LayerRepository $layerRepository, CacheTagManager $cacheTagManager)
    {
        parent::__construct($cacheTagManager);
    }

    #[Override]
    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator,
    ): void {
        $referenceModel = $this->layerRepository->find((int) $layerModel->reference);
        if (! $referenceModel instanceof Model) {
            throw LayerNotFound::withLayerId(
                $layerModel->layerId(),
                $context->mapId(),
            );
        }

        $hydrator->hydrate($referenceModel, $layer, $context, $hydrator);
    }

    #[Override]
    protected function supportedType(): string
    {
        return 'reference';
    }
}
```

`src/Map/Layer/File/FileLayerHydrator.php`:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\File;

use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\FilesModel;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Expression\InlineExpression;
use Cowegis\Core\Definition\GeoData\ExternalData;
use Cowegis\Core\Definition\Layer\DataLayer;
use Cowegis\Core\Definition\Layer\Layer;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Override;

use function assert;

final class FileLayerHydrator extends LayerTypeHydrator
{
    public function __construct(private readonly RepositoryManager $repositoryManager, CacheTagManager $cacheTagManager)
    {
        parent::__construct($cacheTagManager);
    }

    #[Override]
    protected function supportedType(): string
    {
        return 'file';
    }

    #[Override]
    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator,
    ): void {
        assert($layer instanceof DataLayer);

        if ($layerModel->pointToLayer !== null) {
            $layer->options()->set(
                'pointToLayer',
                $context->callbacks()->add(new InlineExpression($layerModel->pointToLayer)),
            );
        }

        if ($layerModel->onEachFeature !== null) {
            $layer->options()->set(
                'onEachFeature',
                $context->callbacks()->add(new InlineExpression($layerModel->onEachFeature)),
            );
        }

        $repository = $this->repositoryManager->getRepository(FilesModel::class);
        $fileModel  = $repository->findOneBy(['.uuid=?'], [$layerModel->file]);

        if (! $fileModel instanceof FilesModel || $fileModel->type !== 'file') {
            return;
        }

        $layer->withData(new ExternalData($fileModel->path, $layerModel->fileFormat));
    }
}
```

Column check for the renamed single-line constructors against the 120-col limit: `GroupLayerHydrator` (114 cols), `ReferenceLayerHydrator` (116 cols) and `FileLayerHydrator` (120 cols) all fit exactly at or under the limit as written above — no wrapping needed. Step 7 below runs phpcs to confirm.

- [ ] **Step 4: Update the YAML wiring**

In `src/Resources/config/layers.yaml`, replace every `'@netzmacht.contao_toolkit.response_tagger'` argument (lines 38, 63, 98, 106, 122, 136, 150, 162 — `TileLayerHydrator`, `MarkersLayerHydrator`, `FileLayerHydrator`, `GroupLayerHydrator`, `MarkerClusterGroupHydrator`, `ReferenceLayerHydrator`, `OverpassLayerHydrator`, `VectorsLayerHydrator`) with `'@contao.cache.tag_manager'`. The argument position within each service stays the same — only the referenced service id changes.

In `src/Resources/config/services.yaml:41`, change:

```yaml
    Cowegis\Bundle\Contao\Hydrator\Hydrator:
        class: Cowegis\Bundle\Contao\Hydrator\DelegatingHydrator
        autoconfigure: false
        arguments:
            - !tagged_iterator 'Cowegis\Bundle\Contao\Hydrator\Hydrator'
            - '@netzmacht.contao_toolkit.response_tagger'
```

to:

```yaml
    Cowegis\Bundle\Contao\Hydrator\Hydrator:
        class: Cowegis\Bundle\Contao\Hydrator\DelegatingHydrator
        autoconfigure: false
        arguments:
            - !tagged_iterator 'Cowegis\Bundle\Contao\Hydrator\Hydrator'
            - '@contao.cache.tag_manager'
```

- [ ] **Step 5: Register the new stub service id**

In `tests/DependencyInjection/StubContainerFactory.php`, add `'contao.cache.tag_manager',` to the `STUB_SERVICE_IDS` array (keep `'netzmacht.contao_toolkit.response_tagger'` in the list — `fragments.yaml` still references it until Task 5):

```php
        'netzmacht.contao_toolkit.repository_manager',
        'netzmacht.contao_toolkit.response_tagger',
        'contao.cache.tag_manager',
        'netzmacht.contao_toolkit.csrf.token_provider',
```

- [ ] **Step 6: Run the container-compilation regression test**

Run: `vendor/bin/phpunit tests/DependencyInjection`
Expected: OK, all tests green (in particular `testContainerCompiles`, `testHydratorTagCollection`, `testMapActionArgumentCount` still pass unchanged).

- [ ] **Step 7: Run static analysis and coding-standard checks on the touched files**

Run: `vendor/bin/phpcq run psalm` and `vendor/bin/phpcq run phpcs`
Expected: no new errors on the files touched in this task (phpcs in particular confirms the alphabetical import order and the 120-col line limit on the rewritten constructors).

- [ ] **Step 8: Commit**

```bash
git add src/Hydrator/ResponseTaggerPlugin.php src/Hydrator/DelegatingHydrator.php \
  src/Map/Layer/Vector/VectorsLayerHydrator.php src/Map/Layer/Group/GroupLayerHydrator.php \
  src/Map/Layer/Markers/Hydrator/MarkersLayerHydrator.php src/Map/Layer/Reference/ReferenceLayerHydrator.php \
  src/Map/Layer/File/FileLayerHydrator.php src/Resources/config/layers.yaml src/Resources/config/services.yaml \
  tests/DependencyInjection/StubContainerFactory.php
git commit -m "refactor: migrate ResponseTagger usage to Contao's CacheTagManager"
```

---

### Task 3: `RequestScopeMatcher` → `Contao\CoreBundle\Routing\ScopeMatcher`

**Files:**
- Modify: `src/EventListener/BackendStyleListener.php`
- Modify: `src/Resources/config/listeners.yaml:18`
- Modify: `tests/DependencyInjection/StubContainerFactory.php`

**Interfaces:**
- Consumes: `Contao\CoreBundle\Routing\ScopeMatcher::isBackendRequest(?Symfony\Component\HttpFoundation\Request $request = null): bool` (from `contao/core-bundle`).

`fragments.yaml` is **not** touched — `MapFragmentAction` still injects the deprecated `RequestScopeMatcher` directly until Task 5.

- [ ] **Step 1: Update `BackendStyleListener`**

In `src/EventListener/BackendStyleListener.php`, change:

```php
use Netzmacht\Contao\Toolkit\Routing\RequestScopeMatcher;
use Netzmacht\Contao\Toolkit\View\Assets\AssetsManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(priority: -128)]
final class BackendStyleListener
{
    public function __construct(
        private readonly AssetsManager $assetsManager,
        private readonly RequestScopeMatcher $scopeMatcher,
    ) {
    }
```

to:

```php
use Contao\CoreBundle\Routing\ScopeMatcher;
use Netzmacht\Contao\Toolkit\View\Assets\AssetsManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(priority: -128)]
final class BackendStyleListener
{
    public function __construct(
        private readonly AssetsManager $assetsManager,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }
```

The `isBackendRequest($event->getRequest())` call body is unchanged.

- [ ] **Step 2: Update the YAML wiring**

In `src/Resources/config/listeners.yaml:18`, change:

```yaml
    Cowegis\Bundle\Contao\EventListener\BackendStyleListener:
        arguments:
            - '@netzmacht.contao_toolkit.assets_manager'
            - '@netzmacht.contao_toolkit.routing.scope_matcher'
```

to:

```yaml
    Cowegis\Bundle\Contao\EventListener\BackendStyleListener:
        arguments:
            - '@netzmacht.contao_toolkit.assets_manager'
            - '@contao.routing.scope_matcher'
```

- [ ] **Step 3: Register the new stub service id**

In `tests/DependencyInjection/StubContainerFactory.php`, add `'contao.routing.scope_matcher',` to `STUB_SERVICE_IDS` (keep `'netzmacht.contao_toolkit.routing.scope_matcher'` — `fragments.yaml` still references it until Task 5):

```php
        'netzmacht.contao_toolkit.assets_manager',
        'netzmacht.contao_toolkit.routing.scope_matcher',
        'contao.routing.scope_matcher',
        'netzmacht.contao_toolkit.contao.backend_adapter',
```

- [ ] **Step 4: Run the container-compilation regression test**

Run: `vendor/bin/phpunit tests/DependencyInjection`
Expected: OK, all tests green.

- [ ] **Step 5: Run static analysis and coding-standard checks**

Run: `vendor/bin/phpcq run psalm` and `vendor/bin/phpcq run phpcs`
Expected: no new errors.

- [ ] **Step 6: Commit**

```bash
git add src/EventListener/BackendStyleListener.php src/Resources/config/listeners.yaml \
  tests/DependencyInjection/StubContainerFactory.php
git commit -m "refactor: migrate BackendStyleListener to Contao's ScopeMatcher"
```

---

### Task 4: DCA color-picker wizard → native `colorpicker` eval

**Files:**
- Modify: `src/Resources/contao/dca/tl_cowegis_icon.php`
- Modify: `src/Resources/contao/dca/tl_cowegis_style.php`

**Interfaces:** None — DCA array structure only, no PHP class interface involved.

- [ ] **Step 1: Update `tl_cowegis_icon.php`**

Change the `iconColor` field:

```php
        'iconColor'         => [
            'exclude'   => true,
            'inputType' => 'text',
            'wizard'    => [
                ['netzmacht.contao_toolkit.dca.listeners.color_picker', 'onWizardCallback'],
            ],
            'eval'      => [
                'maxlength'      => 64,
                'tl_class'       => 'w50 wizard',
                'decodeEntities' => true,
                'nullIfEmpty'    => true,
            ],
            'sql'       => 'varchar(16) NULL',
        ],
```

to:

```php
        'iconColor'         => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'maxlength'      => 64,
                'tl_class'       => 'w50 wizard',
                'decodeEntities' => true,
                'nullIfEmpty'    => true,
                'colorpicker'    => true,
            ],
            'sql'       => 'varchar(16) NULL',
        ],
```

Change the `backgroundColor` field the same way:

```php
        'backgroundColor'   => [
            'exclude'   => true,
            'inputType' => 'text',
            'wizard'    => [
                ['netzmacht.contao_toolkit.dca.listeners.color_picker', 'onWizardCallback'],
            ],
            'eval'      => [
                'maxlength'      => 64,
                'tl_class'       => 'clr w50 wizard',
                'nullIfEmpty'    => true,
                'decodeEntities' => true,
            ],
            'sql'       => 'varchar(16) NULL',
        ],
```

to:

```php
        'backgroundColor'   => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'maxlength'      => 64,
                'tl_class'       => 'clr w50 wizard',
                'nullIfEmpty'    => true,
                'decodeEntities' => true,
                'colorpicker'    => true,
            ],
            'sql'       => 'varchar(16) NULL',
        ],
```

- [ ] **Step 2: Update `tl_cowegis_style.php`**

Change the `color` field:

```php
        'color'       => [
            'exclude'   => true,
            'inputType' => 'text',
            'wizard'    => [
                ['netzmacht.contao_toolkit.dca.listeners.color_picker', 'onWizardCallback'],
            ],
            'eval'      => [
                'tl_class'       => 'w50 wizard clr',
                'maxlength'      => 7,
                'decodeEntities' => true,
            ],
            'sql'       => "varchar(8) NOT NULL default ''",
        ],
```

to:

```php
        'color'       => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'tl_class'       => 'w50 wizard clr',
                'maxlength'      => 7,
                'decodeEntities' => true,
                'colorpicker'    => true,
            ],
            'sql'       => "varchar(8) NOT NULL default ''",
        ],
```

Change the `fillColor` field the same way:

```php
        'fillColor'   => [
            'exclude'   => true,
            'inputType' => 'text',
            'wizard'    => [
                ['netzmacht.contao_toolkit.dca.listeners.color_picker', 'onWizardCallback'],
            ],
            'eval'      => [
                'tl_class'       => 'clr w50 wizard',
                'maxlength'      => 7,
                'decodeEntities' => true,
            ],
            'sql'       => "varchar(8) NOT NULL default ''",
        ],
```

to:

```php
        'fillColor'   => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'tl_class'       => 'clr w50 wizard',
                'maxlength'      => 7,
                'decodeEntities' => true,
                'colorpicker'    => true,
            ],
            'sql'       => "varchar(8) NOT NULL default ''",
        ],
```

- [ ] **Step 3: Lint the changed DCA files**

Run: `vendor/bin/phpcq run phpcs`
Expected: no new errors on `tl_cowegis_icon.php` / `tl_cowegis_style.php`.

- [ ] **Step 4: Manual check (documented, not automatable here)**

Open the Contao backend, edit an icon and a style entry, confirm the color field still shows a working color-picker button next to the text input for `iconColor`, `backgroundColor`, `color`, and `fillColor`.

- [ ] **Step 5: Commit**

```bash
git add src/Resources/contao/dca/tl_cowegis_icon.php src/Resources/contao/dca/tl_cowegis_style.php
git commit -m "refactor: use native colorpicker eval instead of deprecated ColorPickerListener"
```

---

### Task 5: Split the Hybrid fragment controller into `MapContentElementAction`/`MapModuleAction`

**Files:**
- Create: `src/Action/MapFragmentDataTrait.php`
- Modify: `src/Action/MapContentElementAction.php`
- Modify: `src/Action/MapModuleAction.php`
- Delete: `src/Action/MapFragmentAction.php`
- Modify: `src/Resources/config/fragments.yaml`
- Modify: `tests/DependencyInjection/StubContainerFactory.php`
- Modify: `tests/DependencyInjection/CowegisContaoExtensionTest.php:47-59` (`testMapActionArgumentCount`)

**Interfaces:**
- Consumes: `Netzmacht\Contao\Toolkit\Controller\Fragment\AbstractContentElementController`,
  `Netzmacht\Contao\Toolkit\Controller\Fragment\AbstractFrontendModuleController`,
  `Netzmacht\Contao\Toolkit\Controller\Fragment\RenderBackendWildcardTrait` (all from
  `netzmacht/contao-toolkit` `^4.1@dev` (`dev-develop`), already installed after Task 1).
- Produces: `MapFragmentDataTrait` exposes `protected function buildTemplateData(array $data, Request $request, Model $model): array` and declares `abstract protected function getIdentifier(Model $model, string|null $identifier): string`, which `MapContentElementAction`/`MapModuleAction` must implement.

**Why in one task:** `MapContentElementAction`/`MapModuleAction` share the same trait and the same `fragments.yaml` file; splitting the controller rewrite from the config/test update would leave the container broken between commits.

- [ ] **Step 1: Create `MapFragmentDataTrait`**

Create `src/Action/MapFragmentDataTrait.php`:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\FilesModel;
use Contao\Input;
use Contao\Model;
use Contao\StringUtil;
use Cowegis\Core\Filter\Filter;
use Cowegis\Core\Filter\FilterFactory;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\InvalidParameterException;

use function array_merge;
use function assert;
use function is_string;

trait MapFragmentDataTrait
{
    public function __construct(
        private readonly FilterFactory $filterFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly RepositoryManager $repositoryManager,
    ) {
    }

    abstract protected function getIdentifier(Model $model, string|null $identifier): string;

    /**
     * @param array<string,mixed> $data
     *
     * @return array<string,mixed>
     */
    protected function buildTemplateData(array $data, Request $request, Model $model): array
    {
        $data['mapStyle'] = $this->compileMapStyle($model);
        $data['mapId']    = $this->getIdentifier($model, $this->getDefaultIdentifier($model));
        $data['clientJs'] = $this->getClientJs($model);

        try {
            $data['mapUri'] = $this->generateUrl(
                'cowegis_api_map',
                array_merge(
                    $this->createFilter($request)->toQuery()->toArray(),
                    ['mapId' => $model->cowegis_map, '_locale' => $GLOBALS['TL_LANGUAGE']],
                ),
            );
        } catch (InvalidParameterException) {
        }

        return $data;
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    protected function compileMapStyle(Model $model): string
    {
        $style  = '';
        $height = StringUtil::deserialize($model->cowegis_height, true);
        $width  = StringUtil::deserialize($model->cowegis_width, true);

        if (! empty($width['value'])) {
            $style .= 'width:' . $width['value'] . $width['unit'] . ';';
        }

        if (! empty($height['value'])) {
            $style .= 'height:' . $height['value'] . $height['unit'] . ';';
        }

        return $style;
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    private function getDefaultIdentifier(Model $model): string|null
    {
        if ($model->cowegis_mapId) {
            return (string) $model->cowegis_mapId;
        }

        $cssId = StringUtil::deserialize($model->cssID, true);
        if (! empty($cssId[0])) {
            return 'map_' . $cssId[0];
        }

        return null;
    }

    private function createFilter(Request $request): Filter
    {
        $uri      = $this->uriFactory->createUri($request->getUri());
        $autoItem = $this->getContaoAdapter(Input::class)->get('auto_item');

        if (is_string($autoItem) && $autoItem !== '') {
            $query = $uri->getQuery();
            $uri   = $uri->withQuery(($query ? '&' : '') . 'auto_item=' . $autoItem);
        }

        return $this->filterFactory->createFromUri($uri);
    }

    private function getClientJs(Model $model): string|null
    {
        switch ($model->cowegis_client) {
            case 'client':
                return 'bundles/cowegisclient/js/cowegis.js';

            case 'custom':
                $repository = $this->repositoryManager->getRepository(FilesModel::class);
                assert($repository instanceof ContaoRepository);
                /** @psalm-suppress UndefinedMagicMethod */
                $model = $repository->findOneByUuid($model->cowegis_client_custom);

                return $model ? $model->path : null;
        }

        return null;
    }
}
```

- [ ] **Step 2: Rewrite `MapContentElementAction`**

Replace the full content of `src/Action/MapContentElementAction.php` with:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Model;
use Netzmacht\Contao\Toolkit\Controller\Fragment\AbstractContentElementController;
use Netzmacht\Contao\Toolkit\Controller\Fragment\RenderBackendWildcardTrait;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement('cowegis_map', 'includes', 'ce_cowegis_map')]
final class MapContentElementAction extends AbstractContentElementController
{
    use MapFragmentDataTrait;
    use RenderBackendWildcardTrait;

    #[Override]
    protected function preGenerate(FragmentTemplate $template, ContentModel $model, Request $request): Response|null
    {
        if ($this->isBackendScope($request)) {
            return $this->renderBackendWildcard($model, $request);
        }

        return null;
    }

    #[Override]
    protected function prepareTemplateData(array $data, Request $request, ContentModel $model): array
    {
        return $this->buildTemplateData($data, $request, $model);
    }

    #[Override]
    protected function getIdentifier(Model $model, string|null $identifier): string
    {
        if ($identifier === null) {
            return 'map_ce_' . $model->id;
        }

        return $identifier;
    }
}
```

- [ ] **Step 3: Rewrite `MapModuleAction`**

Replace the full content of `src/Action/MapModuleAction.php` with:

```php
<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\Model;
use Contao\ModuleModel;
use Netzmacht\Contao\Toolkit\Controller\Fragment\AbstractFrontendModuleController;
use Override;
use Symfony\Component\HttpFoundation\Request;

#[AsFrontendModule('cowegis_map', 'includes', 'ce_cowegis_map')]
final class MapModuleAction extends AbstractFrontendModuleController
{
    use MapFragmentDataTrait;

    #[Override]
    protected function prepareTemplateData(array $data, Request $request, ModuleModel $model): array
    {
        return $this->buildTemplateData($data, $request, $model);
    }

    #[Override]
    protected function getIdentifier(Model $model, string|null $identifier): string
    {
        if ($identifier === null) {
            return 'map_mod_' . $model->id;
        }

        return $identifier;
    }
}
```

- [ ] **Step 4: Delete the old `MapFragmentAction`**

```bash
git rm src/Action/MapFragmentAction.php
```

- [ ] **Step 5: Rewrite `fragments.yaml`**

Replace the full content of `src/Resources/config/fragments.yaml` with:

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

    Cowegis\Bundle\Contao\Action\MapModuleAction:
        arguments:
            - '@Cowegis\Core\Filter\FilterFactory'
            - '@psr18.http_client'
            - '@netzmacht.contao_toolkit.repository_manager'
```

- [ ] **Step 6: Drop the now-unused stub service ids**

In `tests/DependencyInjection/StubContainerFactory.php`, remove `'netzmacht.contao_toolkit.response_tagger'`, `'netzmacht.contao_toolkit.routing.scope_matcher'`, and `'netzmacht.contao_toolkit.template_renderer'` from `STUB_SERVICE_IDS` — nothing references them anymore after this task. `'netzmacht.contao_toolkit.contao.input_adapter'` stays (still used by `MapLayerBreadcrumbListener` in `listeners.yaml:13`). The final array must read:

```php
    public const STUB_SERVICE_IDS = [
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
        'netzmacht.contao_toolkit.csrf.token_provider',
        'netzmacht.contao_toolkit.dca.manager',
        'netzmacht.contao_toolkit.assets_manager',
        'contao.routing.scope_matcher',
        'netzmacht.contao_toolkit.contao.backend_adapter',
        'netzmacht.contao_toolkit.contao.system_adapter',
        'netzmacht.contao_toolkit.contao.input_adapter',
        'netzmacht.contao_toolkit.callback_invoker',
        'contao.cache.tag_manager',
        'Cowegis\Core\Filter\FilterFactory',
        'Cowegis\Core\Serializer\Serializer',
    ];
```

- [ ] **Step 7: Update the argument-count regression test**

In `tests/DependencyInjection/CowegisContaoExtensionTest.php`, change `testMapActionArgumentCount()` from:

```php
    public function testMapActionArgumentCount(): void
    {
        $container = self::compiledContainer();

        self::assertCount(
            10,
            $container->getDefinition('Cowegis\Bundle\Contao\Action\MapContentElementAction')->getArguments(),
        );
        self::assertCount(
            10,
            $container->getDefinition('Cowegis\Bundle\Contao\Action\MapModuleAction')->getArguments(),
        );
    }
```

to:

```php
    public function testMapActionArgumentCount(): void
    {
        $container = self::compiledContainer();

        self::assertCount(
            3,
            $container->getDefinition('Cowegis\Bundle\Contao\Action\MapContentElementAction')->getArguments(),
        );
        self::assertCount(
            3,
            $container->getDefinition('Cowegis\Bundle\Contao\Action\MapModuleAction')->getArguments(),
        );
    }
```

- [ ] **Step 8: Run the container-compilation regression test**

Run: `vendor/bin/phpunit tests/DependencyInjection`
Expected: OK, all tests green, including the updated `testMapActionArgumentCount`.

- [ ] **Step 9: Run static analysis and coding-standard checks on the rewritten files**

Run: `vendor/bin/phpcq run psalm` and `vendor/bin/phpcq run phpcs`
Expected: no new errors. Pay particular attention to `MapFragmentDataTrait::buildTemplateData()` and `createFilter()` — both call inherited `AbstractController` methods (`generateUrl()`, `getContaoAdapter()`) that only exist once the trait is used inside a class extending Symfony's `AbstractController`; if psalm cannot resolve them because the trait is analyzed standalone, add a targeted `@psalm-suppress UndefinedMethod` with a one-line comment explaining why (mirrors the existing local-suppression style described in `CLAUDE.md`).

- [ ] **Step 10: Manual verification**

Start the Contao installation this bundle is developed against (or a local dev instance), then:

1. Open a page containing a `cowegis_map` content element in the frontend — confirm the map still renders with the same `mapId`/`mapUri`/`clientJs` data as before.
2. Open the same page in the Contao backend article view — confirm a "###COWEGIS_MAP###" wildcard placeholder is shown instead of a live map (same as before this change).
3. Open a page using the `cowegis_map` frontend module — confirm it renders in the frontend, and confirm the backend wildcard placeholder appears automatically in the module's backend list/editor.

- [ ] **Step 11: Commit**

```bash
git add src/Action/MapFragmentDataTrait.php src/Action/MapContentElementAction.php \
  src/Action/MapModuleAction.php src/Resources/config/fragments.yaml \
  tests/DependencyInjection/StubContainerFactory.php tests/DependencyInjection/CowegisContaoExtensionTest.php
git commit -m "refactor: split MapFragmentAction into independent content-element/module controllers

Netzmacht\\Contao\\Toolkit\\Controller\\Hybrid\\AbstractHybridController has no
successor in contao-toolkit 4.1 — split into MapContentElementAction and
MapModuleAction extending the new Controller\\Fragment base classes,
sharing the former's logic via MapFragmentDataTrait."
```

---

### Task 6: Full verification

**Files:** None — verification only.

**Interfaces:** None.

- [ ] **Step 1: Run the full phpcq chain**

Run: `vendor/bin/phpcq run`
Expected: the full `verify` + `analyze` chain (psalm, phpcs, rector, phpmd, phpcpd, phploc, composer-require-checker, composer-normalize, phpspec, phpunit) passes with no new findings.

- [ ] **Step 2: Run phpspec explicitly**

Run: `vendor/bin/phpspec run`
Expected: OK, no failures (no specs exist for the touched classes, this confirms nothing else broke).

- [ ] **Step 3: Run the full PHPUnit suite**

Run: `vendor/bin/phpunit`
Expected: OK, no failures — includes `tests/DependencyInjection`, `tests/ContaoManager`, `tests/EventListener`, `tests/Schema`.

- [ ] **Step 4: Confirm no leftover references to removed/deprecated APIs**

Run: `grep -rn "Netzmacht\\\\Contao\\\\Toolkit\\\\Response\\\\ResponseTagger\|Netzmacht\\\\Contao\\\\Toolkit\\\\Routing\\\\RequestScopeMatcher\|Netzmacht\\\\Contao\\\\Toolkit\\\\Controller\\\\Hybrid\|dca.listeners.color_picker" src/ tests/`
Expected: no matches.

- [ ] **Step 5: Final commit (only if any of the above steps required fixes)**

```bash
git add -A
git commit -m "chore: fix findings from full phpcq/phpspec/phpunit verification run"
```
