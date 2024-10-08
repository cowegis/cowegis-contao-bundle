<?php

declare(strict_types=1);

use Contao\ArrayUtil;

ArrayUtil::arrayInsert(
    $GLOBALS['BE_MOD'],
    1,
    [
        'cowegis' => [
            'cowegis_map' => [
                'tables' => [
                    'tl_cowegis_map',
                    'tl_cowegis_map_layer',
                    'tl_cowegis_map_pane',
                    'tl_cowegis_control',
                    'tl_cowegis_layer',
                ],
            ],
            'cowegis_layer' => [
                'tables' => [
                    'tl_cowegis_layer',
                    'tl_cowegis_marker',
                ],
            ],
            'cowegis_presets' => [
                'tables' => [
                    'tl_cowegis_icon',
                    'tl_cowegis_style',
                    'tl_cowegis_popup',
                    'tl_cowegis_tooltip',
                ],
            ],
        ],
    ],
);
