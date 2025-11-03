<?php

namespace bingMap;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;

class SiteConfigExtension extends Extension
{
    private static array $db = [
        "bingAPIKey"    =>  'Text'
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->addFieldToTab('Root.Azure Maps',TextField::create('bingAPIKey','Azure Maps Subscription Key'));
    }
}