# contao-toolkit 4.1 Migration — Design

Date: 2026-09-21
Status: Approved (pending spec review)

## Context

`netzmacht/contao-toolkit` 4.1.0 raises the internal `contao/core-bundle` floor to `^5.7`
(dropping Contao 4.13 support) and deprecates a cluster of APIs as part of Contao 6 preparation
(see `UPGRADE-5.0.md` in the toolkit repo). None of the deprecations break anything yet in
4.1.x, but 5.0.0 removes them outright, so this bundle should migrate now rather than freeze on
4.0.

This document covers everything that needs to change in `cowegis/cowegis-contao-bundle` to
adopt 4.1.0 and be clean of deprecation warnings, based on a full grep of the codebase against
the toolkit's `CHANGELOG.md` / `UPGRADE-5.0.md`.

## Goals

- Update `composer.json` version constraints for the new floors.
- Replace every deprecated toolkit API this bundle actually uses with its 5.0-ready
  replacement, so the bundle is not carrying dead-man-walking dependencies going into 5.0.0.
- No behavioural change for site visitors or backend editors (same rendering, same caching,
  same backend UX).

## Non-goals

- Migrating the legacy `.html5` fragment templates (`ce_cowegis_map.html5`,
  `mod_cowegis_map.html5`) to native Twig. Contao Core's `AbstractFragmentController::createTemplate()`
  keeps rendering un-namespaced (no `/`) template names through the legacy `FrontendTemplate`
  engine indefinitely — the new toolkit Fragment-Controller base classes do not force a Twig
  migration. Out of scope for this change.
- Any of the toolkit deprecations this bundle does not use (see "Not affected" below) — no
  code touches them, so there is nothing to migrate.

## Affected areas

### 1. Version constraints (`composer.json`)

- `netzmacht/contao-toolkit`: `^4.0` → `^4.1@dev` — 4.1.0 is not tagged on Packagist yet (only
  `4.0.11` is the latest stable tag; `4.1.0`'s changes live on the `develop` branch, published as
  `dev-develop` with a `4.1.x-dev` branch alias). Verified via
  `composer require "netzmacht/contao-toolkit:^4.1@dev" --dry-run`, which resolves to
  `dev-develop`. A plain `^4.1` would fail to resolve.
- `contao/core-bundle`: `^5.3` → `^5.7` (toolkit 4.1.0's own floor; also drops CLAUDE.md's
  documented Contao `^5.3` support down to `^5.7+`). `5.7.13` is a real stable tag, no `@dev`
  needed here.

### 2. `ResponseTagger` → `Contao\CoreBundle\Cache\CacheTagManager`

`Cowegis\Bundle\Contao\Model\Model extends Contao\Model`, so every case this bundle tags
(`CowegisModel` and plain `Contao\Model` instances alike) can use
`CacheTagManager::tagWithModelInstance(Model $instance): void` uniformly — no more branching
between a `CowegisModel` and a `ContaoModel` code path.

Files:
- `src/Hydrator/ResponseTaggerPlugin.php` — trait constructor takes `CacheTagManager`, its
  `tagResponseForData()` collapses to a single `instanceof Contao\Model` check calling
  `tagWithModelInstance()`.
- `src/Hydrator/DelegatingHydrator.php` — constructor type change only (uses the trait).
- `src/Map/Layer/Vector/VectorsLayerHydrator.php`, `Group/GroupLayerHydrator.php`,
  `Markers/Hydrator/MarkersLayerHydrator.php`, `Reference/ReferenceLayerHydrator.php`,
  `File/FileLayerHydrator.php`, `LayerTypeHydrator.php` — constructor type change only.
- `src/Resources/config/fragments.yaml`, `layers.yaml`, `services.yaml` — replace
  `'@netzmacht.contao_toolkit.response_tagger'` references with `'@contao.cache.tag_manager'`
  (dropped entirely from `fragments.yaml`, see section 5 — the fragment controllers no longer
  need it directly).

### 3. `RequestScopeMatcher` → `Contao\CoreBundle\Routing\ScopeMatcher`

