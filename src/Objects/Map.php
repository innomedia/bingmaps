<?php
namespace bingMap;

use bingMap\MapPosition;
use SilverStripe\Dev\Debug;
use SilverStripe\View\ViewableData;
use SilverStripe\SiteConfig\SiteConfig;

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
        $rendered .= "console.log('Rendering " . count($this->Markers) . " markers...');\n";
        
        if ($this->CenterOnPins == true || $this->ClusterLayer == true) {
            $rendered .= "var locs = [];\n";
        }
        
        for ($i = 0; $i < count($this->Markers); $i++) {
            if($this->ClusterLayer == false)
            {
                $rendered .= "console.log('Adding marker " . ($i + 1) . "...');\n";
                $rendered .= $this->Markers[$i]->Render($mapVariable,$this->ClusterLayer);
            }
            
            // Always add locations for centering, regardless of cluster mode
            if ($this->CenterOnPins == true) {
                $loc = $this->Markers[$i]->RenderLocation();
                $rendered .= "locs.push($loc);\n";
            }
        }
        $rendered .= "console.log('Markers rendering complete.');\n";
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
            $rendered .= "console.log('Added ' + clusterPoints.length + ' points to cluster data source');\n";
            
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
            $rendered .= "console.log('Bubble layer added for clusters');\n";
            
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
            $rendered .= "console.log('Symbol layer added for cluster counts');\n";
            
            // Create symbol layer for individual points (unclustered)
            $iconImage = 'pin-round-blue'; // Default icon
            if ($this->IconPath != null) {
                // If custom icon is set, we need to load it as an image template
                $rendered .= "
                // Load custom icon image and create symbol layer when ready
                {$mapVariable}.imageSprite.add('custom-marker', '{$this->IconPath}').then(function() {
                    console.log('Custom icon loaded successfully');
                    // Create symbol layer for individual points after icon is loaded
                    var unclusteredLayer = new atlas.layer.SymbolLayer(clusterDataSource, null, {
                        filter: ['!', ['has', 'point_count']],
                        iconOptions: {
                            image: 'custom-marker',
                            size: 1.0,
                            anchor: 'center'
                        }
                    });
                    {$mapVariable}.layers.add(unclusteredLayer);
                    console.log('Symbol layer added for individual points with custom icon');
                }, function(error) {
                    console.error('Failed to load custom icon:', error);
                    // Fallback to default icon if custom icon fails to load
                    var unclusteredLayer = new atlas.layer.SymbolLayer(clusterDataSource, null, {
                        filter: ['!', ['has', 'point_count']],
                        iconOptions: {
                            image: 'pin-round-blue',
                            size: 1.0,
                            anchor: 'center'
                        }
                    });
                    {$mapVariable}.layers.add(unclusteredLayer);
                    console.log('Symbol layer added for individual points with fallback icon');
                });
                ";
            } else {
                $rendered .= "{$mapVariable}.layers.add(new atlas.layer.SymbolLayer(clusterDataSource, null, {
                    filter: ['!', ['has', 'point_count']],
                    iconOptions: {
                        image: 'pin-round-blue',
                        size: 1.0,
                        anchor: 'center'
                    }
                }));\n";
                $rendered .= "console.log('Symbol layer added for individual points with default icon');\n";
            }
            
            // Add click event for clusters to zoom in
            $rendered .= "{$mapVariable}.events.add('click', clusterDataSource, function(e) {
                if (e.shapes && e.shapes.length > 0) {
                    var properties = e.shapes[0].getProperties();
                    if (properties.cluster) {
                        // This is a cluster, zoom in
                        {$mapVariable}.setCamera({
                            center: e.shapes[0].getCoordinates(),
                            zoom: {$mapVariable}.getCamera().zoom + 2
                        });
                    } else if (properties.popupContent) {
                        // This is an individual point with popup content
                        var popup = new atlas.Popup({
                            content: properties.popupContent,
                            position: e.shapes[0].getCoordinates()
                        });
                        popup.open({$mapVariable});
                    }
                }
            });\n";
            
            // Add mouse enter event to change cursor
            $rendered .= "{$mapVariable}.events.add('mouseenter', clusterDataSource, function() {
                {$mapVariable}.getCanvasContainer().style.cursor = 'pointer';
            });\n";
            
            // Add mouse leave event to reset cursor
            $rendered .= "{$mapVariable}.events.add('mouseleave', clusterDataSource, function() {
                {$mapVariable}.getCanvasContainer().style.cursor = 'grab';
            });\n";
            
            $rendered .= "console.log('Azure Maps clustering setup complete - all layers and events configured');\n";
            
            return $rendered;
        }
        return "";
    }
    private function RenderPolygones($mapVariable){
        if($this->PolygoneData && $this->PolygoneData !== '' && count($this->PolygoneData) > 0){
            $rendered = "";
            for ($i = 0; $i < count($this->PolygoneData); $i++){
                if($this->PolygoneData[$i] !== ''){
                    $rendered .= "
                    var exteriorRing = [
                    ";
                        for ($j = 0; $j < count($this->PolygoneData[$i]['Coords']); $j++){
                            if($this->PolygoneData[$i]['Coords'][$j] &&
                                $this->PolygoneData[$i]['Coords'][$j] !== '' &&
                                $this->PolygoneData[$i]['Coords'][$j]->IsValid()){
                                $rendered .= "[{$this->PolygoneData[$i]['Coords'][$j]->GetLongitude()}, {$this->PolygoneData[$i]['Coords'][$j]->GetLatitude()}],
                            ";
                            }
                        }
                        $rendered .= "
                    ];
                    
                    var polygon{$i} = new atlas.data.Polygon([exteriorRing]);
                    var polygonFeature{$i} = new atlas.data.Feature(polygon{$i}, {});
                    
                    var dataSource{$i} = new atlas.source.DataSource();
                    {$mapVariable}.sources.add(dataSource{$i});
                    dataSource{$i}.add(polygonFeature{$i});
                    
                    {$mapVariable}.layers.add(new atlas.layer.PolygonLayer(dataSource{$i}, null, {
                        fillColor: '{$this->PolygoneData[$i]['Colors']['Background']}',
                        strokeColor: '{$this->PolygoneData[$i]['Colors']['Stroke']}',
                        strokeWidth: 2
                    }));
                    ";
                }
            }
            return $rendered;
        }
        return "";
    }
    private function RenderSpatialDataService($mapVariable)
    {
        if ($this->SpatialDataService == true) {
            $rendered = "";
            
            // Create data source for boundary polygons
            $rendered .= "var boundaryDataSource = new atlas.source.DataSource();\n";
            $rendered .= "{$mapVariable}.sources.add(boundaryDataSource);\n";
            
            // Define polygon styling
            $rendered .= "var polygonLayerOptions = {
                fillColor: 'rgba(13, 66, 104, 0.5)',
                strokeColor: '#fff',
                strokeWidth: 1
            };\n";
            
            $rendered .= "{$mapVariable}.layers.add(new atlas.layer.PolygonLayer(boundaryDataSource, null, polygonLayerOptions));\n";
            
            // Function to fetch boundary data using Azure Maps Search API
            $rendered .= "function fetchBoundaryData(query, entityType) {
                var searchUrl = 'https://atlas.microsoft.com/search/address/json?api-version=1.0&subscription-key=' + 
                    '" . SiteConfig::current_site_config()->bingAPIKey . "' + 
                    '&query=' + encodeURIComponent(query) + 
                    '&entityType=' + entityType + 
                    '&limit=1';
                
                fetch(searchUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (data.results && data.results.length > 0) {
                            var result = data.results[0];
                            if (result.dataSources && result.dataSources.geometry) {
                                // Use the geometry ID to fetch polygon data
                                fetchPolygonData(result.dataSources.geometry.id);
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching boundary data:', error));
            }\n";
            
            // Function to fetch actual polygon geometry
            $rendered .= "function fetchPolygonData(geometryId) {
                var polygonUrl = 'https://atlas.microsoft.com/search/polygon?api-version=1.0&subscription-key=' + 
                    '" . SiteConfig::current_site_config()->bingAPIKey . "' + 
                    '&geometries=' + geometryId;
                
                fetch(polygonUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (data.additionalData && data.additionalData.length > 0) {
                            var polygonData = data.additionalData[0];
                            if (polygonData.geometryData) {
                                // Parse and add polygon to map
                                var coordinates = polygonData.geometryData.coordinates;
                                if (coordinates && coordinates.length > 0) {
                                    var polygon = new atlas.data.Polygon(coordinates);
                                    boundaryDataSource.add(new atlas.data.Feature(polygon));
                                }
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching polygon data:', error));
            }\n";
            
            // Execute boundary requests
            if($this->SpatialDataServicePostalCodes !== null) {
                $rendered .= "// Fetch boundary for postal code\n";
                $rendered .= "fetchBoundaryData('".$this->SpatialDataServicePostalCodes."', 'PostalCode');\n";
            } else {
                $rendered .= "// Fetch boundaries for marker locations\n";
                for ($i = 0; $i < count($this->Markers); $i++) {
                    $location = $this->Markers[$i]->RenderLocation();
                    $rendered .= "fetchBoundaryData($location, '".$this->SpatialDataServiceType."');\n";
                }
            }
            
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

        $optionsarray = [];
        //Remember to
        if ($this->MouseWheelZoom != null) {
            $optionsarray["disableScrollWheelZoom"] = "true";
        }
        $options = "";
        foreach ($optionsarray as $key => $value) {
            $options .= $key . ": " . $value . ",";
        }

        if ($options != "") {
            $options = substr($options, 0, -1);
            $script .= $mapVariable . ".setOptions({{$options}});\n";
        }
        return $script;
    }    public function RenderFunction()
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
        $rendered .= "console.log('Starting Azure Maps initialization for map ID: {$this->ID}');\n";
        
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
            $rendered .= "console.log('API key found, length: " . strlen($apiKey) . "');\n";
            
            // Check WebGL support before initializing Azure Maps
            $rendered .= "if (checkWebGLSupport()) {\n";
            $rendered .= "    console.log('WebGL supported, initializing Azure Maps');\n";
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
            console.log('Creating Azure Map...');
            try {
                var $mapVariable = new atlas.Map('MapContainer{$this->ID}',{
                    center:{$this->RenderLocation()}{$this->RenderZoom()}{$this->RenderMapTypeID()},
                    authOptions: {
                        authType: 'subscriptionKey',
                        subscriptionKey: '" . SiteConfig::current_site_config()->bingAPIKey . "'
                    }
                });
                console.log('Map created, waiting for ready event...');
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
        $rendered .= "console.log('Map is ready! Adding content...');\n";
        
        $rendered .= $this->RenderOptions($mapVariable);
        $rendered .= $this->RenderIcon();
        $rendered .= $this->RenderMarkers($mapVariable);
        $rendered .= $this->RenderMapCenteringOnPins($mapVariable);
        $rendered .= $this->RenderClusterLayer($mapVariable);
        $rendered .= $this->RenderSpatialDataService($mapVariable);
        $rendered .= $this->RenderPolygones($mapVariable);
        
        $rendered .= "});\n"; // Close the ready event
        
        $rendered .= "console.log('Azure Maps setup complete.');\n";
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
}
