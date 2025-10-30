<?php
namespace bingMap;

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
    
    public function GetLocationVariable($ID,string $Suffix): string
    {
        return sprintf('Location_%s_%s', $Suffix, $ID);
    }
    
    public function HasPosition(): bool
    {
        return $this->Coords != null;
    }
    
    //Might Rename to IsValidCoordinate could cause misunderstanding
    public function IsValidCoordinate()
    {
        return $this->Coords->IsValid();
    }
    
    public function RenderLocationVariable($ID,string $Suffix): string
    {
        return sprintf('var Location_%s_%s = [%s, %s]; ', $Suffix, $ID, $this->GetLongitude(), $this->GetLatitude());
    }
    
    public function RenderLocation(): string
    {
        if($this->HasPosition())
        {
            return sprintf('[%s, %s]', $this->GetLongitude(), $this->GetLatitude());
        }
        
        return "";
    } 
}