<?php
namespace bingMap;

// OpenLayers-compatible Marker class
class OpenLayersMarker
{
    use MapPosition;
    private $ID;
    private $InfoBox = null;
    private $IconPath = null;
    private $Base64Icon = null;
    private $IconVariable = null;

    private static $Suffix = "Marker";

    public function __construct($ID)
    {
        $this->ID = $ID;
    }
    
    public static function create($ID)
    {
        return new OpenLayersMarker($ID);
    }
    
    public function SetInfoBox($InfoBox)
    {
        $InfoBox->SetID($this->ID);
        $this->InfoBox = $InfoBox;
        return $this;
    }
    
    public function SetIconURL($IconPath)
    {
        $this->IconPath = $IconPath;
        return $this;
    }
    
    public function SetBase64Icon($Base64)
    {
        $this->Base64Icon = $Base64;
        return $this;
    }
    
    public function SetIconVariable()
    {
        $this->IconVariable = Map::GetIconVariable();
        return $this;
    }
    
    public function GetMarkerVariable()
    {
        return "marker$this->ID";
    }
    
    public function RenderOpenLayers($mapVariable, $ClusterEnabled)
    {
        if (!$this->IsValidCoordinate()) {
            return "console.log('Skipping invalid coordinates for marker {$this->ID}');\n";
        }
        
        $rendered = "";
        $coords = "[{$this->GetLongitude()}, {$this->GetLatitude()}]";
        
        $rendered .= "
        var marker{$this->ID}Coords = ol.proj.fromLonLat($coords);
        var marker{$this->ID}Feature = new ol.Feature({
            geometry: new ol.geom.Point(marker{$this->ID}Coords),
            markerId: '{$this->ID}'";
            
        // Add icon URL if available
        if ($this->IconPath) {
            $rendered .= ",\n            iconUrl: '{$this->IconPath}'";
        } elseif ($this->Base64Icon) {
            $rendered .= ",\n            iconUrl: '{$this->Base64Icon}'";
        } elseif ($this->IconVariable) {
            $rendered .= ",\n            iconUrl: {$this->IconVariable}";
        }
        
        // Add popup content if InfoBox exists
        if ($this->InfoBox) {
            $content = $this->InfoBox->GetContent();
            $rendered .= ",\n            popupContent: '" . addslashes($content) . "'";
        }
        
        $rendered .= "
        });
        markerFeatures.push(marker{$this->ID}Feature);
        ";
        
        // Add click event for popup if InfoBox exists
        if ($this->InfoBox) {
            $rendered .= "
            markers.push({
                feature: marker{$this->ID}Feature,
                popup: function(map) {
                    var popup = new ol.Overlay({
                        element: document.createElement('div'),
                        positioning: 'bottom-center',
                        stopEvent: false,
                        offset: [0, -20]
                    });
                    
                    var popupElement = popup.getElement();
                    popupElement.innerHTML = '" . addslashes($this->InfoBox->GetContent()) . "';
                    popupElement.style.cssText = 'background: white; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);';
                    
                    map.addOverlay(popup);
                    popup.setPosition(marker{$this->ID}Coords);
                    
                    // Close popup when clicking elsewhere
                    setTimeout(function() {
                        map.on('click', function() {
                            map.removeOverlay(popup);
                        });
                    }, 100);
                }
            });
            ";
        }
        
        return $rendered;
    }
    
    // Keep the original Render method for backward compatibility
    public function Render($mapVariable, $ClusterEnabled)
    {
        return $this->RenderOpenLayers($mapVariable, $ClusterEnabled);
    }
    
    public function GetReactData($iconPath = "")
    {
        if (!$this->IsValidCoordinate()) {
            return null;
        }
        $icon = $this->IconPath;
        if($icon == "")
        {
            $icon = $iconPath;
        }
        $data = [
            "key" => $this->ID,
            "icon" => $icon,
            "coordinates" => $this->GetPosition()->GetReactData()
        ];
        if($this->InfoBox != null)
        {
            $data["infobox"] = $this->InfoBox->GetReactData();
        }
        return $data;
    }
}
