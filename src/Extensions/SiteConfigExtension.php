<?php

namespace bingMap;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

class SiteConfigExtension extends DataExtension
{
    private static $db = [
        "bingAPIKey"    =>  'Text',
        "geoapifyAPIKey" => 'Text'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Azure Maps',TextField::create('bingAPIKey','Azure Maps Subscription Key'));
        $fields->addFieldToTab('Root.Geoapify Maps',TextField::create('geoapifyAPIKey','Geoapify API Key'));
    }
}