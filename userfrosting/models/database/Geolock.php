<?php

namespace UserFrosting;

use \Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Geolock
 *
 * Represents a rectangular area.
 *
 * @package Taxibo
 * @author Allan Th. Andersen
 *
 */
class Geolock extends UFModel {
    
    /**
     * @var string The id of the table for the current model.
     */ 
    protected static $_table_id = "geolock";    
    /**
     * @var bool Enable timestamps for Users.
     */ 
    public $timestamps = true;    
    
    /**
     * Create a new User object.
     *
     */
    public function __construct($properties = []) {    
            
        parent::__construct($properties);
    }
    

    /**
     * Determine if the property for this object exists. 
     *
     * Every property in __get must also be implemented here for Twig to recognize it.
     * @param string $name the name of the property to check.
     * @return bool true if the property is defined, false otherwise.
     */ 
    public function __isset($name) {
        if (in_array($name, [
                /* "primary_group",
                "theme",
                "icon",
                "landing_page",
                "last_sign_in_event",
                "last_sign_in_time",
                "sign_up_time",
                "last_password_reset_time",
                "last_verification_request_time" */
            ]))
            return true;
        else
            return parent::__isset($name);
    }
    
    /**
     * Get a property for this object.
     *
     * @param string $name the name of the property to retrieve.
     * @throws Exception the property does not exist for this object.
     * @return string the associated property.
     */
    public function __get($name){
        
         if ($name == "start")
            return $this->start();
        /*
        else if ($name == "last_sign_in_time")
            return $this->lastEventTime('sign_in');
        else if ($name == "sign_up_time")
            return $this->lastEventTime('sign_up');
        else if ($name == "last_password_reset_time")
            return $this->lastEventTime('password_reset_request');
        else if ($name == "last_verification_request_time")
            return $this->lastEventTime('verification_request');
        else if ($name == "primary_group")
            return $this->getPrimaryGroup();
        else if ($name == "theme")
            return $this->getPrimaryGroup()->theme;
        else if ($name == "icon")
            return $this->getPrimaryGroup()->icon;
        else if ($name == "landing_page")
            return $this->getPrimaryGroup()->landing_page;
        else */
            return parent::__get($name);
    }    


    
}
