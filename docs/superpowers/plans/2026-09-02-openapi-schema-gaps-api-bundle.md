# cowegis-api-bundle – OpenAPI schema gaps Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Pick up the new `cowegis-core` schema building blocks, stop emitting `info.version: "latest"`, and give the schema endpoint its first test coverage.

**Architecture:** The api-bundle owns `SchemaAction` and the `schema.yaml` wiring only — concrete describers live in core. So this plan is: bump the core constraint, tag core's new `ErrorSchemaDescriber` into the delegating chain, resolve the `latest` version sentinel to the installed package version inside `SchemaAction`, and add a phpspec suite.

**Tech Stack:** PHP `^8.2`, Symfony `^6.4 || ^7`, `cowegis/cowegis-core`, phpspec, phpcq QA chain.

**Spec:** `2026-09-02-openapi-schema-gaps-findings.md` (same directory) — this plan covers **G8**; it also unblocks G1/G6 by wiring core's `ErrorSchemaDescriber`.

## Global Constraints

- `declare(strict_types=1);`, `#[Override]`, `final` — as in the existing codebase.
- Doctrine Coding Standard, 120 cols. Psalm green.
- **Depends on** the `cowegis-core` plan being merged/tagged first (or a path/`dev-*` constraint pointing at it). This plan bumps `cowegis/cowegis-core` to the version that ships `Cowegis\Core\Schema\Error\ErrorSchemaDescriber`.
- Do not change route paths or the `cowegis_api.*` parameter contract.
- Full check before "done": `vendor/bin/phpspec run` and `vendor/bin/phpcq run`.

---

## File Structure

| File | Responsibility |
|---|---|
| `composer.json` (modify) | Bump `cowegis/cowegis-core`; add `composer-runtime-api`; fix `autoload-dev` spec path. |
| `src/Resources/config/schema.yaml` (modify) | Tag `Cowegis\Core\Schema\Error\ErrorSchemaDescriber` as a `SchemaDescriber`. |
| `src/Action/SchemaAction.php` (modify) | Resolve `apiVersion === 'latest'` to the installed package version. |
| `src/DependencyInjection/Configuration.php` (modify) | Reword the `version` node `info()` text to document the `latest` behaviour. |
| `spec/Action/SchemaActionSpec.php` (new) | First spec: valid document, resolved `info.version`, error component present. |

---

## Task 1: Bump the core dependency and wire `ErrorSchemaDescriber`

**Files:**
- Modify: `composer.json`
- Modify: `src/Resources/config/schema.yaml`
- Test: covered by Task 3's spec (`components.responses`/`schemas.Error` presence)

**Interfaces:**
- Consumes: `Cowegis\Core\Schema\Error\ErrorSchemaDescriber` (core plan Task 1), `Cowegis\Core\Schema\SchemaDescriber` tag already consumed by the `DelegatingSchemaDescriber` `!tagged_iterator` in `schema.yaml`.
- Produces: the generated document has `components.schemas.Error`.

- [ ] **Step 1: Bump `cowegis/cowegis-core` in `composer.json`**

Set the constraint to the release that contains `Cowegis\Core\Schema\Error\ErrorSchemaDescriber` (e.g. `"cowegis/cowegis-core": "^<next-minor>"`). While iterating locally before that tag exists, use a path repository or `"dev-<branch>"`.

- [ ] **Step 2: Run `composer update cowegis/cowegis-core`**

Run: `composer update cowegis/cowegis-core --with-dependencies`
Expected: lock updated, `Cowegis\Core\Schema\Error\ErrorSchemaDescriber` autoloadable:
`php -r "var_dump(class_exists('Cowegis\\Core\\Schema\\Error\\ErrorSchemaDescriber'));"` → `bool(true)`.

- [ ] **Step 3: Tag the describer in `schema.yaml`**

