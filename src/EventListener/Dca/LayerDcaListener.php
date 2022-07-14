<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\Backend;
use Contao\CoreBundle\Framework\Adapter;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Cowegis\Bundle\Contao\Map\Layer\DataLayerType;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry;
use Cowegis\Bundle\Contao\Map\Layer\NodeLayerType;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_keys;
use function assert;
use function implode;
use function is_array;
use function is_string;
use function sprintf;
use function strip_tags;

final class LayerDcaListener extends AbstractListener
{
    /** @var string */
    protected static $name = 'tl_cowegis_layer';

    /** @var LayerTypeRegistry */
    private $layerTypes;

    /** @var TranslatorInterface */
    private $translator;

    /** @var Adapter */
    private $backendAdapter;

    /**
     * File formats.
     *
     * @var array<string,list<string>>
     */
    private $fileFormats;

    /** @var string[] */
    private $amenities;

    /**
     * @param array<string,list<string>> $fileFormats
     * @param string[]                   $amenities
     */
    public function __construct(
        Manager $dcaManager,
        LayerTypeRegistry $typeRegistry,
        TranslatorInterface $translator,
        Adapter $backendAdapter,
        array $fileFormats,
        array $amenities
    ) {
        parent::__construct($dcaManager);

        $this->layerTypes     = $typeRegistry;
        $this->fileFormats    = $fileFormats;
        $this->translator     = $translator;
        $this->backendAdapter = $backendAdapter;
        $this->amenities      = $amenities;
    }

    /**
     * Generate a row.
     *
     * @param array<string,mixed> $row   The data row.
     * @param string              $label Current row label.
     */
    public function rowLabel(array $row, string $label): string
    {
        $layerType = null;
        if ($row['type'] && $this->layerTypes->has($row['type'])) {
            $layerType = $this->layerTypes->get($row['type']);
        }

        if ($layerType) {
            $src = $layerType->iconUrl();
        } else {
            $src = 'iconPLAIN.svg';
        }

        $activeIcon = $src;
        $alt        = $this->getFormatter()->formatValue('type', $row['type']);
        assert(is_string($alt));
        $attributes = sprintf(
            'class="list-icon" title="%s" data-icon="%s"',
            StringUtil::specialchars(strip_tags($alt)),
            $activeIcon
        );

        $icon = Image::getHtml($src, $alt, $attributes);

        if ($layerType) {
            $label = $layerType->label($label, $row);
        }

        return $icon . ' ' . $label;
    }

    /**
     * Generate the markers button.
     *
     * @param array<string,mixed> $row        The data row.
     * @param string              $href       The button href.
     * @param string              $label      The button label.
     * @param string              $title      The button title.
     * @param string              $icon       The button icon.
     * @param string              $attributes Optional attributes.
     */
    public function editDataButton(
        array $row,
        string $href,
        string $label,
        string $title,
        string $icon,
        string $attributes
    ): string {
        if (! $this->layerTypes->has($row['type'])) {
            return '';
        }

        $type = $this->layerTypes->get($row['type']);
        if (! $type instanceof DataLayerType) {
            return '';
        }

        $href = Backend::addToUrl('table=' . $type->dataTable());

        return $this->generateButton($row, $href, $label, $title, $icon, $attributes);
    }

    /** @return string[] */
    public function typeOptions(): array
    {
        $options = [];

        foreach ($this->layerTypes as $layerType) {
            $options[] = $layerType->name();
        }

        return $options;
    }

    /** @return string[] */
    public function fileFormatOptions(): array
    {
        return array_keys($this->fileFormats);
    }

    /** @return string[] */
    public function amenitiesOptions(): array
    {
        return $this->amenities;
    }

    /**
     * Prepare the file widget.
     *
     * @param mixed         $value         Given value.
     * @param DataContainer $dataContainer Data container driver.
     *
     * @return mixed
     */
    public function prepareFileWidget($value, DataContainer $dataContainer)
    {
        if ($dataContainer->activeRecord) {
            $fileFormat = $dataContainer->activeRecord->fileFormat;

            if (isset($this->fileFormats[$fileFormat])) {
                $definition = $this->getDefinition();
                $definition->set(
                    ['fields', $dataContainer->field, 'eval', 'extensions'],
                    implode(',', $this->fileFormats[$fileFormat])
                );

                $definition->set(
                    ['fields', $dataContainer->field, 'label', 1],
                    sprintf(
                        $definition->get(['fields', $dataContainer->field, 'label', 1]),
                        implode(', ', $this->fileFormats[$fileFormat])
                    )
                );
            }
        }

        return $value;
    }

    /**
     * Generate a button.
     *
     * @param array<string,mixed> $row        The data row.
     * @param string              $href       The button href.
     * @param string              $label      The button label.
     * @param string              $title      The button title.
     * @param string              $icon       The button icon.
     * @param string              $attributes Optional attributes.
     */
    protected function generateButton(
        array $row,
        string $href,
        string $label,
        string $title,
        string $icon,
        string $attributes
    ): string {
        return sprintf(
            '<a href="%s" title="%s">%s</a> ',
            Backend::addToUrl($href . '&amp;id=' . $row['id']),
            $title,
            Image::getHtml($icon, $label, $attributes)
        );
    }

    /**
     * Get the paste buttons depending on the layer type.
     *
     * @param DataContainer       $dataContainer The dataContainer driver.
     * @param array<string,mixed> $row           The data row.
     * @param string              $table         The table name.
     * @param mixed               $whatever      Who knows what the purpose of this var is.
     * @param array<string,mixed> $children      The child content.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function pasteButtons(
        DataContainer $dataContainer,
        array $row,
        string $table,
        $whatever,
        array $children
    ): string {
        $pasteAfterUrl = $this->backendAdapter->addToUrl(
            'act=' . $children['mode'] . '&amp;mode=1&amp;pid=' . $row['id']
            . (! is_array($children['id']) ? '&amp;id=' . $children['id'] : '')
        );

        $buffer = sprintf(
            '<a href="%s" title="%s" onclick="Backend.getScrollOffset()">%s</a> ',
            $pasteAfterUrl,
            StringUtil::specialchars($this->translator->trans('pasteafter.1', [$row['id']], 'contao_' . $table)),
            Image::getHtml(
                'pasteafter.svg',
                $this->translator->trans('pasteafter.1', [$row['id']], 'contao_' . $table)
            )
        );

        if (
            $row['type']
            && $this->layerTypes->has($row['type'])
            && $this->layerTypes->get($row['type']) instanceof NodeLayerType
        ) {
            $pasteIntoUrl = $this->backendAdapter->addToUrl(
                sprintf(
                    'act=%s&amp;mode=2&amp;pid=%s%s',
                    $children['mode'],
                    $row['id'],
                    ! is_array($children['id']) ? '&amp;id=' . $children['id'] : ''
                )
            );

            $buffer .= sprintf(
                '<a href="%s" title="%s" onclick="Backend.getScrollOffset()">%s</a> ',
                $pasteIntoUrl,
                StringUtil::specialchars($this->translator->trans('pasteinto.1', [$row['id']], 'contao_' . $table)),
                Image::getHtml(
                    'pasteinto.svg',
                    $this->translator->trans('pasteinto.1', [$row['id']], 'contao_' . $table)
                )
            );
        } elseif ($row['id'] > 0) {
            $buffer .= Image::getHtml('pasteinto_.svg');
        }

        return $buffer;
    }
}
