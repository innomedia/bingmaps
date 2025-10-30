<?php
namespace bingMap;

// Corresponds to BING Pushpin
class Marker
{
    use MapPosition;
    private $ID;
    
    private $InfoBox;
    
    private $IconPath;
    
    private $Base64Icon;
    
    private ?string $IconVariable = null;

    private static string $Suffix = "Marker";

    public function __construct($ID)
    {
        $this->ID = $ID;
    }
    
    public static function create($ID): Marker
    {
        return new Marker($ID);
    }
    
    public function SetInfoBox($InfoBox): static
    {
        $InfoBox->SetID($this->ID);
        $this->InfoBox = $InfoBox;
        return $this;
    }
    
    public function SetIconURL($IconPath): static
    {
        $this->IconPath = $IconPath;
        return $this;
    }
    
    public function SetBase64Icon($Base64): static
    {
        $this->Base64Icon = $Base64;
        return $this;
    }
    
    //Used if Map Defines Icon
    public function SetIconVariable(): static
    {
        $this->IconVariable = Map::GetIconVariable();
        return $this;
    }
    
    public function GetMarkerVariable(): string
    {
        return 'marker' . $this->ID;
    }
    
    public function GetInfoBox()
    {
        return $this->InfoBox;
    }
    
    public function HasInfoBox(): bool
    {
        return $this->InfoBox !== null;
    }
    
    public function RenderInfoBoxClosingFunction()
    {
        if ($this->InfoBox != null) {
            return $this->InfoBox->RenderHTMLCloser();
        }
        
        return "";
    }
    
    public function Render($mapVariable,$ClusterEnabled): string
    {
        if ($this->InfoBox != null && !$this->InfoBox->HasPosition()) {
            $this->InfoBox->SetPosition($this->GetPosition());
        }
        
        $rendered = "";
        $rendered .= $this->RenderLocationVariable($this->ID, self::$Suffix) . "\n";
        
        // Create HTML marker with proper Azure Maps syntax
        $rendered .= "var marker$this->ID = new atlas.HtmlMarker({\n";
        $rendered .= '    position: ' . $this->GetLocationVariable($this->ID, self::$Suffix);
        
        // Add icon options if available
        if ($this->IconPath != null || $this->Base64Icon != null || $this->IconVariable != null) {
            $rendered .= ",\n    htmlContent: '<div style=\"background-image: url(";
            if ($this->IconPath != null) {
                $rendered .= $this->IconPath;
            } elseif ($this->Base64Icon != null) {
                $rendered .= $this->Base64Icon;
            } elseif ($this->IconVariable != null) {
                $rendered .= sprintf("' + %s + '", $this->IconVariable);
            }
            
            $rendered .= ')"><img src="';
            if ($this->IconPath != null) {
                $rendered .= $this->IconPath;
            } elseif ($this->Base64Icon != null) {
                $rendered .= $this->Base64Icon;
            } elseif ($this->IconVariable != null) {
                $rendered .= sprintf("' + %s + '", $this->IconVariable);
            }
            
            $rendered .= "\" style=\"display: block;\"></div>'";
        }
        
        $rendered .= "\n});\n";
        
        if ($this->InfoBox != null) {
            $rendered .= $this->InfoBox->Render($mapVariable, 'marker' . $this->ID);
        }
        
        if(!$ClusterEnabled)
        {
            $rendered .= "{$mapVariable}.markers.add(marker$this->ID);\n";
        }
        
        return $rendered;
    }
    
    public function RenderClusterMarker($mapVariable,$ClusterEnabled): array
    {
        $data = [];

        if ($this->InfoBox != null && !$this->InfoBox->HasPosition()) {
            $this->InfoBox->SetPosition($this->GetPosition());
        }
        
        $rendered = "";
        $rendered .= $this->RenderLocationVariable($this->ID, self::$Suffix) . "\n";
        
        // For clustering, we need to create a Point feature instead of an HtmlMarker
        $rendered .= "var point$this->ID = new atlas.data.Feature(new atlas.data.Point({$this->GetLocationVariable($this->ID, self::$Suffix)}), {\n";
        $rendered .= sprintf("    markerId: '%s'", $this->ID);
        if ($this->InfoBox != null) {
            $content = $this->InfoBox->GetContent();
            $rendered .= ",\n    popupContent: " . json_encode($content);
        }
        
        $rendered .= "\n});\n";
        
        if ($this->InfoBox != null) {
            $rendered .= $this->InfoBox->Render($mapVariable, 'point' . $this->ID);
        }
        
        $data["rendered"] = $rendered;
        $data["pushpinvariable"] = 'point' . $this->ID;

        return $data;
    }
    
    private function GetInfoBoxData()
    {
        return $this->InfoBox->GetReactData();
    }
    
    public function GetReactData($iconPath = ""): ?array
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
