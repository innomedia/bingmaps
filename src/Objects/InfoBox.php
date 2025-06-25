<?php

namespace bingMap;

use bingMap\MapPosition;
use bingMap\HelperMethods;
use SilverStripe\View\ViewableData;

class InfoBox
{
    use MapPosition;
    
    private $ID;
    private $Title = null;
    private $Description = null;
    private $InitialVisibility = false;
    private $HTMLContent = null;
    private static $Suffix = "InfoBox";

    public function __construct()
    {
        $InitialVisibility = false;
    }
    public static function create()
    {
        return new InfoBox();
    }
    //Will be set by Marker to make JS code more readable
    public function SetID($ID)
    {
        $this->ID = $ID;
        return $this;
    }
    public function SetTitle($Title)
    {
        $this->Title = $Title;
        return $this;
    }
    public function SetContent($Content)
    {
        $this->Description = $Content;
        return $this;
    }
    public function SetDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }
    public function SetHTMLContent($HTMLContent)
    {
        $this->HTMLContent = $HTMLContent;
        return $this;
    }
    public function SetInitialVisibility($InitialVisibility)
    {
        $this->InitialVisibility = $InitialVisibility;
        return $this;
    }
    public function hasID($ID)
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
            return "content: '{$this->Description}',";
        }
        return "";
    }
    private function RenderHTMLContent()
    {
        if($this->HTMLContent != null)
        {
            $rendered = $this->getRenderedHTMLContent();
            return "content: '{$rendered}',";
        }
        return "";
    }
    private function getRenderedHTMLContent()
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
            return $rendered;
        }
        return "";
    }
    public function RenderHTMLCloser()
    {
        if($this->HTMLContent != null)
        {
            return "function closePopup$this->ID(){
                popup$this->ID.close();
            }";
        }
        return "";
    }
    private function RenderInitialVisibility()
    {
        if($this->InitialVisibility == true)
        {
            return "isVisible: true";
        }
        return "isVisible: false";
    }
    public function Render($mapVariable, $markerVariable)
    {
        if($this->IsValidCoordinate())
        {
            $rendered = "";
            $rendered .= $this->RenderLocationVariable($this->ID,self::$Suffix);
            
            // Build content - use double quotes to avoid conflicts with single quotes in HTML
            $content = '<div style="padding:10px">';
            if ($this->Title) {
                $content .= '<h3>' . htmlspecialchars($this->Title, ENT_QUOTES) . '</h3>';
            }
            if ($this->Description) {
                $content .= '<p>' . htmlspecialchars($this->Description, ENT_QUOTES) . '</p>';
            }
            if ($this->HTMLContent) {
                $content .= htmlspecialchars($this->getRenderedHTMLContent(), ENT_QUOTES);
            }
            $content .= '</div>';
            
            // Use json_encode to properly escape the content for JavaScript
            $contentJson = json_encode($content);
            
            $rendered .= "var popup$this->ID = new atlas.Popup({
                position: {$this->GetLocationVariable($this->ID,self::$Suffix)},
                content: $contentJson
            });
            InfoBoxCollection.push(popup$this->ID);
            {$mapVariable}.popups.add(popup$this->ID);
            {$mapVariable}.events.add('click', $markerVariable, function() {
                popup{$this->ID}.open({$mapVariable});
            });
            ";
            
            return $rendered;
        }
        return "console.log('Skipping Invalid Coordinates');";
        
    }
    public function GetContent()
    {
        $content = "<div style='padding:10px'>";
        if ($this->Title) {
            $content .= "<h3>" . htmlspecialchars($this->Title) . "</h3>";
        }
        if ($this->Description) {
            $content .= "<p>" . htmlspecialchars($this->Description) . "</p>";
        }
        if ($this->HTMLContent) {
            $content .= $this->getRenderedHTMLContent();
        }
        $content .= "</div>";
        return $content;
    }
    public function GetReactData()
    {
        //we don't want to return false data that has no position to prevent map from not working at all
        if(!$this->IsValidCoordinate())
        {
            return null;
        }
        return [
            "key" => $this->ID,
            "title" => $this->Title,
            "description" => $this->Description,
            "initialVisibility" => $this->InitialVisibility,
            "htmlContent"   =>  $this->getRenderedHTMLContent(),
            "coordinates"   => $this->GetPosition()->GetReactData()
        ];
    }
}