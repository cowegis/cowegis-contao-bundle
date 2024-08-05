<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_cowegis_control_layer'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'sql'           => [
            'keys' => [
                'id'      => 'primary',
                'cid,lid' => 'unique',
            ],
        ],
    ],

    'fields' => [
        'id'      => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'tstamp'  => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'sorting' => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'cid'     => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'lid'     => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'mode'    => ['sql' => "varchar(16) NOT NULL default ''"],
    ],
];
