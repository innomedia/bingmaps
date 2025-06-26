<?php
namespace bingMap;

use bingMap\MapPosition;
use bingMap\Coordinates;
use SilverStripe\Dev\Debug;
use SilverStripe\View\ViewableData;
use SilverStripe\SiteConfig\SiteConfig;

class GeoapifyMap extends ViewableData
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

    public function __construct($ID = "1", $loadOnStartClass = "", $Debug = false)
    {
        $this->Debug = $Debug;
        $this->loadOnStartClass = $loadOnStartClass;
        $this->ID = $ID;
        $this->Style = "width: {$this->Width}px; height: {$this->Height}px;";
    }

    public function setClusterLayer($enabled)
    {
        $this->ClusterLayer = $enabled;
        return $this;
    }

    public function setSpatialDataService($enabled, $postalCodes = null)
    {
        $this->SpatialDataService = $enabled;
        $this->SpatialDataServicePostalCodes = $postalCodes;
        return $this;
    }

    public function AddMarker($marker)
    {
        array_push($this->Markers, $marker);
    }

    public function SetMapType($mapType)
    {
        $this->MapType = $mapType;
        return $this;
    }

    public function SetRoadMapType()
    {
        return $this->SetMapType('road');
    }

    public function SetSatelliteMapType()
    {
        return $this->SetMapType('satellite');
    }

    public function SetLightMapType()
    {
        return $this->SetMapType('light');
    }

    public function SetGrayscaleMapType()
    {
        return $this->SetMapType('grayscale');
    }

    public function addScriptSetting($key, $value)
    {
        $this->ScriptSettings[$key] = $value;
        return $this;
    }

    public function SetWidth($width)
    {
        $this->Width = $width;
        $this->Style = "width: {$this->Width}px; height: {$this->Height}px;";
        return $this;
    }

    public function SetHeight($height)
    {
        $this->Height = $height;
        $this->Style = "width: {$this->Width}px; height: {$this->Height}px;";
        return $this;
    }

    public function SetZoom($zoom)
    {
        $this->Zoom = $zoom;
        return $this;
    }

    private function RenderZoom()
    {
        return $this->Zoom ?? 10;
    }

    public function XML_val($field, $arguments = [], $cache = false)
    {
        $data = $this->getData();
        return $this->customise($data)->renderWith("geoapifyMap");
    }

    private function getData()
    {
        return [
            "Script" => $this->RenderFunction(),
            "ID" => $this->ID,
            "Styles" => $this->Style,
        ];
    }

    public function RenderFunction()
    {
        $rendered = "";
        
        // Script attributes
        $Attributes = "";
        if (!empty($this->ScriptSettings)) {
            foreach($this->ScriptSettings as $key => $value) {
                $Attributes .= $key.'="'.$value.'" ';
            }
        }
        
        if ($this->loadOnStartClass != "" || $Attributes != "") {
            $rendered .= "<script class='$this->loadOnStartClass' $Attributes>\n";
        } else {
            $rendered .= "<script type='text/javascript'>\n";
        }
        
        $rendered .= "var InfoBoxCollection = [];\n";
        $rendered .= "console.log('Starting Geoapify Maps initialization for map ID: {$this->ID}');\n";
        
        // Check if API key is available
        $apiKey = SiteConfig::current_site_config()->geoapifyAPIKey ?? '';
        if (empty($apiKey)) {
            $rendered .= "showMapError('Geoapify API key is not configured. Please contact the administrator.');\n";
        } else {
            $rendered .= "console.log('Geoapify API key found, length: " . strlen($apiKey) . "');\n";
            $rendered .= $this->RenderGeoapifyMap();
        }
        
        $rendered .= "</script>\n";
        
        return $rendered;
    }

    private function RenderGeoapifyMap()
    {
        $mapVariable = "map" . $this->ID;
        $rendered = "";
        
        $rendered .= "function GetMap{$this->ID}(){\n";
        $rendered .= "console.log('Creating Geoapify Map...');\n";
        
        // Initialize Geoapify map
        $apiKey = SiteConfig::current_site_config()->geoapifyAPIKey;
        $rendered .= "var {$mapVariable} = new maplibregl.Map({\n";
        $rendered .= "    container: 'MapContainer{$this->ID}',\n";
        $rendered .= "    style: 'https://maps.geoapify.com/v1/styles/{$this->getGeoapifyStyle()}/style.json?apiKey={$apiKey}',\n";
        $rendered .= "    center: {$this->RenderLocation()},\n";
        $rendered .= "    zoom: {$this->RenderZoom()}\n";
        $rendered .= "});\n";

        $rendered .= "{$mapVariable}.on('load', function () {\n";
        $rendered .= "    console.log('Geoapify map loaded successfully');\n";
        
        // Add markers
        $rendered .= $this->RenderMarkers($mapVariable);
        
        // Add clustering if enabled
        if ($this->ClusterLayer) {
            $rendered .= $this->RenderClusterLayer($mapVariable);
        }
        
        // Add spatial data if enabled
        if ($this->SpatialDataService) {
            $rendered .= $this->RenderSpatialDataService($mapVariable);
        }
        
        // Center on pins if enabled
        $rendered .= $this->RenderMapCenteringOnPins($mapVariable);
        
        $rendered .= "});\n";
        $rendered .= "}\n";
        $rendered .= "GetMap{$this->ID}();\n";
        
        return $rendered;
    }

    private function getGeoapifyStyle()
    {
        switch ($this->MapType) {
            case 'satellite':
                return 'satellite';
            case 'light':
                return 'positron';
            case 'grayscale':
                return 'toner';
            case 'dark':
                return 'dark-matter';
            default:
                return 'osm-bright';
        }
    }

    private function RenderMarkers($mapVariable)
    {
        $rendered = "console.log('Starting marker rendering...');\n";
        $rendered .= "var markers = [];\n";
        
        for ($i = 0; $i < count($this->Markers); $i++) {
            if ($this->ClusterLayer == false) {
                $rendered .= $this->Markers[$i]->RenderGeoapify($mapVariable, false);
            }
            
            if ($this->CenterOnPins == true) {
                $loc = $this->Markers[$i]->RenderLocation();
                $rendered .= "locs.push($loc);\n";
            }
        }
        
        $rendered .= "console.log('Markers rendering complete.');\n";
        return $rendered;
    }

    private function RenderClusterLayer($mapVariable)
    {
        $rendered = "";
        
        // Add GeoJSON source for clustering
        $rendered .= "{$mapVariable}.addSource('markers', {\n";
        $rendered .= "    'type': 'geojson',\n";
        $rendered .= "    'data': {\n";
        $rendered .= "        'type': 'FeatureCollection',\n";
        $rendered .= "        'features': [\n";
        
        // Add marker features
        for ($i = 0; $i < count($this->Markers); $i++) {
            $marker = $this->Markers[$i];
            $pos = $marker->GetPosition();
            $rendered .= "            {\n";
            $rendered .= "                'type': 'Feature',\n";
            $rendered .= "                'properties': {\n";
            $rendered .= "                    'markerId': '{$marker->GetID()}'\n";
            if ($marker->HasInfoBox()) {
                $infoBox = $marker->GetInfoBox();
                $rendered .= "                    ,'popupContent': " . json_encode($infoBox->GetContent()) . "\n";
            }
            $rendered .= "                },\n";
            $rendered .= "                'geometry': {\n";
            $rendered .= "                    'type': 'Point',\n";
            $rendered .= "                    'coordinates': [{$pos->GetLongitude()}, {$pos->GetLatitude()}]\n";
            $rendered .= "                }\n";
            $rendered .= "            }";
            if ($i < count($this->Markers) - 1) {
                $rendered .= ",";
            }
            $rendered .= "\n";
        }
        
        $rendered .= "        ]\n";
        $rendered .= "    },\n";
        $rendered .= "    'cluster': true,\n";
        $rendered .= "    'clusterMaxZoom': 14,\n";
        $rendered .= "    'clusterRadius': 50\n";
        $rendered .= "});\n";
        
        // Add cluster layers
        $rendered .= $this->AddClusterLayers($mapVariable);
        
        return $rendered;
    }

    private function AddClusterLayers($mapVariable)
    {
        $rendered = "";
        
        // Clusters
        $rendered .= "{$mapVariable}.addLayer({\n";
        $rendered .= "    'id': 'clusters',\n";
        $rendered .= "    'type': 'circle',\n";
        $rendered .= "    'source': 'markers',\n";
        $rendered .= "    'filter': ['has', 'point_count'],\n";
        $rendered .= "    'paint': {\n";
        $rendered .= "        'circle-color': [\n";
        $rendered .= "            'step',\n";
        $rendered .= "            ['get', 'point_count'],\n";
        $rendered .= "            '#51bbd6',\n";
        $rendered .= "            100, '#f1f075',\n";
        $rendered .= "            750, '#f28cb1'\n";
        $rendered .= "        ],\n";
        $rendered .= "        'circle-radius': [\n";
        $rendered .= "            'step',\n";
        $rendered .= "            ['get', 'point_count'],\n";
        $rendered .= "            20, 100, 30, 750, 40\n";
        $rendered .= "        ]\n";
        $rendered .= "    }\n";
        $rendered .= "});\n";
        
        // Cluster count
        $rendered .= "{$mapVariable}.addLayer({\n";
        $rendered .= "    'id': 'cluster-count',\n";
        $rendered .= "    'type': 'symbol',\n";
        $rendered .= "    'source': 'markers',\n";
        $rendered .= "    'filter': ['has', 'point_count'],\n";
        $rendered .= "    'layout': {\n";
        $rendered .= "        'text-field': '{point_count_abbreviated}',\n";
        $rendered .= "        'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],\n";
        $rendered .= "        'text-size': 12\n";
        $rendered .= "    }\n";
        $rendered .= "});\n";
        
        // Individual points
        $rendered .= "{$mapVariable}.addLayer({\n";
        $rendered .= "    'id': 'unclustered-point',\n";
        $rendered .= "    'type': 'circle',\n";
        $rendered .= "    'source': 'markers',\n";
        $rendered .= "    'filter': ['!', ['has', 'point_count']],\n";
        $rendered .= "    'paint': {\n";
        $rendered .= "        'circle-color': '#11b4da',\n";
        $rendered .= "        'circle-radius': 8,\n";
        $rendered .= "        'circle-stroke-width': 1,\n";
        $rendered .= "        'circle-stroke-color': '#fff'\n";
        $rendered .= "    }\n";
        $rendered .= "});\n";
        
        return $rendered;
    }

    private function RenderSpatialDataService($mapVariable)
    {
        if ($this->SpatialDataService == true) {
            $rendered = "";
            
            // Geoapify Postcode API implementation for postal code boundaries
            $apiKey = SiteConfig::current_site_config()->geoapifyAPIKey;
            
            $rendered .= "// Function to fetch postcode boundary data using Geoapify Postcode API\n";
            $rendered .= "function fetchGeoapifyPostcodeBoundary(postcode) {\n";
            $rendered .= "    var postcodeUrl = 'https://api.geoapify.com/v1/geocode/search?postcode=' + encodeURIComponent(postcode) + '&format=geojson&apiKey={$apiKey}';\n";
            $rendered .= "    \n";
            $rendered .= "    fetch(postcodeUrl)\n";
            $rendered .= "        .then(response => response.json())\n";
            $rendered .= "        .then(data => {\n";
            $rendered .= "            if (data.features && data.features.length > 0) {\n";
            $rendered .= "                // Get the first feature which contains the postcode area\n";
            $rendered .= "                var feature = data.features[0];\n";
            $rendered .= "                \n";
            $rendered .= "                // Add postcode boundary to map if geometry exists\n";
            $rendered .= "                if (feature.geometry) {\n";
            $rendered .= "                    {$mapVariable}.addSource('postcode-boundary', {\n";
            $rendered .= "                        'type': 'geojson',\n";
            $rendered .= "                        'data': feature\n";
            $rendered .= "                    });\n";
            $rendered .= "                    \n";
            $rendered .= "                    {$mapVariable}.addLayer({\n";
            $rendered .= "                        'id': 'postcode-boundary-fill',\n";
            $rendered .= "                        'type': 'fill',\n";
            $rendered .= "                        'source': 'postcode-boundary',\n";
            $rendered .= "                        'paint': {\n";
            $rendered .= "                            'fill-color': 'rgba(13, 66, 104, 0.3)',\n";
            $rendered .= "                            'fill-outline-color': '#0d4268'\n";
            $rendered .= "                        }\n";
            $rendered .= "                    });\n";
            $rendered .= "                    \n";
            $rendered .= "                    {$mapVariable}.addLayer({\n";
            $rendered .= "                        'id': 'postcode-boundary-outline',\n";
            $rendered .= "                        'type': 'line',\n";
            $rendered .= "                        'source': 'postcode-boundary',\n";
            $rendered .= "                        'paint': {\n";
            $rendered .= "                            'line-color': '#0d4268',\n";
            $rendered .= "                            'line-width': 2\n";
            $rendered .= "                        }\n";
            $rendered .= "                    });\n";
            $rendered .= "                }\n";
            $rendered .= "            }\n";
            $rendered .= "        })\n";
            $rendered .= "        .catch(error => console.error('Error fetching postcode boundary data:', error));\n";
            $rendered .= "}\n";
            
            // Execute postcode boundary requests
            if ($this->SpatialDataServicePostalCodes !== null) {
                $rendered .= "// Fetch boundary for postcode\n";
                $rendered .= "fetchGeoapifyPostcodeBoundary('{$this->SpatialDataServicePostalCodes}');\n";
            }
            
            return $rendered;
        }
        return "";
    }

    private function RenderMapCenteringOnPins($mapVariable)
    {
        if ($this->CenterOnPins == true && count($this->Markers) > 0) {
            $rendered = "if (typeof locs !== 'undefined' && locs.length > 0) {\n";
            $rendered .= "    var bounds = new maplibregl.LngLatBounds();\n";
            $rendered .= "    locs.forEach(function(loc) {\n";
            $rendered .= "        bounds.extend(loc);\n";
            $rendered .= "    });\n";
            $rendered .= "    {$mapVariable}.fitBounds(bounds, { padding: {$this->Padding} });\n";
            $rendered .= "}\n";
            return $rendered;
        }
        return "";
    }

    // Maintain API compatibility with original Map class
    public static function GetIconVariable()
    {
        return "Icon";
    }

    public function HasLoadOnStartClass()
    {
        return $this->loadOnStartClass != "";
    }

    public function GetLoadOnStartClass()
    {
        return $this->loadOnStartClass;
    }
}