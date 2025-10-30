<?php

namespace bingMap;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Extension;

class SiteConfigExtension extends Extension
{
    private static $db = [
        "bingAPIKey"    =>  'Text'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Azure Maps',TextField::create('bingAPIKey','Azure Maps Subscription Key'));
    }
}