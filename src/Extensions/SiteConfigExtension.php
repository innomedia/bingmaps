<?php

namespace bingMap;

class SiteConfigExtension extends  \DataExtension
{
    private static $db = array(
        "bingAPIKey"    =>  'Text'
    );

    public function updateCMSFields(\FieldList $fields)
    {
        $fields->addFieldToTab('Root.Bing',\TextField::create('bingAPIKey','Bing API Schlüssel'));
    }
}
