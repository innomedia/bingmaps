<?php
namespace bingMap;

use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Model\ModelData;
use bingMap\MapPosition;

class OpenLayersMap extends ModelData
{
    use MapPosition;

    private $Debug = true;
    
    private $ID;
    
    private $Style;
    
    private $Height = 500;
    
    private $Width = 500;
    
    private $loadOnStartClass;
    
    private $IconPath;
    
    private $Base64Icon;
    
    private $CenterOnPins = true;
    
    private $Padding = 50;
    
    private array $Markers = [];
    
    private $ScriptSettings = [];
    
    private $Zoom;
    
    private ?bool $MouseWheelZoom = null;
    
    private $MapType;
    
    private $ClusterLayer = false;

    public function __construct($ID = "1", $loadOnStartClass = "", $Debug = false)
    {
        $this->Debug = $Debug;
        $this->loadOnStartClass = $loadOnStartClass;
        $this->ID = $ID;
    }
    
    public static function createMap($ID = "1", $loadOnStartClass = "", $Debug = false): OpenLayersMap
    {
        return OpenLayersMap::create($ID, $loadOnStartClass, $Debug);
    }
    
    public function SetCenterOnPins($value): static
    {
        $this->CenterOnPins = $value;
        return $this;
    }
    
    public function setClusterLayer($value): static
    {
        $this->ClusterLayer = $value;
        return $this;
    }
    
    public function addScriptSetting($key,$value): static
    {
        $this->ScriptSettings[$key] = $value; 
        return $this;
    }
    
    public function SetZoom($value): static
    {
        $this->Zoom = $value;
        return $this;
    }
    
    public function DisableMouseWheelZoom(): static
    {
        $this->MouseWheelZoom = true;
        return $this;
    }
    
    public function SetIcon($IconPath): static
    {
        $this->IconPath = $IconPath;
        return $this;
    }
    
    public function SetCenterOnPinsPadding($value): static
    {
        $this->Padding = $value;
        return $this;
    }
    
    public function SetBase64Icon($Base64): static
    {
        $this->Base64Icon = $Base64;
        return $this;
    }
    
    public function SetStyle($style): static
    {
        $this->Style = $style;
        return $this;
    }
    
    public function SetHeight($pixel): static
    {
        $this->Height = $pixel;
        return $this;
    }
    
    public function SetWidth($pixel): static
    {
        $this->Width = $pixel;
        return $this;
    }
    
    public function SetMapType($Type): static
    {
        $this->MapType = $Type;
        return $this;
    }
    
    public function SetDarkMapType(): static
    {
        return $this->SetMapType('dark');
    }
    
    public function SetLightMapType(): static
    {
        return $this->SetMapType('osm');
    }
    
    public function SetGrayscaleMapType(): static
    {
        return $this->SetMapType('grayscale');
    }
    
    public function HasLoadOnStartClass(): bool
    {
        return $this->loadOnStartClass != "";
    }
    
    public function AddMarker($marker): void
    {
        $this->Markers[] = $marker;
    }
    
    public function XML_val($field, $arguments = [], $cache = false): DBHTMLText
    {
        $data = $this->getData();
        return $this->customise($data)->renderWith("openLayersMap");
    }
    
    private function getData(): array
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
    
    public static function GetIconVariable(): string
    {
        return "Icon";
    }
    
    private function RenderMarkers(string $mapVariable): string
    {
        $rendered = "";
        $rendered .= "console.log('Rendering " . count($this->Markers) . " markers...');\n";
        $rendered .= "var markers = [];\n";
        $rendered .= "var markerFeatures = [];\n";
        $counter = count($this->Markers);
        
        for ($i = 0; $i < $counter; ++$i) {
            $rendered .= "console.log('Adding marker " . ($i + 1) . "...');\n";
            $rendered .= $this->Markers[$i]->RenderOpenLayers($mapVariable, $this->ClusterLayer);
        }
        
        if ($this->Markers !== []) {
            $rendered .= "
            var vectorSource = new ol.source.Vector({
                features: markerFeatures
            });
            
            var markerLayer = new ol.layer.Vector({
                source: vectorSource,
                style: function(feature) {
                    var iconUrl = feature.get('iconUrl');
                    if (iconUrl) {
                        return new ol.style.Style({
                            image: new ol.style.Icon({
                                src: iconUrl,
                                scale: 0.8
                            })
                        });
                    } else {
                        return new ol.style.Style({
                            image: new ol.style.Circle({
                                radius: 8,
                                fill: new ol.style.Fill({color: 'red'}),
                                stroke: new ol.style.Stroke({color: 'white', width: 2})
                            })
                        });
                    }
                }
            });
            
            {$mapVariable}.addLayer(markerLayer);
            ";
        }
        
        return $rendered . "console.log('Markers rendering complete.');\n";
    }
    
