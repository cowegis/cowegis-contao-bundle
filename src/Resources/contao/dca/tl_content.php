<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_content']['metapalettes']['cowegis_map'] = [
    'type'      => ['type', 'headline'],
    'cowegis'   => ['cowegis_map', 'cowegis_mapId', 'cowegis_width', 'cowegis_height', 'cowegis_client'],
    'template'  => [':hide', 'customTpl', 'cowegis_template'],
    'protected' => [':hide', 'protected'],
    'expert'    => [':hide', 'guests', 'cssID', 'space'],
    'invisible' => [':hide', 'invisible', 'start', 'start'],
];

$GLOBALS['TL_DCA']['tl_content']['metasubselectpalettes']['cowegis_client']['custom'] = ['cowegis_client_custom'];

$GLOBALS['TL_DCA']['tl_content']['fields']['cowegis_map'] = [
    'inputType'  => 'select',
    'exclude'    => true,
    'eval'       => [
        'tl_class'           => 'w50 wizard',
        'includeBlankOption' => true,
        'chosen'             => true,
    ],
    'foreignKey' => 'tl_cowegis_map.title',
    'sql'        => "int(10) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['cowegis_mapId'] = [
    'inputType' => 'text',
    'exclude'   => true,
    'eval'      => [
        'tl_class'  => 'w50',
        'maxlength' => 16,
    ],
    'sql'       => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['cowegis_width'] = [
    'inputType' => 'inputUnit',
    'options'   => ['px', '%', 'em', 'rem'],
    'search'    => false,
    'exclude'   => true,
    'eval'      => ['rgxp' => 'digit', 'tl_class' => 'clr w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['cowegis_height'] = [
    'inputType' => 'inputUnit',
    'options'   => ['px', '%', 'em', 'rem'],
    'search'    => false,
    'exclude'   => true,
    'eval'      => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['cowegis_client'] = [
    'inputType' => 'select',
    'exclude'   => true,
    'eval'      => [
        'tl_class'           => 'w50 wizard',
        'includeBlankOption' => true,
        'chosen'             => true,
        'submitOnChange'     => true,
    ],
    'sql'       => "varchar(8) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['cowegis_client_custom'] = [
    'inputType' => 'fileTree',
    'search'    => false,
    'exclude'   => true,
    'eval'      => ['filesOnly' => true, 'tl_class' => 'clr long', 'mandatory' => true, 'extensions' => 'js'],
    'sql'       => 'binary(16) NULL',
];
