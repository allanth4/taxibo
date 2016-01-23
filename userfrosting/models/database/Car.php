<?php

namespace UserFrosting;

use \Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Car
 *
 * Represents a Car object as stored in the database.
 *
 * @package Taxibo
 * @author Allan Th. Andersen
 *
 */
class Car extends UFModel {
    
    /**
     * @var string The id of the table for the current model.
     */ 
    protected static $_table_id = "car";    
    /**
     * @var bool Enable timestamps for Users.
     */ 
    public $timestamps = true;    
    
    /**
     * Create a new User object.
     *
     */
    public function __construct($properties = []) {    
        // Set default locale, if not specified
        if (!isset($properties['locale']))
            $properties['locale'] = static::$app->site->default_locale;
            
        parent::__construct($properties);
    }
    


    
}
