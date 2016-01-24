<?php

namespace UserFrosting;

/**
 * BookingController Class
 *
 * New booking
 *
 * @package Taxibo
 * @author Allan Th. Andersen
 */
class BookingController extends \UserFrosting\BaseController {

    /**
     * Create a new BookingController object.
     *
     * @param UserFrosting $app The main UserFrosting app.
     */
    public function __construct($app){
        $this->_app = $app;
    }

    /**
     * Renders the default home page for UserFrosting.
     *
     * By default, this is the page that non-authenticated users will first see when they navigate to your website's root.
     * Request type: GET
     */
    public function showBookingForm($userId) {

        $client = User::where('id', $userId)->first();

        $this->_app->render('bookingform.twig', [
            'client' => $client,
            'date' => strftime("%F", time()+7200),
            'time' => strftime("%H:00", time()+7200),
            'passengerCountRange' => range(1, 8),
            'csrf' => $_SESSION['csrf_token'],
        ]);
    }

    public function book($userId, $post)
    {
        $offer = $this->getOffer($userId, $post['origin'], $post['destination'], $post['date'], $post['time']);
        $response = new \StdClass;
        $response->success = "no";
        if ($offer->success == "yes") {
            foreach(array('name', 'phone', 'email', 'passengerCount') as $fieldName) {
                $$fieldName = !empty($post[$fieldName]) ? $post[$fieldName] : '';
                if (!$$fieldName) {
                    $response->errorText = "Fyll in alla felt";
                    print json_encode($response);
                    exit;
                }
            }
            $originCity = !empty($post['originCity']) ? $post['originCity'] : '';
            $destinationCity = !empty($post['destinationCity']) ? $post['destinationCity'] : '';

            // save to db                
            $booking = new Booking;

            $booking->user_id = $userId;
            $booking->alphaId = "ZZZ". substr(""+time(), 3, 7);
       
            $booking->origin = $offer->offerFrom;
            $booking->destination = $offer->offerTo;
            $booking->originCity = $originCity;
            $booking->destinationCity = $destinationCity;
            $booking->startUts = $offer->uts;
            $booking->endUts = $offer->uts + $offer->offerDurationValue;

            $booking->duration = $offer->offerDuration;
            $booking->distance = $offer->offerDistance;
            $booking->price = $offer->offerPrice;

            $booking->name = $name;
            $booking->email = $email;
            $booking->phone = $phone;
            $booking->passengerCount = $passengerCount;
            $booking->hash = md5($name . $email . $offer->offerFrom . time() . "38lx93øå");
            $response->bookingSaved = $booking->save();


            // mail to customer
            TaxiMail::sendBookingReceivedMail($booking, $userId);

            // mail to taxi company
            TaxiMail::sendNewBookingMail($booking, $userId);

            
            $response->success = "yes";
        }

        return $response;
    }

    public function getOffer($userId, $origin, $destination, $date, $time, $latLng = NULL) {

        $response = new \StdClass;
        $response->success = "no";
        

        if (!is_null($latLng)) {
            $originOk = FALSE;
            $destinationOk = FALSE;
            $geolocks = Geolock::where('user_id', $userId)->get();

            foreach ($geolocks as $key => $geolock) {
                if ($latLng[0] <= $geolock->north && $latLng[0] >= $geolock->south && $latLng[1] <= $geolock->east && $latLng[1] >= $geolock->west) {
                    $originOk = TRUE;
                }
                if ($latLng[2] <= $geolock->north && $latLng[2] >= $geolock->south && $latLng[3] <= $geolock->east && $latLng[3] >= $geolock->west) {
                    $destinationOk = TRUE;
                }
                
            }    

            if (!$originOk) {
                $response->errorMessage = 'Ursprunget är utanför det täckta området.';
            } elseif (!$destinationOk) {
                $response->errorMessage = 'Destinationen är utanför det täckta området.';
            }

            if (!$originOk || !$destinationOk) {
                return $response;
            }
        }
        

        $uts = strtotime("$date $time");

        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?key=AIzaSyB5KregYdUnsFjQuuV91XQCx5_TJmePmo8&language=sv&origins=' . urlencode($origin) . '&destinations=' .  urlencode($destination) . '&departure_time=' . urlencode($uts) . '&traffic_model=pessimistic';
        $content = file_get_contents($url);

        $obj = json_decode($content);
        $response->url = $url;
        $response->content = $obj;
        if ($obj->status == 'OK' && $obj->rows[0]->elements[0]->status == 'OK') {

            $response->offerFrom = $obj->origin_addresses[0];
            $response->offerTo = $obj->destination_addresses[0];
            $response->offerDate = strftime('%A %e %B %Y, %H.%M (%Z)', $uts);
            $response->uts = $uts;
            $response->offerDuration = $obj->rows[0]->elements[0]->duration->text;
            $response->offerDistance = $obj->rows[0]->elements[0]->distance->text;
            $response->offerDurationValue = $obj->rows[0]->elements[0]->duration->value;
            $response->offerDistanceValue = $obj->rows[0]->elements[0]->distance->value;

            $minutes = intval(($obj->rows[0]->elements[0]->duration->value) / 60);
            $kms = round(($obj->rows[0]->elements[0]->distance->value) / 1000);

            $priceObject = $this->calculatePrice($uts, $uts + $response->offerDurationValue, $kms);

            $response->offerPrice = $priceObject->priceRoundedZero;
            $response->priceObject = $priceObject;

            $response->success = "yes";
        } else {
            $errorMessage = $obj->error_message;
            if (strpos($errorMessage, 'departure_time is in the past') !== FALSE) {
                $errorMessage = 'Välj en annan avgångstid.';
            } 
            $response->errorMessage = $errorMessage;
        }
        return $response;
    }