Files:
- `src/EventListener/BackendStyleListener.php` — constructor type change
  `RequestScopeMatcher` → `ScopeMatcher`; `isBackendRequest($event->getRequest())` call is
  unchanged (the `$request` argument is still accepted, just no longer required).
- `src/Resources/config/listeners.yaml` — `'@netzmacht.contao_toolkit.routing.scope_matcher'`
  → `'@contao.routing.scope_matcher'`.
- Dropped entirely from `src/Action/MapFragmentAction.php`'s successors (section 5) — the new
  Fragment-Controller base classes provide `isBackendScope()` already wired to
  `contao.routing.scope_matcher` internally.

### 4. DCA color-picker wizard → native `colorpicker` eval

`Dca\Listener\Wizard\ColorPickerListener` (service
`netzmacht.contao_toolkit.dca.listeners.color_picker`) is deprecated in favour of Contao Core's
native `eval => ['colorpicker' => true]`.

Files:
- `src/Resources/contao/dca/tl_cowegis_icon.php` — fields `iconColor` (line ~318),
  `backgroundColor` (line ~332): remove the `wizard` key, add `'colorpicker' => true` to `eval`.
- `src/Resources/contao/dca/tl_cowegis_style.php` — fields `color` (line ~144),
  `fillColor` (line ~177): same change.

### 5. Fragment-Controller split (the structural change)

`Netzmacht\Contao\Toolkit\Controller\Hybrid\AbstractHybridController` (used by
`src/Action/MapFragmentAction.php`) is removed in 5.0.0 with **no Hybrid successor** — the
toolkit's new `Controller\Fragment\AbstractContentElementController` and
`AbstractFrontendModuleController` are independent siblings, both built directly on
`Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController` /
`FrontendModule\AbstractFrontendModuleController`.

Those Contao Core base classes already provide, inherited, what `MapFragmentAction` currently
wires by hand:

| Today (constructor-injected) | Replacement |
|---|---|
| `Netzmacht\...\Response\ResponseTagger` | Automatic: Core's `__invoke()` calls `$this->tagResponse($model)` for the top-level model on every request. |
| `Netzmacht\...\Routing\RequestScopeMatcher` | Inherited `$this->isBackendScope($request)`. |
| `Netzmacht\...\View\Template\TemplateRenderer` | Inherited `createTemplate()`/`getResponse()` pipeline (still renders the existing `.html5` templates, see Non-goals). |
| `Contao\CoreBundle\Framework\Adapter $inputAdapter` | Inherited `$this->getContaoAdapter(Contao\Input::class)`. |
| `Symfony\...\RouterInterface $router` | Inherited `$this->generateUrl(...)` (same exception behaviour for the existing `try { } catch (InvalidParameterException) { }` around the `mapUri` generation). |
| `Symfony\...\TranslatorInterface $translator` | Dropped — not actually used by `MapFragmentAction`'s own logic (only ever passed through to the deprecated parent constructor). |
| `Contao\CoreBundle\...\TokenChecker $tokenChecker` | Dropped from our constructor — the toolkit's `IsHiddenTrait` (already used inside the new `AbstractContentElementController`) fetches it itself via `getSubscribedServices()`. |

Remaining genuine constructor dependencies: `Cowegis\Core\Filter\FilterFactory`,
`Psr\Http\Message\UriFactoryInterface` (wired to `@psr18.http_client`), and
`Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager`.

**Sharing strategy:** since `AbstractContentElementController` and
`AbstractFrontendModuleController` share no common ancestor besides Contao Core's fragment
plumbing, the logic currently living in `MapFragmentAction` moves into a new
`src/Action/MapFragmentDataTrait.php`, used by both concrete controllers:

- Constructor: the three dependencies above.
- `compileMapStyle(Model $model): string` — unchanged.
- `getDefaultIdentifier(Model $model): string|null` — unchanged.
- `getClientJs(Model $model): string|null` — unchanged.
- `createFilter(Request $request): Filter` — unchanged except using
  `$this->getContaoAdapter(Input::class)` instead of an injected `Adapter`.
