<?php

declare(strict_types=1);

namespace spec\Cowegis\Bundle\Contao\EventListener;

use Contao\CoreBundle\Event\MenuEvent;
use Cowegis\Bundle\Contao\EventListener\MapLayerBreadcrumbListener;
use Knp\Menu\ItemInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use spec\Cowegis\Bundle\Contao\EventListener\Fixture\InputAdapterStub;

final class MapLayerBreadcrumbListenerSpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beConstructedWith(new InputAdapterStub());
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(MapLayerBreadcrumbListener::class);
    }

    public function it_rewrites_the_map_parent_link_to_the_custom_layer_view(
        MenuEvent $event,
        ItemInterface $tree,
        ItemInterface $moduleItem,
        ItemInterface $parentItem,
        ItemInterface $currentItem,
    ): void {
        $this->beConstructedWith(new InputAdapterStub(['do' => 'cowegis_map', 'table' => 'tl_cowegis_map_layer']));

        $event->getTree()->willReturn($tree);
        $tree->getName()->willReturn('breadcrumbMenu');
        $tree->getChildren()->willReturn([
            'current_0' => $moduleItem,
            'current_1' => $parentItem,
            'current_2' => $currentItem,
        ]);

        $moduleItem->getUri()->willReturn('/contao?do=cowegis_map&table=tl_cowegis_map');
        $moduleItem->getChildren()->willReturn([]);

        $parentItem->getUri()->willReturn('/contao?do=cowegis_map&id=2&table=tl_cowegis_map_layer');
        $parentItem->getChildren()->willReturn([]);
        $parentItem->setUri(Argument::any())->willReturn($parentItem);

        $currentItem->getUri()->willReturn('/contao?do=cowegis_map&id=5&table=tl_cowegis_map_layer&act=edit');
        $currentItem->getChildren()->willReturn([]);

        $this->onBuild($event);

        $parentItem->setUri('/contao?do=cowegis_map&id=2&table=tl_cowegis_layer')->shouldHaveBeenCalled();
        $moduleItem->setUri(Argument::any())->shouldNotHaveBeenCalled();
        $currentItem->setUri(Argument::any())->shouldNotHaveBeenCalled();
    }

    public function it_recurses_into_child_items(
        MenuEvent $event,
        ItemInterface $tree,
        ItemInterface $parentItem,
        ItemInterface $sibling,
    ): void {
        $this->beConstructedWith(new InputAdapterStub(['do' => 'cowegis_map', 'table' => 'tl_cowegis_map_layer']));

        $event->getTree()->willReturn($tree);
        $tree->getName()->willReturn('breadcrumbMenu');
        $tree->getChildren()->willReturn(['current_1' => $parentItem]);

        $parentItem->getUri()->willReturn('/contao?do=cowegis_map&id=2&table=tl_cowegis_map_layer');
        $parentItem->getChildren()->willReturn(['sibling_0' => $sibling]);
        $parentItem->setUri(Argument::any())->willReturn($parentItem);

        $sibling->getUri()->willReturn('/contao?do=cowegis_map&id=3&table=tl_cowegis_map_layer');
        $sibling->getChildren()->willReturn([]);
        $sibling->setUri(Argument::any())->willReturn($sibling);

        $this->onBuild($event);

        $parentItem->setUri('/contao?do=cowegis_map&id=2&table=tl_cowegis_layer')->shouldHaveBeenCalled();
        $sibling->setUri('/contao?do=cowegis_map&id=3&table=tl_cowegis_layer')->shouldHaveBeenCalled();
    }

    public function it_ignores_menu_trees_other_than_the_breadcrumb(
        MenuEvent $event,
        ItemInterface $tree,
    ): void {
        $this->beConstructedWith(new InputAdapterStub(['do' => 'cowegis_map', 'table' => 'tl_cowegis_map_layer']));

        $event->getTree()->willReturn($tree);
        $tree->getName()->willReturn('mainMenu');

        $this->onBuild($event);

        $tree->getChildren()->shouldNotHaveBeenCalled();
    }

    public function it_does_nothing_when_another_module_is_active(
        MenuEvent $event,
        ItemInterface $tree,
    ): void {
        $this->beConstructedWith(new InputAdapterStub(['do' => 'cowegis_layer', 'table' => 'tl_cowegis_map_layer']));

        $event->getTree()->willReturn($tree);
        $tree->getName()->willReturn('breadcrumbMenu');

        $this->onBuild($event);

        $tree->getChildren()->shouldNotHaveBeenCalled();
    }

    public function it_does_nothing_when_not_editing_a_map_layer_record(
        MenuEvent $event,
        ItemInterface $tree,
    ): void {
        $this->beConstructedWith(new InputAdapterStub(['do' => 'cowegis_map', 'table' => 'tl_cowegis_layer']));

        $event->getTree()->willReturn($tree);
        $tree->getName()->willReturn('breadcrumbMenu');

        $this->onBuild($event);

        $tree->getChildren()->shouldNotHaveBeenCalled();
    }

    public function it_keeps_links_without_a_query_string_untouched(
        MenuEvent $event,
        ItemInterface $tree,
        ItemInterface $item,
    ): void {
        $this->beConstructedWith(new InputAdapterStub(['do' => 'cowegis_map', 'table' => 'tl_cowegis_map_layer']));

        $event->getTree()->willReturn($tree);
        $tree->getName()->willReturn('breadcrumbMenu');
        $tree->getChildren()->willReturn(['current_0' => $item]);

        $item->getUri()->willReturn(null);
        $item->getChildren()->willReturn([]);

        $this->onBuild($event);

        $item->setUri(Argument::any())->shouldNotHaveBeenCalled();
    }
}