    private function RenderMapCenteringOnPins(string $mapVariable): string
    {
        if ($this->CenterOnPins == true && $this->Markers !== []) {
            return "
            if (markerFeatures.length > 0) {
                var extent = vectorSource.getExtent();
                {$mapVariable}.getView().fit(extent, {
                    padding: [{$this->Padding}, {$this->Padding}, {$this->Padding}, {$this->Padding}],
                    maxZoom: 16
                });
            }
            ";
        }
        
        return "";
    }
    
    private function GetMapSource(): string
    {
        switch ($this->MapType) {
            case 'dark':
                return "new ol.source.XYZ({
                    url: 'https://cartodb-basemaps-{a-c}.global.ssl.fastly.net/dark_all/{z}/{x}/{y}.png'
                })";
            case 'grayscale':
                return "new ol.source.XYZ({
                    url: 'https://cartodb-basemaps-{a-c}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png'
                })";
            default:
                return "new ol.source.OSM()";
        }
    }
    
    public function RenderZoom()
    {
        if ($this->Zoom != null) {
            return $this->Zoom;
        }
        
        return 10;
    }
    
    public function RenderFunction(): string|array|null
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
        
        if ($this->loadOnStartClass != "" || $Attributes !== "") {
            $rendered .= "<script class='$this->loadOnStartClass' {$Attributes}>\n";
        } else {
            $rendered .= "<script type='text/javascript'>\n";
        }
        
        $rendered .= sprintf("console.log('Starting OpenLayers map initialization for map ID: %s');", $this->ID);
        $rendered .= "function GetMap{$this->ID}(){\n";
        $mapVariable = "map" . $this->ID;

        $rendered .= "
            console.log('Creating OpenLayers Map...');
            
            var centerCoords = ol.proj.fromLonLat({$this->RenderLocation()});
            
            var {$mapVariable} = new ol.Map({
                target: 'MapContainer{$this->ID}',
                layers: [
                    new ol.layer.Tile({
                        source: {$this->GetMapSource()}
                    })
                ],
                view: new ol.View({
                    center: centerCoords,
                    zoom: {$this->RenderZoom()}
                })
            });
            console.log('Map created successfully!');
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
        
        $rendered .= $this->RenderMarkers($mapVariable);
        $rendered .= $this->RenderMapCenteringOnPins($mapVariable);

        $rendered .= "console.log('Map setup complete.');\n";
        $rendered .= "}\n";
        
        // Add the function call
        $rendered .= "GetMap{$this->ID}();\n";
        
        $rendered .= "</script>\n";
        
        if (!$this->Debug) {
            return HelperMethods::MinifyString($rendered);
        }

        return HelperMethods::RemoveEmptyLines($rendered);
    }
    
    public function GetReactData(): array
    {
        return [
            "key" => $this->ID,
            "loadOnStartClass" => $this->loadOnStartClass,
            "centerOnPins" => $this->CenterOnPins,
            "padding" => $this->Padding,
            "markers" => $this->GetMarkersData(),
            "zoom" => $this->Zoom,
            "position" => $this->Coords->GetReactData(),
        ];
    }
    
    /**
     * @return mixed[]
     */
    private function GetMarkersData(): array
    {
        $MarkersData = [];
        $iconPath = $this->IconPath;
        foreach ($this->Markers as $Marker) {
            $MarkersData[] = $Marker->GetReactData($iconPath);
        }
        
        return $MarkersData;
    }
    
    public function GetJSONReactData()
    {
        return json_encode($this->GetReactData());
    }
}