- `buildTemplateData(array $data, Request $request, Model $model): array` — the current body of
  `MapFragmentAction::prepareTemplateData()`, using `$this->generateUrl(...)` for `mapUri` and
  `$this->getIdentifier(...)` (abstract, implemented per concrete controller as today).
- `getIdentifier(Model $model, string|null $identifier): string` stays an abstract method
  declared by the trait, implemented individually by each concrete controller exactly as today
  (`map_ce_*` / `map_mod_*` prefixes).

Concrete controllers:

- `src/Action/MapContentElementAction.php`: `extends Fragment\AbstractContentElementController`,
  `use MapFragmentDataTrait, Fragment\RenderBackendWildcardTrait;`. No `__invoke()` needed
  anymore (inherited from Contao Core). Implements:
  - `prepareTemplateData(array $data, Request $request, ContentModel $model): array` →
    `return $this->buildTemplateData($data, $request, $model);`
  - `preGenerate(FragmentTemplate $template, ContentModel $model, Request $request): Response|null`
    → renders `renderBackendWildcard($model, $request)` when `isBackendScope($request)`,
    replacing today's `renderContentBackendView()` call. This preserves the current backend
    editor behaviour (a "###COWEGIS_MAP###" wildcard placeholder instead of a live map).
  - `getIdentifier()` — unchanged body (`map_ce_` prefix).
- `src/Action/MapModuleAction.php`: `extends Fragment\AbstractFrontendModuleController`,
  `use MapFragmentDataTrait;`. No `__invoke()`, no backend-wildcard code (Contao Core's
  `AbstractFrontendModuleController::__invoke()` already renders the wildcard automatically
  before `getResponse()` is reached). Implements `prepareTemplateData()` (same delegation) and
  `getIdentifier()` (unchanged, `map_mod_` prefix).
- `src/Action/MapFragmentAction.php` is deleted.

`src/Resources/config/fragments.yaml`: both services' argument lists shrink from 10 entries to
3 (`FilterFactory`, `psr18.http_client`, `repository_manager`).

## Not affected (verified via grep, no matches)

- `InsertTag\*` toolkit classes — the only insert-tag usage in the bundle
  (`Map/Layer/Markers/Hydrator/MarkerHydrator.php`) already uses Contao Core's own
  `Contao\CoreBundle\InsertTag\InsertTagParser`, not the toolkit's.
- `StateButtonCallbackListener`, `FilePickerListener`, `PagePickerListener`,
  `AbstractPickerListener`, `PopupWizardListener`.
- `GenerateAliasListener`, `FilterBasedAliasGenerator`, `AliasGeneratorFactory`,
  `ToolkitAliasGeneratorFactory`.
- `ContaoServicesFactory::createBackendUserInstance()`/`createFrontendUserInstance()` and the
  `netzmacht.contao_toolkit.contao.backend_user`/`...frontend_user` services.
- Legacy `View\Template` classes (`FrontendTemplate`, `BackendTemplate`, `TemplateTrait`,
  `TemplateFactory`, `ToolkitTemplateFactory`, `GetTemplateHelpersEvent`/`Listener`,
  `HelperNotFound`) — the `Contao\BackendTemplate` usages in `MapLayerSelectionDcaListener` and
  `MapLayerDcaListener` are Contao Core's own class, unrelated to the toolkit.

## Testing

- No existing phpspec/phpunit coverage for `MapFragmentAction`/`MapContentElementAction`/
  `MapModuleAction` (verified: no matching spec files). The refactor is otherwise covered by
  running the full phpcq chain (`vendor/bin/phpcq run`) plus existing phpspec/phpunit suites for
  the hydrators and DCA listeners whose constructors change type.
- Manual verification after the change: load a page with a `cowegis_map` content element and one
  with the frontend module, in both frontend and backend (article) view, to confirm the map
  still renders frontend-side and the wildcard placeholder still appears in the backend editor
  for the content element.
