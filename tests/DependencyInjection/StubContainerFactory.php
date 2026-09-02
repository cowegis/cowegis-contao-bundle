<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Test\DependencyInjection;

use Cowegis\Bundle\Contao\DependencyInjection\CowegisContaoExtension;
use stdClass;
use Symfony\Component\DependencyInjection\Compiler\CheckExceptionOnInvalidReferenceBehaviorPass;
use Symfony\Component\DependencyInjection\Compiler\DefinitionErrorExceptionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class StubContainerFactory
{
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ClassConstantTypeHint.MissingNativeTypeHint
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
            $definition = new Definition(stdClass::class);
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

        // Keep the two integrity passes so a dangling required `@service` reference (the most likely
        // XML->YAML conversion defect) still fails compilation; drop every other removing pass so no
        // definition/alias/tag is pruned or inlined and the whole graph stays inspectable.
        // `@?service` (ignore-on-invalid) references still resolve to null as intended.
        $passConfig->setRemovingPasses([
            new CheckExceptionOnInvalidReferenceBehaviorPass(),
            new DefinitionErrorExceptionPass(),
        ]);
        $passConfig->setAfterRemovingPasses([]);

        $container->compile();
    }
}
