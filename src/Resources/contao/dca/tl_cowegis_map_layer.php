<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_cowegis_map_layer'] = [
    'config'       => [
        'dataContainer' => 'Table',
        'ptable'        => 'tl_cowegis_map',
        'closed'        => true,
        'notCopyable'   => true,
        'notCreatable'  => true,
        'notDeletable'  => true,
        'notSortable'   => true,
        'sql'           => [
            'keys' => [
                'id'          => 'primary',
                'pid,layerId' => 'unique',
            ],
        ],
    ],
    'metapalettes' => [
        'default' => [
            'active' => [
                'active',
                'initialVisible',
                'pane',
            ],
            'config' => [],
        ],
        '_filter_ extends default' => [
            'filter' => ['filterRules'],
        ],
        'markers extends _filter_' => [
            '+config'  => ['adjustBounds'],
            '+active' => ['dataPane'],
        ],
    ],
    'list'         => [
        'sorting'    => [
            'mode'         => 4,
            'fields'       => ['sorting'],
            'headerFields' => ['title'],
        ],
        'operations' => [
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
        ],
    ],
    'fields'       => [
        'id'             => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'pid'            => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'tstamp'         => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'sorting'        => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'layerId'        => [
            'foreignKey' => 'tl_cowegis_layer.title',
            'eval'       => [
                'mandatory'      => true,
                'tl_class'       => 'clr',
                'submitOnChange' => true,
            ],
            'relation'   => ['type' => 'hasOne', 'load' => 'eager'],
            'sql'        => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'pane'           => [
            'inputType' => 'select',
            'exclude'   => true,
            'filter'    => true,
            'eval'      => ['maxlength' => 64, 'tl_class' => 'w50', 'includeBlankOption' => true],
            'sql'       => ['type' => 'string', 'length' => 64, 'default' => '', 'notnull' => true],
        ],
        'dataPane'           => [
            'inputType' => 'select',
            'exclude'   => true,
            'filter'    => true,
            'eval'      => ['maxlength' => 64, 'tl_class' => 'w50', 'includeBlankOption' => true],
            'sql'       => ['type' => 'string', 'length' => 64, 'default' => '', 'notnull' => true],
        ],
        'filterRules'    => [
            'inputType' => 'checkbox',
            'exclude'   => true,
            'reference' => &$GLOBALS['TL_LANG']['tl_cowegis_map_layer']['rules'],
            'eval'      => ['multiple' => true, 'tl_class' => 'clr', 'helpwizard' => true],
            'sql'       => ['type' => 'blob', 'notnull' => false, 'default' => null],
        ],
        'initialVisible' => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'filter'    => true,
            'default'   => true,
            'eval'      => ['tl_class' => 'w50 ,12'],
            'sql'       => ['type' => 'boolean', 'default' => 1],
        ],
        'adjustBounds' => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'filter'    => true,
            'default'   => false,
            'eval'      => ['tl_class' => 'w50 ,12'],
            'sql'       => ['type' => 'boolean', 'default' => 0],
        ],
        'active'         => [
            'exclude'   => true,
            'inputType' => 'checkbox',
            'filter'    => true,
            'eval'      => ['tl_class' => 'clr w50'],
            'sql'       => ['type' => 'string', 'length' => '1', 'default' => ''],
//            'save_callback' => [
//                ['netzmacht.contao_leaflet.listeners.dca.leaflet', 'clearCache'],
//            ],
        ],
    ],
];
