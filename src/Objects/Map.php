<?php
namespace bingMap;

use bingMap\MapPosition;
use SilverStripe\Dev\Debug;
use SilverStripe\View\ViewableData;

class Map extends ViewableData
{
    use MapPosition;

    private bool $Debug;
    private string $ID;
    private string $Style;
    private string $loadOnStartClass;
    private ?string $IconPath = null;
    private ?string $Base64Icon = null;
    private bool $CenterOnPins = true;
    private int $Padding = 50;
    private $Markers = [];

    public function __construct(string $ID = "1", string $loadOnStartClass = "", $Debug = false)
    {
        $this->Debug = $Debug;
        $this->loadOnStartClass = $loadOnStartClass;
        $this->ID = $ID;
    }
    public static function create(string $ID = "1", string $loadOnStartClass = "", $Debug = false)
    {
        return new Map($ID, $loadOnStartClass, $Debug);
    }
    public function SetCenterOnPins(bool $value)
    {
        $this->CenterOnPins = $value;
        return $this;
    }
    public function SetIcon(string $IconPath)
    {
        $this->IconPath = $IconPath;
        return $this;
    }
    public function SetCenterOnPinsPadding(int $value)
    {
        $this->Padding = $value;
        return $this;
    }
    public function SetBase64Icon(string $Base64)
    {
        $this->Base64Icon = $Base64;
        return $this;
    }
    public function SetStyle(string $style)
    {
        $this->Style = $style;
        return $this;
    }
    public function HasLoadOnStartClass()
    {
        return $this->loadOnStartClass != "";
    }
    public function AddMarker(Marker $marker)
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
            "Style" => $this->Style,
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
            var $mapVariable = new Microsoft.Maps.Map('#MapContainer{$this->ID}',{center:{$this->RenderLocation()}});\n
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

}
