<?php

/**
 * Leaflet maps for Contao CMS.
 *
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2014-2017 netzmacht David Molineus. All rights reserved.
 * @license    LGPL-3.0 https://github.com/netzmacht/contao-leaflet-maps/blob/master/LICENSE
 * @filesource
 */

$GLOBALS['TL_LANG']['cowegis_control']['zoom'][0]        = 'Zoom control';
$GLOBALS['TL_LANG']['cowegis_control']['zoom'][1]        = 'A basic zoom control. For more details read the <a href="http://leafletjs.com/reference.html#control-zoom" target="_blank">Leaflet documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_control']['attribution'][0] = 'Attribution control';
$GLOBALS['TL_LANG']['cowegis_control']['attribution'][1] = 'The attribution control allows you to display attribution data in a small text box on a map. For more details read the <a href="http://leafletjs.com/reference.html#control-attribution" target="_blank">Leaflet documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_control']['layers'][0]      = 'Layers control';
$GLOBALS['TL_LANG']['cowegis_control']['layers'][1]      = 'The layers control gives users the ability to switch between different base layers and switch overlays on/off. For more details read the <a href="http://leafletjs.com/reference.html#control-layers" target="_blank">Leaflet documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_control']['scale'][0]       = 'Scale control';
$GLOBALS['TL_LANG']['cowegis_control']['scale'][1]       = 'A simple scale control that shows the scale of the current center of the screen. For more details read the <a href="http://leafletjs.com/reference.html#control-scale" target="_blank">Leaflet documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_control']['loading'][0]     = 'Loading indicator';
$GLOBALS['TL_LANG']['cowegis_control']['loading'][1]     = 'Leaflet.loading is a simple loading indicator implemented as control. For more details read the <a href="https://github.com/ebrelsford/Leaflet.loading" target="_blank">Plugin documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_control']['fullscreen'][0]  = 'Fullscreen control';
$GLOBALS['TL_LANG']['cowegis_control']['fullscreen'][1]  = 'Add a fullscreen toggle button. For more details read the <a href="https://github.com/brunob/leaflet.fullscreen" target="_blank">plugin documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_control']['geocoder'][0]        = 'Geocoder';
$GLOBALS['TL_LANG']['cowegis_control']['geocoder'][1]        = 'A simple geocoder for Leaflet that by default uses OSM/Nominatim. For more details read the <a href="https://www.liedman.net/leaflet-control-geocoder/docs/" target="_blank">plugin documentation</a>.';

$GLOBALS['TL_LANG']['cowegis_layer']['provider'][0]      = 'Leaflet provider';
$GLOBALS['TL_LANG']['cowegis_layer']['provider'][1]      = 'Leaflet tile provider. For more details read the <a href="https://github.com/leaflet-extras/leaflet-providers" target="_blank">plugin documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_layer']['group'][0]         = 'Layer group';
$GLOBALS['TL_LANG']['cowegis_layer']['group'][1]         = 'Layer groups combines different layers. For more details read the <a href="http://leafletjs.com/reference.html#layergroup" target="_blank">Leaflet documentation</a>. ';
$GLOBALS['TL_LANG']['cowegis_layer']['markers'][0]       = 'Markers';
$GLOBALS['TL_LANG']['cowegis_layer']['markers'][1]       = 'Layer containing Markers.';
$GLOBALS['TL_LANG']['cowegis_layer']['vectors'][0]       = 'Vectors';
$GLOBALS['TL_LANG']['cowegis_layer']['vectors'][1]       = 'Vectors layer containing vectors like polygons, polylines, etc.';
$GLOBALS['TL_LANG']['cowegis_layer']['reference'][0]     = 'Reference';
$GLOBALS['TL_LANG']['cowegis_layer']['reference'][1]     = 'The reference layer is a link to another layer.';
$GLOBALS['TL_LANG']['cowegis_layer']['markercluster'][0] = 'Marker cluster';
$GLOBALS['TL_LANG']['cowegis_layer']['markercluster'][1] = 'Marker cluster layer based on <a href="https://github.com/Leaflet/Leaflet.markercluster" target="_blank">Leaflet.markercluster</a.';
$GLOBALS['TL_LANG']['cowegis_layer']['tileLayer'][0]     = 'Tile layer';
$GLOBALS['TL_LANG']['cowegis_layer']['tileLayer'][1]     = 'Tile layer with full config options.';
$GLOBALS['TL_LANG']['cowegis_layer']['overpass'][0]      = 'Overpass API';
$GLOBALS['TL_LANG']['cowegis_layer']['overpass'][1]      = 'Overpass API data layer.';
$GLOBALS['TL_LANG']['cowegis_layer']['file'][0]          = 'File';
$GLOBALS['TL_LANG']['cowegis_layer']['file'][1]          = 'Geo data from a file (gpx,kml,wkt).';

$GLOBALS['TL_LANG']['cowegis_vector']['polyline'][0]      = 'Polyline';
$GLOBALS['TL_LANG']['cowegis_vector']['polyline'][1]      = 'Polyline overlay. For more details read the <a href="http://leafletjs.com/reference.html#polyline" target="_blank">Leaflet documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_vector']['multiPolyline'][0] = 'Multi polylines';
$GLOBALS['TL_LANG']['cowegis_vector']['multiPolyline'][1] = 'Multi polylines with shared styling. For more details read the <a href="http://leafletjs.com/reference.html#multipolyline" target="_blank">Leaflet documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_vector']['polygon'][0]       = 'Polygon';
$GLOBALS['TL_LANG']['cowegis_vector']['polygon'][1]       = 'Polygon overlay. For more details read the <a href="http://leafletjs.com/reference.html#polygon" target="_blank">Leaflet documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_vector']['multiPolygon'][0]  = 'Multi polygons';
$GLOBALS['TL_LANG']['cowegis_vector']['multiPolygon'][1]  = 'Multi polygons with shared styling. For more details read the <a href="http://leafletjs.com/reference.html#multipolygon" target="_blank">Leaflet documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_vector']['circle'][0]        = 'Circle';
$GLOBALS['TL_LANG']['cowegis_vector']['circle'][1]        = 'Circle overlay. For more details read the <a href="http://leafletjs.com/reference.html#circle" target="_blank">Leaflet documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_vector']['circleMarker'][0]  = 'Circle marker';
$GLOBALS['TL_LANG']['cowegis_vector']['circleMarker'][1]  = 'Circle marker with fixed pixel width. For more details read the <a href="http://leafletjs.com/reference.html#circlemarker" target="_blank">Leaflet documentation</a>.';
$GLOBALS['TL_LANG']['cowegis_vector']['rectangle'][0]     = 'Rectangle';
$GLOBALS['TL_LANG']['cowegis_vector']['rectangle'][1]     = 'Rectangle overlay. For more details read the <a href="http://leafletjs.com/reference.html#rectangle" target="_blank">Leaflet documentation</a>.';

$GLOBALS['TL_LANG']['cowegis']['searchPosition'] = 'Search location';
$GLOBALS['TL_LANG']['cowegis']['applyPosition']  = 'Apply position';

$GLOBALS['TL_LANG']['cowegis']['invalidAlias']       = 'Invalid alias given. Alias may not be empty, begin with a numeric value nor contain any special chars (underscore allowed).';
$GLOBALS['TL_LANG']['cowegis']['invalidCoordinates'] = 'Invalid coordinate values given.';