Add under `services:` (the file's `_defaults` already set `autoconfigure: true`, but core `SchemaDescriber` is not an autoconfigured interface here — tag explicitly, like the sibling entries):

```yaml
  Cowegis\Core\Schema\Error\ErrorSchemaDescriber:
    tags:
      - { name: Cowegis\Core\Schema\SchemaDescriber }
```

- [ ] **Step 4: Verify the container compiles and the schema builds**

Run: `php -r "require 'vendor/autoload.php';"` then, if the repo has a container smoke test, run it; otherwise defer verification to Task 3.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock src/Resources/config/schema.yaml
git commit -m "feat(schema): wire core ErrorSchemaDescriber into the delegating chain"
```

---

## Task 2: Resolve the `latest` version sentinel

**Files:**
- Modify: `src/Action/SchemaAction.php`
- Modify: `composer.json` (add `"composer-runtime-api": "^2.0"` to `require`)
- Modify: `src/DependencyInjection/Configuration.php` (doc text only)
- Test: Task 3's spec

**Interfaces:**
- Produces: `SchemaAction` emits `info.version` = the pretty installed version of `cowegis/cowegis-api-bundle` when the configured `cowegis_api.api_version` is the literal `'latest'`; otherwise the configured string is used unchanged. Never emits `"latest"`.

- [ ] **Step 1: Add `composer-runtime-api` to `require`**

```json
"require": {
    "...": "...",
    "composer-runtime-api": "^2.0"
}
```

Run: `composer update --lock`

- [ ] **Step 2: Add a private resolver in `SchemaAction`**

Add the import `use Composer\InstalledVersions;` and:

```php
    private function resolveVersion(): string
    {
        if ($this->apiVersion !== 'latest') {
            return $this->apiVersion;
        }

        $version = InstalledVersions::getPrettyVersion('cowegis/cowegis-api-bundle');

        return $version ?? 'dev';
    }
```

- [ ] **Step 3: Use it in `__invoke()`**

Change:

```php
        $info = Info::create()
            ->title('Cowegis API')
            ->description('Cowegis map API')
            ->version($this->apiVersion);
```

to:

```php
        $info = Info::create()
            ->title('Cowegis API')
            ->description('Cowegis map API')
            ->version($this->resolveVersion());
```

- [ ] **Step 4: Update the config doc text**

In `Configuration.php`, change the `->info(...)` on the `version` node to:

```php
                            ->info('API version reported in the generated OpenAPI document. The default'
                                . ' "latest" is resolved at runtime to the installed cowegis/cowegis-api-bundle'
                                . ' version. Set a fixed string to pin it.')
```

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock src/Action/SchemaAction.php src/DependencyInjection/Configuration.php
git commit -m "fix(schema): resolve info.version 'latest' to the installed package version"
```

---

## Task 3: First phpspec coverage for `SchemaAction`

**Files:**
- Modify: `composer.json` (`autoload-dev`)
- Create: `spec/Action/SchemaActionSpec.php`

**Interfaces:**
- Consumes: `SchemaAction::__construct(SchemaDescriber $schemaBuilder, iterable $idSchemas, string $baseUri, string $apiVersion)` and `__invoke(Request): JsonResponse`.

- [ ] **Step 1: Fix the spec autoload path**

`autoload-dev.psr-4` currently maps `"spec\\Cowegis\\Bundle\\Api\\": "src/"` — point it at the spec directory:

```json
"autoload-dev": {
    "psr-4": {
        "spec\\Cowegis\\Bundle\\Api\\": "spec/"
    }
}
```

Run: `composer dump-autoload`

- [ ] **Step 2: Write the failing spec**

```php
<?php

declare(strict_types=1);

namespace spec\Cowegis\Bundle\Api\Action;

use Cowegis\Bundle\Api\Action\SchemaAction;
use Cowegis\Core\Schema\Id\IntegerIdSchema;
use Cowegis\Core\Schema\SchemaBuilder;
use Cowegis\Core\Schema\SchemaDescriber;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpFoundation\Request;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class SchemaActionSpec extends ObjectBehavior
{
    public function let(SchemaDescriber $describer): void
    {
        $describer->describe(new \Prophecy\Argument\Token\TypeToken(SchemaBuilder::class))->willReturn(null);

        $this->beConstructedWith($describer, [new IntegerIdSchema()], 'cowegis/api', '1.2.3');
    }

    public function it_emits_the_configured_version_verbatim(): void
    {
        $response = $this->__invoke(Request::create('https://example.com/cowegis/docs/schema.json'));

        $doc = json_decode((string) $response->getWrappedObject()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        expect($doc['info']['version'])->toBe('1.2.3');
        expect($doc)->shouldHaveKey('paths');
        expect($doc)->shouldHaveKey('components');
        expect($doc['openapi'])->toBe('3.0.2');
    }

    public function it_never_emits_the_latest_sentinel(SchemaDescriber $describer): void
    {
        $this->beConstructedWith($describer, [new IntegerIdSchema()], 'cowegis/api', 'latest');

        $response = $this->__invoke(Request::create('https://example.com/cowegis/docs/schema.json'));
        $doc      = json_decode((string) $response->getWrappedObject()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        expect($doc['info']['version'])->notToBe('latest');
    }
}
```

> If `expect()` helper is unavailable, add `friends-of-phpspec/phpspec-expect` (already
> a dev dep of `cowegis-core`) to `require-dev`, or rewrite the assertions with
> `$response->shouldReturnAnInstanceOf(...)` style matchers.

- [ ] **Step 3: Run it, verify it fails, then passes**

Run: `vendor/bin/phpspec run spec/Action/SchemaActionSpec.php`
Expected before Task 2: FAIL on the `latest` example. After Tasks 1–2: PASS.

- [ ] **Step 4: Commit**

```bash
git add composer.json spec/Action/SchemaActionSpec.php
git commit -m "test: cover SchemaAction version + document shape"
```

---

## Task 4: QA + README note

**Files:**
- Modify: `README.md` (short note), `CHANGELOG.md` (if kept)

- [ ] **Step 1: Full suites**

Run: `vendor/bin/phpspec run`
Run: `vendor/bin/phpcq run`
Expected: green. Fix psalm/phpcs findings.

- [ ] **Step 2: README note**

Add a short paragraph under the docs section:

```markdown
The `GET {prefix}/docs/schema.json` document is generated dynamically from every
installed Cowegis bundle. Responses are envelopes: `GET .../map/{mapId}` returns
`{ "map": <MapSchema>, "assets": [<Asset>] }` and the layer-data endpoints return
`{ "data": <…>, "assets": [<Asset>] }`. `info.version` defaults to the installed
`cowegis/cowegis-api-bundle` version; pin it with `cowegis.api.version`.
```

- [ ] **Step 3: Commit**

```bash
git add README.md CHANGELOG.md
git commit -m "docs: describe the schema envelope and version resolution"
```

---

## Self-Review checklist

- [ ] G8 fixed in Task 2; verified by `SchemaActionSpec::it_never_emits_the_latest_sentinel`.
- [ ] `ErrorSchemaDescriber` FQCN in `schema.yaml` matches core plan Task 1 exactly (`Cowegis\Core\Schema\Error\ErrorSchemaDescriber`).
- [ ] `composer-runtime-api` added — `Composer\InstalledVersions` is safe to reference.
- [ ] No route/parameter contract changes.
