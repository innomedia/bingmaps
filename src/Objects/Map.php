<?php
namespace bingMap;

use bingMap\MapPosition;
use SilverStripe\Dev\Debug;
use SilverStripe\View\ViewableData;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Azure Maps integration class for SilverStripe
 * 
 * This class provides a comprehensive interface for creating and configuring Azure Maps
 * with support for markers, clustering, spatial data, and various interaction options.
 * 
 * Available interaction control methods:
 * - DisableMouseWheelZoom(): Disables scroll wheel zooming
 * - DisablePanning(): Disables map dragging/panning 
 * - DisableZooming(): Disables all zoom interactions
 * - DisableRotation(): Disables map rotation
 * - DisablePitching(): Disables 3D pitch/tilt
 * 
 * Available UI control methods:
 * - HideZoomButtons(): Hides zoom control buttons
 * - HideCompass(): Hides compass control
 * - HidePitchToggle(): Hides pitch toggle control  
 * - HideStylePicker(): Hides style picker control
 * 
 * Usage example:
 * $map = Map::createMap("myMap", "", true);
 * $map->SetZoom(10)
 *     ->DisableMouseWheelZoom()
 *     ->HideZoomButtons();
 * echo $map->XML_val("");
 */
class Map extends ViewableData
{
    use MapPosition;

    private $Debug = true;
    private $ID;
    private $Style;
    private $Height = 500;
    private $Width = 500;
    private $loadOnStartClass;
    private $IconPath = null;
    private $Base64Icon = null;
    private $CenterOnPins = true;
    private $Padding = 50;
    private $Markers = [];
    private $ScriptSettings = [];
    private $Zoom = null;
    private $MouseWheelZoom = null;
    private $MapType = null;
    private $ClusterLayer = false;
    private $SpatialDataService = false;
    private $SpatialDataServiceType = "PopulatedPlace";
    private $SpatialDataServicePostalCodes = null;
    private $PolygoneData = [];
    
    // Additional Azure Maps specific options
    private $DisablePanning = false;
    private $DisableZooming = false;
    private $DisableRotation = false;
    private $DisablePitching = false;
    private $ShowZoomButtons = true;
    private $ShowCompass = true;
    private $ShowPitchToggle = true;
    private $ShowStylePicker = true;
    private $ShowFullscreenControl = false;
    private $ZoomButtonsPosition = "top-right";
    private $CompassPosition = "bottom-right";
    private $MarkerAnchor = "bottom";
    private $IsUserCentrics = false;

    /*
      • AdminDivision1: First administrative level within the country/region level, such as a state or a province.
  • AdminDivision2: Second administrative level within the country/region level, such as a county.
  • CountryRegion: Country or region.
  • Neighborhood: A section of a populated place that is typically well-known, but often with indistinct boundaries.
  • PopulatedPlace: A concentrated area of human settlement, such as a city, town or village.
  • Postcode1: The smallest post code category, such as a zip code.
  • Postcode2: The next largest post code category after Postcode1 that is created by aggregating Postcode1 areas.
  • Postcode3: The next largest post code category after Postcode2 that is created by aggregating Postcode2 areas.
  • Postcode4: The next largest post code category after Postcode3 that is created by aggregating Postcode3 areas.

    */

    public function __construct($ID = "1", $loadOnStartClass = "", $Debug = false)
    {
        $this->Debug = $Debug;
        $this->loadOnStartClass = $loadOnStartClass;
        $this->ID = $ID;
    }
    
    public static function createMap($ID = "1", $loadOnStartClass = "", $Debug = false)
    {
        return new Map($ID, $loadOnStartClass, $Debug);
    }
    
    /**
     * Helper method to conditionally add console.log statements based on debug mode
     * @param string $message The message to log
     * @param string $type The console method type (console.log, console.warn, console.error)
     * @return string JavaScript console statement or empty string
     */
    private function debugLog($message, $type = "console.log")
    {
        if ($this->Debug) {
            return "{$type}('{$message}');\n";
        }
        return "";
    }
    public function SetIsUserCentrics($value)
    {
        $this->IsUserCentrics = $value;
        return $this;
    }
    public function SetCenterOnPins($value)
    {
        $this->CenterOnPins = $value;
        return $this;
    }
    public function setClusterLayer($value)
    {
        $this->ClusterLayer = $value;
        return $this;
    }
    public function setSpatialDataService($value)
    {
        $this->SpatialDataService = $value;
        return $this;
    }
    public function addScriptSetting($key,$value)
    {
        $this->ScriptSettings[$key] = $value; 
        return $this;
    }
    public function removeScriptSetting($key)
    {
        unset($this->ScriptSettings[$key]);
        return $this;
    }
    public function setSpatialDataServiceType($value)
    {
        $this->SpatialDataServiceType = $value;
        return $this;
    }
    public function setSpatialDataServicePostalCodes($value)
    {
        $this->SpatialDataServicePostalCodes = $value;
        return $this;
    }
    public function setPolygoneData($value)
    {
        $this->PolygoneData = $value;
        return $this;
    }
    public function SetZoom($value)
    {
        $this->Zoom = $value;
        return $this;
    }
    public function DisableMouseWheelZoom()
    {
        $this->MouseWheelZoom = true;
    }
    
    /**
     * Disable panning (dragging) on the map
     */
    public function DisablePanning()
    {
        $this->DisablePanning = true;
        return $this;
    }
    
    /**
     * Disable zooming functionality (both scroll wheel and zoom buttons)
     */
    public function DisableZooming()
    {
        $this->DisableZooming = true;
        return $this;
    }
    
    /**
     * Disable map rotation
     */
    public function DisableRotation()
    {
        $this->DisableRotation = true;
        return $this;
    }
    
    /**
     * Disable map pitching (3D tilt)
     */
    public function DisablePitching()
    {
        $this->DisablePitching = true;
        return $this;
    }
    
    /**
     * Hide zoom control buttons
     */
    public function HideZoomButtons()
    {
        $this->ShowZoomButtons = false;
        return $this;
    }
    
    /**
     * Hide compass control
     */
    public function HideCompass()
    {
        $this->ShowCompass = false;
        return $this;
    }
    
    /**
     * Hide pitch toggle control
     */
    public function HidePitchToggle()
    {
        $this->ShowPitchToggle = false;
        return $this;
    }
    
    /**
     * Hide style picker control
     */
    public function HideStylePicker()
    {
        $this->ShowStylePicker = false;
        return $this;
    }
    
    /**
     * Enable fullscreen control
     */
    public function ShowFullscreenControl()
    {
        $this->ShowFullscreenControl = true;
        return $this;
    }
    
    /**
     * Set position for zoom buttons control
     * @param string $position Position for zoom control ('top-left', 'top-right', 'bottom-left', 'bottom-right')
     */
    public function SetZoomButtonsPosition($position)
    {
        $this->ZoomButtonsPosition = $position;
        return $this;
    }
    
    /**
     * Set position for compass control
     * @param string $position Position for compass control ('top-left', 'top-right', 'bottom-left', 'bottom-right')
     */
    public function SetCompassPosition($position)
    {
        $this->CompassPosition = $position;
        return $this;
    }
    
