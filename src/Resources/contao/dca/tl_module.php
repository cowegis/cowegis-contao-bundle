<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_module']['metapalettes']['cowegis_map'] = [
    'type'      => ['name', 'headline', 'type'],
    'cowegis'   => ['cowegis_map', 'cowegis_mapId', 'cowegis_width', 'cowegis_height'],
    'templates' => [':hide', 'customTpl', 'cowegis_template'],
    'protected' => [':hide', 'protected'],
    'expert'    => [':hide', 'guests', 'cssID', 'space'],
    'invisible' => [':hide', 'invisible', 'start', 'start'],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cowegis_map'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_module']['cowegis_map'],
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

$GLOBALS['TL_DCA']['tl_module']['fields']['cowegis_mapId'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['cowegis_mapId'],
    'inputType' => 'text',
    'exclude'   => true,
    'eval'      => [
        'tl_class'  => 'w50',
        'maxlength' => 16,
    ],
    'sql'       => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cowegis_width'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['cowegis_width'],
    'inputType' => 'inputUnit',
    'options'   => $GLOBALS['TL_CSS_UNITS'],
    'search'    => false,
    'exclude'   => true,
    'eval'      => ['rgxp' => 'digit', 'tl_class' => 'clr w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];


$GLOBALS['TL_DCA']['tl_module']['fields']['cowegis_height'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['cowegis_height'],
    'inputType' => 'inputUnit',
    'options'   => $GLOBALS['TL_CSS_UNITS'],
    'search'    => false,
    'exclude'   => true,
    'eval'      => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];
