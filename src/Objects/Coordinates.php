<?php
namespace bingMap;

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
        $addressLine = urlencode($Address);
        $request = "https://atlas.microsoft.com/search/address/json?subscription-key=$APIKey&api-version=1.0&query=$addressLine";
        return self::getCoordsFromRequest($request);
    }
    public static function GetCoordinatesFromQuery(string $query)
    {
        $APIKey = SiteConfig::current_site_config()->bingAPIKey;
        if ($APIKey == "") {
            throw new \Exception("No API Key Found");
        }
        $query = urlencode($query);
        $request = "https://atlas.microsoft.com/search/fuzzy/json?subscription-key=$APIKey&api-version=1.0&query=$query";
        return self::getCoordsFromRequest($request);
    }
    private static function getCoordsFromRequest($requestURL)
    {
        $output = file_get_contents($requestURL);
        $response = json_decode($output, true);

        // Extract data (e.g. latitude and longitude) from the results
        if (isset($response['results']) && count($response['results']) > 0) {
            $latitude = $response['results'][0]['position']['lat'];
            $longitude = $response['results'][0]['position']['lon'];
        } else {
            throw new \Exception("No coordinates found for the given address");
        }
        
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
