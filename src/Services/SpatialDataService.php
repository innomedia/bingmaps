<?php
namespace bingMap;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Flushable;
use SilverStripe\SiteConfig\SiteConfig;
use Psr\SimpleCache\CacheInterface;

/**
 * Azure Maps Spatial Data Service
 * Handles fetching and caching of spatial boundary data to reduce API costs
 */
class SpatialDataService implements Flushable
{
    /**
     * Cache duration in seconds (24 hours by default)
     */
    private static $cache_duration = 86400;
    
    /**
     * Cache key prefix
     */
    private static $cache_prefix = 'azuremaps_spatial_';
    
    /**
     * Azure Maps API base URL
     */
    private static $api_base_url = 'https://atlas.microsoft.com';
    
    /**
     * @var CacheInterface
     */
    private $cache;
    
    public function __construct()
    {
        // Get cache instance using SilverStripe's dependency injection
        // This follows the PSR-16 standard for caching
        $this->cache = Injector::inst()->get(CacheInterface::class . '.AzureMapsSpatialCache');
    }
    
    /**
     * Get spatial boundary data for coordinates
     * 
     * @param float $lat Latitude
     * @param float $lng Longitude  
     * @param string $entityType Entity type (Postcode3, PopulatedPlace, etc.)
     * @param string $description Description for logging
     * @return array|null Polygon data or null if not found
     */
    public function getBoundaryForCoordinates($lat, $lng, $entityType, $description)
    {
        $cacheKey = $this->getCacheKey('coords', $lat, $lng, $entityType);
        
        // Check cache first
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            error_log("SpatialDataService: Using cached data for {$description}");
            return $cachedData;
        }
        
        error_log("SpatialDataService: Fetching fresh data for {$description} at ({$lat}, {$lng})");
        
        // Step 1: Reverse geocoding to get address
        $addressData = $this->reverseGeocode($lat, $lng);
        if (!$addressData) {
            error_log("SpatialDataService: Reverse geocoding failed for {$description}");
            return null;
        }
        
        // Step 2: Extract location query based on entity type
        $query = $this->extractLocationQuery($addressData, $entityType);
        if (!$query) {
            error_log("SpatialDataService: Could not extract location query for {$description}");
            return null;
        }
        
        // Step 3: Search for boundary data
        $boundaryData = $this->searchBoundary($query['query'], $query['entityType']);
        if (!$boundaryData) {
            error_log("SpatialDataService: Boundary search failed for {$description}");
            return null;
        }
        
        // Step 4: Fetch polygon geometry
        $polygonData = $this->fetchPolygonGeometry($boundaryData['geometryId']);
        if (!$polygonData) {
            error_log("SpatialDataService: Polygon fetch failed for {$description}");
            return null;
        }
        
        // Prepare result
        $result = [
            'description' => $description,
            'geometryId' => $boundaryData['geometryId'],
            'coordinates' => $polygonData['coordinates'],
            'query' => $query['query'],
            'entityType' => $query['entityType'],
            'address' => $addressData
        ];
        
        // Cache the result
        $this->cache->set($cacheKey, $result, self::$cache_duration);
        
