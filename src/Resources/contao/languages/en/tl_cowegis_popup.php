<?php

declare(strict_types=1);

/**
 * Leaflet maps for Contao CMS.
 *
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2014-2017 netzmacht David Molineus. All rights reserved.
 * @license    LGPL-3.0 https://github.com/netzmacht/contao-leaflet-maps/blob/master/LICENSE
 * @filesource
 */

$GLOBALS['TL_LANG']['tl_cowegis_popup']['title_legend']  = 'Title';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['config_legend'] = 'Configuration';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['size_legend']   = 'Popup size';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['active_legend'] = 'Activation';

$GLOBALS['TL_LANG']['tl_cowegis_popup']['layersBtn'][0] = 'Manage layers';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['layersBtn'][1] = 'Manage leaflet layers';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['styles'][0]    = 'Manage styles';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['styles'][1]    = 'Manage vector styles';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['icons'][0]     = 'Manage icons';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['icons'][1]     = 'Manage marker icons';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['new'][0]       = 'Create popup';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['new'][1]       = 'Create new popup';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['edit'][0]      = 'Edit popup';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['edit'][1]      = 'Edit popup ID %s';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['copy'][0]      = 'Copy popup';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['copy'][1]      = 'Copy popup ID %s';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['controls'][0]  = 'Manage controls';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['controls'][1]  = 'Manage controls of popup ID %s';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['map'][0]       = 'Manage maps';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['map'][1]       = 'Manage leaflet maps.';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['delete'][0]    = 'Delete popup';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['delete'][1]    = 'Delete popup ID %s';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['show'][0]      = 'Show details';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['show'][1]      = 'Show popup ID %s details';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['toggle'][0]    = 'Toggle activation';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['toggle'][1]    = 'Toggle popup ID %s activation';

$GLOBALS['TL_LANG']['tl_cowegis_popup']['title'][0]            = 'Title';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['title'][1]            = 'Title of the icon.';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['alias'][0]            = 'Alias';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['alias'][1]            = 'Alias of the icon.';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['maxWidth'][0]         = 'Max width';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['maxWidth'][1]         = 'Max width of the popup.';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['minWidth'][0]         = 'Min width';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['minWidth'][1]         = 'Min width of the popup.';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['maxHeight'][0]        = 'Max height';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['maxHeight'][1]        = 'Max height of the popup.';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['autoClose'][0]        = 'Auto close';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['autoClose'][1]        = 'Disable it if you want to override the default behavior of the popup closing when another popup is opened.';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['closeOnEscapeKey'][0] = 'Close on escape key';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['closeOnEscapeKey'][1] = 'Disable it if you want to override the default behavior of the ESC key for closing of the popup.';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['closeButton'][0]      = 'Close button';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['closeButton'][1]      = 'Controls the presense of a close button in the popup.';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['closeOnClick'][0]     = 'Close on map click';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['closeOnClick'][1]     = 'Disable it if you want to override the default behavior of the popup closing when user clicks the map ';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['offset'][0]           = 'Offset';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['offset'][1]           = 'The offset of the popup position as comma separated point (e.g. 5,2)';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['keepInView'][0]       = 'Keep in view';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['keepInView'][1]       = 'Set it to true if you want to prevent users from panning the popup off of the screen while it is open.';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['className'][0]        = 'Class name';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['className'][1]        = 'A custom class name to assign to the popup.';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['autoPanPadding'][0]   = 'Auto pan padding';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['autoPanPadding'][1]   = 'The margin between the popup and the top left (first input) and the bottom right (second input) of the map view. Each value as comma separated point (e.g. 5,5)';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['autoPan'][0]          = 'Pan map';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['autoPan'][1]          = 'Automatically pan the map to display the popup.';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['active'][0]           = 'Activate popup';
$GLOBALS['TL_LANG']['tl_cowegis_popup']['active'][1]           = 'Only activated popups are assigned to the map object.';
