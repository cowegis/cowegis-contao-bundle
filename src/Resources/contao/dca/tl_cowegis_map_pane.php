<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_cowegis_map_pane'] = [
    'config'       => [
        'dataContainer' => 'Table',
        'ptable'        => 'tl_cowegis_map',
        'sql'           => [
            'keys' => [
                'id'       => 'primary',
                'pid,name' => 'unique',
            ],
        ],
    ],
    'metapalettes' => [
        'default' => [
            'layer' => [
                'title',
                'name',
                'zIndex',
                'pointerEvents',
            ],
        ],
    ],
    'list'         => [
        'sorting'    => [
            'mode'         => 4,
            'fields'       => ['zIndex'],
            'headerFields' => ['title'],
        ],
        'operations' => [
            'edit'   => [
                'href' => 'act=edit',
                'icon' => 'header.gif',
            ],
            'copy'   => [
                'href' => 'act=copy',
                'icon' => 'copy.gif',
            ],
            'delete' => [
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm']
                    . '\'))return false;Backend.getScrollOffset()"',
            ],
            'show'   => [
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
        ],
    ],
    'fields'       => [
        'id'            => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid'           => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tstamp'        => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'sorting'       => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'title'         => [
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'name'          => [
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'clr w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'zIndex'        => [
            'exclude'   => true,
            'inputType' => 'text',
            'filter'    => true,
            'eval'      => ['tl_class' => 'w50', 'rgxp' => 'natural'],
            'sql'       => ['type' => 'integer', 'unsigned' => true, 'notnull' => false, 'default' => null],
        ],
        'pointerEvents' => [
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => ['auto', 'none'],
            'default'   => null,
            'search'    => true,
            'eval'      => [
                'helpwizard' => true,
                'maxlength'          => 255,
                'tl_class'           => 'clr w50',
                'includeBlankOption' => true,
            ],
            'sql'       => "char(4) default NULL",
        ],
    ],
];