    /**
     * Set marker icon anchor position
     * @param string $anchor Anchor position ('center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right')
     */
    public function SetMarkerAnchor($anchor)
    {
        $this->MarkerAnchor = $anchor;
        return $this;
    }
    public function SetIcon($IconPath)
    {
        $this->IconPath = $IconPath;
        return $this;
    }
    public function SetCenterOnPinsPadding($value)
    {
        $this->Padding = $value;
        return $this;
    }
    public function SetBase64Icon($Base64)
    {
        $this->Base64Icon = $Base64;
        return $this;
    }
    public function SetStyle($style)
    {
        $this->Style = $style;
        return $this;
    }
    public function SetHeight($pixel)
    {
        $this->Height = $pixel;
        return $this;
    }
    public function SetWidth($pixel)
    {
        $this->Width = $pixel;
        return $this;
    }
    //When Bing adds new Types not covered by Set[Type]Type Methods
    public function SetMapType($Type)
    {
        $this->MapType = $Type;
        return $this;
    }
    public function SetDarkMapType()
    {
        return $this->SetMapType("'dark'");
    }
    //Default when nothing is set
    public function SetLightMapType()
    {
        return $this->SetMapType("'road'");
    }
    public function SetGrayscaleMapType()
    {
        return $this->SetMapType("'grayscale_light'");
    }
    public function HasLoadOnStartClass()
    {
        return $this->loadOnStartClass != "";
    }
    public function AddMarker($marker)
    {
        array_push($this->Markers, $marker);
    }
    public function XML_val($field, $arguments = [], $cache = false)
    {
        $data = $this->getData();
        return $this->customise($data)->renderWith("bingMap");
    }
    private function getData()
    {
        return [
            "Script" => $this->RenderFunction(),
            "ID" => $this->ID,
            "Styles" => $this->Style,
            "IsUserCentrics" => $this->IsUserCentrics
        ];
    }
    public function GetLoadOnStartClass()
    {
        return $this->loadOnStartClass;
    }
    public static function GetIconVariable()
    {
        return "Icon";
    }
    private function RenderMarkers($mapVariable)
    {
        $rendered = "";
        $rendered .= $this->debugLog("Rendering " . count($this->Markers) . " markers...");
        
        if ($this->CenterOnPins == true || $this->ClusterLayer == true) {
            $rendered .= "var locs = [];\n";
        }
        
        // Create datasource and layer for non-clustered markers
        if ($this->ClusterLayer == false && count($this->Markers) > 0) {
            $rendered .= $this->debugLog("Creating dataSource for non-clustered markers...");
            $rendered .= "var dataSource = new atlas.source.DataSource();\n";
            $rendered .= "{$mapVariable}.sources.add(dataSource);\n";
            $rendered .= $this->debugLog("DataSource added to map");
            
            // Collect all unique custom icons that need to be loaded
            $customIcons = [];
            for ($i = 0; $i < count($this->Markers); $i++) {
                $marker = $this->Markers[$i];
                $iconPath = $marker->GetIconPath();
                if ($iconPath != null && !in_array($iconPath, $customIcons)) {
                    $customIcons[] = $iconPath;
                }
                $base64Icon = $marker->GetBase64Icon();
                if ($base64Icon != null && !in_array($base64Icon, $customIcons)) {
                    $customIcons[] = $base64Icon;
                }
            }
            
            // If there's a map-level icon, add it too
            if ($this->IconPath != null && !in_array($this->IconPath, $customIcons)) {
                $customIcons[] = $this->IconPath;
            }
            
            if (!empty($customIcons)) {
                $rendered .= $this->debugLog("Loading custom icons...");
                $rendered .= "var iconsToLoad = " . json_encode($customIcons) . ";\n";
                $rendered .= "var loadedIcons = 0;\n";
                $rendered .= "var totalIcons = iconsToLoad.length;\n";
                
                // Function to create the symbol layer after all icons are loaded
                $rendered .= "function createSymbolLayerAfterIconsLoaded() {\n";
                $rendered .= "    if (loadedIcons === totalIcons) {\n";
                $rendered .= "        " . ($this->Debug ? "console.log('All custom icons loaded, creating SymbolLayer...');\n" : "") . "";
                $rendered .= "        var symbolLayer = new atlas.layer.SymbolLayer(dataSource, null, {\n";
                $rendered .= "            iconOptions: {\n";
                $rendered .= "                image: ['case',\n";
                $rendered .= "                    ['has', 'iconUrl'], ['get', 'iconUrl'],\n";
                if ($this->IconPath != null) {
                    $rendered .= "                    'icon-" . md5($this->IconPath) . "'\n";
                } else {
                    $rendered .= "                    'pin-red'\n";
                }
                $rendered .= "                ],\n";
                $rendered .= "                anchor: '{$this->MarkerAnchor}',\n";
                $rendered .= "                allowOverlap: true,\n";
                $rendered .= "                size: 1.0\n";
                $rendered .= "            },\n";
                $rendered .= "            textOptions: {\n";
                $rendered .= "                textField: ['get', 'title'],\n";
                $rendered .= "                offset: [0, -2]\n";
                $rendered .= "            }\n";
                $rendered .= "        });\n";
                $rendered .= "        {$mapVariable}.layers.add(symbolLayer);\n";
                $rendered .= "        " . ($this->Debug ? "console.log('SymbolLayer added to map');\n" : "") . "";
                
                // Add click event handler
                $rendered .= "        {$mapVariable}.events.add('click', symbolLayer, function(e) {\n";
                $rendered .= "            if (e.shapes && e.shapes.length > 0) {\n";
                $rendered .= "                var shape = e.shapes[0];\n";
                $rendered .= "                var properties = shape.getProperties();\n";
                $rendered .= "                if (properties.popupContent) {\n";
                $rendered .= "                    showPopup(properties.popupContent, shape.getCoordinates());\n";
                $rendered .= "                }\n";
                $rendered .= "            }\n";
                $rendered .= "        });\n";
                $rendered .= "        " . ($this->Debug ? "console.log('Click event handler added for markers');\n" : "") . "";
                $rendered .= "    }\n";
                $rendered .= "}\n";
                
                // Load each custom icon
                foreach ($customIcons as $iconPath) {
                    $iconId = 'icon-' . md5($iconPath);
                    $rendered .= "{$mapVariable}.imageSprite.add('$iconId', '$iconPath').then(function() {\n";
                    $rendered .= "    " . ($this->Debug ? "console.log('Loaded custom icon: $iconPath');\n" : "") . "";
                    $rendered .= "    loadedIcons++;\n";
                    $rendered .= "    createSymbolLayerAfterIconsLoaded();\n";
                    $rendered .= "}, function(error) {\n";
                    $rendered .= "    console.error('Failed to load custom icon $iconPath:', error);\n";
                    $rendered .= "    loadedIcons++;\n";
                    $rendered .= "    createSymbolLayerAfterIconsLoaded();\n";
                    $rendered .= "});\n";
                }
            } else {
                // No custom icons, create symbol layer immediately with default icon
                $rendered .= $this->debugLog("No custom icons, creating SymbolLayer with default icon...");
                $rendered .= "var symbolLayer = new atlas.layer.SymbolLayer(dataSource, null, {\n";
                $rendered .= "    iconOptions: {\n";
                $rendered .= "        image: 'pin-red',\n";
                $rendered .= "        anchor: '{$this->MarkerAnchor}',\n";
                $rendered .= "        allowOverlap: true,\n";
                $rendered .= "        size: 1.0\n";
                $rendered .= "    },\n";
                $rendered .= "    textOptions: {\n";
                $rendered .= "        textField: ['get', 'title'],\n";
                $rendered .= "        offset: [0, -2]\n";
                $rendered .= "    }\n";
                $rendered .= "};\n";
                $rendered .= "{$mapVariable}.layers.add(symbolLayer);\n";
                $rendered .= $this->debugLog("SymbolLayer added to map");
                
                // Add click event handler
                $rendered .= "{$mapVariable}.events.add('click', symbolLayer, function(e) {\n";
                $rendered .= "    if (e.shapes && e.shapes.length > 0) {\n";
                $rendered .= "        var shape = e.shapes[0];\n";
                $rendered .= "        var properties = shape.getProperties();\n";
                $rendered .= "        if (properties.popupContent) {\n";
                $rendered .= "            showPopup(properties.popupContent, shape.getCoordinates());\n";
                $rendered .= "        }\n";
                $rendered .= "    }\n";
                $rendered .= "});\n";
                $rendered .= $this->debugLog("Click event handler added for markers");
            }
        }
        
        for ($i = 0; $i < count($this->Markers); $i++) {
            if($this->ClusterLayer == false)
            {
                $rendered .= $this->debugLog("Adding marker " . ($i + 1) . "...");
                $rendered .= $this->Markers[$i]->Render($mapVariable,$this->ClusterLayer);
            }
            
            // Always add locations for centering, regardless of cluster mode
            if ($this->CenterOnPins == true) {
                $loc = $this->Markers[$i]->RenderLocation();
                $rendered .= "locs.push($loc);\n";
            }
        }
        $rendered .= $this->debugLog("Markers rendering complete.");
        return $rendered;
    }
    private function RenderInfoBoxCloser()
    {
        $rendered = "";
        for ($i = 0; $i < count($this->Markers); $i++) {
            $rendered .= "function closePopup{$i}(){\n";
            $rendered .= "    if(InfoBoxCollection[$i]) InfoBoxCollection[$i].close();\n";
            $rendered .= "}\n";
        }
        return $rendered;
    }
    private function RenderMapCenteringOnPins($mapVariable)
    {
        if ($this->CenterOnPins == true && count($this->Markers) > 0) {
            return "{$mapVariable}.setCamera({\n
                bounds: atlas.data.BoundingBox.fromPositions(locs),\n
                padding: $this->Padding\n
            });\n";
        }
        return "";
    }
    private function RenderClusterLayer($mapVariable)
    {
        if ($this->ClusterLayer == true) {
            $rendered = "";
            
            // Collect all unique custom icons that need to be loaded for clustering
            $customIcons = [];
            for ($i = 0; $i < count($this->Markers); $i++) {
                $marker = $this->Markers[$i];
                $iconPath = $marker->GetIconPath();
                if ($iconPath != null && !in_array($iconPath, $customIcons)) {
                    $customIcons[] = $iconPath;
                }
                $base64Icon = $marker->GetBase64Icon();
                if ($base64Icon != null && !in_array($base64Icon, $customIcons)) {
                    $customIcons[] = $base64Icon;
                }
            }
            
            // If there's a map-level icon, add it too
            if ($this->IconPath != null && !in_array($this->IconPath, $customIcons)) {
                $customIcons[] = $this->IconPath;
            }
            
            // Create data source for clustering
            $rendered .= "var clusterDataSource = new atlas.source.DataSource(null, {
                cluster: true,
                clusterRadius: 80,
                clusterMaxZoom: 15,
                clusterProperties: {
                    'popupContent': [['get', 'popupContent'], ['literal', '']]
                }
            });\n";
            $rendered .= "{$mapVariable}.sources.add(clusterDataSource);\n";
            
            // Add markers to the data source
            $rendered .= "var clusterPoints = [];\n";
            for ($i = 0; $i < count($this->Markers); $i++) {
                $output = $this->Markers[$i]->RenderClusterMarker($mapVariable, true);
                $rendered .= $output["rendered"];
                $loc = $output["pushpinvariable"];
                $rendered .= "clusterPoints.push($loc);\n";
            }
            
            $rendered .= "clusterDataSource.add(clusterPoints);\n";
            if ($this->Debug) {
            if ($this->Debug) {
                $rendered .= "console.log('Added ' + clusterPoints.length + ' points to cluster data source');\n";
            }
            }
            
            // Create bubble layer for clusters with dynamic sizing
            $rendered .= "{$mapVariable}.layers.add(new atlas.layer.BubbleLayer(clusterDataSource, null, {
                radius: [
                    'step',
                    ['get', 'point_count'],
                    15,        // Default radius for small clusters
                    10, 20,    // If point_count >= 10, radius = 20
                    50, 30,    // If point_count >= 50, radius = 30
                    100, 40    // If point_count >= 100, radius = 40
                ],
                color: [
                    'step',
                    ['get', 'point_count'],
                    '#51bbd6',   // Default color for small clusters
                    10, '#f1f075',  // Yellow for medium clusters
                    50, '#f28cb1',  // Pink for large clusters
                    100, '#e55e5e'  // Red for very large clusters
                ],
                strokeColor: 'white',
                strokeWidth: 2,
                filter: ['has', 'point_count']
            }));\n";
            $rendered .= $this->debugLog("Bubble layer added for clusters");
            
            // Create symbol layer for cluster count labels
            $rendered .= "{$mapVariable}.layers.add(new atlas.layer.SymbolLayer(clusterDataSource, null, {
                iconOptions: {
                    image: 'none'
                },
                textOptions: {
                    textField: ['get', 'point_count_abbreviated'],
                    offset: [0, 0.4],
                    color: 'white',
                    font: ['StandardFont-Bold'],
                    size: 12
                },
                filter: ['has', 'point_count']
            }));\n";
            $rendered .= $this->debugLog("Symbol layer added for cluster counts");
            
            if (!empty($customIcons)) {
                $rendered .= $this->debugLog("Loading custom icons for clustering...");
                $rendered .= "var clusterIconsToLoad = " . json_encode($customIcons) . ";\n";
                $rendered .= "var clusterLoadedIcons = 0;\n";
                $rendered .= "var clusterTotalIcons = clusterIconsToLoad.length;\n";
                
                // Function to create unclustered layer after all icons are loaded
                $rendered .= "function createUnclusteredLayerAfterIconsLoaded() {\n";
                $rendered .= "    if (clusterLoadedIcons === clusterTotalIcons) {\n";
                $rendered .= "        " . ($this->Debug ? "console.log('All cluster icons loaded, creating unclustered layer...');\n" : "") . "";
                
                // Create symbol layer for individual points (unclustered)
                $rendered .= "        var unclusteredLayer = new atlas.layer.SymbolLayer(clusterDataSource, null, {\n";
                $rendered .= "            filter: ['!', ['has', 'point_count']],\n";
                $rendered .= "            iconOptions: {\n";
                $rendered .= "                image: ['case',\n";
                $rendered .= "                    ['has', 'iconUrl'], ['get', 'iconUrl'],\n";
                if ($this->IconPath != null) {
                    $iconId = 'icon-' . md5($this->IconPath);
                    $rendered .= "                    '$iconId'\n";
                } else {
                    $rendered .= "                    'pin-red'\n";
                }
                $rendered .= "                ],\n";
                $rendered .= "                size: 1.0,\n";
                $rendered .= "                anchor: '{$this->MarkerAnchor}'\n";
                $rendered .= "            }\n";
                $rendered .= "        });\n";
                $rendered .= "        {$mapVariable}.layers.add(unclusteredLayer);\n";
                $rendered .= "        " . ($this->Debug ? "console.log('Symbol layer added for individual points with custom icons');\n" : "") . "";
                $rendered .= "    }\n";
                $rendered .= "}\n";
                
                // Load each custom icon
                foreach ($customIcons as $iconPath) {
                    $iconId = 'icon-' . md5($iconPath);
                    $rendered .= "{$mapVariable}.imageSprite.add('$iconId', '$iconPath').then(function() {\n";
                    $rendered .= "    " . ($this->Debug ? "console.log('Loaded cluster icon: $iconPath');\n" : "") . "";
                    $rendered .= "    clusterLoadedIcons++;\n";
                    $rendered .= "    createUnclusteredLayerAfterIconsLoaded();\n";
                    $rendered .= "}, function(error) {\n";
                    $rendered .= "    console.error('Failed to load cluster icon $iconPath:', error);\n";
                    $rendered .= "    clusterLoadedIcons++;\n";
                    $rendered .= "    createUnclusteredLayerAfterIconsLoaded();\n";
                    $rendered .= "});\n";
                }
            } else {
                // No custom icons, create unclustered layer immediately with default icon
                $rendered .= "{$mapVariable}.layers.add(new atlas.layer.SymbolLayer(clusterDataSource, null, {\n";
                $rendered .= "    filter: ['!', ['has', 'point_count']],\n";
                $rendered .= "    iconOptions: {\n";
                $rendered .= "        image: 'pin-red',\n";
                $rendered .= "        size: 1.0,\n";
                $rendered .= "        anchor: '{$this->MarkerAnchor}'\n";
                $rendered .= "    }\n";
                $rendered .= "}));\n";
                $rendered .= $this->debugLog("Symbol layer added for individual points with default icon");
            }
            
            // Add click event for clusters to zoom in
            $rendered .= "{$mapVariable}.events.add('click', clusterDataSource, function(e) {\n";
            $rendered .= "    if (e.shapes && e.shapes.length > 0) {\n";
            $rendered .= "        var properties = e.shapes[0].getProperties();\n";
            $rendered .= "        if (properties.cluster) {\n";
            $rendered .= "            // This is a cluster, zoom in\n";
            $rendered .= "            {$mapVariable}.setCamera({\n";
            $rendered .= "                center: e.shapes[0].getCoordinates(),\n";
            $rendered .= "                zoom: {$mapVariable}.getCamera().zoom + 2\n";
            $rendered .= "            });\n";
            $rendered .= "        } else if (properties.popupContent) {\n";
            $rendered .= "            // This is an individual point with popup content\n";
            $rendered .= "            showPopup(properties.popupContent, e.shapes[0].getCoordinates());\n";
            $rendered .= "        }\n";
            $rendered .= "    }\n";
            $rendered .= "});\n";
            
            // Add mouse enter event to change cursor
            $rendered .= "{$mapVariable}.events.add('mouseenter', clusterDataSource, function() {\n";
            $rendered .= "    {$mapVariable}.getCanvasContainer().style.cursor = 'pointer';\n";
            $rendered .= "});\n";
            
            // Add mouse leave event to reset cursor
            $rendered .= "{$mapVariable}.events.add('mouseleave', clusterDataSource, function() {\n";
            $rendered .= "    {$mapVariable}.getCanvasContainer().style.cursor = 'grab';\n";
            $rendered .= "});\n";
            
            $rendered .= $this->debugLog("Azure Maps clustering setup complete - all layers and events configured");
            
            return $rendered;
        }
        return "";
    }
    private function RenderPolygones($mapVariable){
        if($this->PolygoneData && $this->PolygoneData !== '' && count($this->PolygoneData) > 0){
            $rendered = "";
            
            if ($this->Debug) {
                $rendered .= "console.log('Setting up polygon overlays - ' + " . count($this->PolygoneData) . " + ' polygons to render');\n";
            }
            
            // Create a single datasource for all custom polygons
            $rendered .= "var polygonDataSource = new atlas.source.DataSource('polygon-data-source');\n";
            $rendered .= "{$mapVariable}.sources.add(polygonDataSource);\n";
            
            // Collect all polygon features
            $rendered .= "var polygonFeatures = [];\n";
            
            for ($i = 0; $i < count($this->PolygoneData); $i++){
                if($this->PolygoneData[$i] !== ''){
                    $rendered .= "
                    // Polygon {$i}
                    var exteriorRing{$i} = [";
                        
                        $coordCount = 0;
                        for ($j = 0; $j < count($this->PolygoneData[$i]['Coords']); $j++){
                            if($this->PolygoneData[$i]['Coords'][$j] &&
                                $this->PolygoneData[$i]['Coords'][$j] !== '' &&
                                $this->PolygoneData[$i]['Coords'][$j]->IsValid()){
                                $rendered .= "[{$this->PolygoneData[$i]['Coords'][$j]->GetLongitude()}, {$this->PolygoneData[$i]['Coords'][$j]->GetLatitude()}],";
                                $coordCount++;
                            }
                        }
                        
                        $rendered .= "];
                    
                    if (exteriorRing{$i}.length >= 3) {
                        var polygon{$i} = new atlas.data.Polygon([exteriorRing{$i}]);
                        var polygonFeature{$i} = new atlas.data.Feature(polygon{$i}, {
                            polygonId: {$i},
                            fillColor: '{$this->PolygoneData[$i]['Colors']['Background']}',
                            strokeColor: '{$this->PolygoneData[$i]['Colors']['Stroke']}',
                            source: 'custom-polygon'
                        });
                        polygonFeatures.push(polygonFeature{$i});
                    } else {
                        console.warn('Polygon {$i} has insufficient coordinates (' + exteriorRing{$i}.length + '), skipping');
                    }
                    ";
                }
            }
            
            // Add all polygon features to the datasource at once
            $rendered .= "
            if (polygonFeatures.length > 0) {
                polygonDataSource.add(polygonFeatures);
                
                // Create polygon layer with data-driven styling
                var polygonLayer = new atlas.layer.PolygonLayer(polygonDataSource, 'custom-polygon-layer', {
                    fillColor: [
                        'case',
                        ['has', 'fillColor'],
                        ['get', 'fillColor'],
                        'rgba(13, 66, 104, 0.5)' // default fill color
                    ],
                    strokeColor: [
                        'case',
                        ['has', 'strokeColor'],
                        ['get', 'strokeColor'],
                        '#0d4268' // default stroke color
                    ],
                    strokeWidth: 2,
                    strokeOpacity: 0.8,
                    fillOpacity: 0.6
                });
                
                {$mapVariable}.layers.add(polygonLayer);
                
                // Polygon click events disabled - no tooltips needed for custom polygons
                // If you need polygon interactions in the future, you can add click events here
            } else {" . $this->debugLog("No valid polygon features to add", "console.warn") . "
            }
            ";
            
            $rendered .= $this->debugLog("Polygon overlays setup complete");
            
            return $rendered;
        }
        return "";
    }

      private function RenderSpatialDataService($mapVariable)
    {
        if ($this->SpatialDataService == true) {
            
            // Fetch polygon data for markers and add to PolygoneData for rendering
            if (!empty($this->Markers)) {
                error_log("Fetching spatial polygons for " . count($this->Markers) . " markers (caching enabled)");
                
                // Initialize PolygoneData if not set
                if (!$this->PolygoneData) {
                    $this->PolygoneData = [];
                }
                
                $cacheHits = 0;
                $cacheMisses = 0;
                
                for ($i = 0; $i < count($this->Markers); $i++) {
                    $marker = $this->Markers[$i];
                    if ($marker->HasPosition()) {
                        $latitude = $marker->GetLatitude();
                        $longitude = $marker->GetLongitude();
                        
                        error_log("Processing polygon for marker $i at coordinates: $latitude, $longitude");
                        
                        // Try to get polygon data from cache first
                        $polygonResult = null;
                        $cachedData = $this->getPolygonFromCache($latitude, $longitude);
                        if ($cachedData && isset($cachedData['data'])) {
                            $polygonResult = $cachedData['data'];
                            $cacheHits++;
                            error_log("Using cached polygon data for marker $i");
                        } else {
                            $cacheMisses++;
                        }
                        
                        // If no cache hit, fetch from API
                        if (!$polygonResult) {
                            error_log("Fetching polygon from API for marker $i at coordinates: $latitude, $longitude");
                            $polygonResult = $this->getPolygonForCoordinate($latitude, $longitude);
                            // Cache the result if successful
                            if ($polygonResult) {
                                $this->savePolygonToCache($latitude, $longitude, $polygonResult);
                            }
                        }
                        
                        if ($polygonResult && isset($polygonResult['coordinates'])) {
                            // Convert polygon data to PolygoneData format
                            $polygonForMap = [
                                'Coords' => [],
                                'Colors' => [
                                    'Background' => $this->getPolygonColorByLevel($polygonResult['level'] ?? 'municipality'),
                                    'Stroke' => $this->getPolygonStrokeColorByLevel($polygonResult['level'] ?? 'municipality')
                                ],
                                'Source' => 'spatial-data-service',
                                'EntityType' => $polygonResult['entityType'] ?? 'Unknown',
                                'Level' => $polygonResult['level'] ?? 'municipality',
                                'Name' => $polygonResult['name'] ?? 'Unknown Area',
                                'GeometryId' => $polygonResult['geometryId'] ?? ''
                            ];
                            
                            // Convert coordinates to coordinate objects
                            foreach ($polygonResult['coordinates'] as $coord) {
                                // Create a simple coordinate object that implements the required methods
                                $coordObj = new class($coord['lat'], $coord['lng']) {
                                    private $lat, $lng;
                                    
                                    public function __construct($lat, $lng) {
                                        $this->lat = $lat;
                                        $this->lng = $lng;
                                    }
                                    
                                    public function GetLatitude() { return $this->lat; }
                                    public function GetLongitude() { return $this->lng; }
                                    public function IsValid() { return !empty($this->lat) && !empty($this->lng); }
                                };
                                
                                $polygonForMap['Coords'][] = $coordObj;
                            }
                            
                            // Add to PolygoneData array
                            $this->PolygoneData[] = $polygonForMap;
                            
                            error_log("Added " . $polygonResult['level'] . " polygon '" . $polygonResult['name'] . "' with " . count($polygonResult['coordinates']) . " points");
                            
                            if ($this->Debug) {
                                error_log("Spatial polygon added - Level: " . $polygonResult['level'] . ", Entity: " . $polygonResult['entityType'] . ", Points: " . count($polygonResult['coordinates']));
                            }
                        } else {
                            error_log("No polygon found for marker $i at coordinates: $latitude, $longitude");
                        }
                    } else {
                        error_log("Marker $i has no valid position");
                    }
                }
                
                error_log("Spatial data service processing complete. Total polygons in PolygoneData: " . count($this->PolygoneData));
                error_log("Cache performance - Hits: $cacheHits, Misses: $cacheMisses, Hit Rate: " . 
                         ($cacheHits + $cacheMisses > 0 ? round(($cacheHits / ($cacheHits + $cacheMisses)) * 100, 1) : 0) . "%");
            } else {
                error_log("No markers found for spatial data service");
            }
            
            // Return empty string as polygons will be rendered by RenderPolygones
            return "";
        }
        return "";
    }
    
    private function RenderIcon()
    {
        if ($this->IconPath != null) {
            $iconvariable = Self::GetIconVariable();
            return "var $iconvariable = '$this->IconPath';";
        }
        if ($this->Base64Icon != null) {
            $iconvariable = Self::GetIconVariable();
            return "var $iconvariable = '$this->Base64Icon';";
        }
        return "";
    }

    public function RenderZoom()
    {
        if ($this->Zoom != null) {
            return ",\n                zoom: " . $this->Zoom;
        }
        return "";
    }
    
    public function RenderZoomValue()
    {
        if ($this->Zoom != null) {
            return $this->Zoom;
        }
        return 10;
    }
    public function RenderMapTypeID()
    {
        if($this->MapType != null && $this->MapType != "")
        {
            return ",\n                style: ".$this->MapType;
        }
        return "";
        
    }

    public function RenderOptions($mapVariable)
    {
        $script = "";
        
        // Azure Maps doesn't have setOptions() like Bing Maps
        // Instead, we need to use specific methods after map creation
        
        // Handle user interaction options
        $userInteractionOptions = [];
        
        if ($this->MouseWheelZoom != null) {
            $userInteractionOptions['scrollZoomInteraction'] = false;
        }
        
        if ($this->DisablePanning) {
            $userInteractionOptions['dragPanInteraction'] = false;
        }
        
        if ($this->DisableZooming) {
            $userInteractionOptions['scrollZoomInteraction'] = false;
            $userInteractionOptions['dblClickZoomInteraction'] = false;
        }
        
        if ($this->DisableRotation) {
            $userInteractionOptions['dragRotateInteraction'] = false;
            $userInteractionOptions['keyboardInteraction'] = false;
        }
        
        if ($this->DisablePitching) {
            $userInteractionOptions['touchInteraction'] = false;
        }
        
        // Apply user interaction options if any are set
        if (!empty($userInteractionOptions)) {
            $script .= "{$mapVariable}.setUserInteraction({\n";
            $optionPairs = [];
            foreach ($userInteractionOptions as $key => $value) {
                $optionPairs[] = "    {$key}: " . ($value ? 'true' : 'false');
            }
            $script .= implode(",\n", $optionPairs) . "\n";
            $script .= "});\n";
            $script .= $this->debugLog("User interaction options applied");
        }
        
        // Handle control visibility - add controls that should be shown
        $script .= $this->debugLog("Adding Azure Maps controls");
        
        // Add map controls
        $script .= $this->AddCustomControls($mapVariable);
        
        return $script;
    }
    
    /**
     * Get map constructor options for Azure Maps
     * These options need to be set during map creation, not after
     * @return string JSON-formatted options for map constructor
     */
    public function GetMapConstructorOptions()
    {
        $options = [];
        
        // Add any options that need to be set during map creation
        // Most interaction and control options are better set after creation
        
        return !empty($options) ? ',' . json_encode($options, JSON_UNESCAPED_SLASHES) : '';
    }   
    public function RenderFunction()
    {
        $rendered = "";
        $Attributes = "";
        if(count($this->ScriptSettings) > 0)
        {
            foreach($this->ScriptSettings as $key => $value)
            {
                $Attributes .= $key.'="'.$value.'" ';
            }
        }
        if($this->IsUserCentrics)
        {
            $Attributes .= 'data-usercentrics="Azure Maps" type="text/plain" ';
        }
        if ($this->loadOnStartClass != "" || $Attributes != "") {
            $rendered .= "<script class='$this->loadOnStartClass' $Attributes>\n";
        } else {
            $rendered .= "<script type='text/javascript'>\n";
        }
        
        $rendered .= "var InfoBoxCollection = [];\n";
        $rendered .= $this->debugLog("Starting Azure Maps initialization for map ID: {$this->ID}");
        
        // Function to dynamically load Azure Maps CSS
        $rendered .= "function loadAzureMapCSS() {\n";
        $rendered .= "    // Check if CSS is already loaded\n";
        $rendered .= "    var existingLink = document.querySelector('link[href*=\"atlas.min.css\"]');\n";
        $rendered .= "    if (!existingLink) {\n";
        $rendered .= "        var link = document.createElement('link');\n";
        $rendered .= "        link.rel = 'stylesheet';\n";
        $rendered .= "        link.type = 'text/css';\n";
        $rendered .= "        link.href = 'https://atlas.microsoft.com/sdk/javascript/mapcontrol/2/atlas.min.css';\n";
        $rendered .= "        document.head.appendChild(link);\n";
        $rendered .= "        " . ($this->Debug ? "console.log('Azure Maps CSS loaded dynamically');\n" : "") . "";
        $rendered .= "    }\n";
        $rendered .= "}\n";
        $rendered .= "\n";
        $rendered .= "// Load CSS immediately\n";
        $rendered .= "loadAzureMapCSS();\n";
        $rendered .= "\n";
        
        // WebGL detection function
        $rendered .= "function checkWebGLSupport() {\n";
        $rendered .= "    try {\n";
        $rendered .= "        var canvas = document.createElement('canvas');\n";
        $rendered .= "        var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');\n";
        $rendered .= "        return !!gl;\n";
        $rendered .= "    } catch (e) {\n";
        $rendered .= "        console.error('WebGL check failed:', e);\n";
        $rendered .= "        return false;\n";
        $rendered .= "    }\n";
        $rendered .= "}\n";
        
        // Function to show error message
        $rendered .= "function showMapError(message) {\n";
        $rendered .= "    console.error('Map Error: ' + message);\n";
        $rendered .= "    var container = document.getElementById('MapContainer{$this->ID}');\n";
        $rendered .= "    if (container) {\n";
        $rendered .= "        container.innerHTML = '<div style=\"padding: 20px; text-align: center; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; color: #6c757d;\">' +\n";
        $rendered .= "                              '<strong>Map Error:</strong><br>' + message + '</div>';\n";
        $rendered .= "    }\n";
        $rendered .= "}\n";
        
        // Check if API key is available
        $apiKey = SiteConfig::current_site_config()->bingAPIKey ?? '';
        if (empty($apiKey)) {
            $rendered .= "showMapError('Azure Maps API key is not configured. Please contact the administrator.');\n";
        } else {
            $rendered .= $this->debugLog("API key found, length: " . strlen($apiKey));
            
            // Check WebGL support before initializing Azure Maps
            $rendered .= "if (checkWebGLSupport()) {\n";
            $rendered .= "    " . ($this->Debug ? "console.log('WebGL supported, initializing Azure Maps');\n" : "") . "";
            $rendered .= $this->RenderAzureMap();
            $rendered .= "} else {\n";
            $rendered .= "    showMapError('WebGL is not supported in your browser. Azure Maps requires WebGL to function properly.');\n";
            $rendered .= "}\n";
        }
        
        $rendered .= "</script>\n";
        /*if (!$this->Debug) {
            $rendered = HelperMethods::MinifyString($rendered);
        } else {
            $rendered = HelperMethods::RemoveEmptyLines($rendered);
        }*/

        return $rendered;
    }
    
    private function RenderAzureMap()
    {
        $rendered = "";
        $rendered .= "function GetMap{$this->ID}(){\n";
        $mapVariable = "map" . $this->ID;

        // Check if atlas library is loaded before trying to create map
        $rendered .= "
            // Check if Azure Maps atlas library is available
            if (typeof atlas === 'undefined') {
                console.error('Azure Maps atlas library is not loaded');
                showMapError('Azure Maps library is not available. Please check your internet connection and try refreshing the page.');
                return;
            }
            
            " . ($this->Debug ? "console.log('Atlas library available, creating Azure Map...');\n" : "") . "
            try {
                var $mapVariable = new atlas.Map('MapContainer{$this->ID}',{
                    center:{$this->RenderLocation()}{$this->RenderZoom()}{$this->RenderMapTypeID()},
                    authOptions: {
                        authType: 'subscriptionKey',
                        subscriptionKey: '" . SiteConfig::current_site_config()->bingAPIKey . "'
                    }
                });
                " . ($this->Debug ? "console.log('Map created, waiting for ready event...');\n" : "") . "
            } catch (error) {
                console.error('Failed to create Azure Map:', error);
                showMapError('Failed to initialize Azure Maps. Please check your internet connection and try again.');
                return;
            }
        ";
        
        // Wait for map to be ready before adding content
        $rendered .= "var mapReadyTimeout = setTimeout(function() {\n";
        $rendered .= "    showMapError('Map is taking too long to load. Please check your internet connection and try again.');\n";
        $rendered .= "}, 10000);\n"; // 10 second timeout
        
        $rendered .= "{$mapVariable}.events.add('ready', function() {\n";
        $rendered .= "clearTimeout(mapReadyTimeout);\n";
        $rendered .= $this->debugLog("Map is ready! Adding content...");
        
        // Create a global popup variable to ensure only one popup is open at a time
        $rendered .= "var globalPopup = null;\n";
        $rendered .= $this->debugLog("Global popup variable initialized");
        
        // Helper function to show popup (closes existing popup first)
        $rendered .= "function showPopup(content, position) {\n";
        $rendered .= "    // Close existing popup if open\n";
        $rendered .= "    if (globalPopup) {\n";
        $rendered .= "        globalPopup.close();\n";
        $rendered .= "        globalPopup = null;\n";
        $rendered .= "    }\n";
        $rendered .= "    // Create and open new popup\n";
        $rendered .= "    globalPopup = new atlas.Popup({\n";
        $rendered .= "        content: content,\n";
        $rendered .= "        position: position\n";
        $rendered .= "    });\n";
        $rendered .= "    globalPopup.open({$mapVariable});\n";
        $rendered .= "}\n";
        $rendered .= $this->debugLog("Popup helper function defined");
        
        $rendered .= $this->RenderOptions($mapVariable);
        $rendered .= $this->RenderIcon();
        $rendered .= $this->RenderMarkers($mapVariable);
        $rendered .= $this->RenderMapCenteringOnPins($mapVariable);
        $rendered .= $this->RenderClusterLayer($mapVariable);
        //do not change the order RenderSpatialDataService must before RenderPolygones because it adds Polgyones
        $rendered .= $this->RenderSpatialDataService($mapVariable);
        $rendered .= $this->RenderPolygones($mapVariable);
        
        $rendered .= "});\n"; // Close the ready event
        
        $rendered .= $this->debugLog("Azure Maps setup complete.");
        $rendered .= "}\n";
        $rendered .= $this->RenderInfoBoxCloser();
        $rendered .= "GetMap{$this->ID}();\n";
        
        return $rendered;
    }
    
    private function GetMarkersData()
    {
        $MarkersData = [];
        $iconPath = $this->IconPath;
        foreach ($this->Markers as $Marker) {
            $MarkersData[] = $Marker->GetReactData($iconPath);
        }
        return $MarkersData;
    }
    public function GetReactData()
    {
        $data = [
            "key" => $this->ID,
            "loadOnStartClass" => $this->loadOnStartClass,
            "centerOnPins" => $this->CenterOnPins,
            "padding" => $this->Padding,
            "markers" => $this->GetMarkersData(),
            "zoom" => $this->Zoom,
            "position" => $this->Coords->GetReactData(),
        ];
        return $data;
    }
    public function GetJSONReactData()
    {
        return json_encode($this->GetReactData());
    }

    // Getter methods for debugging
    public function getID()
    {
        return $this->ID;
    }
    
    public function getClusterLayer()
    {
        return $this->ClusterLayer;
    }
    
    public function getMarkers()
    {
        return $this->Markers;
    }
    
    public function getIconPath()
    {
        return $this->IconPath;
    }
    
    /**
     * Get polygon outline for a coordinate using Azure Maps Search - Get Polygon API
     * @param float $latitude The latitude coordinate
     * @param float $longitude The longitude coordinate
     * @param array $options Optional parameters for the search
     * @return array|null Returns polygon data or null if not found
     */
    public function getPolygonForCoordinate($latitude, $longitude, $options = [])
    {
        $apiKey = SiteConfig::current_site_config()->bingAPIKey ?? '';
        
        if (!$apiKey) {
            error_log("Azure Maps API key not found for getPolygonForCoordinate");
            return null;
        }
        
        // Default options
        $defaultOptions = [
            'entityType' => 'Municipality,CountrySubdivision,CountrySecondarySubdivision', // City, State, County
            'returnGeometry' => true,
            'geometryFormat' => 'geojson'
        ];
        $options = array_merge($defaultOptions, $options);
        
        // Step 1: First get the address/place information for the coordinate
        $reverseGeoUrl = "https://atlas.microsoft.com/search/address/reverse/json";
        $reverseParams = [
            'api-version' => '1.0',
            'subscription-key' => $apiKey,
            'query' => $latitude . ',' . $longitude
        ];
        
        error_log("Getting address for coordinate: $latitude, $longitude");
        $reverseResponse = $this->makeHttpRequest($reverseGeoUrl, $reverseParams);
        if (!$reverseResponse || !isset($reverseResponse['addresses']) || empty($reverseResponse['addresses'])) {
            error_log("Failed to get address for coordinate: $latitude, $longitude");
            return null;
        }
        
        $address = $reverseResponse['addresses'][0];
        error_log("Found address: " . json_encode($address['address']));
        
        // Step 2: Try to get polygon using different administrative levels
        $polygonData = null;
        // Try municipality (city/village) first
        if (isset($address['address']['municipality'])) {
            $polygonData = $this->fetchPolygonByEntity($latitude, $longitude, 'Municipality', $apiKey);
            if ($polygonData) {
                $polygonData['level'] = 'municipality';
                $polygonData['name'] = $address['address']['municipality'];
            }
        }
        // If no municipality polygon, try county/subdivision
        if (!$polygonData && isset($address['address']['countrySubdivision'])) {
            $polygonData = $this->fetchPolygonByEntity($latitude, $longitude, 'CountrySubdivision', $apiKey);
            if ($polygonData) {
                $polygonData['level'] = 'state';
                $polygonData['name'] = $address['address']['countrySubdivision'];
            }
        }
        
        // If no subdivision polygon, try secondary subdivision (county)
        if (!$polygonData && isset($address['address']['countrySecondarySubdivision'])) {
            $polygonData = $this->fetchPolygonByEntity($latitude, $longitude, 'CountrySecondarySubdivision', $apiKey);
            if ($polygonData) {
                $polygonData['level'] = 'county';
                $polygonData['name'] = $address['address']['countrySecondarySubdivision'];
            }
        }
        
        // If still no polygon, try country level
        if (!$polygonData && isset($address['address']['country'])) {
            $polygonData = $this->fetchPolygonByEntity($latitude, $longitude, 'Country', $apiKey);
            if ($polygonData) {
                $polygonData['level'] = 'country';
                $polygonData['name'] = $address['address']['country'];
            }
        }
        
        if ($polygonData) {
            // Add additional address information
            $polygonData['address'] = $address['address'];
            $polygonData['coordinate'] = ['lat' => $latitude, 'lon' => $longitude];
            
            // Add map rendering data for easy integration
            $polygonData['mapData'] = $this->formatPolygonForMapRendering($polygonData);
            
            error_log("Successfully found polygon for coordinate at " . $polygonData['level'] . " level: " . $polygonData['name']);
            
            // Debug output (only in debug mode)
            if ($this->Debug) {
                error_log("Polygon data summary - Type: " . $polygonData['type'] . ", Points: " . count($polygonData['coordinates']) . ", Entity: " . $polygonData['entityType']);
            }
        } else {
            error_log("No polygon found for coordinate: $latitude, $longitude");
        }
        
        return $polygonData;
    }
    
    /**
     * Fetch polygon by entity type for a specific coordinate
     * @param float $latitude The latitude coordinate
     * @param float $longitude The longitude coordinate  
     * @param string $entityType The type of entity (Municipality, CountrySubdivision, etc.)
     * @param string $apiKey Azure Maps API key
     * @return array|null Returns polygon data or null if not found
     */
    private function fetchPolygonByEntity($latitude, $longitude, $entityType, $apiKey)
    {
        // Step 1: First do a reverse geocoding search to get the geometry ID
        $searchUrl = "https://atlas.microsoft.com/search/address/reverse/json";
        
        // Map entity types to search entity types for reverse geocoding
        $entityTypeMapping = [
            'Municipality' => 'Municipality',
            'CountrySubdivision' => 'CountrySubdivision', 
            'CountrySecondarySubdivision' => 'CountrySecondarySubdivision',
            'Country' => 'Country'
        ];
        
        $searchEntityType = $entityTypeMapping[$entityType] ?? 'Municipality';
        
        $searchParams = [
            'api-version' => '1.0',
            'subscription-key' => $apiKey,
            'query' => $latitude . ',' . $longitude,
            'entityType' => $searchEntityType,
            'returnGeometry' => 'true'
        ];
        
        error_log("Step 1: Searching for $entityType at coordinate: $latitude, $longitude");
        error_log("Search URL: " . $searchUrl . "?" . http_build_query($searchParams));
        
        $searchResponse = $this->makeHttpRequest($searchUrl, $searchParams, 'GET');
        
        if (!$searchResponse) {
            error_log("No response received from reverse geocoding API");
            return null;
        }
        
        // Check if we have results with geometry
        if (!isset($searchResponse['addresses']) || empty($searchResponse['addresses'])) {
            error_log("No addresses found in reverse geocoding response for $entityType");
            if (isset($searchResponse['summary'])) {
                error_log("Search summary: " . json_encode($searchResponse['summary']));
            }
            return null;
        }
        
        $geometryId = null;
        foreach ($searchResponse['addresses'] as $address) {
            if (isset($address['dataSources']['geometry']['id'])) {
                $geometryId = $address['dataSources']['geometry']['id'];
                error_log("Found geometry ID: $geometryId for $entityType");
                break;
            }
        }
        
        if (!$geometryId) {
            error_log("No geometry ID found in reverse geocoding response for $entityType");
            return null;
        }
        
        // Step 2: Use the geometry ID to fetch the polygon data
        $polygonUrl = "https://atlas.microsoft.com/search/polygon/json";
        
        $polygonParams = [
            'api-version' => '1.0',
            'subscription-key' => $apiKey,
            'geometries' => $geometryId
        ];
        
        error_log("Step 2: Fetching polygon data using geometry ID: $geometryId");
        error_log("Polygon URL: " . $polygonUrl . "?" . http_build_query($polygonParams));
        
        $polygonResponse = $this->makeHttpRequest($polygonUrl, $polygonParams, 'GET');
        if (!$polygonResponse) {
            error_log("No response received from polygon API");
            return null;
        }
        
        // Check if we have the expected response structure
        if (!isset($polygonResponse['additionalData']) || empty($polygonResponse['additionalData'])) {
            error_log("No additionalData found in polygon response for $entityType");
            error_log("Response keys: " . implode(', ', array_keys($polygonResponse)));
            return null;
        }
        
        $polygonData = $polygonResponse['additionalData'][0];
        
        // Check if we have geometry data
        if (!isset($polygonData['geometryData'])) {
            error_log("No geometryData found in polygon response");
            return null;
        }
        
        $geometryData = $polygonData['geometryData'];
        
        // Handle GeoJSON FeatureCollection format
        if (isset($geometryData['type']) && $geometryData['type'] === 'FeatureCollection' && isset($geometryData['features'])) {
            foreach ($geometryData['features'] as $feature) {
                if (isset($feature['geometry'])) {
                    $geoData = $feature['geometry'];
                    break;
                }
            }
        } elseif (isset($geometryData['geometry'])) {
            // Standard GeoJSON format
            $geoData = $geometryData['geometry'];
        } elseif (isset($geometryData['type']) && isset($geometryData['coordinates'])) {
            // Direct GeoJSON format
            $geoData = $geometryData;
        } else {
            error_log("Unknown geometry data format");
            error_log("GeometryData keys: " . implode(', ', array_keys($geometryData)));
            return null;
        }
        
        if (!isset($geoData)) {
            error_log("Could not extract geometry data from response");
            return null;
        }
        
        // Process the geometry data
        if ($geoData['type'] === 'Polygon' && isset($geoData['coordinates'])) {
            $coordinates = $geoData['coordinates'][0]; // Get outer ring
            
            // Convert to lat/lng format (Azure Maps returns lng/lat)
            $polygonPoints = [];
            foreach ($coordinates as $coord) {
                $polygonPoints[] = [
                    'lat' => $coord[1], 
                    'lng' => $coord[0]
                ];
            }
            
            error_log("Found $entityType polygon with " . count($polygonPoints) . " points");
            
            return [
                'type' => 'polygon',
                'entityType' => $entityType,
                'coordinates' => $polygonPoints,
                'rawGeometry' => $geoData,
                'properties' => isset($polygonData['properties']) ? $polygonData['properties'] : [],
                'geometryId' => $geometryId
            ];
        }
        elseif ($geoData['type'] === 'MultiPolygon' && isset($geoData['coordinates'])) {
            // Handle MultiPolygon - take the largest polygon
            $largestPolygon = null;
            $maxPoints = 0;
            
            foreach ($geoData['coordinates'] as $polygon) {
                $coordinates = $polygon[0]; // Get outer ring
                if (count($coordinates) > $maxPoints) {
                    $maxPoints = count($coordinates);
                    $largestPolygon = $coordinates;
                }
            }
            
            if ($largestPolygon) {
                $polygonPoints = [];
                foreach ($largestPolygon as $coord) {
                    $polygonPoints[] = [
                        'lat' => $coord[1], 
                        'lng' => $coord[0]
                    ];
                }
                
                error_log("Found $entityType MultiPolygon, using largest with " . count($polygonPoints) . " points");
                
                return [
                    'type' => 'multipolygon',
                    'entityType' => $entityType,
                    'coordinates' => $polygonPoints,
                    'rawGeometry' => $geoData,
                    'properties' => isset($polygonData['properties']) ? $polygonData['properties'] : [],
                    'geometryId' => $geometryId
                ];
            }
        }
        
        error_log("$entityType polygon found but could not extract coordinates");
        return null;
    }
    
    /**
     * Test Azure Maps API connectivity
     * @param string $apiKey Azure Maps API key to test
     * @return array Test results with status and messages
     */
    public function testAzureMapsConnectivity($apiKey)
    {
        $results = [
            'overall' => 'unknown',
            'tests' => []
        ];
        
        if (empty($apiKey)) {
            $results['overall'] = 'failed';
            $results['tests']['api_key'] = [
                'status' => 'failed',
                'message' => 'No API key provided'
            ];
            return $results;
        }
        
        $results['tests']['api_key'] = [
            'status' => 'passed',
            'message' => 'API key provided (length: ' . strlen($apiKey) . ' chars)'
        ];
        
        // Test 1: Basic search API connectivity
        try {
            $searchUrl = "https://atlas.microsoft.com/search/address/json";
            $searchParams = [
                'api-version' => '1.0',
                'subscription-key' => $apiKey,
                'query' => 'Berlin, Germany',
                'limit' => 1
            ];
            
            $response = $this->makeHttpRequest($searchUrl, $searchParams);
            if ($response && isset($response['results']) && !empty($response['results'])) {
                $results['tests']['search_api'] = [
                    'status' => 'passed',
                    'message' => 'Search API is accessible and working'
                ];
            } else {
                $results['tests']['search_api'] = [
                    'status' => 'failed',
                    'message' => 'Search API call failed or returned no results'
                ];
            }
        } catch (Exception $e) {
            $results['tests']['search_api'] = [
                'status' => 'failed',
                'message' => 'Search API error: ' . $e->getMessage()
            ];
        }
        
        // Test 2: Polygon API connectivity
        try {
            $polygonUrl = "https://atlas.microsoft.com/search/polygon";
            $polygonParams = [
                'api-version' => '1.0',
                'subscription-key' => $apiKey,
                'coordinates' => '13.4050,52.5200', // Berlin coordinates
                'geometries' => 'municipality'
            ];
            
            $response = $this->makeHttpRequest($polygonUrl, $polygonParams);
            if ($response && isset($response['geometries'])) {
                $results['tests']['polygon_api'] = [
                    'status' => 'passed',
                    'message' => 'Polygon API is accessible'
                ];
            } else {
                $results['tests']['polygon_api'] = [
                    'status' => 'failed',
                    'message' => 'Polygon API call failed or returned unexpected format'
                ];
            }
        } catch (Exception $e) {
            $results['tests']['polygon_api'] = [
                'status' => 'failed',
                'message' => 'Polygon API error: ' . $e->getMessage()
            ];
        }
        
        // Test 3: Postal code specific search
        try {
            $postalUrl = "https://atlas.microsoft.com/search/address/structured/json";
            $postalParams = [
                'api-version' => '1.0',
                'subscription-key' => $apiKey,
                'postalCode' => '10115',
                'countryCode' => 'DE',
                'limit' => 1
            ];
            
            $response = $this->makeHttpRequest($postalUrl, $postalParams);
            if ($response && isset($response['results']) && !empty($response['results'])) {
                $results['tests']['postal_search'] = [
                    'status' => 'passed',
                    'message' => 'Postal code search is working'
                ];
            } else {
                $results['tests']['postal_search'] = [
                    'status' => 'failed',
                    'message' => 'Postal code search failed'
                ];
            }
        } catch (Exception $e) {
            $results['tests']['postal_search'] = [
                'status' => 'failed',
                'message' => 'Postal code search error: ' . $e->getMessage()
            ];
        }
        
        // Determine overall status
        $passedTests = 0;
        $totalTests = count($results['tests']);
        foreach ($results['tests'] as $test) {
            if ($test['status'] === 'passed') {
                $passedTests++;
            }
        }
        
        if ($passedTests === $totalTests) {
            $results['overall'] = 'passed';
        } else if ($passedTests > 0) {
            $results['overall'] = 'partial';
        } else {
            $results['overall'] = 'failed';
        }
        
        return $results;
    }
    
    /**
     * Make HTTP request to external API
     * @param string $url The URL to request
     * @param array $params Query parameters to include in the request
     * @param string $method HTTP method (GET or POST)
     * @return array|null Decoded JSON response or null on failure
     */
    private function makeHttpRequest($url, $params = [], $method = 'GET',$debug = false)
    {
        // Build query string for GET requests or URL with params
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Azure Maps SilverStripe Integration/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json'
            ]
        ]);
        
        // Handle POST requests
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($params)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }
        }
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if($debug)
        {
            Debug::Dump(json_decode($response));die;
        }
        
        // Handle cURL errors
        if ($response === false || !empty($error)) {
            error_log("HTTP request failed: $error");
            return null;
        }
        
        // Handle non-200 HTTP status codes
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("HTTP request returned status code: $httpCode");
            error_log("Response: " . substr($response, 0, 500));
            return null;
        }
        
        // Decode JSON response
        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to decode JSON response: " . json_last_error_msg());
            error_log("Raw response: " . substr($response, 0, 500));
            return null;
        }
        
        return $decodedResponse;
    }
    
    /**
     * Add custom positioned controls to the Azure Map
     * @param string $mapVariable The map variable name
     * @return string JavaScript code to add controls
     */
    private function AddCustomControls($mapVariable)
    {
        $script = "";
        
        // Add default Azure Maps controls (they're not included automatically)
        
        if ($this->ShowZoomButtons) {
            $script .= "// Add zoom control\n";
            $script .= "{$mapVariable}.controls.add(new atlas.control.ZoomControl(), {\n";
            $script .= "    position: '{$this->ZoomButtonsPosition}'\n";
            $script .= "});\n";
        }
        
        if ($this->ShowCompass) {
            $script .= "// Add compass control\n";
            $script .= "{$mapVariable}.controls.add(new atlas.control.CompassControl(), {\n";
            $script .= "    position: '{$this->CompassPosition}'\n";
            $script .= "});\n";
        }
        
        if ($this->ShowPitchToggle) {
            $script .= "// Add pitch control\n";
            $script .= "{$mapVariable}.controls.add(new atlas.control.PitchControl(), {\n";
            $script .= "    position: 'bottom-left'\n";
            $script .= "});\n";
        }
        
        if ($this->ShowStylePicker) {
            $script .= "// Add style picker control\n";
            $script .= "{$mapVariable}.controls.add(new atlas.control.StyleControl({\n";
            $script .= "    mapStyles: ['road', 'grayscale_light', 'grayscale_dark', 'night', 'satellite', 'satellite_road_labels']\n";
            $script .= "}), {\n";
            $script .= "    position: 'top-left'\n";
            $script .= "});\n";
        }
        
        if ($this->ShowFullscreenControl) {
            $script .= "// Add fullscreen control\n";
            $script .= "{$mapVariable}.controls.add(new atlas.control.FullscreenControl(), {\n";
            $script .= "    position: 'top-right'\n";
            $script .= "});\n";
        }
        
        if (!empty($script)) {
            $script = "// Add map controls\n" . $script;
            $script .= $this->debugLog("Map controls added");
        }
        
        return $script;
    }
    
    /**
     * Format polygon data for map rendering integration
     * @param array $polygonData Raw polygon data from fetchPolygonByEntity
     * @return array Formatted data ready for map rendering
     */
    private function formatPolygonForMapRendering($polygonData)
    {
        if (!$polygonData || !isset($polygonData['coordinates'])) {
            return null;
        }
        
        // Convert coordinates to the format expected by Azure Maps
        $azureMapCoords = [];
        foreach ($polygonData['coordinates'] as $coord) {
            // Azure Maps expects [longitude, latitude] format
            $azureMapCoords[] = [$coord['lng'], $coord['lat']];
        }
        
        // Ensure polygon is closed (first point = last point)
        if (count($azureMapCoords) > 0) {
            $firstPoint = $azureMapCoords[0];
            $lastPoint = end($azureMapCoords);
            if ($firstPoint[0] !== $lastPoint[0] || $firstPoint[1] !== $lastPoint[1]) {
                $azureMapCoords[] = $firstPoint; // Close the polygon
            }
        }
        
        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => $polygonData['type'] === 'multipolygon' ? 'MultiPolygon' : 'Polygon',
                'coordinates' => $polygonData['type'] === 'multipolygon' ? [$azureMapCoords] : [$azureMapCoords]
            ],
            'properties' => array_merge($polygonData['properties'] ?? [], [
                'entityType' => $polygonData['entityType'],
                'level' => $polygonData['level'] ?? '',
                'name' => $polygonData['name'] ?? '',
                'geometryId' => $polygonData['geometryId'] ?? '',
                'fillColor' => $this->getPolygonColorByLevel($polygonData['level'] ?? ''),
                'strokeColor' => $this->getPolygonStrokeColorByLevel($polygonData['level'] ?? ''),
                'fillOpacity' => 0.3,
                'strokeWidth' => 2
            ])
        ];
    }
    
    /**
     * Get polygon fill color based on administrative level
     * @param string $level Administrative level (municipality, county, state, country)
     * @return string CSS color value
     */
    private function getPolygonColorByLevel($level)
    {
        switch (strtolower($level)) {
            case 'municipality':
                return 'rgba(13, 66, 104, 0.4)'; // Blue for cities/municipalities
            case 'county':
                return 'rgba(255, 165, 0, 0.4)'; // Orange for counties  
            case 'state':
                return 'rgba(46, 125, 50, 0.4)'; // Green for states
            case 'country':
                return 'rgba(156, 39, 176, 0.4)'; // Purple for countries
            default:
                return 'rgba(13, 66, 104, 0.4)'; // Default blue
        }
    }
    
    /**
     * Get polygon stroke color based on administrative level
     * @param string $level Administrative level (municipality, county, state, country)
     * @return string CSS color value
     */
    private function getPolygonStrokeColorByLevel($level)
    {
        switch (strtolower($level)) {
            case 'municipality':
                return '#0d4268'; // Dark blue for cities/municipalities
            case 'county':
                return '#ff6f00'; // Dark orange for counties
            case 'state':
                return '#2e7d32'; // Dark green for states
            case 'country':
                return '#7b1fa2'; // Dark purple for countries
            default:
                return '#0d4268'; // Default dark blue
        }
    }
    
    /**
     * Add a polygon from coordinate search results to the map
     * @param float $latitude The latitude coordinate to search
     * @param float $longitude The longitude coordinate to search
     * @param array $options Optional search options
     * @return bool True if polygon was found and added, false otherwise
     */
    public function addPolygonFromCoordinate($latitude, $longitude, $options = [])
    {
        $polygonData = $this->getPolygonForCoordinate($latitude, $longitude, $options);
        
        if (!$polygonData || !isset($polygonData['coordinates'])) {
            return false;
        }
        
        // Convert the polygon data to the format expected by setPolygoneData
        $polygonForMap = [
            'Coords' => []
        ];
        
        foreach ($polygonData['coordinates'] as $coord) {
            $polygonForMap['Coords'][] = [
                'lat' => $coord['lat'],
                'lng' => $coord['lng']
            ];
        }
        
        // Add to existing polygon data or create new array
        if (!$this->PolygoneData) {
            $this->PolygoneData = [];
        }
        
        $this->PolygoneData[] = $polygonForMap;
        
        // Log success
        if ($this->Debug) {
            error_log("Added " . $polygonData['level'] . " polygon '" . $polygonData['name'] . "' with " . count($polygonData['coordinates']) . " points to map");
        }
        
        return true;
    }
    
    /**
     * Clear all polygon data from the map
     */
    public function clearPolygons()
    {
        $this->PolygoneData = [];
        return $this;
    }
    
    /**
     * Get the current polygon data
     * @return array Current polygon data
     */
    public function getPolygonData()
    {
        return $this->PolygoneData ?? [];
    }

    // Cache configuration
    private $cacheDirectory = null;
    
    /**
     * Set custom cache directory for polygon data
     * @param string $directory Absolute path to cache directory
     * @return $this
     */
    public function setCacheDirectory($directory)
    {
        $this->cacheDirectory = $directory;
        return $this;
    }
    
    /**
     * Get the cache directory path
     * @return string Cache directory path
     */
    private function getCacheDirectory()
    {
        if ($this->cacheDirectory) {
            return $this->cacheDirectory;
        }
        
        // Use public folder with AzureMapCacheFolder directory
        $defaultCacheDir = dirname(__DIR__, 3) . '/public/AzureMapCacheFolder';
        
        // Create directory if it doesn't exist
        if (!is_dir($defaultCacheDir)) {
            if (!mkdir($defaultCacheDir, 0755, true)) {
                error_log("Failed to create polygon cache directory: $defaultCacheDir");
                return null;
            }
        }
        
        return $defaultCacheDir;
    }
    
    /**
     * Generate cache key for a coordinate
     * @param float $latitude Latitude coordinate
     * @param float $longitude Longitude coordinate
     * @param int $precision Decimal precision for coordinates (default: 4)
     * @return string Cache key
     */
    private function generateCacheKey($latitude, $longitude, $precision = 4)
    {
        // Round coordinates to reduce cache fragmentation
        $roundedLat = round($latitude, $precision);
        $roundedLng = round($longitude, $precision);
        
        return 'polygon_' . md5("{$roundedLat},{$roundedLng}");
    }
    
    /**
     * Get polygon data from cache
     * @param float $latitude Latitude coordinate
     * @param float $longitude Longitude coordinate
     * @return array|null Cached polygon data or null if not found
     */
    private function getPolygonFromCache($latitude, $longitude)
    {
        $cacheDir = $this->getCacheDirectory();
        if (!$cacheDir) {
            return null;
        }
        
        $cacheKey = $this->generateCacheKey($latitude, $longitude);
        $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
        
        if (!file_exists($cacheFile)) {
            if ($this->Debug) {
                error_log("Polygon cache miss for coordinates: $latitude, $longitude");
            }
            return null;
        }
        
        $cacheContent = file_get_contents($cacheFile);
        if ($cacheContent === false) {
            error_log("Failed to read polygon cache file: $cacheFile");
            return null;
        }
        
        $cachedData = json_decode($cacheContent, true);
        if ($cachedData === null) {
            error_log("Failed to decode polygon cache data: $cacheFile");
            unlink($cacheFile);
            return null;
        }
        
        if ($this->Debug) {
            error_log("Polygon cache hit for coordinates: $latitude, $longitude");
        }
        
        return $cachedData;
    }
    
    /**
     * Save polygon data to cache
     * @param float $latitude Latitude coordinate
     * @param float $longitude Longitude coordinate
     * @param array $polygonData Polygon data to cache
     * @return bool Success status
     */
    private function savePolygonToCache($latitude, $longitude, $polygonData)
    {
        $cacheDir = $this->getCacheDirectory();
        if (!$cacheDir) {
            return false;
        }
        $cacheKey = $this->generateCacheKey($latitude, $longitude);
        $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
        
        // Add cache metadata
        $cacheData = [
            'timestamp' => time(),
            'coordinates' => ['lat' => $latitude, 'lng' => $longitude],
            'data' => $polygonData
        ];
        
        $jsonData = json_encode($cacheData, JSON_PRETTY_PRINT);
        if ($jsonData === false) {
            error_log("Failed to encode polygon data for cache");
            return false;
        }
        
        $result = file_put_contents($cacheFile, $jsonData, LOCK_EX);
        if ($result === false) {
            error_log("Failed to write polygon cache file: $cacheFile");
            return false;
        }
        
        if ($this->Debug) {
            error_log("Polygon data cached for coordinates: $latitude, $longitude");
        }
        
        return true;
    }
    
    /**
     * Clear all polygon cache files
     * @return array Results with counts of deleted files and errors
     */
    public function clearPolygonCache()
    {
        $result = [
            'success' => false,
            'deleted_files' => 0,
            'errors' => 0,
            'message' => ''
        ];
        
        $cacheDir = $this->getCacheDirectory();
        if (!$cacheDir) {
            $result['message'] = 'Cache directory not accessible';
            return $result;
        }
        
        if (!is_dir($cacheDir)) {
            $result['success'] = true;
            $result['message'] = 'Cache directory does not exist';
            return $result;
        }
        
        $files = glob($cacheDir . '/polygon_*.json');
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $result['deleted_files']++;
            } else {
                $result['errors']++;
                error_log("Failed to delete cache file: $file");
            }
        }
        
        $result['success'] = true;
        $result['message'] = "Deleted {$result['deleted_files']} cache files";
        
        if ($result['errors'] > 0) {
            $result['message'] .= " with {$result['errors']} errors";
        }
        
        error_log("Polygon cache cleared: {$result['message']}");
        
        return $result;
    }
    
    /**
     * Clear polygon cache for specific coordinate
     * @param float $latitude Latitude coordinate
     * @param float $longitude Longitude coordinate
     * @return bool Success status
     */
    public function clearPolygonCacheForCoordinate($latitude, $longitude)
    {
        $cacheDir = $this->getCacheDirectory();
        if (!$cacheDir) {
            return false;
        }
        
        $cacheKey = $this->generateCacheKey($latitude, $longitude);
        $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
        
        if (file_exists($cacheFile)) {
            if (unlink($cacheFile)) {
                error_log("Cleared polygon cache for coordinates: $latitude, $longitude");
                return true;
            } else {
                error_log("Failed to clear polygon cache for coordinates: $latitude, $longitude");
                return false;
            }
        }
        
        return true; // File doesn't exist, consider it cleared
    }
    
    /**
     * Get cache statistics
     * @return array Cache statistics
     */
    public function getPolygonCacheStats()
    {
        $stats = [
            'directory' => $this->getCacheDirectory(),
            'total_files' => 0,
            'total_size_bytes' => 0,
            'oldest_file' => null,
            'newest_file' => null
        ];
        
        $cacheDir = $this->getCacheDirectory();
        if (!$cacheDir || !is_dir($cacheDir)) {
            return $stats;
        }
        
        $files = glob($cacheDir . '/polygon_*.json');
        $stats['total_files'] = count($files);
        
        $totalSize = 0;
        $oldestTime = null;
        $newestTime = null;
        
        foreach ($files as $file) {
            $size = filesize($file);
            $time = filemtime($file);
            
            $totalSize += $size;
            
            if ($oldestTime === null || $time < $oldestTime) {
                $oldestTime = $time;
            }
            
            if ($newestTime === null || $time > $newestTime) {
                $newestTime = $time;
            }
        }
        
        $stats['total_size_bytes'] = $totalSize;
        $stats['total_size_mb'] = round($totalSize / 1024 / 1024, 2);
        
        if ($oldestTime) {
            $stats['oldest_file'] = date('Y-m-d H:i:s', $oldestTime);
        }
        
        if ($newestTime) {
            $stats['newest_file'] = date('Y-m-d H:i:s', $newestTime);
        }
        
        return $stats;
    }
}
