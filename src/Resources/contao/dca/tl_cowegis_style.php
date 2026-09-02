<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_cowegis_style'] = [
    'config' => [
        'dataContainer'    => DC_Table::class,
        'enableVersioning' => true,
        'sql'              => [
            'keys' => [
                'id'    => 'primary',
                'alias' => 'unique',
            ],
        ],
    ],

    'list' => [
        'sorting'           => [
            'mode'         => 1,
            'fields'       => ['title'],
            'flag'         => 1,
            'panelLayout'  => 'limit',
            'headerFields' => ['title', 'type'],
        ],
        'label'             => [
            'fields' => ['title', 'type'],
            'format' => '%s <span class="tl_gray">[%s]</span>',
        ],
        'global_operations' => [
            'icons'  => [
                'href'       => 'table=tl_cowegis_icon',
                'icon'       => 'bundles/cowegiscontao/img/icons.png',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
                'primary'    => true,
            ],
            'popups' => [
                'href'       => 'table=tl_cowegis_popup',
                'icon'       => 'bundles/cowegiscontao/img/popup.png',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
                'primary'    => true,
            ],
            'tooltips' => [
                'href'       => 'table=tl_cowegis_tooltip',
                'icon'       => 'bundles/cowegiscontao/img/tooltip.png',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
                'primary'    => true,
            ],
        ],
        'operations'        => [
            'edit'   => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy'   => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '')
                    . '\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'icon'       => 'visible.svg',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'href'       => 'act=toggle&amp;field=active',
            ],
            'show'   => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    'palettes' => [
        '__selector__' => ['type'],
    ],

    'metapalettes' => [
        'default'               => [
            'title'  => ['title', 'alias', 'type'],
            'config' => [],
            'active' => ['active'],
        ],
        'fixed extends default' => [
            'config' => ['stroke', 'fill'],
        ],
    ],

    'metasubpalettes' => [
        'stroke' => ['color', 'weight', 'opacity', 'dashArray', 'dashOffset', 'lineCap', 'lineJoin'],
        'fill'   => ['fillColor', 'fillOpacity'],
    ],

    'fields' => [
        'id'          => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'tstamp'      => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'title'       => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'alias'       => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'mandatory'   => false,
                'maxlength'   => 255,
                'tl_class'    => 'w50',
                'unique'      => true,
                'doNotCopy'   => true,
                'nullIfEmpty' => true,
            ],
            'sql'       => 'varchar(255) NULL',
        ],
        'type'        => [
            'exclude'   => true,
            'inputType' => 'select',
            'eval'      => [
                'mandatory'          => true,
                'tl_class'           => 'w50',
                'includeBlankOption' => true,
                'submitOnChange'     => true,
                'chosen'             => true,
                'helpwizard'         => true,
            ],
            'reference' => &$GLOBALS['TL_LANG']['cowegis_style'],
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'stroke'      => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => true,
            'eval'      => ['tl_class' => 'w50', 'submitOnChange' => true],
            'sql'       => "char(1) NOT NULL default '1'",
        ],
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
        'weight'      => [
            'exclude'   => true,
            'inputType' => 'text',
            'default'   => 5,
            'eval'      => ['mandatory' => false, 'maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'],
            'sql'       => "int(4) NOT NULL default '5'",
        ],
        'opacity'     => [
            'exclude'   => true,
            'inputType' => 'text',
            'default'   => '0.5',
            'eval'      => ['mandatory' => false, 'maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'],
            'sql'       => "varchar(4) NOT NULL default '0.5'",
        ],
        'fill'        => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => ['tl_class' => 'clr w50', 'submitOnChange' => true],
            'sql'       => "char(1) NOT NULL default ''",
        ],
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
        'fillOpacity' => [
            'exclude'   => true,
            'inputType' => 'text',
            'default'   => '0.2',
            'eval'      => ['mandatory' => false, 'maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'],
            'sql'       => "varchar(4) NOT NULL default '0.2'",
        ],
        'dashArray'   => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 32, 'tl_class' => 'w50'],
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'dashOffset'  => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 32, 'tl_class' => 'w50', 'rgxp' => 'digit'],
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'lineCap'     => [
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => ['butt', 'round', 'square', 'inherit'],
            'reference' => &$GLOBALS['TL_LANG']['tl_cowegis_style']['lineCaps'],
            'eval'      => [
                'mandatory'          => false,
                'tl_class'           => 'w50 clr',
                'includeBlankOption' => true,
                'helpwizard'         => true,
            ],
            'sql'       => "varchar(8) NOT NULL default ''",
        ],
        'lineJoin'    => [
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => ['miter', 'round', 'bevel', 'inherit'],
            'reference' => &$GLOBALS['TL_LANG']['tl_cowegis_style']['lineJoins'],
            'eval'      => ['mandatory' => false, 'tl_class' => 'w50', 'includeBlankOption' => true, 'helpwizard'],
            'sql'       => "varchar(8) NOT NULL default ''",
        ],
        'active'      => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'filter'    => true,
            'sorting'   => true,
            'search'    => false,
            'toggle'    => true,
            'flag'      => 12,
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
    ],
];
