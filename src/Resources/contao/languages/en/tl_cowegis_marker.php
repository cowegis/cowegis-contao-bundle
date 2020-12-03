<?php

/**
 * Leaflet maps for Contao CMS.
 *
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2014-2017 netzmacht David Molineus. All rights reserved.
 * @license    LGPL-3.0 https://github.com/netzmacht/contao-leaflet-maps/blob/master/LICENSE
 * @filesource
 */

$GLOBALS['TL_LANG']['tl_cowegis_marker']['title_legend']   = 'Title and type';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['popup_legend']   = 'Popup';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['tooltip_legend'] = 'Tooltip';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['config_legend']  = 'Configuration';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['active_legend']  = 'Activation';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['expert_legend']  = 'Expert settings';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['icon_legend']    = 'Icon';

$GLOBALS['TL_LANG']['tl_cowegis_marker']['new'][0]    = 'Create marker';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['new'][1]    = 'Create new marker';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['edit'][0]   = 'Edit marker';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['edit'][1]   = 'Edit marker ID %s';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['copy'][0]   = 'Copy marker';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['copy'][1]   = 'Copy marker ID %s';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['delete'][0] = 'Delete marker';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['delete'][1] = 'Delete marker ID %s';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['show'][0]   = 'Show details';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['show'][1]   = 'Show marker ID %s details';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['toggle'][0] = 'Toggle activation';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['toggle'][1] = 'Toggle marker ID %s activation';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['icons'][0]  = 'Manage icons';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['icons'][1]  = 'Manage marker icons.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['popups'][0] = 'Manage popups';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['popups'][1] = 'Manage popup options.';

$GLOBALS['TL_LANG']['tl_cowegis_marker']['title'][0]          = 'Title';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['title'][1]          = 'Title of the map.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['alias'][0]          = 'Alias';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['alias'][1]          = 'Alias of the map.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['coordinates'][0]    = 'Coordinates';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['coordinates'][1]    = 'Coordinates of the marker as comma separated value (Latitude, longitude [, altitude]).';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['tooltip'][0]        = 'Tooltip';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['tooltip'][1]        = 'Marker tooltip rendered as title attribute.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['alt'][0]            = 'Alternative text';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['alt'][1]            = 'Text for the alt attribute of the icon image (useful for accessibility).';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['addPopup'][0]       = 'Add popup';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['addPopup'][1]       = 'Add a popup for the marker';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['popup'][0]          = 'Popup preset';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['popup'][1]          = 'Choose a popup which options should be applied.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['popupContent'][0]   = 'Popup content';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['popupContent'][1]   = 'Content of the popup. Insert tags are replaced.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['draggable'][0]      = 'Draggable';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['draggable'][1]      = 'Whether the marker is draggable with mouse/touch or not.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['keyboard'][0]       = 'Keyboard navigation';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['keyboard'][1]       = 'Whether the marker can be tabbed to with a keyboard and clicked by pressing enter.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['zIndexOffset'][0]   = 'ZIndex offset';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['zIndexOffset'][1]   = 'By default, marker images zIndex is set automatically based on its latitude. Use this option if you want to put the marker on top of all others (or below), specifying a high value like 1000 (or high negative value, respectively).';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['customIcon'][0]     = 'Custom icon';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['customIcon'][1]     = 'Use a custom icon.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['icon'][0]           = 'Icon';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['icon'][1]           = 'Select a custom icon.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['active'][0]         = 'Activate marker';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['active'][1]         = 'Only activated markers are rendered on the map.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['featureData'][0]    = 'Feature data';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['featureData'][1]    = 'The marker is transferred as GeoJSON feature. These data is passed as <em>feature.properties.data</em>.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['markerSymbol'][0]   = 'Marker symbol';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['markerSymbol'][1]   = 'Override the marker symbol. Option is only applied if the layer uses an icon which supports custom marker symbols.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['options'][0]        = 'Extra options';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['options'][1]        = 'Add extra marker options as valid json. See <a href="https://leafletjs.com/reference.html#marker-option">https://leafletjs.com/reference-1.7.1.html#marker-option</a>.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['addTooltip'][0]     = 'Add tooltip';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['addTooltip'][1]     = 'Add a tooltip for this marker.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['tooltipContent'][0] = 'Tooltip content';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['tooltipContent'][1] = 'Content of the tooltip. Insert tags are replaced.';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['tooltipPreset'][0]  = 'Tooltip preset';
$GLOBALS['TL_LANG']['tl_cowegis_marker']['tooltipPreset'][1]  = 'Choose a tooltip preset.';
