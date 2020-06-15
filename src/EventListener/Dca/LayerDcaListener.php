<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\Backend;
use Contao\CoreBundle\Framework\Adapter;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Cowegis\Bundle\Contao\Map\Layer\DataLayerType;
use Cowegis\Bundle\Contao\Map\Layer\LayerType;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry;
use Cowegis\Bundle\Contao\Map\Layer\NodeLayerType;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_keys;
use function implode;
use function is_array;
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
     * @var array
     */
    private $fileFormats;

    /** @var string[] */
    private $amenities;

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
     * @param array  $row   The data row.
     * @param string $label Current row label.
     *
     * @return string
     */
    public function rowLabel(array $row, string $label)
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
     * @param array  $row        Current row.
     * @param string $href       The button href.
     * @param string $label      The button label.
     * @param string $title      The button title.
     * @param string $icon       The button icon.
     * @param string $attributes Optional attributes.
     *
     * @return string
     */
    public function editDataButton(array $row, $href, $label, $title, $icon, $attributes) : string
    {
        if (!$this->layerTypes->has($row['type'])) {
            return '';
        }

        $type = $this->layerTypes->get($row['type']);
        if (!$type instanceof DataLayerType) {
            return '';
        }

        $href = Backend::addToUrl('table=' . $type->dataTable());

        return $this->generateButton($row, $href, $label, $title, $icon, $attributes);
    }

    public function typeOptions() : array
    {
        $options = [];

        foreach ($this->layerTypes as $layerType) {
            if (!$layerType instanceof LayerType) {
                continue;
            }

            $options[] = $layerType->name();
        }

        return $options;
    }

    public function fileFormatOptions() : array
    {
        return array_keys($this->fileFormats);
    }

    public function amenitiesOptions() : array
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
    public function prepareFileWidget($value, $dataContainer)
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
     * @param array  $row        Current row.
     * @param string $href       The button href.
     * @param string $label      The button label.
     * @param string $title      The button title.
     * @param string $icon       The button icon.
     * @param string $attributes Optional attributes.
     *
     * @return string
     */
    protected function generateButton($row, $href, $label, $title, $icon, $attributes) : string
    {
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
     * @param \DataContainer $dataContainer The dataContainer driver.
     * @param array          $row           The data row.
     * @param string         $table         The table name.
     * @param null           $whatever      Who knows what the purpose of this var is.
     * @param array          $children      The child content.
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function pasteButtons($dataContainer, $row, $table, $whatever, $children) : string
    {
        $pasteAfterUrl = $this->backendAdapter->addToUrl(
            'act=' . $children['mode'] . '&amp;mode=1&amp;pid=' . $row['id']
            . (!is_array($children['id']) ? '&amp;id=' . $children['id'] : '')
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

        if ($row['type']
            && $this->layerTypes->has($row['type'])
            && $this->layerTypes->get($row['type']) instanceof NodeLayerType
        ) {
            $pasteIntoUrl = $this->backendAdapter->addToUrl(
                sprintf(
                    'act=%s&amp;mode=2&amp;pid=%s%s',
                    $children['mode'],
                    $row['id'],
                    !is_array($children['id']) ? '&amp;id=' . $children['id'] : ''
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
