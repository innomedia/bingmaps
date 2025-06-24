# SilverStripe Azure Maps Abstraction

This module provides Azure Maps functionality (migrated from Bing Maps)

## Installation

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

