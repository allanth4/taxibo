<?php
        
    // This is the path to initialize.php, your site's gateway to the rest of the UF codebase!  Make sure that it is correct!
    $init_path = "../userfrosting/initialize.php";

    // This if-block just checks that the path for initialize.php is correct.  Remove this once you know what you're doing.
    if (!file_exists($init_path)){
        echo "<h2>We can't seem to find our way to initialize.php!  Please check the require_once statement at the top of index.php, and make sure it contains the correct path to initialize.php.</h2><br>";
    }

    require_once($init_path);

    use UserFrosting as UF;
   
    // Front page
    $app->get('/', function () use ($app) {
        // This if-block detects if mod_rewrite is enabled.
        // This is just an anti-noob device, remove it if you know how to read the docs and/or breathe through your nose.
        if (isset($_SERVER['SERVER_TYPE']) && ($_SERVER['SERVER_TYPE'] == "Apache") && !isset($_SERVER['HTTP_MOD_REWRITE'])) {
            $app->render('errors/bad-config.twig');
            exit;
        }
    
        // Check that we can connect to the DB.  Again, you can remove this if you know what you're doing.
        if (!UF\Database::testConnection()){
            // In case the error is because someone is trying to reinstall with new db info while still logged in, log them out
            session_destroy();
            // TODO: log out from remember me as well.
            $controller = new UF\AccountController($app);
            return $controller->pageDatabaseError();
        }
    
        // Forward to installation if not complete
        // TODO: Is there any way to detect that installation was complete, but the DB is malfunctioning?
        if (!isset($app->site->install_status) || $app->site->install_status == "pending"){
            $app->redirect($app->urlFor('uri_install'));
        }
        
        // Forward to the user's landing page (if logged in), otherwise take them to the home page
        // This is probably where you, the developer, would start making changes if you need to change the default behavior.
        if ($app->user->isGuest()){
            $controller = new UF\AccountController($app);
            $controller->pageHome();
        // If this is the first the root user is logging in, take them to site settings
        } else if ($app->user->id == $app->config('user_id_master') && $app->site->install_status == "new"){
            $app->site->install_status = "complete";
            $app->site->store();
            $app->alerts->addMessage("success", "Congratulations, you've successfully logged in for the first time.  Please take a moment to customize your site settings.");
            $app->redirect($app->urlFor('uri_settings'));  
        } else {
            $app->redirect($app->user->landing_page);        
        }
    })->name('uri_home');

    /********** FEATURE PAGES **********/
    /*
    $app->get('/test/?', function () use ($app) {

        error_log("test, line: " . __LINE__);
        $booking = UF\Booking::where('id', 100)->first();
        $sent = UF\TaxiMail::sendBookingReceivedMail($booking, 2);
        error_log("test, line: " . __LINE__ . ", success: " . var_export($sent, 1));
        error_log("test, line: " . __LINE__);

    });
    */

    $app->post('/cars/?', function() use ($app){

        $post = $app->request->post();
        
        if (isset($post['save'])) {

            $car = new UF\Car;
            $car->user_id = $app->user->id;

            if (!empty($post['id'])) {
                $car = UF\Car::where('user_id', $app->user->id)->where('id', $post['id'])->first();
            }
            
            foreach(['title', 'email', 'phone'] as $field) {
                if (!empty($post[$field])) {
                    $car->$field = $post[$field];
                }
            }
            
            $car->save();

            $uri = $app->site->uri['public'] . '/cars';
            header("Location: $uri");
            exit;
        } elseif (isset($post['id'], $post['delete'])) {

            $car = UF\Car::where('user_id', $app->user->id)->where('id', $post['id'])->first();
            $car->delete();

            $uri = $app->site->uri['public'] . '/cars';
            header("Location: $uri");
            exit;

        }

    });

    $app->get('/cars/?', function() use ($app){


        $cars = UF\Car::where('user_id', $app->user->id)
        ->orderBy('title')
        ->get();

        if ($cars->isEmpty()) {

            $car = new UF\Car;
            $car->user_id = $app->user->id;
            $car->title = "Standardbil";
            $car->email = "";
            $car->phone = "";
            $car->save();

            $cars = UF\Car::where('user_id', $app->user->id)->get();
        }


        $app->render('cars.twig', [
            "cars" => $cars,
            'csrf' => $_SESSION['csrf_token'],
        ]); 

    });

    $app->get('/booking/:booking_id/:action/:hash/?', function ($bookingId, $action, $hash) use ($app) {
        
        $action = $action == 'reject' ? 'reject' : 'accept';

        $booking = UF\Booking::where('id', $bookingId)->where('hash', $hash)->first();
        if (!$booking) {
            exit("Accept/reject booking $bookingId: Booking not found");
        }



        switch ($action) {
            case 'accept':
                
                $booking->status = 'accepted'; 
                break;
            
            case 'reject':
                $booking->status = 'rejected';

                break;
        }
        
        $booking->save();
        UF\TaxiMail::sendBookingHandled($booking, $action);
        
        print "Booking " . $booking->status;
        exit;

    });

    $app->get('/book/:user_id/?', function ($userId) use ($app) {
        
        $controller = new UF\BookingController($app);
        $controller->showBookingForm($userId);
    
    });

    $app->get('/book/:user_id/getOffer/:origin/:destination/:date/:time/?', function ($userId, $origin, $destination, $date, $time) use ($app) {
        
        $get = $app->request->get();
        $latLng = $get['latLng'];


        $controller = new UF\BookingController($app);
        $offer = $controller->getOffer($userId, $origin, $destination, $date, $time, $latLng);
        print json_encode($offer);
    });

    $app->post('/book/:user_id/book/?', function ($userId) use ($app) {

        $post = $app->request->post();
        $controller = new UF\BookingController($app);
        $result = $controller->book($userId, $post);
        print json_encode($result);
    });


    $app->get('/dashboard/?', function () use ($app) {    
        // Access-controlled page
        if (!$app->user->checkAccess('uri_dashboard')){
            $app->notFound();
        }
        
        $app->render('dashboard.twig', []);          
    });
    
    $app->get('/zerg/?', function () use ($app) {    
        // Access-controlled page
        if (!$app->user->checkAccess('uri_zerg')){
            $app->notFound();
        }
        
        $app->render('users/zerg.twig'); 
    });    


    $app->post('/pricing/?', function () use ($app){
        $post = $app->request->post();

        $json = json_encode($post['pricing']);

                   

        $pricing = UF\Pricing::where('user_id', $app->user->id)->first();

        if (is_null($pricing)) {
            $pricing = new UF\Pricing;
            $pricing->user_id = $app->user->id;
        }

        $pricing->json = json_encode($post['pricing']);
        $pricing->save();
        $app->redirect($app->urlFor('pricing'));

    });

    $app->get('/pricing/?', function () use ($app){

        $pricings = [];
        $originUts = 1451822400;

        for ($i=1; $i < 8; $i++) { 

            $pricings["$i"] = [
                'index' => $i,
                'title' => strftime("%A", $originUts + $i * 86400),
                'A' => ['active' => 0, 'hour' => 0, 'hourlyRate' => 0, 'kmRate' => 0],
                'B' => ['active' => 0, 'hour' => 0, 'hourlyRate' => 0, 'kmRate' => 0],
                'C' => ['active' => 0, 'hour' => 0, 'hourlyRate' => 0, 'kmRate' => 0],

            ];
        }
        $baseRate = 0;

        $dbPricing = UF\Pricing::where('user_id', $app->user->id)->first();

        if (!is_null($dbPricing)) {
            $obj = json_decode($dbPricing->json);
            

            if (is_object($obj)) {
                foreach ($obj as $dayOfWeek => $day) {

                    if (is_object($day)) {
                        foreach ($day as $daySection => $daySectionConf) { // daySection = A, B or C
                            if (isset($daySectionConf->active)) {
                                $pricings[$dayOfWeek][$daySection]['active'] = $daySectionConf->hour ? 1 : 0;
                            }

                            if (isset($daySectionConf->hour)) {
                                $pricings[$dayOfWeek][$daySection]['hour'] = intval($daySectionConf->hour);
                            }

                            if (isset($daySectionConf->hourlyRate)) {
                                $pricings[$dayOfWeek][$daySection]['hourlyRate'] = intval($daySectionConf->hourlyRate);
                            }

                            if (isset($daySectionConf->kmRate)) {
                                $pricings[$dayOfWeek][$daySection]['kmRate'] = floatval($daySectionConf->kmRate);
                            }
                        }
                    }
                }    
                if (isset($obj->baseRate)) {
                    $baseRate = intval($obj->baseRate);
                }

            }
            
        }
        

        $app->render('pricing.twig', [
            'csrf' => $_SESSION['csrf_token'],
            'pricings' => $pricings,
            'baseRate' =>  $baseRate
        ]);

    })->name('pricing');


    $app->get('/geolock/?', function () use ($app) {    
        
        $geolocks = UF\Geolock::where('user_id', $app->user->id)->get();

        $app->render('geolock.twig', [
            'csrf' => $_SESSION['csrf_token'],
            'geolocks' => $geolocks,
        ]); 
    })->name('geolock');

    $app->post('/geolock/?', function () use ($app) {    

        

        $post = $app->request->post();

        

        if (isset($post['action'], $post['lat'], $post['lng']) && $post['action'] == 'delete') {

            $geolock = UF\Geolock::where('user_id', $app->user->id)

            ->where('north', '>=', $post['lat'])
            ->where('south', '<=', $post['lat'])
            ->where('east', '>=', $post['lng'])
            ->where('west', '<=', $post['lng'])
            ->first();

            if ($geolock) {
                $geolock->delete();
            }
        
        } else {




            
            
            $north = max($post['startLat'], $post['endLat']);
            

            $geolockArea = new UF\Geolock([
                'north' => max($post['startLat'], $post['endLat']),
                'south' => min($post['startLat'], $post['endLat']),
                'east' => max($post['startLng'], $post['endLng']),
                'west' => min($post['startLng'], $post['endLng']),
                'user_id' => $app->user->id,
            ]);
            
            $geolockArea->save();
        }
        $app->redirect($app->urlFor('geolock'));
    });    
       
    // update booking
    $app->post('/bookings/:booking_id/?', function ($bookingId) use ($app) {
        

        $booking = UF\Booking::where('user_id', $app->user->id)
        ->where('id', $bookingId)
        ->first();

        
        
        if (empty($booking)) {
            exit("Booking not found: $bookingId");
        }



        
        
        $post = $app->request->post();

        if (isset($post['delete'])) {




            $booking->delete();
            

        } else {


            // Load the request schema
            $requestSchema = new \Fortress\RequestSchema($app->config('schema.path') . "/forms/booking-update.json");
               
            // Get the alert message stream
            $ms = $app->alerts; 


            // Set up Fortress to process the request
            $rf = new \Fortress\HTTPRequestFortress($ms, $requestSchema, $post);                    

            // Sanitize
            $rf->sanitize();

            // Validate, and halt on validation errors.
            if (!$rf->validate()) {
                $app->halt(400);
            }   
            
            // Get the filtered data
            $data = $rf->data();
            

            
            
            
            $uts = strtotime($data['date'] . " " . $data['time']);

            if ($uts < 1451606400) {
                $app->halt(400, "Date is before 2016");
            }


            $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?key=AIzaSyB5KregYdUnsFjQuuV91XQCx5_TJmePmo8&language=sv&origins=' . urlencode($data['origin']) . '&destinations=' .  urlencode($data['destination']) . '&departure_time=' . urlencode($uts) . '&traffic_model=pessimistic';
            $content = file_get_contents($url);

            $obj = json_decode($content);
        
            if ($obj->status != 'OK' || $obj->rows[0]->elements[0]->status != 'OK') {
                $ms->addMessageTranslated("success",$obj->error_message, $post);
                //$app->halt(400, "Could not calculate distance between origin and destination\nurl: $url");
            } else {


                $booking->origin = $obj->origin_addresses[0];
                $booking->destination = $obj->destination_addresses[0];
                $booking->startUts = $uts;
                $booking->endUts = $uts + $obj->rows[0]->elements[0]->duration->value;
                $booking->duration = $obj->rows[0]->elements[0]->duration->text;
                $booking->distance = $obj->rows[0]->elements[0]->distance->text;
                    
                $booking->name = $data['name'];
                $booking->email = $data['email'];
                $booking->phone = $data['phone'];

                if (!empty($_POST['status'])) {
                    $status = $_POST['status'];
 
                    if (in_array($status, ['new', 'accepted', 'rejected'])) {
                                                $booking->status = $status;
                        $booking->carId = NULL;
                    } else {
                        preg_match('/accepted-([0-9]+)/', $status, $matches);
                        if (!empty($matches[1])) {
                            $booking->status = 'accepted';
                            $booking->carId = $matches[1];
                        } 
        
                    }
                    
                }

                $booking->save();
                
                $ms->addMessageTranslated("success", "Bokning uppdateras", $post);
            }            
        }

    });

    // show booking
    $app->get('/bookings/:booking_id/?', function ($bookingId) use ($app) {   
        
        

        $booking = UF\Booking::where('user_id', $app->user->id)
        ->where('id', $bookingId)
        ->first();



        if (empty($booking)) {
            exit("Show booking $bookingId: Booking not found");
        }


        // Get the validation rules for the form on this page
        $schema = new \Fortress\RequestSchema($app->config('schema.path') . "/forms/booking-update.json");
        $app->jsValidator->setSchema($schema); 

        $cars = UF\Car::where('user_id', $app->user->id)->get();
        $statuses = array(
            'new' => 'Ohanterat',
            'accepted' => 'Accepterad',
        );
        foreach($cars as $car) {
            $statuses['accepted-' . $car->id] = 'Accepterad (' . $car->title . ')';    
        }
        $statuses['rejected'] = 'Avvisad';

        $app->render('booking.twig', [
            'booking' => $booking,
            'validators' => $app->jsValidator->rules(),
            'statuses' => $statuses,
            
        ]); 

    }); 

     
    $app->get('/bookings/?', function () use ($app) {   

        $period = isset($_GET['period']) && in_array($_GET['period'], ['day', 'week', 'month']) ? $_GET['period'] : 'plan';
        $startUts = $endUts = 0;
        switch ($period) {
            case 'plan':
                $startUts = strtotime('today');
                $endUts = $startUts + 8640000;
                break;
            case 'day':
                $startUts = strtotime('today');
                $endUts = strtotime('tomorrow');
                break;
            
            case 'week':
                $startUts = strtotime('this week');
                $endUts = strtotime('next week');
                break;
            
            case 'month':
                $d = new DateTime();
                $d->modify('first day of this month 00:00:00');
                $startUts = $d->format('U');
                $d->modify('first day of next month');
                $endUts = $d->format('U');
                break;
            
            default:
                exit("wrong period: $period");
                break;
        }

        $futureBookingsQuery = UF\Booking::QueryBuilder()->where('user_id', $app->user->id)
            ->where('startUts', '>=', $startUts)
            ->where('endUts', '<', $endUts)
            ->orderBy('startUts');

        if ($period == 'plan') {
            $futureBookingsQuery = $futureBookingsQuery->take(25);
        }

        $futureBookings = $futureBookingsQuery->get();
        

        $app->render('bookings.twig', [
            "bookings" => $futureBookings,
            "period" => $period,
        ]); 

    })->name('bookings');


    /********** ACCOUNT MANAGEMENT INTERFACE **********/
    
    $app->get('/account/:action/?', function ($action) use ($app) {    
        // Forward to installation if not complete
        if (!isset($app->site->install_status) || $app->site->install_status == "pending"){
            $app->redirect($app->urlFor('uri_install'));
        }
    
        $get = $app->request->get();
        
        $controller = new UF\AccountController($app);
    
        $twig = $app->view()->getEnvironment();   
        $loader = $twig->getLoader();
          
        switch ($action) {
            case "login":               return $controller->pageLogin();
            case "logout":              return $controller->logout(true); 
            case "register":            return $controller->pageRegister();         
            case "resend-activation":   return $controller->pageResendActivation();
            case "forgot-password":     return $controller->pageForgotPassword();
            case "activate":            return $controller->activate();
            case "set-password":        return $controller->pageSetPassword(true); 
            case "reset-password":      if (isset($get['confirm']) && $get['confirm'] == "true")
                                            return $controller->pageSetPassword(false);
                                        else
                                            return $controller->denyResetPassword();
            case "captcha":             return $controller->captcha();
            case "settings":            return $controller->pageAccountSettings();
            default:                    return $controller->page404();   
        }
    });

    $app->post('/account/:action/?', function ($action) use ($app) {            
        $controller = new UF\AccountController($app);
    
        switch ($action) {
            case "login":               return $controller->login();     
            case "register":            return $controller->register();
            case "resend-activation":   return $controller->resendActivation();
            case "forgot-password":     return $controller->forgotPassword();
            case "set-password":        return $controller->setPassword(true);
            case "reset-password":      return $controller->setPassword(false);            
            case "settings":            return $controller->accountSettings();
            default:                    $app->notFound();
        }
    });    
    
    /********** USER MANAGEMENT INTERFACE **********/
    
    // List users
    $app->get('/users/?', function () use ($app) {
        $controller = new UF\UserController($app);
        return $controller->pageUsers();
    })->name('uri_users');    

    // List users in a particular primary group
    $app->get('/users/:primary_group/?', function ($primary_group) use ($app) {
        $controller = new UF\UserController($app);
        return $controller->pageUsers($primary_group);
    });
    
    // User info form (update/view)
    $app->get('/forms/users/u/:user_id/?', function ($user_id) use ($app) {
        $controller = new UF\UserController($app);
        $get = $app->request->get();        
        if (isset($get['mode']) && $get['mode'] == "update")
            return $controller->formUserEdit($user_id);
        else
            return $controller->formUserView($user_id);
    });  

    // User edit password form
    $app->get('/forms/users/u/:user_id/password/?', function ($user_id) use ($app) {
        $controller = new UF\UserController($app);
        $get = $app->request->get();        
        return $controller->formUserEditPassword($user_id);
    });
    
    // User creation form
    $app->get('/forms/users/?', function () use ($app) {
        $controller = new UF\UserController($app);
        return $controller->formUserCreate();
    });
    
    // User info page
    $app->get('/users/u/:user_id/?', function ($user_id) use ($app) {
        $controller = new UF\UserController($app);
        return $controller->pageUser($user_id);
    });       

    // Create user
    $app->post('/users/?', function () use ($app) {
        $controller = new UF\UserController($app);
        return $controller->createUser();
    });
    
    // Update user info
    $app->post('/users/u/:user_id/?', function ($user_id) use ($app) {
        $controller = new UF\UserController($app);
        return $controller->updateUser($user_id);
    });       
    
    // Delete user
    $app->post('/users/u/:user_id/delete/?', function ($user_id) use ($app) {
        $controller = new UF\UserController($app);
        return $controller->deleteUser($user_id);
    });
    
    /********** GROUP MANAGEMENT INTERFACE **********/
    
    // List groups
    $app->get('/groups/?', function () use ($app) {
        $controller = new UF\GroupController($app);
        return $controller->pageGroups();
    }); 
    
    // List auth rules for a group
    $app->get('/groups/g/:group_id/auth?', function ($group_id) use ($app) {
        $controller = new UF\GroupController($app);
        return $controller->pageGroupAuthorization($group_id);
    })->name('uri_authorization');  
    
    // Group info form (update/view)
    $app->get('/forms/groups/g/:group_id/?', function ($group_id) use ($app) {
        $controller = new UF\GroupController($app);
        $get = $app->request->get();        
        if (isset($get['mode']) && $get['mode'] == "update")
            return $controller->formGroupEdit($group_id);
        else
            return $controller->formGroupView($group_id);
    });

    // Group creation form
    $app->get('/forms/groups/?', function () use ($app) {
        $controller = new UF\GroupController($app);
        return $controller->formGroupCreate();
    });    
    
    // Create group
    $app->post('/groups/?', function () use ($app) {
        $controller = new UF\GroupController($app);
        return $controller->createGroup();
    });
    
    // Update group info
    $app->post('/groups/g/:group_id/?', function ($group_id) use ($app) {
        $controller = new UF\GroupController($app);
        return $controller->updateGroup($group_id);
    });       

    // Delete group
    $app->post('/groups/g/:group_id/delete/?', function ($group_id) use ($app) {
        $controller = new UF\GroupController($app);
        return $controller->deleteGroup($group_id);
    });
    
    /********** GROUP AUTH RULES INTERFACE **********/
    
    // Group auth creation form
    $app->get('/forms/groups/g/:group_id/auth/?', function ($group_id) use ($app) {
        $controller = new UF\AuthController($app);
        return $controller->formAuthCreate($group_id, "group");
    });      
    
    // Group auth update form
    $app->get('/forms/groups/auth/a/:rule_id/?', function ($rule_id) use ($app) {
        $controller = new UF\AuthController($app);
        $get = $app->request->get();        
        return $controller->formAuthEdit($rule_id);
    });    

    // Group auth create
    $app->post('/groups/g/:group_id/auth/?', function ($group_id) use ($app) {
        $controller = new UF\AuthController($app);
        return $controller->createAuthRule($group_id, "group");
    });     

    // Group auth update
    $app->post('/groups/auth/a/:rule_id?', function ($rule_id) use ($app) {
        $controller = new UF\AuthController($app);
        return $controller->updateAuthRule($rule_id, "group");
    });
    
    // Group auth delete
    $app->post('/auth/a/:rule_id/delete/?', function ($rule_id) use ($app) {
        $controller = new UF\AuthController($app);
        $get = $app->request->get();        
        return $controller->deleteAuthRule($rule_id);
    });  
        
    /************ ADMIN TOOLS *************/
    
    $app->get('/config/settings/?', function () use ($app) {
        $controller = new UF\AdminController($app);
        return $controller->pageSiteSettings();
    })->name('uri_settings');     
    
    $app->post('/config/settings/?', function () use ($app) {
        $controller = new UF\AdminController($app);
        return $controller->siteSettings();        
    });
    
    // Build the minified, concatenated CSS and JS
    $app->get('/config/build', function() use ($app){
        // Access-controlled page
        if (!$app->user->checkAccess('uri_minify')){
            $app->notFound();
        }
        
        $app->schema->build(true);
        $app->alerts->addMessageTranslated("success", "MINIFICATION_SUCCESS");
        $app->redirect($app->urlFor('uri_settings'));
    });    
    
    // Slim info page
    $app->get('/sliminfo/?', function () use ($app) {
        // Access-controlled page
        if (!$app->user->checkAccess('uri_slim_info')){
            $app->notFound();
        }
        echo "<pre>";
        print_r($app->environment());
        echo "</pre>";
    });

    // PHP info page
    $app->get('/phpinfo/?', function () use ($app) {
        // Access-controlled page
        if (!$app->user->checkAccess('uri_php_info')){
            $app->notFound();
        }    
        echo "<pre>";
        print_r(phpinfo());
        echo "</pre>";
    });

    // Error log page
    $app->get('/errorlog/?', function () use ($app) {
        // Access-controlled page
        if (!$app->user->checkAccess('uri_error_log')){
            $app->notFound();
        }
        $log = $app->site->getLog();
        echo "<pre>";
        echo implode("<br>",$log['messages']);
        echo "</pre>";
    });      
       
    /************ INSTALLER *************/

    $app->get('/install/?', function () use ($app) {
        $controller = new UF\InstallController($app);
        if (isset($app->site->install_status)){
            // If tables have been created, move on to master account setup
            if ($app->site->install_status == "pending"){
                $app->redirect($app->site->uri['public'] . "/install/master");
            } else {
                // Everything is set up, so go to the home page!
                $app->redirect($app->urlFor('uri_home'));
            }
        } else {
            return $controller->pageSetupDB();
        }
    })->name('uri_install');
    
    $app->get('/install/master/?', function () use ($app) {
        $controller = new UF\InstallController($app);
        if (isset($app->site->install_status) && ($app->site->install_status == "pending")){
            return $controller->pageSetupMasterAccount();
        } else {
            $app->redirect($app->urlFor('uri_install'));
        }
    });

    $app->post('/install/:action/?', function ($action) use ($app) {
        $controller = new UF\InstallController($app);
        switch ($action) {
            case "master":            return $controller->setupMasterAccount();      
            default:                  $app->notFound();
        }   
    });
    
    /************ API *************/
    
    $app->get('/api/users/?', function () use ($app) {
        $controller = new UF\ApiController($app);
        $controller->listUsers();
    });
    
    
    /************ MISCELLANEOUS UTILITY ROUTES *************/
    
    // Generic confirmation dialog
    $app->get('/forms/confirm/?', function () use ($app) {
        $get = $app->request->get();
        
        // Load the request schema
        $requestSchema = new \Fortress\RequestSchema($app->config('schema.path') . "/forms/confirm-modal.json");
        
        // Get the alert message stream
        $ms = $app->alerts;         
        
        // Remove csrf_token
        unset($get['csrf_token']);
        
        // Set up Fortress to process the request
        $rf = new \Fortress\HTTPRequestFortress($ms, $requestSchema, $get);                    
    
        // Sanitize
        $rf->sanitize();
    
        // Validate, and halt on validation errors.
        if (!$rf->validate()) {
            $app->halt(400);
        }           
        
        $data = $rf->data();
        
        $app->render('components/common/confirm-modal.twig', $data);   
    }); 
    
    // Alert stream
    $app->get('/alerts/?', function () use ($app) {
        $controller = new UF\BaseController($app);
        $controller->alerts();
    });
    
    // JS Config
    $app->get($app->config('uri')['js-relative'] . '/config.js', function () use ($app) {
        $controller = new UF\BaseController($app);
        $controller->configJS();
    });
    
    // Theme CSS
    $app->get($app->config('uri')['css-relative'] . '/theme.css', function () use ($app) {
        $controller = new UF\BaseController($app);
        $controller->themeCSS();
    });
    
    // Not found page (404)
    $app->notFound(function () use ($app) {
        if ($app->request->isGet()) {
            $controller = new UF\BaseController($app);
            $controller->page404();
        } else {
            $app->alerts->addMessageTranslated("danger", "SERVER_ERROR");
        }
    });

    $app->run();
