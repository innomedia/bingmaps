# SilverStripe Azure Maps Abstraction

This module provides Azure Maps functionality with automatic OpenLayers fallback (migrated from Bing Maps)

## Installation

```bash
composer require innomedia/bingmaps
``` 
## Configuration

Go to https://docs.microsoft.com/en-us/azure/azure-maps/how-to-manage-account-keys and follow instructions to get an Azure Maps Subscription Key
and enter it inside the SiteConfig of your page under "Azure Maps" tab.

**Note:** This module has been migrated from Bing Maps to Azure Maps while maintaining API compatibility. All method names remain the same for drop-in replacement.

## Features

- **Azure Maps Integration**: Modern mapping service from Microsoft
- **WebGL Detection**: Automatically detects browser WebGL support
- **OpenLayers Fallback**: Falls back to OpenStreetMap when WebGL is not available
- **UserCentrics Support**: Full cookie consent management integration
- **Multi-language Support**: German, English, and French translations
- **Clustering**: Advanced marker clustering with custom icons
- **Spatial Data Services**: Polygon and boundary data support
- **Custom Styling**: Multiple map themes and customization options
- **Error Handling**: Comprehensive error handling with user-friendly messages

## Usage

```php
    //Map Constructor has 3 Optional Parameters 
    //$ID - In case there are multiple Maps on one Page and that causes Problems
    //$loadOnStartClass - In case you want to wait for pageLoad to reduce initial page Loading times then afterwards enable Scriptblock via JS
    //$Debug - true disables minification/removing redundant spaces and new line characters
    $map = bingMap\Map::createMap() // 
        ->SetPosition(bingMap\Coordinates::GetCoordinates($Latitute,$Longitude)) //Optional if you just want to display a map without pins to center maps at Coordinates
        ->SetStyle("width:100%;height:400px;position:relative;") //Required CSS-Style of the container of the map
        ->SetIcon($IconURL)// Renders Icon with Url from Web (might not work localy) (Priority 1)
        ->SetBase64Icon($Icon)// Renders Icon with Base64 StringFormat "data:image/png;base64,$base64Data" adapt image/png to your needs (Priority 2)
        ->SetCenterOnPins(false) //Optional default true - adds Script that centers Map so all Pins are visible
        ->SetCenterOnPinsPadding(40) //Optional default 50 - adds Padding to pin centering map (only works without SetCenterOnPins(false))
        ->SetZoom(5) //use With SetCenterOnPins(false)
        ->SetDarkMapType() //Sets Dark Theme
        ->SetLightMapType() //Sets Light Theme (Default?)
        ->SetGrayscaleMapType()  //Sets Grayscale Theme
        ->SetMapType(MapType) //Sets MapType Theme in case new themes are added
        ->setClusterLayer(true) // enables Clustering with advanced icon support
        ->setSpatialDataService(true) // enables Spatial Data Services for polygon boundaries
        ->setPolygoneData($polygonArray) // adds custom polygon overlays
        ->addScriptSetting("type","application/javascript") // only needed if you use a cookie consent tool
        
        // New Azure Maps specific interaction controls
        ->DisablePanning() // Disables map dragging/panning
        ->DisableZooming() // Disables all zoom interactions
        ->DisableRotation() // Disables map rotation
        ->DisablePitching() // Disables 3D pitch/tilt
        ->DisableMouseWheelZoom() // Disables scroll wheel zooming
        
        // New UI control methods
        ->HideZoomButtons() // Hides zoom control buttons
        ->HideCompass() // Hides compass control
        ->HidePitchToggle() // Hides pitch toggle control
        ->HideStylePicker() // Hides style picker control
        ;
        
    $Marker = bingMap\Marker::create($ID) //$ID - Some Number must be unique for all Markers
            ->SetPosition(bingMap\Coordinates::GetCoordinatesFromAddress("Teststreet 39 AreaCode AreaName"))
            ->SetIconURL($IconURL) // Sets IconURL (Priority: 1)
            ->SetBase64Icon($Base64String) // String format same as above (Priority: 2)
            ->SetIconVariable() // Has no parameters simply sets the default IconVariable if you just need 1 Icon for all Markers (requires Icon to be set in map) (Priority: 3)
            ->SetInfoBox($Tooltip) // For setting a tooltip to open upon click (initialization below)
            ->SetPostalCode($postalCode) // For spatial data services
            ;
    $map->AddMarker($Marker);

    $Tooltip = bingMap\InfoBox::create()
        ->SetTitle("Title") // Sets Title of InfoBox
        ->SetDescription("Some Text") // (Semi-Optional) Sets Description of default InfoBox
        ->SetHTMLContent($HTML) // (Semi-Optional) In case you want to use a Custom InfoBox template
        ->SetInitialVisibility(true) // If you want to display Infobox from the get go
        ->SetPosition($Marker->GetPosition()) // Generally want to take the same as $Marker but can supply own Coordinates Object
        ;
        
    // Coordinate creation methods
    Coordinates::GetCoordinates($Latitude,$Longitude); // creates a Coordinates object with both values
    Coordinates::GetCoordinatesFromAddress(/*string*/ $Address); // creates a Coordinates object after querying Azure Maps for Lat/Lng from Address

    return $map; // return Map object to Template remember to use $MapVariable.RAW in Template

    // React component support
    $reactData = $map->GetReactData(); // Get data for React component
    $jsonData = $map->GetJSONReactData(); // Get JSON encoded data for convenience
```

