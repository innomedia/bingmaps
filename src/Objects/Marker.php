<?php
namespace bingMap;

// Corresponds to BING Pushpin
class Marker
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
        return new Marker($ID);
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
    //Used if Map Defines Icon
    public function SetIconVariable()
    {
        $this->IconVariable = Map::GetIconVariable();
        return $this;
    }
    public function GetMarkerVariable()
    {
        return "marker$this->ID";
    }
    
    public function GetInfoBox()
    {
        return $this->InfoBox;
    }
    
    public function HasInfoBox()
    {
        return $this->InfoBox !== null;
    }
    
    private function RenderIcon()
    {
        if ($this->IconPath != null) {
            return "{iconOptions: {image: '$this->IconPath'}}";
        }
        if ($this->Base64Icon != null) {
            return "{iconOptions: {image: '$this->Base64Icon'}}";
        }
        if ($this->IconVariable != null) {
            return "{iconOptions: {image: $this->IconVariable}}";
        }

        return "{}";
    }
    public function RenderInfoBoxClosingFunction()
    {
        if ($this->InfoBox != null) {
            return $this->InfoBox->RenderHTMLCloser();
        }
        return "";
    }
    public function Render($mapVariable,$ClusterEnabled)
    {
        if ($this->InfoBox != null && !$this->InfoBox->HasPosition()) {
            $this->InfoBox->SetPosition($this->GetPosition());
        }
        $rendered = "";
        $rendered .= $this->RenderLocationVariable($this->ID, self::$Suffix) . "\n";
        
        // Create HTML marker with proper Azure Maps syntax
        $rendered .= "var marker$this->ID = new atlas.HtmlMarker({\n";
        $rendered .= "    position: {$this->GetLocationVariable($this->ID, self::$Suffix)}";
        
        // Add icon options if available
        if ($this->IconPath != null || $this->Base64Icon != null || $this->IconVariable != null) {
            $rendered .= ",\n    htmlContent: '<div style=\"background-image: url(";
            if ($this->IconPath != null) {
                $rendered .= "$this->IconPath";
            } elseif ($this->Base64Icon != null) {
                $rendered .= "$this->Base64Icon";
            } elseif ($this->IconVariable != null) {
                $rendered .= "' + $this->IconVariable + '";
            }
            $rendered .= ")\"><img src=\"";
            if ($this->IconPath != null) {
                $rendered .= "$this->IconPath";
            } elseif ($this->Base64Icon != null) {
                $rendered .= "$this->Base64Icon";
            } elseif ($this->IconVariable != null) {
                $rendered .= "' + $this->IconVariable + '";
            }
            $rendered .= "\" style=\"display: block;\"></div>'";
        }
        
        $rendered .= "\n});\n";
        
        if ($this->InfoBox != null) {
            $rendered .= $this->InfoBox->Render($mapVariable, "marker$this->ID");
        }
        if(!$ClusterEnabled)
        {
            $rendered .= "{$mapVariable}.markers.add(marker$this->ID);\n";
        }
        
        return $rendered;
    }
    public function RenderClusterMarker($mapVariable,$ClusterEnabled)
    {
        $data = [];

        if ($this->InfoBox != null && !$this->InfoBox->HasPosition()) {
            $this->InfoBox->SetPosition($this->GetPosition());
        }
        $rendered = "";
        $rendered .= $this->RenderLocationVariable($this->ID, self::$Suffix) . "\n";
        
        // For clustering, we need to create a Point feature instead of an HtmlMarker
        $rendered .= "var point$this->ID = new atlas.data.Feature(new atlas.data.Point({$this->GetLocationVariable($this->ID, self::$Suffix)}), {\n";
        $rendered .= "    markerId: '$this->ID'";
        if ($this->InfoBox != null) {
            $content = $this->InfoBox->GetContent();
            $rendered .= ",\n    popupContent: " . json_encode($content);
        }
        $rendered .= "\n});\n";
        
        if ($this->InfoBox != null) {
            $rendered .= $this->InfoBox->Render($mapVariable, "point$this->ID");
        }
        
        $data["rendered"] = $rendered;
        $data["pushpinvariable"] = "point$this->ID";

        return $data;
    }
    private function GetInfoBoxData()
    {
        return $this->InfoBox->GetReactData();
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
            $data["infobox"] = $this->GetInfoBoxData();
        }
        return $data;
    }

    public function RenderGeoapify($mapVariable, $ClusterEnabled)
    {
        if ($this->InfoBox != null && !$this->InfoBox->HasPosition()) {
            $this->InfoBox->SetPosition($this->GetPosition());
        }
        
        $rendered = "";
        $rendered .= $this->RenderLocationVariable($this->ID, self::$Suffix) . "\n";
        
        if (!$ClusterEnabled) {
            // Create MapLibre GL marker
            $rendered .= "var marker{$this->ID} = new maplibregl.Marker({\n";
            
            // Add custom icon if available
            if ($this->IconPath != null || $this->Base64Icon != null || $this->IconVariable != null) {
                $rendered .= "    element: (function() {\n";
                $rendered .= "        var el = document.createElement('div');\n";
                $rendered .= "        el.className = 'custom-marker';\n";
                $rendered .= "        el.style.backgroundImage = 'url(";
                
                if ($this->IconPath != null) {
                    $rendered .= $this->IconPath;
                } elseif ($this->Base64Icon != null) {
                    $rendered .= $this->Base64Icon;
                } elseif ($this->IconVariable != null) {
                    $rendered .= "' + {$this->IconVariable} + '";
                }
                
                $rendered .= ")';\n";
                $rendered .= "        el.style.width = '32px';\n";
                $rendered .= "        el.style.height = '32px';\n";
                $rendered .= "        el.style.backgroundSize = 'cover';\n";
                $rendered .= "        el.style.borderRadius = '50%';\n";
                $rendered .= "        el.style.cursor = 'pointer';\n";
                $rendered .= "        return el;\n";
                $rendered .= "    })()\n";
            }
            
            $rendered .= "})\n";
            $rendered .= ".setLngLat({$this->GetLocationVariable($this->ID, self::$Suffix)})\n";
            $rendered .= ".addTo({$mapVariable});\n";
            
            // Add popup if InfoBox exists
            if ($this->InfoBox != null) {
                $content = $this->InfoBox->GetContent();
                $rendered .= "var popup{$this->ID} = new maplibregl.Popup({ offset: 25 })\n";
                $rendered .= ".setHTML(" . json_encode($content) . ");\n";
                $rendered .= "marker{$this->ID}.setPopup(popup{$this->ID});\n";
                
                // Add click event to show popup
                $rendered .= "marker{$this->ID}.getElement().addEventListener('click', function() {\n";
                $rendered .= "    popup{$this->ID}.addTo({$mapVariable});\n";
                $rendered .= "});\n";
            }
        }
        
        return $rendered;
    }

    public function GetID()
    {
        return $this->ID;
    }
}
