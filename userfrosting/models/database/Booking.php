<?php

namespace UserFrosting;

use \Illuminate\Database\Capsule\Manager as Capsule;

/**
 * User Booking
 *
 * Represents a User object as stored in the database.
 *
 * @package Taxibo
 * @author Allan Th. Andersen
 *
 */
class Booking extends UFModel {
    
    /**
     * @var string The id of the table for the current model.
     */ 
    protected static $_table_id = "booking";    
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
    

    /**
     * Determine whether or not this User object is a guest user (id set to `user_id_guest`) or an authenticated user.
     *
     * @return boolean True if the user is a guest, false otherwise.
     */ 
    public function isGuest(){
        if (!isset($this->id) || $this->id == static::$app->config('user_id_guest'))   // Need to use loose comparison for now, because some DBs return `id` as a string
            return true;
        else
            return false;
    }
    
    /**
     * @todo
     */ 
    public static function isLoggedIn(){
        // TODO.  Not sure how to implement this right now.  Flag in DB?  Or, check sessions?
    }
    
    /**
     * Refresh the User and their associated Groups from the DB.
     *
     * @see http://stackoverflow.com/a/27748794/2970321
     */
    public function fresh(array $options = []){
        // TODO: Update table and column info, in case it has changed?
        $user = parent::fresh($options);
        $user->getGroupIds();
        $user->_primary_group = $user->fetchPrimaryGroup();      
        return $user;
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
        
         if ($name == "car") {
            return $this->getCar();
         } 
            
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

    public function save()
    {
        
        parent::save();

        
        if (strpos($this->alphaId, 'ZZZ') === FALSE) {
            return false;
        }
        $alpha1 = $this->originCity ? mb_substr($this->originCity,0,2) : 'X' . rand(1,9);
        $alpha2 = $this->destinationCity ? mb_substr($this->destinationCity,0,2) : 'Y' . rand(1, 9);
        $number = 1;

        $alphaId = mb_strtoupper($alpha1 . '-' . str_pad($number, 2, "0", STR_PAD_LEFT) . '-' . $alpha2);

        

        while (self::alphaIdExists($alphaId, $this->user_id)) {
            $number++;
            $alphaId = mb_strtoupper($alpha1 . '-' . str_pad($number, 2, "0", STR_PAD_LEFT) . '-' . $alpha2);
        
        }

        $this->alphaId = $alphaId;
        
        return $this->save();


    }

    public static function alphaIdExists($alphaId, $userId)
    {
        
        $booking = Booking::where('alphaId', $alphaId)
        ->where('user_id', $userId)->first();
        
        if ($booking) {
        
            return true;
        }
        
        return false;
    }

    public function getCar () {

        $carId = $this->carId ? $this->carId : 0;
        return Car::where('user_id', $this->user_id)->where('id', $carId)->first();
        
    }
    
}
