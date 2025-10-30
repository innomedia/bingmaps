<?php

namespace bingMap;

use SilverStripe\Model\ModelData;
use bingMap\MapPosition;
use bingMap\HelperMethods;

class InfoBox
{
    use MapPosition;
    
    private $ID;
    
    private $Title;
    
    private $Description;
    
    private $InitialVisibility = false;
    
    private $HTMLContent;
    
    private static string $Suffix = "InfoBox";

    
    public static function create(): InfoBox
    {
        return new InfoBox();
    }
    
    //Will be set by Marker to make JS code more readable
    public function SetID($ID): static
    {
        $this->ID = $ID;
        return $this;
    }
    
    public function SetTitle($Title): static
    {
        $this->Title = $Title;
        return $this;
    }
    
    public function SetContent($Content): static
    {
        $this->Description = $Content;
        return $this;
    }
    
    public function SetDescription($Description): static
    {
        $this->Description = $Description;
        return $this;
    }
    
    public function SetHTMLContent($HTMLContent): static
    {
        $this->HTMLContent = $HTMLContent;
        return $this;
    }
    
    public function SetInitialVisibility($InitialVisibility): static
    {
        $this->InitialVisibility = $InitialVisibility;
        return $this;
    }
    
    public function hasID($ID): bool
    {
        return $this->ID == null;
    }
    
    private function getRenderedHTMLContent(): string|array
    {
        if($this->HTMLContent != null)
        {
            $renderer = ModelData::create();
            $rendered = $renderer->customise([
                "HTMLContent"   => $this->HTMLContent,
                "Title" =>  $this->Title,
                "ID"    =>  $this->ID
            ])->renderWith("HTMLInfoBox");
            return HelperMethods::prepareJavascriptString($rendered);
        }
        
        return "";
    }
    
    public function RenderHTMLCloser(): string
    {
        if($this->HTMLContent != null)
        {
            return "function closePopup$this->ID(){
                popup$this->ID.close();
            }";
        }
        
        return "";
    }
    
    public function Render($mapVariable, $markerVariable): string
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
            
            return $rendered . "var popup$this->ID = new atlas.Popup({
                position: {$this->GetLocationVariable($this->ID,self::$Suffix)},
                content: {$contentJson}
            });
            InfoBoxCollection.push(popup$this->ID);
            {$mapVariable}.popups.add(popup$this->ID);
            {$mapVariable}.events.add('click', {$markerVariable}, function() {
                popup{$this->ID}.open({$mapVariable});
            });
            ";
        }
        
        return "console.log('Skipping Invalid Coordinates');";
        
    }
    
    public function GetContent(): string
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
        
        return $content . "</div>";
    }
    
    public function GetReactData(): ?array
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