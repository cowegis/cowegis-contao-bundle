<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener;

use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Input;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

use function array_key_exists;
use function explode;
use function http_build_query;
use function is_string;
use function parse_str;
use function parse_url;

use const PHP_URL_QUERY;

/**
 * The tl_cowegis_map_layer junction records are only ever edited through the custom "layers of a
 * map" view (do=cowegis_map&table=tl_cowegis_layer&id=<mapId>) rendered by
 * {@see \Cowegis\Bundle\Contao\EventListener\Dca\MapLayerSelectionDcaListener}, never through a
 * regular tl_cowegis_map_layer parent list.
 *
 * Contao's DcaUrlAnalyzer does not know this and builds the parent entry of the back end breadcrumb
 * as do=cowegis_map&id=<mapId>&table=tl_cowegis_map_layer, which points at a view that does not
 * exist. Rewrite that entry back to the custom layer view.
 */
#[AsEventListener(event: 'contao.backend_menu_build', method: 'onBuild', priority: -255)]
final class MapLayerBreadcrumbListener
{
    /** @param Adapter<Input> $inputAdapter */
    public function __construct(private readonly Adapter $inputAdapter)
    {
    }

    public function onBuild(MenuEvent $event): void
    {
        $tree = $event->getTree();

        if ($tree->getName() !== 'breadcrumbMenu') {
            return;
        }

        if ($this->inputAdapter->get('do') !== 'cowegis_map') {
            return;
        }

        if ($this->inputAdapter->get('table') !== 'tl_cowegis_map_layer') {
            return;
        }

        $this->rewrite($tree);
    }

    private function rewrite(ItemInterface $item): void
    {
        foreach ($item->getChildren() as $child) {
            $uri = $child->getUri();

            if (is_string($uri)) {
                $rewritten = $this->rewriteUri($uri);

                if ($rewritten !== null) {
                    $child->setUri($rewritten);
                }
            }

            $this->rewrite($child);
        }
    }

    private function rewriteUri(string $uri): string|null
    {
        $query = parse_url($uri, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return null;
        }

        parse_str($query, $params);

        // Only the parent list link is wrong: it targets tl_cowegis_map_layer without an action.
        // The current record link carries act=edit and must stay untouched.
        if (($params['table'] ?? null) !== 'tl_cowegis_map_layer' || array_key_exists('act', $params)) {
            return null;
        }

        $params['table'] = 'tl_cowegis_layer';

        [$path] = explode('?', $uri, 2);

        return $path . '?' . http_build_query($params);
    }
}
