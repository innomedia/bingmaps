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
                $rendered .= "                anchor: 'center',\n";
                $rendered .= "                allowOverlap: true,\n";
                $rendered .= "                size: 1.2\n";
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
                $rendered .= "                    var popup = new atlas.Popup({\n";
                $rendered .= "                        content: properties.popupContent,\n";
                $rendered .= "                        position: shape.getCoordinates()\n";
                $rendered .= "                    });\n";
                $rendered .= "                    popup.open({$mapVariable});\n";
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
                $rendered .= "        anchor: 'center',\n";
                $rendered .= "        allowOverlap: true,\n";
                $rendered .= "        size: 1.2\n";
                $rendered .= "    },\n";
                $rendered .= "    textOptions: {\n";
                $rendered .= "        textField: ['get', 'title'],\n";
                $rendered .= "        offset: [0, -2]\n";
                $rendered .= "    }\n";
                $rendered .= "});\n";
                $rendered .= "{$mapVariable}.layers.add(symbolLayer);\n";
                $rendered .= $this->debugLog("SymbolLayer added to map");
                
                // Add click event handler
                $rendered .= "{$mapVariable}.events.add('click', symbolLayer, function(e) {\n";
                $rendered .= "    if (e.shapes && e.shapes.length > 0) {\n";
                $rendered .= "        var shape = e.shapes[0];\n";
                $rendered .= "        var properties = shape.getProperties();\n";
                $rendered .= "        if (properties.popupContent) {\n";
                $rendered .= "            var popup = new atlas.Popup({\n";
                $rendered .= "                content: properties.popupContent,\n";
                $rendered .= "                position: shape.getCoordinates()\n";
                $rendered .= "            });\n";
                $rendered .= "            popup.open({$mapVariable});\n";
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
                $rendered .= "                anchor: 'center'\n";
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
                $rendered .= "        anchor: 'center'\n";
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
            $rendered .= "            var popup = new atlas.Popup({\n";
            $rendered .= "                content: properties.popupContent,\n";
            $rendered .= "                position: e.shapes[0].getCoordinates()\n";
            $rendered .= "            });\n";
            $rendered .= "            popup.open({$mapVariable});\n";
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
                
                // Add click event for polygon interactions
                {$mapVariable}.events.add('click', polygonLayer, function(e) {
                    if (e.shapes && e.shapes.length > 0) {
                        var properties = e.shapes[0].getProperties();
                        
                        var popup = new atlas.Popup({
                            content: '<div style=\"padding: 10px;\"><strong>Custom Polygon</strong><br/>ID: ' + (properties.polygonId !== undefined ? properties.polygonId : 'Unknown') + '</div>',
                            position: e.position
                        });
                        popup.open({$mapVariable});
                    }
                });
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

            $rendered = "";

            $rendered .= "var geoDataRequestOptions = {
                entityType: '".$this->SpatialDataServiceType."',
                getAllPolygons: true
            };\n";

            if($this->SpatialDataServicePostalCodes !== null) {
                $rendered .= "var polygonStyle = {
                    fillColor: 'rgba(13, 66, 104, 1)',
                    strokeColor: '#fff',
                    strokeThickness: 1
                };\n";
            }

            $rendered .= "Microsoft.Maps.loadModule('Microsoft.Maps.SpatialDataService',function () {";
                $rendered .= "var Locations = [];";
                if($this->SpatialDataServicePostalCodes !== null){
                    $rendered .= "Microsoft.Maps.SpatialDataService.GeoDataAPIManager.getBoundary(
                        ".$this->SpatialDataServicePostalCodes.",
                        geoDataRequestOptions,
                        {$mapVariable},
                        function (data) {
                            //Add the polygons to the map.
                            if (data.results && data.results.length > 0) {
                                {$mapVariable}.entities.push(data.results[0].Polygons);
                            }
                        }, polygonStyle);";
                } else {
                    for ($i = 0; $i < count($this->Markers); $i++) {
                        $rendered .= "Microsoft.Maps.SpatialDataService.GeoDataAPIManager.getBoundary(
                            ".$this->Markers[$i]->RenderLocation().",
                            geoDataRequestOptions,
                            {$mapVariable},
                            function (data) {
                                //Add the polygons to the map.
                                if (data.results && data.results.length > 0) {
                                    {$mapVariable}.entities.push(data.results[0].Polygons);
                                }
                            });";
                    }
                }
                /*
                    
                */
            $rendered .="});\n";
            return $rendered;
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
        
        // Handle control visibility
        if (!$this->ShowZoomButtons) {
            $script .= "{$mapVariable}.controls.remove('zoom');\n";
        }
        
        if (!$this->ShowCompass) {
            $script .= "{$mapVariable}.controls.remove('compass');\n";
        }
        
        if (!$this->ShowPitchToggle) {
            $script .= "{$mapVariable}.controls.remove('pitch');\n";
        }
        
        if (!$this->ShowStylePicker) {
            $script .= "{$mapVariable}.controls.remove('style');\n";
        }
        
        if (!$this->ShowZoomButtons || !$this->ShowCompass || !$this->ShowPitchToggle || !$this->ShowStylePicker) {
            $script .= $this->debugLog("Map controls configured");
        }
        
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
        if ($this->loadOnStartClass != "" || $Attributes != "") {
            $rendered .= "<script class='$this->loadOnStartClass' $Attributes>\n";
        } else {
            $rendered .= "<script type='text/javascript'>\n";
        }
        
        $rendered .= "var InfoBoxCollection = [];\n";
        $rendered .= $this->debugLog("Starting Azure Maps initialization for map ID: {$this->ID}");
        
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

        $rendered .= "
            " . ($this->Debug ? "console.log('Creating Azure Map...');\n" : "") . "
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
        
        $rendered .= $this->RenderOptions($mapVariable);
        $rendered .= $this->RenderIcon();
        $rendered .= $this->RenderMarkers($mapVariable);
        $rendered .= $this->RenderMapCenteringOnPins($mapVariable);
        $rendered .= $this->RenderClusterLayer($mapVariable);
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
            'query' => $latitude . ',' . $longitude,
            'returnSpeedLimit' => false,
            'returnRoadUse' => false,
            'allowFreeformNewline' => false
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
            error_log("Successfully found polygon for coordinate at " . $polygonData['level'] . " level: " . $polygonData['name']);
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
        $polygonUrl = "https://atlas.microsoft.com/search/polygon/json";
        $polygonParams = [
            'api-version' => '1.0',
            'subscription-key' => $apiKey,
            'coordinates' => $longitude . ',' . $latitude, // Note: longitude first for Azure Maps
            'entityType' => $entityType,
            'returnGeometry' => true
        ];
        
        error_log("Fetching $entityType polygon for coordinate: $latitude, $longitude");
        $response = $this->makeHttpRequest($polygonUrl, $polygonParams);
        
        if (!$response || !isset($response['geometries']) || empty($response['geometries'])) {
            error_log("No $entityType polygon found for coordinate");
            return null;
        }
        
        $geometry = $response['geometries'][0];
        
        // Extract polygon coordinates
        if (isset($geometry['geometryData']) && isset($geometry['geometryData']['geometry'])) {
            $geoData = $geometry['geometryData']['geometry'];
            
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
                    'properties' => isset($geometry['properties']) ? $geometry['properties'] : []
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
                        'properties' => isset($geometry['properties']) ? $geometry['properties'] : []
                    ];
                }
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
}
