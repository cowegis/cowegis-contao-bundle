<?php

declare(strict_types=1);

/**
 * Leaflet maps for Contao CMS.
 *
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_cowegis_marker'] = [
    'config' => [
        'dataContainer'     => 'Table',
        'enableVersioning'  => true,
        'ptable'            => 'tl_cowegis_layer',
        'sql'               => [
            'keys' => [
                'id'  => 'primary',
                'pid' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting'           => [
            'mode'         => 4,
            'fields'       => ['sorting'],
            'flag'         => 1,
            'panelLayout'  => 'sort,filter;search,limit',
            'headerFields' => ['title', 'type'],
        ],
        'label'             => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'global_operations' => [
            'all' => [
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();" accesskey="e"',
            ],
        ],
        'operations'        => [
            'edit'   => [
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],
            'copy'   => [
                'href' => 'act=copy',
                'icon' => 'copy.gif',
            ],
            'delete' => [
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '')
                    . '\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'icon'            => 'visible.gif',
                'attributes'      => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => [
                    'netzmacht.contao_toolkit.dca.listeners.state_button_callback',
                    'handleButtonCallback',
                ],
                'toolkit'         => [
                    'state_button' => ['stateColumn' => 'active'],
                ],
            ],
            'show'   => [
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
        ],
    ],

    'metapalettes'    => [
        'default' => [
            'title'   => ['title', 'alias', 'coordinates'],
            'icon'    => ['markerSymbol', 'icon', 'tooltip', 'alt'],
            'popup'   => ['addPopup'],
            'tooltip' => [':hide', 'addTooltip'],
            'expert'  => [':hide', 'featureData', 'options'],
            'active'  => ['active'],
        ],
    ],
    'metasubpalettes' => [
        'addPopup'   => ['popupContent', 'popup'],
        'addTooltip' => ['tooltipContent', 'tooltipPreset'],
    ],
    'fields'          => [
        'id'              => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'tstamp'          => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'sorting'         => [
            'sql'     => "int(10) unsigned NOT NULL default '0'",
            'sorting' => true,
        ],
        'pid'             => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'title'           => [
            'exclude'   => true,
            'search'    => true,
            'sorting'   => true,
            'flag'      => 1,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'alias'           => [
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
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
        'coordinates'     => [
            'exclude'   => true,
            'inputType' => 'cowegis_geocode',
            'eval'      => [
                'maxlength'      => 255,
                'tl_class'       => 'clr',
                'nullIfEmpty'    => true,
                'doNotSaveEmpty' => true,
            ],
        ],
        'latitude'        => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => 'decimal(10,8) NULL',
        ],
        'longitude'       => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => 'decimal(11,8) NULL',
        ],
        'altitude'        => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => 'float NULL',
        ],
        'active'          => [
            'exclude'   => true,
            'filter'    => true,
            'sorting'   => true,
            'flag'      => 12,
            'inputType' => 'checkbox',
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'tooltip'         => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'alt'             => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'addPopup'        => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'filter'    => true,
            'eval'      => ['tl_class' => 'w50', 'submitOnChange' => true],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'popup'           => [
            'exclude'    => true,
            'inputType'  => 'select',
            'eval'       => [
                'mandatory'          => false,
                'tl_class'           => 'w50',
                'chosen'             => true,
                'includeBlankOption' => true,
            ],
            'foreignKey' => 'tl_cowegis_popup.title',
            'sql'        => "int(10) unsigned NOT NULL default '0'",
        ],
        'popupContent'    => [
            'exclude'     => true,
            'inputType'   => 'text',
            'eval'        => [
                'mandatory'  => true,
                'rte'        => 'tinyMCE',
                'helpwizard' => true,
                'tl_class'   => 'clr',
            ],
            'explanation' => 'insertTags',
            'sql'         => 'mediumtext NULL',
        ],
        'addTooltip'      => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'filter'    => true,
            'default'   => '',
            'eval'      => ['tl_class' => 'clr w50', 'submitOnChange' => true],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'tooltipPreset'   => [
            'exclude'    => true,
            'inputType'  => 'select',
            'eval'       => [
                'mandatory'          => false,
                'tl_class'           => 'w50',
                'chosen'             => true,
                'includeBlankOption' => true,
            ],
            'foreignKey' => 'tl_cowegis_tooltip.title',
            'sql'        => "int(10) unsigned NOT NULL default '0'",
        ],
        'tooltipContent'  => [
            'exclude'     => true,
            'inputType'   => 'text',
            'eval'        => [
                'mandatory'  => true,
                'rte'        => 'tinyMCE',
                'helpwizard' => true,
                'tl_class'   => 'clr',
            ],
            'explanation' => 'insertTags',
            'sql'         => 'mediumtext NULL',
        ],
        'icon'            => [
            'exclude'    => true,
            'inputType'  => 'select',
            'eval'       => [
                'mandatory'          => false,
                'tl_class'           => 'w50',
                'chosen'             => true,
                'includeBlankOption' => true,
            ],
            'foreignKey' => 'tl_cowegis_icon.title',
            'sql'        => "int(10) unsigned NOT NULL default '0'",
        ],
        'markerSymbol'        => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => 'varchar(255) NOT NULL default \'\'',
        ],
        'featureData'     => [
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval'      => [
                'tl_class'  => 'clr lng',
                'allowHtml' => true,
                'style'     => 'min-height: 40px;',
                'rte'       => 'ace|json',
            ],
            'sql'       => 'text NULL',
        ],
        'options'         => [
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval'      => [
                'tl_class'  => 'clr lng',
                'allowHtml' => true,
                'style'     => 'min-height: 40px;',
                'rte'       => 'ace|json',
            ],
            'sql'       => 'text NULL',
        ],
    ],
];
