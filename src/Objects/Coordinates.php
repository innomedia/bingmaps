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
        $siteConfig = SiteConfig::current_site_config();
        $geoapifyAPIKey = $siteConfig->geoapifyAPIKey;
        $azureAPIKey = $siteConfig->bingAPIKey;
        
        // Try Geoapify first, fallback to Azure Maps
        if (!empty($geoapifyAPIKey)) {
            return self::getGeoapifyCoordinatesFromAddress($Address, $geoapifyAPIKey);
        } elseif (!empty($azureAPIKey)) {
            return self::getAzureCoordinatesFromAddress($Address, $azureAPIKey);
        } else {
            throw new \Exception("No API Key Found - either Geoapify or Azure Maps API key required");
        }
    }
    public static function GetCoordinatesFromQuery(string $query)
    {
        $siteConfig = SiteConfig::current_site_config();
        $geoapifyAPIKey = $siteConfig->geoapifyAPIKey;
        $azureAPIKey = $siteConfig->bingAPIKey;
        
        // Try Geoapify first, fallback to Azure Maps
        if (!empty($geoapifyAPIKey)) {
            return self::getGeoapifyCoordinatesFromQuery($query, $geoapifyAPIKey);
        } elseif (!empty($azureAPIKey)) {
            return self::getAzureCoordinatesFromQuery($query, $azureAPIKey);
        } else {
            throw new \Exception("No API Key Found - either Geoapify or Azure Maps API key required");
        }
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

    // Geoapify geocoding methods
    private static function getGeoapifyCoordinatesFromAddress(string $address, string $apiKey)
    {
        $addressLine = urlencode($address);
        $request = "https://api.geoapify.com/v1/geocode/search?text=$addressLine&apiKey=$apiKey";
        return self::getGeoapifyCoordinatesFromRequest($request);
    }

    private static function getGeoapifyCoordinatesFromQuery(string $query, string $apiKey)
    {
        $queryLine = urlencode($query);
        $request = "https://api.geoapify.com/v1/geocode/search?text=$queryLine&apiKey=$apiKey";
        return self::getGeoapifyCoordinatesFromRequest($request);
    }

    private static function getGeoapifyCoordinatesFromRequest($requestURL)
    {
        $output = file_get_contents($requestURL);
        $response = json_decode($output, true);

        // Extract data from Geoapify response format
        if (isset($response['features']) && count($response['features']) > 0) {
            $coordinates = $response['features'][0]['geometry']['coordinates'];
            $longitude = $coordinates[0];
            $latitude = $coordinates[1];
        } else {
            throw new \Exception("No coordinates found for the given address");
        }
        
        return new Coordinates($latitude, $longitude);
    }

    // Azure Maps fallback methods (renamed from existing)
    private static function getAzureCoordinatesFromAddress(string $address, string $apiKey)
    {
        $addressLine = urlencode($address);
        $request = "https://atlas.microsoft.com/search/address/json?subscription-key=$apiKey&api-version=1.0&query=$addressLine";
        return self::getCoordsFromRequest($request);
    }

    private static function getAzureCoordinatesFromQuery(string $query, string $apiKey)
    {
        $queryLine = urlencode($query);
        $request = "https://atlas.microsoft.com/search/fuzzy/json?subscription-key=$apiKey&api-version=1.0&query=$queryLine";
        return self::getCoordsFromRequest($request);
    }

}
