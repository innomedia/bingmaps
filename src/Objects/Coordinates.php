<?php
namespace bingMap;

use SimpleXMLElement;
use bingMap\Coordinates;
use SilverStripe\Dev\Debug;
use SilverStripe\SiteConfig\SiteConfig;

class Coordinates
{
    private $Latitude;
    private $Longitude;

    private function __construct($Latitude, $Longitude)
    {
        $this->Latitude = $Latitude;
        $this->Longitude = $Longitude;
    }
    public static function GetCoordinates($Latitude, $Longitude)
    {
        return new Coordinates($Latitude, $Longitude);
    }
    public static function GetCoordinatesFromAddress(string $Address)
    {
        $APIKey = SiteConfig::current_site_config()->bingAPIKey;
        if ($APIKey == "") {
            throw new \Exception("No API Key Found");
        }
        $addressLine = str_ireplace(" ", "%20", $Address);
        $request = "http://dev.virtualearth.net/REST/v1/Locations?addressLine=$addressLine&key=$APIKey&output=xml";
        return self::getCoordsFromRequest($request);
    }
    private static function getCoordsFromRequest($requestURL)
    {
        $output = file_get_contents($requestURL);
        $response = new SimpleXMLElement($output);

// Extract data (e.g. latitude and longitude) from the results
        $latitude = (string) $response->ResourceSets->ResourceSet->Resources->Location->Point->Latitude;
        $longitude = (string) $response->ResourceSets->ResourceSet->Resources->Location->Point->Longitude;
        return new Coordinates($latitude, $longitude);
    }
    public function GetLatitude()
    {
        return $this->Latitude;
    }
    public function GetLongitude()
    {
        return $this->Longitude;
    }
    public function IsValid()
    {
        return is_numeric($this->Latitude) && is_numeric($this->Longitude);
    }
    public function GetReactData()
    {
        return [
            "latitude" => $this->GetLatitude(),
            "longitude" => $this->GetLongitude(),
        ];
    }

}
