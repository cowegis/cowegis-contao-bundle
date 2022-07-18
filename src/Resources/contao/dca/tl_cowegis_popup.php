<?php

declare(strict_types=1);

/**
 * Leaflet maps for Contao CMS.
 *
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_cowegis_popup'] = [
    'config' => [
        'dataContainer'     => 'Table',
        'enableVersioning'  => true,
        'sql'               => [
            'keys' => [
                'id'    => 'primary',
                'alias' => 'unique',
            ],
        ],
        /*
        'onsubmit_callback' => [
            ['netzmacht.contao_leaflet.listeners.dca.leaflet', 'clearCache'],
        ],
        */
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
            'styles' => [
                'href'       => 'table=tl_cowegis_style',
                'icon'       => 'bundles/cowegiscontao/img/style.png',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
            'icons'  => [
                'href'       => 'table=tl_cowegis_icon',
                'icon'       => 'bundles/cowegiscontao/img/icons.png',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
            'all'    => [
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();" accesskey="e"',
            ],
        ],
        'operations'        => [
            'edit'   => [
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'copy'   => [
                'href'  => 'act=copy',
                'icon'  => 'copy.gif',
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
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
        ],
    ],

    'palettes' => [
        '__selector__' => ['type'],
    ],

    'metapalettes' => [
        'default' => [
            'title'  => ['title', 'alias'],
            'size'   => ['maxWidth', 'minWidth', 'maxHeight'],
            'config' => [
                ':hide',
                'closeButton',
                'keepInView',
                'autoClose',
                'closeOnEscapeKey',
                'closeOnClick',
                'offset',
                'className',
                'autoPan',
            ],
            'active' => ['active'],
        ],
    ],

    'metasubpalettes' => [
        'autoPan' => ['autoPanPadding'],
    ],

    'fields' => [
        'id'             => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'tstamp'         => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'title'          => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'alias'          => [
            'exclude'       => true,
            'inputType'     => 'text',
            'eval'          => [
                'mandatory'   => false,
                'maxlength'   => 255,
                'tl_class'    => 'w50',
                'unique'      => true,
                'doNotCopy'   => true,
                'nullIfEmpty' => true,
            ],
            'sql'           => 'varchar(255) NULL',
        ],
        'maxWidth'       => [
            'exclude'   => true,
            'inputType' => 'text',
            'default'   => null,
            'eval'      => ['mandatory' => false, 'maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'],
            'sql'       => 'int(4) NULL',
        ],
        'minWidth'       => [
            'exclude'   => true,
            'inputType' => 'text',
            'default'   => null,
            'eval'      => ['mandatory' => false, 'maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'],
            'sql'       => 'int(4) NULL',
        ],
        'maxHeight'      => [
            'exclude'   => true,
            'inputType' => 'text',
            'default'   => null,
            'eval'      => ['mandatory' => false, 'maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'],
            'sql'       => 'int(4) NULL',
        ],
        'autoPan'        => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => true,
            'eval'      => ['tl_class' => 'clr w50', 'submitOnChange' => true],
            'sql'       => "char(1) NOT NULL default '1'",
        ],
        'keepInView'     => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => false,
            'eval'      => ['tl_class' => 'w50', 'submitOnChange' => false],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'closeButton'    => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => true,
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default '1'",
        ],
        'offset'         => [
            'exclude'       => true,
            'inputType'     => 'text',
            'eval'          => [
                'maxlength'   => 255,
                'tl_class'    => 'clr w50',
                'nullIfEmpty' => true,
            ],
            'sql'           => 'varchar(255) NULL',
        ],
        'autoPanPadding' => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'maxlength'   => 255,
                'tl_class'    => 'w50',
                'nullIfEmpty' => true,
                'multiple'    => true,
                'size'        => 2,
            ],
            'sql'       => 'varchar(255) NULL',
        ],
        'autoClose'   => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => true,
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default '1'",
        ],
        'closeOnClick'   => [
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => ['0', '1'],
            'default'   => null,
            'eval'      => ['tl_class' => 'w50', 'nullIfEmpty' => true, 'includeBlankOption' => true],
            'sql'       => 'char(5) default NULL',
        ],
        'closeOnEscapeKey'   => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => true,
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default '1'",
        ],
        'className'      => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 64, 'tl_class' => 'w50'],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'active'         => [
            'exclude'       => true,
            'inputType'     => 'checkbox',
            'filter'        => true,
            'sorting'       => true,
            'search'        => false,
            'flag'          => 12,
            'eval'          => ['tl_class' => 'w50'],
            'sql'           => "char(1) NOT NULL default ''",
            /*
            'save_callback' => [
                  ['netzmacht.contao_leaflet.listeners.dca.leaflet', 'clearCache'],
            ],
            */
        ],
    ],
];