## WebGL Detection & Fallback

The module automatically detects WebGL support in the browser:

- **WebGL Available**: Uses Azure Maps for full functionality
- **No WebGL**: Automatically falls back to OpenLayers with OpenStreetMap
- **Seamless Experience**: Users get maps regardless of browser capabilities
- **Debug Logging**: Console messages indicate which mapping engine is being used

## UserCentrics Integration

Full support for UserCentrics cookie consent management:

```php
// Enable UserCentrics support in your template
<% if IsUserCentrics %>
    // UserCentrics integration is automatically handled
<% end_if %>
```

Features:
- **Automatic consent checking**: Monitors Azure Maps cookie consent status
- **Multi-language warnings**: Displays translated messages when cookies aren't accepted
- **Event handling**: Responds to consent changes in real-time
- **Graceful degradation**: Shows helpful messages instead of broken maps

## Spatial Data Services

Access to Azure Maps spatial data:

```php
$map->setSpatialDataService(true)
    ->setSpatialDataServiceType('Municipality') // or 'CountrySubdivision', 'Postcode1', etc.
    ->setSpatialDataServicePostalCodes($postalCodes);

// Get polygon data for coordinates
$polygonData = $map->getPolygonForCoordinate($latitude, $longitude);
```

## Custom Polygon Overlays

Add custom polygon overlays to your maps:

```php
$polygonData = [
    [
        'Coords' => [$coord1, $coord2, $coord3], // Array of Coordinates objects
        'Colors' => [
            'Background' => '#rgba(13, 66, 104, 0.5)',
            'Stroke' => '#0d4268'
        ]
    ]
];
$map->setPolygoneData($polygonData);
```

## Advanced Clustering

Enhanced clustering with custom icon support:

```php
$map->setClusterLayer(true); // Enables clustering
// Custom icons work automatically with clustering
$marker->SetIconURL($customIconPath); // Will be used in both clustered and individual views
```

## Error Handling & Debugging

Comprehensive error handling and debugging features:

```php
$map = bingMap\Map::createMap("myMap", "", true); // Enable debug mode
```

Features:
- **Library loading detection**: Automatically detects if Azure Maps library loads
- **API connectivity testing**: Built-in methods to test Azure Maps API connectivity
- **Console logging**: Detailed logging for debugging
- **User-friendly error messages**: Clear error messages for end users
- **Timeout handling**: Graceful handling of slow network connections

## Migration from Bing Maps

This module has been updated to use Azure Maps instead of the discontinued Bing Maps service. The migration maintains complete API compatibility:

- All method names remain identical
- All functionality is preserved and enhanced
- Only the underlying map service has changed
- Configuration now uses Azure Maps Subscription Key instead of Bing API Key
- React component has been updated to use Azure Maps Web SDK

### Breaking Changes
- **None** - This is designed as a drop-in replacement
- Simply update your Azure Maps subscription key in SiteConfig

### New Features in Azure Maps Migration
- **WebGL Detection**: Automatic fallback to OpenLayers when WebGL not supported
- **Enhanced Error Handling**: Better error messages and debugging capabilities  
- **UserCentrics Integration**: Full cookie consent management support
- **Multi-language Support**: German, English, and French translations
- **Advanced Clustering**: Improved clustering with custom icon support
- **Spatial Data Services**: Access to polygon and boundary data
- **Custom Polygon Overlays**: Add custom polygon shapes to maps
- **Enhanced Interaction Controls**: Fine-grained control over map interactions
- **Better Performance**: Modern API with improved loading and rendering
- **Active Development**: Ongoing support and updates from Microsoft

### Compatibility Matrix

| Feature | Bing Maps | Azure Maps | OpenLayers Fallback |
|---------|-----------|------------|-------------------|
| Basic Map Display | ✅ | ✅ | ✅ |
| Markers/Pins | ✅ | ✅ | ✅ |
| Custom Icons | ✅ | ✅ | ✅ |
| Info Boxes/Popups | ✅ | ✅ | ✅ |
| Clustering | ✅ | ✅ | ✅ |
| Map Themes | ✅ | ✅ | ✅ |
| Interaction Controls | Limited | ✅ | ✅ |
| WebGL Requirement | ❌ | ✅ | ❌ |
| Spatial Data | ✅ | ✅ | ❌ |
| Cookie Consent | ❌ | ✅ | ✅ |

## Troubleshooting

### Common Issues

**Map not displaying:**
1. Check if Azure Maps API key is configured in SiteConfig
2. Verify browser console for WebGL support messages
3. If using UserCentrics, ensure Azure Maps cookies are accepted

**WebGL Issues:**
- The module automatically falls back to OpenLayers if WebGL is not supported
- Check browser console for "WebGL supported" or fallback messages

**UserCentrics Integration:**
- Ensure UserCentrics service is named "Azure Maps" 
- Check browser console for consent status messages
- Verify translation files are loaded for proper error messages

**Performance Optimization:**
- Enable clustering for maps with many markers: `->setClusterLayer(true)`
- Use debug mode during development: `Map::createMap("id", "", true)`
- Optimize custom icon sizes and formats

### Debug Mode

Enable debug mode for detailed logging:

```php
$map = bingMap\Map::createMap("debugMap", "", true);
```

This provides:
- Detailed console logging
- Library loading status
- WebGL detection results  
- UserCentrics consent status
- API call debugging
- Performance timing information

## API Reference

### Map Control Methods

```php
// Interaction Controls
->DisablePanning()        // Disable map dragging
->DisableZooming()        // Disable zoom interactions
->DisableRotation()       // Disable map rotation  
->DisablePitching()       // Disable 3D tilt
->DisableMouseWheelZoom() // Disable scroll wheel zoom

// UI Controls  
->HideZoomButtons()       // Hide zoom buttons
->HideCompass()           // Hide compass control
->HidePitchToggle()       // Hide pitch control
->HideStylePicker()       // Hide style picker

// Data Services
->setSpatialDataService(true)                    // Enable spatial data
->setSpatialDataServiceType('Municipality')     // Set entity type
->setSpatialDataServicePostalCodes($codes)      // Set postal codes
->setPolygoneData($polygonArray)                // Add custom polygons

// Utility Methods
->getPolygonForCoordinate($lat, $lng)           // Get polygon for coordinate
->testAzureMapsConnectivity($apiKey)            // Test API connectivity
```

### Translation Keys

Available in `lang/` directory for German (de.yml), English (en.yml), and French (fr.yml):

- `bingMap.COOKIE_CONSENT_REQUIRED`: Cookie consent title
- `bingMap.COOKIE_CONSENT_MESSAGE`: Cookie consent explanation
```