        error_log("SpatialDataService: Successfully fetched and cached data for {$description}");
        return $result;
    }
    
    /**
     * Get spatial boundary data for a postal code
     * 
     * @param string $postalCode Postal code
     * @param string $description Description for logging
     * @return array|null Polygon data or null if not found
     */
    public function getBoundaryForPostalCode($postalCode, $description)
    {
        $cacheKey = $this->getCacheKey('postal', $postalCode);
        
        // Check cache first
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            error_log("SpatialDataService: Using cached postal code data for {$description}");
            return $cachedData;
        }
        
        error_log("SpatialDataService: Fetching fresh postal code data for {$description}");
        
        // Search for boundary data
        $boundaryData = $this->searchBoundary($postalCode, 'PostalCodeArea');
        if (!$boundaryData) {
            error_log("SpatialDataService: Postal code boundary search failed for {$description}");
            return null;
        }
        
        // Fetch polygon geometry
        $polygonData = $this->fetchPolygonGeometry($boundaryData['geometryId']);
        if (!$polygonData) {
            error_log("SpatialDataService: Postal code polygon fetch failed for {$description}");
            return null;
        }
        
        // Prepare result
        $result = [
            'description' => $description,
            'geometryId' => $boundaryData['geometryId'],
            'coordinates' => $polygonData['coordinates'],
            'query' => $postalCode,
            'entityType' => 'PostalCodeArea'
        ];
        
        // Cache the result
        $this->cache->set($cacheKey, $result, self::$cache_duration);
        
        error_log("SpatialDataService: Successfully fetched and cached postal code data for {$description}");
        return $result;
    }
    
    /**
     * Perform reverse geocoding
     */
    private function reverseGeocode($lat, $lng)
    {
        $apiKey = SiteConfig::current_site_config()->bingAPIKey;
        if (!$apiKey) {
            error_log("SpatialDataService: No Azure Maps API key configured");
            return null;
        }
        
        $url = self::$api_base_url . '/search/address/reverse/json?' . http_build_query([
            'api-version' => '1.0',
            'subscription-key' => $apiKey,
            'lat' => $lat,
            'lon' => $lng
        ]);
        
        $response = $this->makeHttpRequest($url);
        
        if ($response && isset($response['addresses']) && count($response['addresses']) > 0) {
            return $response['addresses'][0];
        }
        
        return null;
    }
    
    /**
     * Extract location query from address data based on entity type
     */
    private function extractLocationQuery($addressData, $entityType)
    {
        $address = $addressData['address'] ?? [];
        
        switch ($entityType) {
            case 'Postcode1':
            case 'Postcode2':
            case 'Postcode3':
            case 'Postcode4':
                return [
                    'query' => $address['postalCode'] ?? null,
                    'entityType' => 'PostalCodeArea'
                ];
                
            case 'PopulatedPlace':
                return [
                    'query' => $address['municipality'] ?? $address['localName'] ?? null,
                    'entityType' => 'Municipality'
                ];
                
            case 'AdminDivision1':
                return [
                    'query' => $address['countrySubdivision'] ?? null,
                    'entityType' => 'CountrySubdivision'
                ];
                
            case 'AdminDivision2':
                return [
                    'query' => $address['countrySecondarySubdivision'] ?? null,
                    'entityType' => 'CountrySecondarySubdivision'
                ];
                
            case 'CountryRegion':
                return [
                    'query' => $address['country'] ?? null,
                    'entityType' => 'Country'
                ];
                
            default:
                return [
                    'query' => $address['municipality'] ?? $address['localName'] ?? null,
                    'entityType' => 'Municipality'
                ];
        }
    }
    
    /**
     * Search for boundary data
     */
    private function searchBoundary($query, $entityType)
    {
        if (!$query) {
            return null;
        }
        
        $apiKey = SiteConfig::current_site_config()->bingAPIKey;
        if (!$apiKey) {
            return null;
        }
        
        $url = self::$api_base_url . '/search/address/json?' . http_build_query([
            'api-version' => '1.0',
            'subscription-key' => $apiKey,
            'query' => $query,
            'entityType' => $entityType,
            'limit' => 1
        ]);
        
        $response = $this->makeHttpRequest($url);
        
        if ($response && isset($response['results']) && count($response['results']) > 0) {
            $result = $response['results'][0];
            if (isset($result['dataSources']['geometry']['id'])) {
                return [
                    'geometryId' => $result['dataSources']['geometry']['id'],
                    'address' => $result['address'] ?? null
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Fetch polygon geometry by geometry ID
     */
    private function fetchPolygonGeometry($geometryId)
    {
        $apiKey = SiteConfig::current_site_config()->bingAPIKey;
        if (!$apiKey) {
            return null;
        }
        
        // Try different API versions for polygon endpoint
        $apiVersions = ['2023-06-01', '1.0', '2022-08-01'];
        
        foreach ($apiVersions as $apiVersion) {
            $url = self::$api_base_url . '/search/polygon?' . http_build_query([
                'api-version' => $apiVersion,
                'subscription-key' => $apiKey,
                'geometries' => $geometryId
            ]);
            
            $response = $this->makeHttpRequest($url);
            
            if ($response && isset($response['additionalData']) && count($response['additionalData']) > 0) {
                $polygonData = $response['additionalData'][0];
                if (isset($polygonData['geometryData']['coordinates'])) {
                    error_log("SpatialDataService: Successfully fetched polygon with API version {$apiVersion}");
                    return [
                        'coordinates' => $polygonData['geometryData']['coordinates'],
                        'apiVersion' => $apiVersion
                    ];
                }
            }
            
            error_log("SpatialDataService: Failed to fetch polygon with API version {$apiVersion}");
        }
        
        return null;
    }
    
    /**
     * Make HTTP request with error handling
     */
    private function makeHttpRequest($url)
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'method' => 'GET',
                'header' => [
                    'User-Agent: SilverStripe AzureMaps SpatialDataService/1.0'
                ]
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            error_log("SpatialDataService: HTTP request failed: " . ($error['message'] ?? 'Unknown error'));
            return null;
        }
        
        $data = json_decode($response, true);
        
        if ($data === null) {
            error_log("SpatialDataService: Failed to decode JSON response");
            return null;
        }
        
        return $data;
    }
    
    /**
     * Generate cache key
     */
    private function getCacheKey($type, ...$params)
    {
        $keyParts = array_merge([self::$cache_prefix, $type], $params);
        return implode('_', array_map('md5', $keyParts));
    }
    
    /**
     * Clear all cached spatial data
     */
    public function clearCache()
    {
        $this->cache->clear();
        error_log("SpatialDataService: Cache cleared successfully");
    }
    
    /**
     * Implement Flushable interface - called when ?flush=1 is triggered
     */
    public static function flush()
    {
        $service = Injector::inst()->get(self::class);
        $service->clearCache();
    }
}
