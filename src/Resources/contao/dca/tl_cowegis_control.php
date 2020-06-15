<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_cowegis_control'] = [
    'config' => [
        'dataContainer'    => 'Table',
        'enableVersioning' => true,
        'ptable'           => 'tl_cowegis_map',
        'sql'              => [
            'keys' => [
                'id'  => 'primary',
                'pid' => 'index',
            ],
        ],
//        'onsubmit_callback' => [
//            ['netzmacht.contao_leaflet.listeners.dca.leaflet', 'clearCache'],
//        ],
    ],

    // List configuration
    'list'   => [
        'sorting'           => [
            'mode'         => 4,
            'fields'       => ['sorting'],
            'headerFields' => ['title'],
            'flag'         => 1,
            'sorting'      => 2,
            'panelLayout'  => 'filter,sort;search,limit',
//            'child_record_callback' => ['netzmacht.contao_leaflet.listeners.dca.control', 'generateRow'],
        ],
        'label'             => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'global_operations' => [
            'all' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();" accesskey="e"',
            ],
        ],
        'operations'        => [
            'edit'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_cowegis_control']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'header.gif',
            ],
            'copy'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_cowegis_control']['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.gif',
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_cowegis_control']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm']
                    . '\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'label'           => &$GLOBALS['TL_LANG']['tl_cowegis_control']['toggle'],
                'icon'            => 'visible.gif',
                'attributes'      => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => [
                    'netzmacht.contao_toolkit.dca.listeners.state_button_callback',
                    'handleButtonCallback',
                ],
                'toolkit'         => [
                    'state_button' => [
                        'stateColumn' => 'active',
                    ],
                ],
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_cowegis_control']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
        ],
    ],

    'palettes' => [
        '__selector__' => ['type'],
    ],

    'metapalettes' => [
        'default'                     => [
            'name'   => ['title', 'alias', 'type', 'position'],
            'config' => [],
            'active' => ['active'],
        ],
        'zoom extends default'        => [
            'config' => ['zoomInText', 'zoomOutText', 'zoomInTitle', 'zoomOutTitle', 'disableDefault'],
        ],
        'layers extends default'      => [
            'config'              => ['layers', 'collapsed', 'hideSingleBase'],
            'expert after config' => [':hide', 'autoZIndex', 'sortLayers', 'nameFunction'],
        ],
        'scale extends default'       => [
            'config' => ['maxWidth', 'metric', 'imperial', 'updateWhenIdle'],
        ],
        'attribution extends default' => [
            'config' => ['attributions', 'prefix', 'disableDefault'],
        ],
        'loading extends default'     => [
            'config' => ['zoomControl', 'delayIndicator', 'separate', 'spinjs'],
        ],
        'fullscreen extends default'  => [
            'config' => ['fullscreenTitle', 'fullscreenCancelTitle', 'separate', 'simulateFullScreen'],
        ],
    ],

    'metasubpalettes' => [
        'spinjs'     => ['spin'],
        'sortLayers' => ['sortFunction'],
    ],

    'fields' => [
        'id'                    => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid'                   => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tstamp'                => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'sorting'               => [
            'sql'     => "int(10) unsigned NOT NULL default '0'",
            'sorting' => true,
        ],
        'title'                 => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['title'],
            'exclude'   => true,
            'inputType' => 'text',
            'sorting'   => true,
            'search'    => true,
            'flag'      => 1,
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'alias'                 => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['alias'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'eval'      => [
                'mandatory'   => false,
                'maxlength'   => 255,
                'tl_class'    => 'w50',
                'nullIfEmpty' => true,
                'doNotCopy'   => true,
            ],
            'sql'       => 'varchar(255) NULL',
        ],
        'type'                  => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['type'],
            'exclude'   => true,
            'inputType' => 'select',
            'filter'    => true,
            'sorting'   => true,
            'eval'      => [
                'mandatory'          => true,
                'tl_class'           => 'w50',
                'includeBlankOption' => true,
                'submitOnChange'     => true,
                'chosen'             => true,
                'helpwizard'         => true,
            ],
            'reference' => &$GLOBALS['TL_LANG']['cowegis_control'],
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'position'              => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['position'],
            'exclude'   => true,
            'inputType' => 'select',
            'filter'    => true,
            'sorting'   => true,
            'options'   => ['topleft', 'topright', 'bottomleft', 'bottomright'],
            'reference' => &$GLOBALS['TL_LANG']['tl_cowegis_control'],
            'eval'      => [
                'maxlength'          => 255,
                'tl_class'           => 'w50',
                'helpwizard'         => true,
                'nullIfEmpty'        => true,
                'includeBlankOption' => true,
            ],
            'sql'       => "varchar(255) default NULL",
        ],
        'active'                => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['active'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'filter'    => true,
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
//            'save_callback' => [
//                ['netzmacht.contao_leaflet.listeners.dca.leaflet', 'clearCache'],
//            ],
        ],
        'zoomInText'            => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['zoomInText'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50', 'nullIfEmpty' => true],
            'sql'       => "varchar(255) default NULL",
        ],
        'zoomOutText'           => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['zoomInText'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50', 'nullIfEmpty' => true],
            'sql'       => "varchar(255) default NULL",
        ],
        'zoomInTitle'           => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['zoomInTitle'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50', 'nullIfEmpty' => true],
            'sql'       => "varchar(255) default NULL",
        ],
        'zoomOutTitle'          => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['zoomOutTitle'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50', 'nullIfEmpty' => true],
            'sql'       => "varchar(255) default NULL",
        ],
        'collapsed'             => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['collapsed'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => '1',
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'autoZIndex'            => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['autoZIndex'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => '1',
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'layers'                => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['layers'],
            'exclude'   => true,
            'inputType' => 'multiColumnWizard',
            'eval'      => [
                'tl_class'     => 'leaflet-mcw leaflet-mcw-control-layers',
                'style'        => 'max-width: 800px',
                'columnFields' => [
                    'layer' => [
                        'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['layer'],
                        'exclude'   => true,
                        'inputType' => 'select',
//                        'options_callback' => ['netzmacht.contao_leaflet.listeners.dca.control', 'getLayers'],
                        'eval'      => [
                            'style'              => 'width: 100%',
                            'chosen'             => true,
                            'includeBlankOption' => true,
                        ],
                    ],
                    'mode'  => [
                        'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['layerMode'],
                        'exclude'   => true,
                        'inputType' => 'select',
                        'options'   => ['base', 'overlay'],
                        'reference' => &$GLOBALS['TL_LANG']['tl_cowegis_control'],
                        'eval'      => [
                            'style'      => 'width: 100%',
                            'helpwizard' => true,
                        ],
                    ],
                ],
            ],
            'sql'       => 'mediumblob NULL',
        ],
        'maxWidth'              => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['maxWidth'],
            'exclude'   => true,
            'inputType' => 'text',
            'default'   => 100,
            'eval'      => ['tl_class' => 'w50', 'rgxp' => 'digit'],
            'sql'       => "int(5) NOT NULL default '100'",
        ],
        'metric'                => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['metric'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => '1',
            'eval'      => ['tl_class' => 'w50 clr'],
            'sql'       => "char(1) NOT NULL default '1'",
        ],
        'imperial'              => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['imperial'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => '1',
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default '1'",
        ],
        'updateWhenIdle'        => [
            'label'     => &$GLOBALS['TL_LANG']['tl_cowegis_control']['updateWhenIdle'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'prefix'                => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) default NULL",
        ],
        'attributions'          => [
            'exclude'   => true,
            'inputType' => 'listWizard',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'clr', 'allowHtml' => true],
            'sql'       => 'mediumblob NULL',
        ],
        'separate'              => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'zoomControl'           => [
            'exclude'   => true,
            'inputType' => 'select',
            'reference' => &$GLOBALS['TL_LANG']['tl_cowegis_control'],
            'eval'      => [
                'mandatory'          => false,
                'tl_class'           => 'w50',
                'chosen'             => true,
                'includeBlankOption' => true,
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'spinjs'                => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => ['tl_class' => 'w50', 'submitOnChange' => true],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'spin'                  => [
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval'      => [
                'style'          => 'height:60px',
                'preserveTags'   => true,
                'decodeEntities' => true,
                'allowHtml'      => true,
                'rte'            => 'ace|json',
                'tl_class'       => 'clr',
            ],
            'sql'       => 'mediumtext NULL',
        ],
        'simulateFullScreen'    => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => ['tl_class' => 'w50 m12'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'fullscreenTitle'       => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'fullscreenCancelTitle' => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'disableDefault'        => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => true,
            'eval'      => ['tl_class' => 'w50 m12'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'delayIndicator'        => [
            'exclude'   => true,
            'inputType' => 'text',
            'default'   => true,
            'eval'      => ['tl_class' => 'w50', 'maxlength' => 4, 'rgxp' => 'natural', 'nullIfEmpty' => true],
            'sql'       => "integer(4) UNSIGNED default NULL",
        ],
        'hideSingleBase'        => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => false,
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'sortLayers'            => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'default'   => false,
            'eval'      => ['tl_class' => 'w50', 'submitOnChange' => true],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'sortFunction'          => [
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval'      => [
                'preserveTags'   => true,
                'decodeEntities' => true,
                'allowHtml'      => true,
                'rte'            => 'ace|javascript',
                'tl_class'       => 'clr',
            ],
            'sql'       => 'mediumtext NULL',
        ],
        'nameFunction'          => [
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval'      => [
                'preserveTags'   => true,
                'decodeEntities' => true,
                'allowHtml'      => true,
                'rte'            => 'ace|javascript',
                'tl_class'       => 'clr',
            ],
            'sql'       => 'mediumtext NULL',
        ],
    ],
];
