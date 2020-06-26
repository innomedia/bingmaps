<?php
namespace bingMap;
// Corresponds to BING Pushpin
class Marker
{
    use MapPosition;
    private int $ID;
    private ?InfoBox $InfoBox = null;
    private ?string $IconPath = null;
    private ?string $Base64Icon = null;
    private ?string $IconVariable = null;
   
    private static string $Suffix = "Marker";

    public function __construct(int $ID)
    {
        $this->ID = $ID;
    }
    public static function create(int $ID)
    {
        return new Marker($ID);
    }
    public function SetInfoBox(InfoBox $InfoBox)
    {
        $InfoBox->SetID($this->ID);
        $this->InfoBox = $InfoBox;
        return $this;
    }
    public function SetIconURL(string $IconPath)
    {
        $this->IconPath = $IconPath;
        return $this;
    }
    public function SetBase64Icon(string $Base64)
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
        if($this->IconPath != null)
        {
            return "{icon: '$this->IconPath'}";
        }
        if($this->Base64Icon != null)
        {
            return "{icon: '$this->Base64Icon'}";
        }
        if($this->IconVariable != null)
        {
            return "{icon: $this->IconVariable}";
        }
        
        return "null";
    }
    public function RenderInfoBoxClosingFunction()
    {
        if($this->InfoBox != null)
        {
            return $this->InfoBox->RenderHTMLCloser();
        }
        return "";
    }
    public function Render($mapVariable)
    {
        if($this->InfoBox != null && !$this->InfoBox->HasPosition())
        {
            $this->InfoBox->SetPosition($this->GetPosition());
        }
        $rendered = "";
        $rendered .= $this->RenderLocationVariable($this->ID,self::$Suffix)."\n";
        $rendered .= "var pushpin$this->ID = new Microsoft.Maps.Pushpin({$this->GetLocationVariable($this->ID,self::$Suffix)},{$this->RenderIcon()});\n";
        if($this->InfoBox != null)
        {
            $rendered .= $this->InfoBox->Render($mapVariable,"pushpin$this->ID");
        }
        $rendered .= "{$mapVariable}.entities.push(pushpin$this->ID);\n";
        return $rendered;
    }
}