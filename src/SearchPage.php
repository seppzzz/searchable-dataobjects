<?php

namespace seppzzz\SearchableDataObjects;

use \Page;
use \PageController;

class SearchPage extends Page
{

    private static $db = array(
    );

    private static $has_one = array(
    );

    private static $defaults = array(
        'ShowInMenu' => false
    );

    private static $table_name = 'SearchPage';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        return $fields;
    }
}

class SearchPageController extends PageController
{
}
