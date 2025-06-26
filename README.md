# SilverStripe Maps Abstraction

This module provides comprehensive mapping functionality with support for multiple mapping providers:
- **Geoapify Maps** (Primary - newest implementation)
- **Azure Maps** (Fallback - migrated from Bing Maps)
- **OpenLayers** (Alternative implementation)

## Installation

Install via Composer:
```bash
composer require innomedia/bingmaps
```

## Migration to Geoapify

This module now includes **Geoapify Maps** support as the primary mapping solution. The migration provides:

### New Geoapify Features
- Modern MapLibre GL-based mapping
- High-performance vector tiles
- Comprehensive style options
- Enhanced clustering capabilities  
- Advanced Boundaries API integration
- Better geocoding accuracy

### API Key Configuration
Add your Geoapify API key to SiteConfig:
1. Go to Admin → Settings → Geoapify Maps
2. Enter your API key from [Geoapify](https://www.geoapify.com/)

### Backward Compatibility
- All existing Azure Maps functionality remains available
- Automatic fallback to Azure Maps if Geoapify key not configured
- API methods remain identical - drop-in replacement

## Basic Usage

### Using Geoapify (Recommended)

```php
use bingMap\GeoapifyMap;
use bingMap\Marker;
use bingMap\Coordinates;
use bingMap\InfoBox;

// Create map
$Map = new GeoapifyMap("1", "", false)
    ->setPosition(Coordinates::GetCoordinatesFromAddress("Your Address Here"))
    ->setZoom(15)
    ->setWidth(800)
    ->setHeight(600)
    ->SetRoadMapType() // or SetSatelliteMapType(), SetLightMapType(), SetGrayscaleMapType()
    ->setClusterLayer(true) // Enable clustering
    ->setSpatialDataService(true) // Enable boundaries
    ->addScriptSetting("type", "application/javascript");

// Create marker with custom icon and tooltip
$Marker = Marker::create("unique_id")
    ->SetPosition(Coordinates::GetCoordinatesFromAddress("Marker Location"))
    ->SetIconURL("path/to/icon.png")
    ->SetInfoBox(
        InfoBox::create()
            ->SetTitle("Marker Title")
            ->SetDescription("Marker Description")
            ->SetInitialVisibility(false)
    );

$Map->AddMarker($Marker);

// Render in template
echo $Map->XML_val();
```

### Using Azure Maps (Fallback)

```php
use bingMap\Map; // Original class

$Map = new Map("1", "", false)
    // ... same API as before
```

## Features

### Map Types
- `SetRoadMapType()` - Standard road map
- `SetSatelliteMapType()` - Satellite imagery  
- `SetLightMapType()` - Light theme
- `SetGrayscaleMapType()` - Grayscale theme

### Markers & Clustering
- Custom marker icons (URL, Base64, or variable)
- Automatic marker clustering with `setClusterLayer(true)`
- Interactive tooltips/popups
- Custom marker styling

### Spatial Data / Boundaries
- Boundary polygon display with `setSpatialDataService(true)`
- Postal code boundaries
- Administrative boundaries
- Custom polygon data

### Geocoding
Supports both address and fuzzy search:
```php
$coords1 = Coordinates::GetCoordinatesFromAddress("123 Main St, City, Country");
$coords2 = Coordinates::GetCoordinatesFromQuery("Landmark or Place Name");
```

## React Integration

For React applications, use the GeoapifyMap component:

```javascript
import { GeoapifyMap } from './GeoapifyMap.js';

<GeoapifyMap 
    APIKey="your-geoapify-key"
    Data={{
        position: { longitude: 0, latitude: 0 },
        zoom: 10,
        markers: [...],
        mapType: 'road'
    }}
    Position={{ latitude: 0, longitude: 0 }}
    CenterOnPins={true}
/>
```

```bash
composer require innomedia/bingmaps
``` 
## Configuration

Go to https://docs.microsoft.com/en-us/azure/azure-maps/how-to-manage-account-keys and follow instructions to get an Azure Maps Subscription Key
and enter it inside the SiteConfig of your page under "Azure Maps" tab.

**Note:** This module has been migrated from Bing Maps to Azure Maps while maintaining API compatibility. All method names remain the same for drop-in replacement.

## Usage

```
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
        ->SetMapType(MapType) //  //Sets MapType Theme in case bing adds new themes
        ->setClusterLayer(true) // enables Clusting does nor really work with custom pin icons cluster icons and custom icons are both loaded
        ->addScriptSetting("type","application/javascript"); // only needed if you use a cookie consent tool you may add additional attributes for those
        ;
    $Marker = bingMap\Marker::create($ID) //$ID - Some Number must be unique for all Markers
            ->SetPosition(bingMap\Coordinates::GetCoordinatesFromAddress("Teststreet 39 AreaCode AreaName"))
            ->SetIconURL($IconURL) // Sets IconURL (Priority: 1)
            ->SetBase64Icon($Base64String) // String format same as above (Priority: 2)
            ->SetIconVariable() // Has no parameters simply sets the default IconVariable if you just need 1 Icon for all Markers (requires Icon to be set in map) (Priority: 3)
            ->SetInfoBox($Tooltip) // For setting a tooltip to open upon click (initilization below (needs to be above in reality))
            ;
    $map->AddMarker($Marker);

    $Tooltip = bingMap\InfoBox::create()
    ->SetTitle("Title") // Sets Title of InfoBox
    ->SetDescription("Some Text") // (Semi-Optional) Sets Description of default InfoBox
    ->SetHTMLContent($HTML) // (Semi-Optional) In case you want to use a Custom InfoBox in this case add your own HTMLInfoBox.ss template adapt original one in Module
    ->SetInitialVisibility(true) // If you want to display Infobox from the get go
    ->SetPosition($Marker->GetPosition()) // Generally want to take the same as $Marker but can simply supply own Coordinates Object
    ;
    Coordinates::GetCoordinates($Latitude,$Longitude); // creates a Coordinates object with both values
    Coordinates::GetCoordinatesFromAddress(/*string*/ $Address); // creates a Coordinates object after querying Azure Maps for Lat/Lng from Address
*as of 0.2.2
    $map->DisableMouseWheelZoom(); will disable mouswheel zooming

    return $map; // return Map object to Template remember to use $MapVariable.RAW in Template

    alternatively you can also use the React component and get the data it needs with $map->GetReactData();
    If you need the data as JSON you can use $map->GetJSONReactData; (simple json_encode version of GetReactData() result for convenience)

## Migration from Bing Maps

This module has been updated to use Azure Maps instead of the discontinued Bing Maps service. The migration maintains complete API compatibility:

- All method names remain identical
- All functionality is preserved  
- Only the underlying map service has changed
- Configuration now uses Azure Maps Subscription Key instead of Bing API Key
- React component has been updated to use Azure Maps Web SDK

### Breaking Changes
- **None** - This is designed as a drop-in replacement
- Simply update your Azure Maps subscription key in SiteConfig

### New Features in Azure Maps
- More reliable geocoding service
- Better performance and modern API
- Active development and support from Microsoft
```

