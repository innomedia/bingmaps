<?php
namespace bingMap;

use bingMap\MapPosition;
use SilverStripe\Dev\Debug;
use SilverStripe\View\ViewableData;

class Map extends ViewableData
{
    use MapPosition;

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
    private $ScriptSettings = [];
    private $Zoom = null;
    private $MouseWheelZoom = null;
    private $MapType = null;
    private $ClusterLayer = false;
    private $SpatialDataService = false;
    private $SpatialDataServiceType = "PopulatedPlace";
    /*
      • AdminDivision1: First administrative level within the country/region level, such as a state or a province.
  • AdminDivision2: Second administrative level within the country/region level, such as a county.
  • CountryRegion: Country or region.
  • Neighborhood: A section of a populated place that is typically well-known, but often with indistinct boundaries.
  • PopulatedPlace: A concentrated area of human settlement, such as a city, town or village.
  • Postcode1: The smallest post code category, such as a zip code.
  • Postcode2: The next largest post code category after Postcode1 that is created by aggregating Postcode1 areas.
  • Postcode3: The next largest post code category after Postcode2 that is created by aggregating Postcode2 areas.
  • Postcode4: The next largest post code category after Postcode3 that is created by aggregating Postcode3 areas.

    */

    public function __construct($ID = "1", $loadOnStartClass = "", $Debug = false)
    {
        $this->Debug = $Debug;
        $this->Debug = true;
        $this->loadOnStartClass = $loadOnStartClass;
        $this->ID = $ID;
    }
    public static function createMap($ID = "1", $loadOnStartClass = "", $Debug = false)
    {
        return new Map($ID, $loadOnStartClass, $Debug);
    }
    public function SetCenterOnPins($value)
    {
        $this->CenterOnPins = $value;
        return $this;
    }
    public function setClusterLayer($value)
    {
        $this->ClusterLayer = $value;
        return $this;
    }
    public function setSpatialDataService($value)
    {
        $this->SpatialDataService = $value;
        return $this;
    }
    public function addScriptSetting($key,$value)
    {
        $this->ScriptSettings[$key] = $value;   
    }
    public function removeScriptSetting($key)
    {
        unset($this->ScriptSettings[$key]);
    }
    public function setSpatialDataServiceType($value)
    {
        $this->SpatialDataServiceType = $value;
        return $this;
    }
    public function SetZoom($value)
    {
        $this->Zoom = $value;
        return $this;
    }
    public function DisableMouseWheelZoom()
    {
        $this->MouseWheelZoom = true;
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
    public function SetStyle($style)
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
    //When Bing adds new Types not covered by Set[Type]Type Methods
    public function SetMapType($Type)
    {
        $this->MapType = $Type;
        return $this;
    }
    public function SetDarkMapType()
    {
        return $this->SetMapType("Microsoft.Maps.MapTypeId.canvasDark");
    }
    //Default when nothing is set
    public function SetLightMapType()
    {
        return $this->SetMapType("Microsoft.Maps.MapTypeId.canvasLight");
    }
    public function SetGrayscaleMapType()
    {
        return $this->SetMapType("Microsoft.Maps.MapTypeId.grayscale");
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
        if ($this->CenterOnPins == true || $this->ClusterLayer == true) {
            $rendered .= "var locs = [];\n";
        }
        for ($i = 0; $i < count($this->Markers); $i++) {
            if($this->ClusterLayer == false)
            {
                $rendered .= $this->Markers[$i]->Render($mapVariable,$this->ClusterLayer);
                if ($this->CenterOnPins == true) {
                    $loc = $this->Markers[$i]->RenderLocation();
                    $rendered .= "locs.push($loc);\n";
                }
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
    private function RenderClusterLayer($mapVariable)
    {
        if ($this->ClusterLayer == true) {
            $rendered = "";
            for ($i = 0; $i < count($this->Markers); $i++) {
                $output = $this->Markers[$i]->RenderClusterMarker($mapVariable,true);
                $rendered .= $output["rendered"];
                $loc = $output["pushpinvariable"];
                $rendered .= "locs.push($loc);\n";
                
            }

            $rendered .= "Microsoft.Maps.loadModule('Microsoft.Maps.Clustering',function () {
                clusterLayer = new Microsoft.Maps.ClusterLayer(locs);
                {$mapVariable}.layers.insert(clusterLayer);
            });\n";
            return $rendered;
        }
        return "";
    }
    private function RenderSpatialDataService($mapVariable)
    {
        if ($this->SpatialDataService == true) {

            $rendered = "";

            $rendered .= "var geoDataRequestOptions = {
                entityType: '".$this->SpatialDataServiceType."'
            };\n";

            $rendered .= "Microsoft.Maps.loadModule('Microsoft.Maps.SpatialDataService',function () {";
                $rendered .= "var Locations = [];";
                for ($i = 0; $i < count($this->Markers); $i++) {
                    $rendered .= "Microsoft.Maps.SpatialDataService.GeoDataAPIManager.getBoundary(
                        ".$this->Markers[$i]->RenderLocation().",
                        geoDataRequestOptions,
                        {$mapVariable},
                        function (data) {
                            //Add the polygons to the map.
                            if (data.results && data.results.length > 0) {
                                {$mapVariable}.entities.push(data.results[0].Polygons);
                            }
                        });";
                }
                /*
                    
                */
            $rendered .="});\n";
            return $rendered;
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
        if ($this->Zoom != null) {
            return ",zoom: " . $this->Zoom;
        }
        return "";
    }
    public function RenderMapTypeID()
    {
        if($this->MapType != null && $this->MapType != "")
        {
            return ",mapTypeId: ".$this->MapType;
        }
        return "";
        
    }

    public function RenderOptions($mapVariable)
    {
        $script = "";

        $optionsarray = [];
        //Remember to
        if ($this->MouseWheelZoom != null) {
            $optionsarray["disableScrollWheelZoom"] = "true";
        }
        $options = "";
        foreach ($optionsarray as $key => $value) {
            $options .= $key . ": " . $value . ",";
        }

        if ($options != "") {
            $options = substr($options, 0, -1);
            $script .= $mapVariable . ".setOptions({{$options}});\n";
        }
        return $script;
    }

    public function RenderFunction()
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
        if ($this->loadOnStartClass != "" || $Attributes != "") {
            $rendered .= "<script class='$this->loadOnStartClass' $Attributes>\n";
        } else {
            $rendered .= "<script type='text/javascript'>\n";
        }
        $rendered .= "var InfoBoxCollection = [];";
        $rendered .= "function GetMap{$this->ID}(){\n";
        $mapVariable = "map" . $this->ID;

        $rendered .= "
            var $mapVariable = new Microsoft.Maps.Map('#MapContainer{$this->ID}',{center:{$this->RenderLocation()} {$this->RenderZoom()} {$this->RenderMapTypeID()}});\n
        ";
        $rendered .= $this->RenderOptions($mapVariable);
        $rendered .= $this->RenderIcon();

        $rendered .= $this->RenderMarkers($mapVariable);
        $rendered .= $this->RenderMapCenteringOnPins($mapVariable);
        $rendered .= $this->RenderClusterLayer($mapVariable);
        $rendered .= $this->RenderSpatialDataService($mapVariable);

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
        foreach ($this->Markers as $Marker) {
            $MarkersData[] = $Marker->GetReactData($iconPath);
        }
        return $MarkersData;
    }
    public function GetReactData()
    {
        $data = [
            "key" => $this->ID,
            "loadOnStartClass" => $this->loadOnStartClass,
            "centerOnPins" => $this->CenterOnPins,
            "padding" => $this->Padding,
            "markers" => $this->GetMarkersData(),
            "zoom" => $this->Zoom,
            "position" => $this->Coords->GetReactData(),
        ];
        return $data;
    }
    public function GetJSONReactData()
    {
        return json_encode($this->GetReactData());
    }

}
