<?php
namespace bingMap;

use Exception;
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
    
    public static function GetCoordinates($Latitude, $Longitude): Coordinates
    {
        return new Coordinates($Latitude, $Longitude);
    }
    
    public static function GetCoordinatesFromAddress(string $Address): Coordinates
    {
        $APIKey = SiteConfig::current_site_config()->bingAPIKey;
        if ($APIKey == "") {
            throw new Exception("No API Key Found");
        }
        
        $addressLine = urlencode($Address);
        $request = sprintf('https://atlas.microsoft.com/search/address/json?subscription-key=%s&api-version=1.0&query=%s', $APIKey, $addressLine);
        return self::getCoordsFromRequest($request);
    }
    
    public static function GetCoordinatesFromQuery(string $query): Coordinates
    {
        $APIKey = SiteConfig::current_site_config()->bingAPIKey;
        if ($APIKey == "") {
            throw new Exception("No API Key Found");
        }
        
        $query = urlencode($query);
        $request = sprintf('https://atlas.microsoft.com/search/fuzzy/json?subscription-key=%s&api-version=1.0&query=%s', $APIKey, $query);
        return self::getCoordsFromRequest($request);
    }
    
    private static function getCoordsFromRequest(string $requestURL): Coordinates
    {
        $output = file_get_contents($requestURL);
        $response = json_decode($output, true);

        // Extract data (e.g. latitude and longitude) from the results
        if (isset($response['results']) && count($response['results']) > 0) {
            $latitude = $response['results'][0]['position']['lat'];
            $longitude = $response['results'][0]['position']['lon'];
        } else {
            throw new Exception("No coordinates found for the given address");
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
    
    public function IsValid(): bool
    {
        return is_numeric($this->Latitude) && is_numeric($this->Longitude);
    }
    
    public function GetReactData(): array
    {
        return [
            "latitude" => $this->GetLatitude(),
            "longitude" => $this->GetLongitude(),
        ];
    }

}
