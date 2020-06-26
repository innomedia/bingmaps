# SilverStripe Bing Map Abstraction

This module provides bing map functionality

## Installation

```bash
composer require innomedia/bingmaps "0.1"
``` 
## Configuration

Go to https://docs.microsoft.com/en-us/bingmaps/getting-started/bing-maps-dev-center-help/getting-a-bing-maps-key and follow instructions to get an API Key
and enter it inside the SiteConfig of your page.

## Usage

```
    //Map Constructor has 3 Optional Parameters 
    //$ID - In case there are multiple Maps on one Page and that causes Problems
    //$loadOnStartClass - In case you want to wait for pageLoad to reduce initial page Loading times then afterwards enable Scriptblock via JS
    //$Debug - true disables minification/removing redundant spaces and new line characters
    $map = bingMap\Map::create() // 
        ->SetPosition(bingMap\Coordinates::GetCoordinates($Latitute,$Longitude)) //Optional if you just want to display a map without pins to center maps at Coordinates
        ->SetStyle("width:100%;height:400px;position:relative;") //Required CSS-Style of the container of the map
        ->SetIcon($IconURL)// Renders Icon with Url from Web (might not work localy) (Priority 1)
        ->SetBase64Icon($Icon)// Renders Icon with Base64 StringFormat "data:image/png;base64,$base64Data" adapt image/png to your needs (Priority 2)
        ->SetCenterOnPins(false) //Optional default true - adds Script that centers Map so all Pins are visible
        ->SetCenterOnPinsPadding(40) //Optional default 50 - adds Padding to pin centering map
        ;
    $Marker = bingMap\Marker::create($ID) //$ID - Some Number must be unique to
            ->SetPosition(bingMap\Coordinates::GetCoordinatesFromAddress("Westendstraße 20 77971 Kippenheim"))
            ->SetIconURL($IconURL) // Sets IconURL (Priority: 1)
            ->SetBase64Icon($Base64String) // String format same as above (Priority: 2)
            ->SetIconVariable() // Has no parameters simply sets the default IconVariable if you just need 1 Icon for all Markers (Priority: 3)
            ->SetInfoBox($Tooltip) // For setting a tooltip to open upon click (initilization below (needs to be above in reality))
            ;
    $map->AddMarker($Marker);

    $Tooltip = bingMap\InfoBox::create()
    ->SetTitle("Title") // Sets Title of InfoBox
    ->SetDescription("Some Text") // (Semi-Optional) Sets Description of default InfoBox
    ->SetHTMLContent($HTML) // (Semi-Optional) In case you want to use a Custom InfoBox in this case add your own HTMLInfoBox.ss template adapt original one in Module
    ->SetInitialVisibility(true) // If you want to display Infobox from the get go
    ->SetPosition($Marker->GetPosition()) // Generally want to take the same as $Marker but can simply supply own Coordinates Object
    
    Coordinates::GetCoordinates($Latitude,$Longitude) // creates a Coordinates object with both values
    Coordinates::GetCoordinatesFromAddress(/*string*/ $Address) // creates a Coordinates object after querying Bing for Lat/Lng from Address

    return $map; // return Map object to Template remember to use $MapVariable.RAW in Template
```

