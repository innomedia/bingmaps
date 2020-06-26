<?php

namespace bingMap;

use bingMap\MapPosition;
use bingMap\HelperMethods;
use SilverStripe\View\ViewableData;

class InfoBox
{
    use MapPosition;
    
    private int $ID;
    private ?string $Title = null;
    private ?string $Description = null;
    private bool $InitialVisibility = false;
    private ?string $HTMLContent = null;
    private static string $Suffix = "InfoBox";

    public function __construct()
    {
        $InitialVisibility = false;
    }
    public static function create()
    {
        return new InfoBox();
    }
    //Will be set by Marker to make JS code more readable
    public function SetID(int $ID)
    {
        $this->ID = $ID;
        return $this;
    }
    public function SetTitle(string $Title)
    {
        $this->Title = $Title;
        return $this;
    }
    public function SetDescription(string $Description)
    {
        $this->Description = $Description;
        return $this;
    }
    public function SetHTMLContent(string $HTMLContent)
    {
        $this->HTMLContent = $HTMLContent;
        return $this;
    }
    public function SetInitialVisibility(bool $InitialVisibility)
    {
        $this->InitialVisibility = $InitialVisibility;
        return $this;
    }
    public function hasID(int $ID)
    {
        return $this->ID == null;
    }
    
    private function RenderTitle()
    {
        if($this->Title != null)
        {
            return "title: '{$this->Title}',";
        }
        return "";
    }
    private function RenderDescription()
    {
        if($this->Description != null)
        {
            return "description: '{$this->Description}',";
        }
        return "";
    }
    private function RenderHTMLContent()
    {
        if($this->HTMLContent != null)
        {
            $renderer = new ViewableData();
            $rendered = $renderer->customise([
                "HTMLContent"   => $this->HTMLContent,
                "Title" =>  $this->Title,
                "ID"    =>  $this->ID
            ])->renderWith("HTMLInfoBox");
            $rendered = HelperMethods::prepareJavascriptString($rendered);
            return "htmlContent: '{$rendered}',";
        }
        return "";
    }
    public function RenderHTMLCloser()
    {
        if($this->HTMLContent != null)
        {
            return "function closeInfobox$this->ID(){
                infobox$this->ID.setOptions({visible:false});
            }";
        }
        return "";
    }
    private function RenderInitialVisibility()
    {
        if($this->InitialVisibility == true)
        {
            return "visible: true";
        }
        return "visible: false";
    }
    public function Render($mapVariable, $pushpinVariable)
    {
        if($this->IsValidCoordinate())
        {
            $rendered = "";
            $rendered .= $this->RenderLocationVariable($this->ID,self::$Suffix);
            $rendered .= "
            var infobox$this->ID = new Microsoft.Maps.Infobox(\n
                {$this->GetLocationVariable($this->ID,self::$Suffix)},{\n
                    {$this->RenderTitle()}\n
                    {$this->RenderDescription()}\n
                    {$this->RenderHTMLContent()}\n
                    {$this->RenderInitialVisibility()}\n
                }\n
            );\n
            InfoBoxCollection.push(infobox$this->ID);
            infobox{$this->ID}.setMap($mapVariable);\n
            Microsoft.Maps.Events.addHandler($pushpinVariable,'click',() => {\n
                infobox{$this->ID}.setOptions({visible:true});\n
            });\n
            ";
            
            return $rendered;
        }
        return "console.log('Skipping Invalid Coordinates');\n";
        
    }

}