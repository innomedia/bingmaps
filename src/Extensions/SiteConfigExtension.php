<?php

namespace bingMap;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

class SiteConfigExtension extends DataExtension
{
    private static $db = [
        "bingAPIKey"    =>  'Text'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Bing',TextField::create('bingAPIKey','Bing API Schlüssel'));
    }
}