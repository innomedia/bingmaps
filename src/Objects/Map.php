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
                if ($this->CenterOnPins == true) {
                    $loc = $this->Markers[$i]->RenderLocation();
                    $rendered .= "locs.push($loc);\n";
                }
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
                clusterRadius: 45,
                clusterMaxZoom: 15
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
            
            // Create bubble layer for clusters
            $rendered .= "{$mapVariable}.layers.add(new atlas.layer.BubbleLayer(clusterDataSource, null, {
                radius: 20,
                color: '#007faa',
                strokeColor: 'white',
                strokeWidth: 2,
                filter: ['has', 'point_count']
            }));\n";
            
            // Create symbol layer for cluster count labels
            $rendered .= "{$mapVariable}.layers.add(new atlas.layer.SymbolLayer(clusterDataSource, null, {
                iconOptions: {
                    image: 'none'
                },
                textOptions: {
                    textField: ['get', 'point_count_abbreviated'],
                    offset: [0, 0.4],
                    color: 'white'
                },
                filter: ['has', 'point_count']
            }));\n";
            
            // Create symbol layer for individual points (unclustered)
            $rendered .= "{$mapVariable}.layers.add(new atlas.layer.SymbolLayer(clusterDataSource, null, {
                filter: ['!', ['has', 'point_count']],
                iconOptions: {
                    image: 'marker-red'
                }
            }));\n";
            
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
        $rendered .= "console.log('Starting map initialization for map ID: {$this->ID}');\n";
        
        // WebGL detection function
        $rendered .= "function checkWebGLSupport() {\n";
        $rendered .= "    try {\n";
        $rendered .= "        var canvas = document.createElement('canvas');\n";
        $rendered .= "        var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');\n";
        $rendered .= "        return !!gl;\n";
        $rendered .= "    } catch (e) {\n";
        $rendered .= "        return false;\n";
        $rendered .= "    }\n";
        $rendered .= "}\n";
        
        // Check if API key is available
        $apiKey = SiteConfig::current_site_config()->bingAPIKey ?? '';
        if (empty($apiKey)) {
            $rendered .= "console.error('No API key found! Using OpenLayers fallback.');\n";
            $rendered .= $this->RenderOpenLayersMap();
        } else {
            $rendered .= "console.log('API key found, length: " . strlen($apiKey) . "');\n";
            
            // Try Azure Maps first, fallback to OpenLayers if WebGL not supported
            $rendered .= "if (checkWebGLSupport()) {\n";
            $rendered .= "    console.log('WebGL supported, using Azure Maps');\n";
            $rendered .= $this->RenderAzureMap();
            $rendered .= "} else {\n";
            $rendered .= "    console.log('WebGL not supported, using OpenLayers fallback');\n";
            $rendered .= $this->RenderOpenLayersMap();
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
            var $mapVariable = new atlas.Map('MapContainer{$this->ID}',{
                center:{$this->RenderLocation()}{$this->RenderZoom()}{$this->RenderMapTypeID()},
                authOptions: {
                    authType: 'subscriptionKey',
                    subscriptionKey: '" . SiteConfig::current_site_config()->bingAPIKey . "'
                }
            });\n
            console.log('Map created, waiting for ready event...');
        ";
        
        // Wait for map to be ready before adding content
        $rendered .= "{$mapVariable}.events.add('ready', function() {\n";
        $rendered .= "console.log('Map is ready! Adding content...');\n";
        
        $rendered .= $this->RenderOptions($mapVariable);
        $rendered .= $this->RenderIcon();
        $rendered .= $this->RenderMarkers($mapVariable);
        $rendered .= $this->RenderMapCenteringOnPins($mapVariable);
        $rendered .= $this->RenderClusterLayer($mapVariable);
        $rendered .= $this->RenderSpatialDataService($mapVariable);
        $rendered .= $this->RenderPolygones($mapVariable);
        
        $rendered .= "});\n"; // Close the ready event
        
        // Add error handling with fallback
        $rendered .= "{$mapVariable}.events.add('error', function(error) {\n";
        $rendered .= "console.error('Azure Maps error, falling back to OpenLayers:', error);\n";
        $rendered .= "document.getElementById('MapContainer{$this->ID}').innerHTML = '';\n";
        $rendered .= $this->RenderOpenLayersMapDirect();
        $rendered .= "});\n";
        
        $rendered .= "console.log('Azure Maps setup complete.');\n";
        $rendered .= "}\n";
        $rendered .= $this->RenderInfoBoxCloser();
        $rendered .= "GetMap{$this->ID}();\n";
        
        return $rendered;
    }
    
    private function RenderOpenLayersMap()
    {
        $rendered = "";
        $rendered .= "function GetMap{$this->ID}(){\n";
        $rendered .= $this->RenderOpenLayersMapDirect();
        $rendered .= "}\n";
        $rendered .= "GetMap{$this->ID}();\n";
        
        return $rendered;
    }
    
    private function RenderOpenLayersMapDirect()
    {
        $rendered = "";
        $mapVariable = "map" . $this->ID;
        
        $rendered .= "
            console.log('Creating OpenLayers Map...');
            
            var centerCoords = ol.proj.fromLonLat({$this->RenderLocation()});
            
            var {$mapVariable} = new ol.Map({
                target: 'MapContainer{$this->ID}',
                layers: [
                    new ol.layer.Tile({
                        source: {$this->GetOpenLayersSource()}
                    })
                ],
                view: new ol.View({
                    center: centerCoords,
                    zoom: {$this->RenderZoomValue()}
                })
            });
            console.log('OpenLayers map created successfully!');
        ";
        
        if ($this->MouseWheelZoom) {
            $rendered .= "
            {$mapVariable}.getInteractions().forEach(function(interaction) {
                if (interaction instanceof ol.interaction.MouseWheelZoom) {
                    {$mapVariable}.removeInteraction(interaction);
                }
            });
            ";
        }
        
        $rendered .= $this->RenderOpenLayersMarkers($mapVariable);
        $rendered .= "console.log('OpenLayers map setup complete.');\n";
        
        return $rendered;
    }
    
    private function GetOpenLayersSource()
    {
        switch ($this->MapType) {
            case "'dark'":
                return "new ol.source.XYZ({
                    url: 'https://cartodb-basemaps-{a-c}.global.ssl.fastly.net/dark_all/{z}/{x}/{y}.png'
                })";
            case "'grayscale_light'":
                return "new ol.source.XYZ({
                    url: 'https://cartodb-basemaps-{a-c}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png'
                })";
            default:
                return "new ol.source.OSM()";
        }
    }
    
    private function RenderOpenLayersMarkers($mapVariable)
    {
        if (count($this->Markers) == 0) {
            return "";
        }
        
        $rendered = "";
        $rendered .= "console.log('Rendering " . count($this->Markers) . " markers with OpenLayers...');\n";
        $rendered .= "var markerFeatures = [];\n";
        
        for ($i = 0; $i < count($this->Markers); $i++) {
            $marker = $this->Markers[$i];
            if ($marker->IsValidCoordinate()) {
                $coords = "[{$marker->GetLongitude()}, {$marker->GetLatitude()}]";
                $rendered .= "
                var marker{$i}Coords = ol.proj.fromLonLat($coords);
                var marker{$i}Feature = new ol.Feature({
                    geometry: new ol.geom.Point(marker{$i}Coords),
                    markerId: 'marker{$i}'";
                
                // Add popup content if InfoBox exists
                if ($marker->HasInfoBox()) {
                    $infoBox = $marker->GetInfoBox();
                    $content = addslashes($infoBox->GetContent());
                    $rendered .= ",\n                    popupContent: '$content'";
                }
                
                $rendered .= "
                });
                markerFeatures.push(marker{$i}Feature);
                ";
            }
        }
        
        $rendered .= "
        // Create vector layer for markers
        var vectorSource = new ol.source.Vector({
            features: markerFeatures
        });
        
        var markerLayer = new ol.layer.Vector({
            source: vectorSource,
            style: new ol.style.Style({
                image: new ol.style.Circle({
                    radius: 8,
                    fill: new ol.style.Fill({color: 'red'}),
                    stroke: new ol.style.Stroke({color: 'white', width: 2})
                })
            })
        });
        
        {$mapVariable}.addLayer(markerLayer);
        ";
        
        // Center on pins if enabled
        if ($this->CenterOnPins && count($this->Markers) > 0) {
            $rendered .= "
            if (markerFeatures.length > 0) {
                var extent = vectorSource.getExtent();
                {$mapVariable}.getView().fit(extent, {
                    padding: [{$this->Padding}, {$this->Padding}, {$this->Padding}, {$this->Padding}],
                    maxZoom: 16
                });
            }
            ";
        }
        
        // Add popup functionality
        $rendered .= "
        // Add popup overlay
        var popup = new ol.Overlay({
            element: document.createElement('div'),
            positioning: 'bottom-center',
            stopEvent: false,
            offset: [0, -20]
        });
        
        popup.getElement().style.cssText = 'background: white; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); max-width: 200px;';
        {$mapVariable}.addOverlay(popup);
        
        {$mapVariable}.on('click', function(evt) {
            var feature = {$mapVariable}.forEachFeatureAtPixel(evt.pixel, function(feature) {
                return feature;
            });
            
            if (feature && feature.get('popupContent')) {
                popup.getElement().innerHTML = feature.get('popupContent');
                popup.setPosition(evt.coordinate);
            } else {
                popup.setPosition(undefined);
            }
        });
        ";
        
        $rendered .= "console.log('OpenLayers markers rendering complete.');\n";
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

}
