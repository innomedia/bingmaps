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
        return "pushpin$this->ID";
    }
    
    private function RenderIcon()
    {
        if ($this->IconPath != null) {
            return "{icon: '$this->IconPath'}";
        }
        if ($this->Base64Icon != null) {
            return "{icon: '$this->Base64Icon'}";
        }
        if ($this->IconVariable != null) {
            return "{icon: $this->IconVariable}";
        }

        return "null";
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
        $rendered .= "var pushpin$this->ID = new Microsoft.Maps.Pushpin({$this->GetLocationVariable($this->ID, self::$Suffix)},{$this->RenderIcon()});\n";
        if ($this->InfoBox != null) {
            $rendered .= $this->InfoBox->Render($mapVariable, "pushpin$this->ID");
        }
        if(!$ClusterEnabled)
        {
            $rendered .= "{$mapVariable}.entities.push(pushpin$this->ID);\n";
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
        $rendered .= "var pushpin$this->ID = new Microsoft.Maps.Pushpin({$this->GetLocationVariable($this->ID, self::$Suffix)},{$this->RenderIcon()});\n";
        if ($this->InfoBox != null) {
            $rendered .= $this->InfoBox->Render($mapVariable, "pushpin$this->ID");
        }
        if(!$ClusterEnabled)
        {
            $rendered .= "{$mapVariable}.entities.push(pushpin$this->ID);\n";
        }
        $data["rendered"] = $rendered;
        $data["pushpinvariable"] = "pushpin$this->ID";

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
}
