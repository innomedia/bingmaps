<?php
namespace bingMap;

use SilverStripe\Dev\Debug;
spl_autoload_register();

trait MapPosition
{
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
        return "var Location_{$Suffix}_{$ID} = [{$this->GetLongitude()}, {$this->GetLatitude()}]; ";
    }
    public function RenderLocation()
    {
        if($this->HasPosition())
        {
            return "[{$this->GetLongitude()}, {$this->GetLatitude()}]";
        }
        return "";
    } 
}