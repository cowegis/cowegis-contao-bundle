<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_cowegis_icon'] = [
    'config' => [
        'dataContainer'    => 'Table',
        'enableVersioning' => true,
        'sql'              => [
            'keys' => [
                'id'    => 'primary',
                'alias' => 'unique',
            ],
        ],
//        'onsubmit_callback' => [
//            ['netzmacht.contao_leaflet.listeners.dca.leaflet', 'clearCache'],
//        ],
    ],

    'list' => [
        'sorting'           => [
            'mode'         => 1,
            'fields'       => ['type', 'title'],
            'flag'         => 11,
            'panelLayout'  => 'limit',
            'headerFields' => ['title', 'type'],
        ],
        'label'             => [
            'fields' => ['title', 'type'],
            'format' => '%s <span class="tl_gray">[%s]</span>',
        ],
        'global_operations' => [
            'styles'   => [
                'href'       => 'table=tl_cowegis_style',
                'icon'       => 'bundles/cowegiscontao/img/style.png',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
            'popups'   => [
                'href'       => 'table=tl_cowegis_popup',
                'icon'       => 'bundles/cowegiscontao/img/popup.png',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
            'tooltips' => [
                'href'       => 'table=tl_cowegis_tooltip',
                'icon'       => 'bundles/cowegiscontao/img/tooltip.png',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
            'all'      => [
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();" accesskey="e"',
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

    'palettes' => [
        '__selector__' => ['type'],
    ],

    'metapalettes' => [
        'default'               => [
            'title'   => ['title', 'alias', 'type'],
            'config'  => [],
            'anchors' => [
                ':hide',
                'iconAnchor',
                'popupAnchor',
                'tooltipAnchor',
            ],
        ],
        'image extends default' => [
            'config' => [
                'iconImage',
                'iconRetinaImage',
                'iconSize',
                'className',
            ],
            'shadow' => [
                'shadowImage',
                'shadowRetinaImage',
                'shadowAnchor',
            ],
            'active' => ['active'],
        ],

        'div extends default' => [
            'config' => [
                'html',
                'iconSize',
                'className',
            ],
            'active' => ['active'],
        ],

        'extra extends default' => [
            'config' => [
                'shape',
                'icon',
                'prefix',
                'markerColor',
                'iconColor',
            ],
            'assets' => [
                ':hide',
                'assets',
            ],
            'active' => ['active'],
        ],

        'svg extends default' => [
            'config' => [
                'backgroundColor',
                'iconColor',
                'content',
                'iconSize',
                'className',
            ],
            'active' => ['active'],
        ],

        'fontAwesome extends default' => [
            'config' => [
                'backgroundColor',
                'iconColor',
                'faIconSet',
                'icon',
                'iconSize',
                'className',
            ],
            'active' => ['active'],
        ],
    ],

    'fields' => [
        'id'                => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'tstamp'            => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'title'             => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'alias'             => [
            'exclude'       => true,
            'inputType'     => 'text',
//            'save_callback' => [
//                ['netzmacht.contao_toolkit.dca.listeners.alias_generator', 'handleSaveCallback'],
//                ['netzmacht.contao_leaflet.listeners.dca.validator', 'validateAlias'],
//],
            'eval'          => [
                'mandatory'   => false,
                'maxlength'   => 255,
                'tl_class'    => 'w50',
                'unique'      => true,
                'doNotCopy'   => true,
                'nullIfEmpty' => true,
            ],
            'toolkit'       => [
                'alias_generator' => [
                    'factory' => 'netzmacht.contao_leaflet.definition.alias_generator.factory_default',
                    'fields'  => ['title'],
                ],
            ],
            'sql'           => 'varchar(255) NULL',
        ],
        'type'              => [
            'exclude'   => true,
            'inputType' => 'select',
            'eval'      => [
                'mandatory'          => true,
                'tl_class'           => 'w50',
                'includeBlankOption' => true,
                'submitOnChange'     => true,
                'chosen'             => true,
            ],
//            'options_callback' => ['netzmacht.contao_leaflet.listeners.dca.icon', 'getIconOptions'],
            'reference' => &$GLOBALS['TL_LANG']['leaflet_icon'],
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'active'            => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
//            'save_callback' => [
//                ['netzmacht.contao_leaflet.listeners.dca.leaflet', 'clearCache'],
//            ],
        ],
        'iconImage'         => [
            'exclude'   => true,
            'inputType' => 'fileTree',
            'eval'      => [
                'filesOnly'  => true,
                'fieldType'  => 'radio',
                'mandatory'  => true,
                'tl_class'   => 'clr',
                'extensions' => 'gif,png,svg,jpg',
            ],
            'sql'       => 'binary(16) NULL',
        ],
        'iconRetinaImage'   => [
            'exclude'   => true,
            'inputType' => 'fileTree',
            'eval'      => [
                'filesOnly'  => true,
                'fieldType'  => 'radio',
                'mandatory'  => false,
                'tl_class'   => 'clr',
                'extensions' => 'gif,png,svg,jpg',
            ],
            'sql'       => 'binary(16) NULL',
        ],
        'shadowImage'       => [
            'exclude'   => true,
            'inputType' => 'fileTree',
            'eval'      => [
                'filesOnly'  => true,
                'fieldType'  => 'radio',
                'mandatory'  => false,
                'tl_class'   => 'clr',
                'extensions' => 'gif,png,svg,jpg',
            ],
            'sql'       => 'binary(16) NULL',
        ],
        'shadowRetinaImage' => [
            'exclude'   => true,
            'inputType' => 'fileTree',
            'eval'      => [
                'filesOnly'  => true,
                'fieldType'  => 'radio',
                'mandatory'  => false,
                'tl_class'   => 'clr',
                'extensions' => 'gif,png,svg,jpg',
            ],
            'sql'       => 'binary(16) NULL',
        ],
        'iconAnchor'        => [
            'exclude'   => true,
            'inputType' => 'text',
//            'save_callback' => [
//                ['netzmacht.contao_leaflet.listeners.dca.validator', 'validateCoordinates'],
//            ],
            'eval'      => [
                'maxlength'   => 255,
                'tl_class'    => 'w50',
                'nullIfEmpty' => true,
            ],
            'sql'       => 'varchar(255) NULL',
        ],
        'shadowAnchor'      => [
            'exclude'   => true,
            'inputType' => 'text',
//            'save_callback' => [
//                ['netzmacht.contao_leaflet.listeners.dca.validator', 'validateCoordinates'],
//            ],
            'eval'      => [
                'maxlength'   => 255,
                'tl_class'    => 'w50',
                'nullIfEmpty' => true,
            ],
            'sql'       => 'varchar(255) NULL',
        ],
        'popupAnchor'       => [
            'exclude'   => true,
            'inputType' => 'text',
//            'save_callback' => [
//                ['netzmacht.contao_leaflet.listeners.dca.validator', 'validateCoordinates'],
//            ],
            'eval'      => [
                'maxlength'   => 255,
                'tl_class'    => 'w50',
                'nullIfEmpty' => true,
            ],
            'sql'       => 'varchar(255) NULL',
        ],
        'tooltipAnchor'     => [
            'exclude'   => true,
            'inputType' => 'text',
//            'save_callback' => [
//                ['netzmacht.contao_leaflet.listeners.dca.validator', 'validateCoordinates'],
//            ],
            'eval'      => [
                'maxlength'   => 255,
                'tl_class'    => 'w50',
                'nullIfEmpty' => true,
            ],
            'sql'       => 'varchar(255) NULL',
        ],
        'className'         => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => false, 'maxlength' => 64, 'tl_class' => 'w50'],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'iconSize'          => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'maxlength'   => 64,
                'tl_class'    => 'w50',
                'nullIfEmpty' => true,
            ],
            'sql'       => 'varchar(64) NULL',
        ],
        'html'              => [
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval'      => [
                'style'          => 'height:60px',
                'preserveTags'   => true,
                'decodeEntities' => true,
                'allowHtml'      => true,
                'rte'            => 'ace|html',
                'tl_class'       => 'clr',
            ],
            'sql'       => 'mediumtext NULL',
        ],
        'icon'              => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'maxlength'   => 64,
                'tl_class'    => 'clr w50',
                'nullIfEmpty' => true,
            ],
            'sql'       => 'varchar(64) NULL',
        ],
        'prefix'            => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'maxlength'   => 64,
                'tl_class'    => 'w50',
                'nullIfEmpty' => true,
            ],
            'sql'       => 'varchar(64) NULL',
        ],
        'shape'             => [
            'exclude'   => true,
            'inputType' => 'radio',
            'default'   => 'circle',
            'options'   => ['circle', 'square', 'star', 'penta'],
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => 'varchar(64) NULL',
        ],
        'iconColor'         => [
            'exclude'   => true,
            'inputType' => 'text',
            'wizard'    => [
                ['netzmacht.contao_toolkit.dca.listeners.color_picker', 'handleWizardCallback'],
            ],
            'eval'      => [
                'maxlength'      => 64,
                'tl_class'       => 'w50 wizard',
                'decodeEntities' => true,
                'nullIfEmpty'    => true,
            ],
            'sql'       => 'varchar(16) NULL',
        ],
        'markerColor'       => [
            'exclude'   => true,
            'inputType' => 'select',
            'default'   => 'circle',
            'options'   => [
                'blue',
                'red',
                'orange-dark',
                'orange',
                'yellow',
                'blue-dark',
                'cyan',
                'purple',
                'violet',
                'pink',
                'green-dark',
                'green',
                'green-light',
                'black',
                'white',
            ],
            'eval'      => [
                'tl_class'    => 'w50',
                'nullIfEmpty' => true,
            ],
            'sql'       => 'varchar(16) NULL',
        ],
        'backgroundColor'   => [
            'exclude'   => true,
            'inputType' => 'text',
            'wizard'    => [
                ['netzmacht.contao_toolkit.dca.listeners.color_picker', 'handleWizardCallback'],
            ],
            'eval'      => [
                'maxlength'      => 64,
                'tl_class'       => 'clr w50 wizard',
                'nullIfEmpty'    => true,
                'decodeEntities' => true,
            ],
            'sql'       => 'varchar(16) NULL',
        ],
        'faIconSet'         => [
            'exclude'   => true,
            'inputType' => 'radio',
            'default'   => 'solid',
            'options'   => ['solid', 'brands', 'regular'],
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => 'varchar(64) NULL',
        ],
        'assets'            => [
            'exclude'   => true,
            'inputType' => 'fileTree',
            'eval'      => [
                'multiple'   => true,
                'fieldType'  => 'checkbox',
                'filesOnly'  => true,
                'extensions' => 'css,js',
                'orderField' => 'assetsOrder',
                'tl_class'   => 'clr',
            ],
            'sql'       => 'blob NULL',
        ],
        'assetsOrder'       => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['sortOrder'],
            'sql'   => 'blob NULL',
        ],
        'content'           => [
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => 'varchar(64) NOT NULL default \'\'',
        ],
    ],
];