    /**
     * Calculcate price
     * @param int Start time timestamp
     * @param int End time timestamp    
     * @param int Kilometres 
     */
    public function calculatePrice($startUts, $endUts, $kms) 
    {

        $startDate = strftime('%F', $startUts);
        $endDate  = strftime('%F', $endUts);
        $endDateMinusOneDay = strftime('%F', $endUts - 86400);

        $days = array();
        $totalHours = 0;
        if ($startDate == $endDate) {
            $days[] = array(
                'startHour' => $this->getHoursFromUts($startUts),
                'endHour' => $this->getHoursFromUts($endUts),
                'dayOfWeek' => strftime('%u', $startUts),
            );
            $totalHours += $this->getHoursFromUts($endUts) - $this->getHoursFromUts($startUts);
            // one day
        } elseif ($startDate == $endDateMinusOneDay) {

            $dayOfWeek = strftime('%u', $startUts);

            // hen over midnat
            $days[] = array(
                'startHour' => $this->getHoursFromUts($startUts),
                'endHour' => 24,
                'dayOfWeek' => $dayOfWeek,
            );
            $totalHours += 24 - $this->getHoursFromUts($startUts);
            $days[] = array(
                'startHour' => 0,
                'endHour' => $this->getHoursFromUts($endUts),
                'dayOfWeek' => $dayOfWeek == 7 ? 1 : ($dayOfWeek + 1),
            );
            $totalHours += $this->getHoursFromUts($endUts) - 0;
        } else {
            error_log("too long period");
        }


        // fordel kms
        foreach($days as $key => $day) {
            $days[$key]['kms'] = $kms * ($day['endHour'] - $day['startHour']) / $totalHours;
        }



        // Define prices
        $startRate = 0;
/*
        $pricingMondayFriday = array(

            'A' => array('from' => 0, 'to' => 7, 'hourPrice' => 692, 'kmPrice' => 19.3),
            'B' => array('from' => 7, 'to' => 15, 'hourPrice' => 644, 'kmPrice' => 17.9),
            'C' => array('from' => 15, 'to' => 24, 'hourPrice' => 692, 'kmPrice' => 19.3),
        );

        $pricingWeekend = array(
            'A' => array('from' => 0, 'to' => 24, 'hourPrice' => 692, 'kmPrice' => 19.3),
        );
*/
        $pricingDefault = array(
            'A' => array('from' => 0, 'to' => 24, 'hourPrice' => 0, 'kmPrice' => 0),
        );
        $pricing = array(
            '1' => $pricingDefault,
            '2' => $pricingDefault,
            '3' => $pricingDefault,
            '4' => $pricingDefault,
            '5' => $pricingDefault,
            '6' => $pricingDefault,
            '7' => $pricingDefault,
        );

        //error_log("calculatePrice (".__LINE__.") userId: " . print_r($this->_app->user->id,1));

        $dbPricing = Pricing::where('user_id', $this->_app->user->id)->first();

        

        if (!is_null($dbPricing)) {
            $obj = json_decode($dbPricing->json);

            
            if (is_object($obj)) {
                foreach ($obj as $dayOfWeek => $day) {
                    if (is_object($day)) {
                        foreach ($day as $daySection => $daySectionConf) { // daySection = A, B or C

                            if (!isset($daySectionConf->active) || !$daySectionConf->active) {
                                continue;
                            }

                            if (isset($daySectionConf->hour)) {
                                $pricing[$dayOfWeek][$daySection]['from'] = intval($daySectionConf->hour);
                                if ($daySection == 'A') {

                                    $pricing[$dayOfWeek][$daySection]['to'] = 
                                        (isset($day->B->active) && $day->B->active) ?
                                        $day->B->hour : 24;
                                    
                                    
                                    

                                } elseif ($daySection == 'B') {
                                    $pricing[$dayOfWeek][$daySection]['to'] = 
                                        (isset($day->C->active) && $day->C->active) ?
                                        $day->C->hour : 24;
                                    
                                } else {
                                    $pricing[$dayOfWeek][$daySection]['to'] = 24;
                                }


                            }

                            if (isset($daySectionConf->hourlyRate)) {
                                $pricing[$dayOfWeek][$daySection]['hourPrice'] = intval($daySectionConf->hourlyRate);
                            }

                            if (isset($daySectionConf->kmRate)) {
                                $pricing[$dayOfWeek][$daySection]['kmPrice'] = floatval($daySectionConf->kmRate);
                            }
                        }
                    }
                }    
                if (isset($obj->baseRate)) {
                    $startRate = intval($obj->baseRate);
                }
            }
        }

        $totalPrice = $startRate;
        


        foreach($days as $day) {

            if ($day['endHour'] <= $day['startHour']) {
                exit("wrong time, line: " . __LINE__);
            }

            $startHour = $day['startHour'];
            $endHour = $day['endHour'];
            $dayOfWeek = $day['dayOfWeek'];
            $kms = $day['kms'];

            $startRate = '';
            $endRate = '';

            $prisstruktur = $pricing[$dayOfWeek];
    


            foreach ($prisstruktur as $takstSymbol => $takst) {
                if ($takst['from'] <= $startHour && $startHour <= $takst['to']) {
                    $startRate = $takstSymbol;
                }

                if ($takst['from'] <= $endHour && $endHour <= $takst['to']) {
                    $endRate = $takstSymbol;
                }
            }
            $takstKombi = $startRate . $endRate;
            
            switch ($takstKombi) {
                case 'AA':
                case 'BB':
                case 'CC':
                    
                    $totalPrice += $this->getPriceOnePeriod($prisstruktur, $startHour, $endHour, substr($takstKombi, 0,1), $kms);
                    break;

                case 'AB':
                case 'BC':

                    $firstTakst = substr($takstKombi, 0, 1);
                    $secondTakst = substr($takstKombi, 1, 1);
                    $firstKms = $kms * ($prisstruktur[$firstTakst]['to'] - $startHour) / ($endHour - $startHour);
                    $secondKms = $kms * ($endHour - $prisstruktur[$secondTakst]['from']) / ($endHour - $startHour);

                    $totalPrice += $this->getPriceOnePeriod($prisstruktur, $startHour, $prisstruktur[$firstTakst]['to'], $firstTakst, $firstKms);
                    $totalPrice += $this->getPriceOnePeriod($prisstruktur, $prisstruktur[$secondTakst]['from'], $endHour, $secondTakst, $secondKms);
                    
                    break;
                
                case 'AC':

                    $firstKms = $kms * ($prisstruktur['A']['to'] - $startHour) / ($endHour - $startHour);
                    $secondKms = $kms * ($prisstruktur['b']['to'] - $prisstruktur['b']['from']) / ($endHour - $startHour);
                    $thirdKms = $kms * ($endHour - $prisstruktur['C']['from']) / ($endHour - $startHour);


                    $totalPrice += $this->getPriceOnePeriod($prisstruktur, $startHour, $prisstruktur['A']['to'], 'A', $firstKms);
                    $totalPrice += $this->getPriceOnePeriod($prisstruktur, $prisstruktur['B']['from'], $prisstruktur['B']['to'], 'B', $secondKms);
                    $totalPrice += $this->getPriceOnePeriod($prisstruktur, $prisstruktur['C']['from'], $endHour, 'C', $thirdKms);


                    break;
                default:
                    exit("invalid: $takstKombi");
                    break;
            }
        }

        $response = new \StdClass;

        $response->price = $totalPrice;
        $response->priceRoundedZero = round($totalPrice);
        $response->priceRoundedTwo = round($totalPrice, 2);

        return $response;

        
    }

    private function getPriceOnePeriod($pricings, $startHours, $endHours, $takstSymbol, $kms) {

        $hours = $endHours - $startHours;
        
        $hourPrice = $pricings[$takstSymbol]['hourPrice'];
        $hoursPrice = $hours * $hourPrice;
        
        $kmPrice = $pricings[$takstSymbol]['kmPrice'];
        $kmsPrice = $kms * $kmPrice;
        
        $price = $hours * $hourPrice + $kms * $kmPrice;

        error_log(implode(", ", ['hours', $hours, 'hourPrice', $hourPrice, 'hoursPrice', $hoursPrice, 'kms',
         $kms, 'kmPrice', $kmPrice, 'kmsPrice', $kmsPrice, 'price', $price]));
        return $price;
    }

    private function getHoursFromUts ($uts) {
        return intval(strftime("%H", $uts)) + intval(strftime("%M", $uts)) / 60;
    }


}