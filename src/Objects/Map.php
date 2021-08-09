<?php
namespace bingMap;

//use bingMap\MapPosition;
//use SilverStripe\Dev\Debug;
//use SilverStripe\View\ViewableData;

class Map extends \ViewableData
{
    //use MapPosition;

    private $Debug;
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
    private $Zoom = null;

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
    public function SetCenterOnPins( $value)
    {
        $this->CenterOnPins = $value;
        return $this;
    }
    public function SetZoom($value)
    {
        $this->Zoom = $value;
        return $this;
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
    public function SetStyle( $style)
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
        if ($this->CenterOnPins == true) {
            $rendered .= "var locs = [];\n";
        }
        for ($i = 0; $i < count($this->Markers); $i++) {
            $rendered .= $this->Markers[$i]->Render($mapVariable);
            if ($this->CenterOnPins == true) {
                $loc = $this->Markers[$i]->RenderLocation();
                $rendered .= "locs.push($loc);\n";
            }
        }

        return $rendered;
    }
    private function RenderInfoBoxCloser()
    {
        $rendered = "";
        for ($i = 0; $i < count($this->Markers); $i++) {
            $rendered .= "function closeInfoBox(i){
                InfoBoxCollection[i].setOptions({visible:false});
            }";
        }
        return $rendered;
    }
    private function RenderMapCenteringOnPins($mapVariable)
    {
        if ($this->CenterOnPins == true) {
            return "{$mapVariable}.setView({\n
                bounds: Microsoft.Maps.LocationRect.fromLocations(locs),\n
                padding: $this->Padding\n
            });\n";
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
        if($this->Zoom != null)
        {
            return ",zoom: ".$this->Zoom;
        }
        return "";
    }

    public function RenderFunction()
    {
        $rendered = "";
        if ($this->loadOnStartClass != "") {
            $rendered .= "<script class='$this->loadOnStartClass' type='text/plain'>\n";
        } else {
            $rendered .= "<script type='text/javascript'>\n";
        }
        $rendered .= "var InfoBoxCollection = [];";
        $rendered .= "function GetMap{$this->ID}(){\n";
        $mapVariable = "map" . $this->ID;

        $rendered .= "
            var $mapVariable = new Microsoft.Maps.Map('#MapContainer{$this->ID}',{center:{$this->RenderLocation()} {$this->RenderZoom()}});\n
        ";
        $rendered .= $this->RenderIcon();

        $rendered .= $this->RenderMarkers($mapVariable);
        $rendered .= $this->RenderMapCenteringOnPins($mapVariable);

        $rendered .= "}\n";
        $rendered .= $this->RenderInfoBoxCloser();
        $rendered .= "</script>\n";
        if (!$this->Debug) {
            $rendered = HelperMethods::MinifyString($rendered);
        } else {
            $rendered = HelperMethods::RemoveEmptyLines($rendered);
        }

        return $rendered;
    }
    
    private function GetMarkersData()
    {
        $MarkersData = [];
        $iconPath = $this->IconPath;
        foreach($this->Markers as $Marker)
        {
            $MarkersData[] = $Marker->GetReactData($iconPath);
        }
        return $MarkersData;
    }
    public function GetReactData()
    {
        $data = [
            "key" => $this->ID,
            "loadOnStartClass"  =>  $this->loadOnStartClass,
            "centerOnPins"  =>  $this->CenterOnPins,
            "padding"   =>  $this->Padding,
            "markers"   => $this->GetMarkersData(),
            "zoom"  =>  $this->Zoom,
            "position"  =>  $this->Coords->GetReactData(),
        ];
        return $data;
    }
    public function GetJSONReactData()
    {
        return json_encode($this->GetReactData());
    }

    protected $Coords;

    public function SetPosition($coords)
    {
        $this->Coords = $coords;
        return $this;
    }
    public function GetPosition()
    {
        return $this->Coords;
    }
    public function GetLatitude()
    {
        return $this->Coords->GetLatitude();
    }
    public function GetLongitude()
    {
        return $this->Coords->GetLongitude();
    }
    public function GetLocationVariable($ID,$Suffix)
    {
        return "Location_{$Suffix}_{$ID}";
    }
    public function HasPosition()
    {
        return $this->Coords != null;
    }
    //Might Rename to IsValidCoordinate could cause misunderstanding
    public function IsValidCoordinate()
    {
        return $this->Coords->IsValid();
    }
    public function RenderLocationVariable($ID,$Suffix)
    {
        return "var Location_{$Suffix}_{$ID} = new Microsoft.Maps.Location({$this->GetLatitude()},{$this->GetLongitude()}); ";
    }
    public function RenderLocation()
    {
        if($this->HasPosition())
        {
            return "new Microsoft.Maps.Location({$this->GetLatitude()},{$this->GetLongitude()})";
        }
        return "";
    }

}
