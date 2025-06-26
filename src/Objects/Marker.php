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
    private $PostalCode = null;

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
    
    public function SetPostalCode($postalCode)
    {
        $this->PostalCode = $postalCode;
        return $this;
    }
    
    public function GetPostalCode()
    {
        return $this->PostalCode;
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
        
        // Create Point feature for datasource
        $rendered .= "var point$this->ID = new atlas.data.Feature(new atlas.data.Point({$this->GetLocationVariable($this->ID, self::$Suffix)}), {\n";
        $rendered .= "    markerId: '$this->ID'";
        
        // Add icon properties if available
        if ($this->IconPath != null || $this->Base64Icon != null || $this->IconVariable != null) {
            $iconId = '';
            if ($this->IconPath != null) {
                $iconId = 'icon-' . md5($this->IconPath);
            } elseif ($this->Base64Icon != null) {
                $iconId = 'icon-' . md5($this->Base64Icon);
            } elseif ($this->IconVariable != null) {
                $rendered .= ",\n    iconUrl: $this->IconVariable";
            }
            if ($iconId !== '') {
                $rendered .= ",\n    iconUrl: '$iconId'";
            }
        }
        
        if ($this->InfoBox != null) {
            $content = $this->InfoBox->GetContent();
            $rendered .= ",\n    popupContent: " . json_encode($content);
        }
        
        $rendered .= "\n});\n";
        $rendered .= "console.log('Created point feature for marker $this->ID:', point$this->ID);\n";
        
        if ($this->InfoBox != null) {
            $rendered .= $this->InfoBox->Render($mapVariable, "point$this->ID");
        }
        
        // Add to datasource instead of markers collection
        $rendered .= "dataSource.add(point$this->ID);\n";
        $rendered .= "console.log('Added point$this->ID to dataSource');\n";
        
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
        
        // For clustering, we create a Point feature for the datasource
        $rendered .= "var point$this->ID = new atlas.data.Feature(new atlas.data.Point({$this->GetLocationVariable($this->ID, self::$Suffix)}), {\n";
        $rendered .= "    markerId: '$this->ID'";
        
        // Add icon properties if available
        if ($this->IconPath != null || $this->Base64Icon != null || $this->IconVariable != null) {
            $iconId = '';
            if ($this->IconPath != null) {
                $iconId = 'icon-' . md5($this->IconPath);
            } elseif ($this->Base64Icon != null) {
                $iconId = 'icon-' . md5($this->Base64Icon);
            } elseif ($this->IconVariable != null) {
                $rendered .= ",\n    iconUrl: $this->IconVariable";
            }
            if ($iconId !== '') {
                $rendered .= ",\n    iconUrl: '$iconId'";
            }
        }
        
        if ($this->InfoBox != null) {
            $content = $this->InfoBox->GetContent();
            $rendered .= ",\n    popupContent: " . json_encode($content);
        }
        $rendered .= "\n});\n";
        
        if ($this->InfoBox != null) {
            $rendered .= $this->InfoBox->Render($mapVariable, "point$this->ID");
        }
        
        // Don't add to datasource here - clustering handles this differently
        // The point will be added to clusterPoints array in Map.php
        
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
    public function GetIconPath()
    {
        return $this->IconPath;
    }
    
    public function GetBase64Icon()
    {
        return $this->Base64Icon;
    }
    
    public function GetIconVariable()
    {
        return $this->IconVariable;
    }
    
    /**
     * Get the title from the associated InfoBox
     * @return string|null
     */
    public function getTitle()
    {
        if ($this->InfoBox && method_exists($this->InfoBox, 'getTitle')) {
            return $this->InfoBox->getTitle();
        }
        return null;
    }
}
