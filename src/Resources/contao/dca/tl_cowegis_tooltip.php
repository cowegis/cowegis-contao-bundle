<?php

declare(strict_types=1);

/**
 * Leaflet maps for Contao CMS.
 *
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_cowegis_tooltip'] = [
    'config' => [
        'dataContainer'     => 'Table',
        'enableVersioning'  => true,
        'sql'               => [
            'keys' => ['id' => 'primary'],
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
                'label'      => &$GLOBALS['TL_LANG']['tl_cowegis_tooltip']['styles'],
                'href'       => 'table=tl_cowegis_style',
                'icon'       => 'bundles/cowegiscontao/img/style.png',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
            'icons'  => [
                'label'      => &$GLOBALS['TL_LANG']['tl_cowegis_tooltip']['icons'],
                'href'       => 'table=tl_cowegis_icon',
                'icon'       => 'bundles/cowegiscontao/img/icons.png',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
            'popups' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_cowegis_style']['popups'],
                'href'       => 'table=tl_cowegis_popup',
                'icon'       => 'bundles/cowegiscontao/img/popup.png',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
            'all'    => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();" accesskey="e"',
            ],
        ],
        'operations'        => [
            'edit'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_cowegis_tooltip']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'copy'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_cowegis_tooltip']['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.gif',
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_cowegis_tooltip']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm']
                    . '\'))return false;Backend.getScrollOffset()"',
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_cowegis_tooltip']['show'],
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
            'title'  => ['title'],
            'config' => [
                'direction',
                'opacity',
                'offset',
                'className',
                'permanent',
                'sticky',
                'interactive',
                'attribution',
            ],
        ],
    ],

    'metasubpalettes' => [
        'autoPan' => ['autoPanPadding'],
    ],

    'fields' => [
        'id'         => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'tstamp'     => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'title'      => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'direction'  => [
            'exclude'   => true,
            'inputType' => 'select',
            'default'   => null,
            'options'   => ['right', 'left', 'top', 'bottom', 'center', 'auto'],
            'eval'      => [
                'mandatory'          => false,
                'tl_class'           => 'w50',
                'includeBlankOption' => true,
            ],
            'sql'       => 'varchar(6) NULL',
        ],
        'opacity'                        => [
            'exclude'   => true,
            'inputType' => 'text',
            'default'   => '1.0',
            'eval'      => ['mandatory' => false, 'maxlength' => 4, 'rgxp' => 'digit', 'tl_class' => 'w50'],
            'sql'       => "varchar(4) NOT NULL default ''",
        ],
        'permanent'    => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => false,
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'sticky'    => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => false,
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'offset'         => [
            'exclude'       => true,
            'inputType'     => 'text',
            'eval'          => [
                'maxlength'   => 255,
                'tl_class'    => 'w50',
                'nullIfEmpty' => true,
            ],
            'sql'           => 'varchar(255) NULL',
        ],
        'className'         => [
            'exclude'       => true,
            'inputType'     => 'text',
            'eval'          => [
                'maxlength'   => 255,
                'tl_class'    => 'w50',
                'nullIfEmpty' => true,
            ],
            'sql'           => 'varchar(255) NULL',
        ],
        'interactive'    => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => false,
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'attribution'                    => [
            'exclude'   => true,
            'inputType' => 'text',
            'default'   => '',
            'eval'      => [
                'maxlength'   => 255,
                'tl_class'    => 'clr long',
                'allowHtml'   => true,
                'nullIfEmpty' => true,
            ],
            'sql'       => 'varchar(255) default NULL',
        ],
    ],
];
