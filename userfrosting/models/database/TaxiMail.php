<?php

namespace UserFrosting;

use \Illuminate\Database\Capsule\Manager as Capsule;

/**
 * TaxiMail
 *
 * Represents a User object as stored in the database.
 *
 * @package Taxibo
 * @author Allan Th. Andersen
 *
 */
class TaxiMail {
    

    public static function getMailHtml($headerLine1, $headerLine2, $contentHtml, $footerLine) {
$html = "

<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:v=\"urn:schemas-microsoft-com:vml\">
<head>
    <title>Taxibooking</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
<meta name=\"viewport\" content=\"width=device-width\">
<meta name=\"format-detection\" content=\"telephone=no\">
<style type=\"text/css\"><![CDATA[
table.body {
    height: 100%;
    width: 100%;
}
]]></style>
</head>
<body bgcolor=\"#e9e9e9\" style=\"-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #202634; font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; font-weight: normal; line-height: 19px; margin: 0; padding: 0; text-align: left; width: 100% !important\">
    <table bgcolor=\"#e9e9e9\" class=\"body\" style=\"border-collapse: collapse; border-spacing: 0; color: #202634; font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; font-weight: normal; height: 100%; line-height: 19px; margin: 0; padding: 0; text-align: left; width: 100%\">
        <tr style=\"padding: 0\">
            <td class=\"center\" align=\"center\" valign=\"top\" style=\"-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; padding: 0; text-align: center; word-break: break-word\">
                <center style=\"min-width: 580px; width: 100%\">
                    <table class=\"row header\" style=\"border-collapse: collapse; border-spacing: 0; padding: 0px; position: relative; width: 100%\">
                        <tr style=\"padding: 0\">
                            <td class=\"center\" align=\"center\" style=\"-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; padding: 0; text-align: center; word-break: break-word\">
                                <center style=\"min-width: 580px; width: 100%\">
                                    <table class=\"container\" style=\"border-collapse: collapse; border-spacing: 0; margin: 0 auto; padding: 0; text-align: inherit; width: 580px\">
                                        <tr style=\"padding: 0\">
                                            <td class=\"wrapper last\" align=\"left\" style=\"-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; padding: 0px; position: relative; word-break: break-word\">
                                                <table class=\"twelve columns\" style=\"border-collapse: collapse; border-spacing: 0; margin: 0 auto; padding: 0; width: 580px\">
                                                    <tr style=\"padding: 0\">
                                                        <td class=\"six sub-columns\" height=\"13\" style=\"-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; min-width: 0px; padding: 0px 10px 0px 0px; width: 100% !important; word-break: break-word\"></td>
                                                    </tr>
                                                    <tr style=\"padding: 0\">
                                                        <td bgcolor=\"#ef6c00\" style=\"font-weight: bold; color: #ffffff; -moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; min-width: 0px; padding: 11px 0px 12px 14px; width: 100% !important; word-break: break-word\">
                                                            " . htmlspecialchars($headerLine1). "
                                                        </td>
                                                    </tr>
                                                    <tr style=\"padding: 0\">
                                                        <td bgcolor=\"#fb8c00\" style=\"font-weight: bold; color: #ffffff; -moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; min-width: 0px; padding: 11px 0px 12px 14px; width: 100% !important; word-break: break-word\">
                                                            " . htmlspecialchars($headerLine2). "
                                                        </td>
                                                    </tr>
                                                    <tr style=\"padding: 0\">
                                                        <td bgcolor=\"#ffffff\" style=\"-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; min-width: 0px; padding: 11px 0px 12px 14px; width: 100% !important; word-break: break-word\">
                                                            " . $contentHtml . "
                                                        </td>
                                                    </tr>

                                                    <tr style=\"padding: 0\">
                                                        <td bgcolor=\"#424242\" style=\"font-size: 10px; font-weight: normal; color: #ffffff; -moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; min-width: 0px; padding: 11px 0px 12px 14px; width: 100% !important; word-break: break-word\">
                                                            " . htmlspecialchars($footerLine). "
                                                        </td>
                                                    </tr>
                                                    <tr style=\"padding: 0\">
                                                        <td class=\"six sub-columns\" height=\"13\" style=\"-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; hyphens: auto; min-width: 0px; padding: 0px 10px 0px 0px; width: 100% !important; word-break: break-word\"></td>
                                                    </tr>
                                                    
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </center>
                            </td>
                        </tr>
                    </table>
                </center>
            </td>
        </tr>
    </table>
</body>
</html>";
        return $html;
    }

    public static function sendBookingReceivedMail($booking, $userId)
    {
        $user = User::where('id', $userId)->first();
        $date = strftime('%A %e %B %Y, %H.%M (%Z)', $booking->startUts);

        $twig = UserFrosting::getInstance()->view()->getEnvironment();

        $template = $twig->loadTemplate("mail/sendBookingReceivedMail.twig");

        $notification = new Notification($template);
        
        $notification->from("taxibo@allanth.dk", $user->title, $user->email, $user->title);

        $notification->addEmailRecipient($booking->email,$booking->name, [
            'date' => $date,
            'booking' => $booking, 
            'client' => $user,
            'headerLine1' => $user->title,
            'headerLine2' => 'Tack för din taxibokning',
            'footerLine' => "Skickat " . strftime('%A %e %B %Y, %H.%M (%Z)'), 
        ]);

        $success = NULL;
        try {
            
            $success = $notification->send();
            
        } catch (\phpmailerException $e){
            print $e->errorMessage();
            error_log('Mailer Error: ' . $e->errorMessage());
            $app->halt(500);
        }

        return $success;

        

        

    }

    public static function sendBookingHandled($booking, $action)
    {
        $user = User::where('id', $booking->user_id)->first();

        $date = strftime('%A %e %B %Y, %H.%M (%Z)', $booking->startUts);

        $twig = UserFrosting::getInstance()->view()->getEnvironment();

        $template = $twig->loadTemplate("mail/sendBookingHandledMail.twig");

        $subject = 'Din taxibokning er accepterad';
        if ($action == 'reject') {
            $subject = 'Din taxibokning er avvisad';
        }

        $notification = new Notification($template);
        
        $notification->from("taxibo@allanth.dk", $user->title, $user->email, $user->title);

        $notification->addEmailRecipient($booking->email,$booking->name, [
            'date' => $date,
            'booking' => $booking, 
            'client' => $user,
            'headerLine1' => $user->title,
            'headerLine2' => $subject,
            'footerLine' => "Skickat " . strftime('%A %e %B %Y, %H.%M (%Z)'), 
            'subject' => $subject
        ]);

        $success = NULL;
        try {
            
            $success = $notification->send();
            
        } catch (\phpmailerException $e){
            print $e->errorMessage();
            error_log('Mailer Error: ' . $e->errorMessage());
            $app->halt(500);
        }

        return $success;

    }

    public static function sendNewBookingMail($booking, $userId)
    {
        $user = User::where('id', $userId)->first();
        $date = strftime('%A %e %B %Y, %H.%M (%Z)', $booking->startUts);

        $twig = UserFrosting::getInstance()->view()->getEnvironment();

        $template = $twig->loadTemplate("mail/sendNewBookingMail.twig");

        $subject = 'Ny taxibokning';
        if ($booking->originCity && $booking->destinationCity) {
            $subject = 'Ny taxibokning från ' . $booking->originCity . ' till ' . $booking->destinationCity;
            if ($booking->originCity == $booking->destinationCity) {
                $subject = 'Ny taxibokning inom ' . $booking->originCity;
            }
        }

        $notification = new Notification($template);
        
        $notification->from("taxibo@allanth.dk", $user->title, $user->email, $user->title);

        // future bookings
        $bookings = Booking::where('user_id', $userId)->where('startUts', '>', (time() - 21600))->get();
        
        $htmlRows = array();


        $statuses = array(
            'new' => 'Ohanterat',
            'accepted' => 'Accepterad',
            'rejected' => 'Avvisad'
        );
       

        foreach($bookings as $key => $futureBooking) {
            $backgroundColor = ($key % 2 == 0) ? '#e9e9e9' : '#ffffff';
            $style = "background-color: $backgroundColor; font-size: 12px; padding: 10px";
            $buttonHtml = '';
            if ($futureBooking->status == 'new') {
                $buttonHtml = '<a href="' . UserFrosting::getInstance()->site->uri['public'] . '/booking/'.$futureBooking->id.'/accept/'.$futureBooking->hash.'" style="background-color: #8ea604; color: white; padding: 10px; border-radius: 5px;text-decoration: none;margin-right: 20px;">Acceptera</a><a href="' . UserFrosting::getInstance()->site->uri['public'] . '/booking/'.$futureBooking->id.'/reject/'.$futureBooking->hash.'" style="background-color: #c00000; color: white; padding: 10px; border-radius: 5px;text-decoration: none;margin-right: 20px;">Avslå</a>';
                //$buttonHtml = '<a href="http://taxibooking.allanth.dk/?action=accept&amp;bookingId=' . $futureBooking->id . '&amp;hash=' . $futureBooking->hash . '&amp;c=' . $client->name . '" style="background-color: #8ea604; color: white; padding: 10px; border-radius: 5px;text-decoration: none;margin-right: 20px;">Acceptera</a><a href="http://taxibooking.allanth.dk/?action=reject&amp;bookingId=' . $futureBooking->id . '&amp;hash=' . $futureBooking->hash . '&amp;c=' . $client->name . '" style="background-color: #C00000; color: white; padding: 10px; border-radius: 5px;text-decoration: none;margin-right: 20px;">Avslå</a>';
            }

             

            $htmlRows[] = "
                <tr valign=\"top\">
                    <td style=\"$style\" colspan=\"2\">Från <strong>" . htmlspecialchars($futureBooking->origin) . "</strong> till <strong>" . htmlspecialchars($futureBooking->destination) . "</strong></td>
                </tr>
                <tr valign=\"top\">
                    <td style=\"$style\" width=\"60%\">
                        Avresedatum: " . htmlspecialchars(strftime('%A %e %B %Y, %H.%M (%Z)', $futureBooking->startUts)) . "<br />
                        Namn: " . htmlspecialchars($futureBooking->name) . "<br />
                        Email: " . htmlspecialchars($futureBooking->email) . "<br />
                        Telefon: " . htmlspecialchars($futureBooking->phone) . "<br /><br />
                        Avstånd: " . htmlspecialchars($futureBooking->distance) . "<br />
                        Körtid: " . htmlspecialchars($futureBooking->duration) . "<br />
                        Pris: " . htmlspecialchars($futureBooking->price) . ":- kr.
                    </td>
                    <td style=\"$style\" width=\"40%\"><span style=\"font-size: 20px\">Tur: " . htmlspecialchars($futureBooking->alphaId) . "</span>
                    <br />Status: " . htmlspecialchars($statuses[$futureBooking->status]) . "<br /><br />$buttonHtml</td> 
                </tr>";
        }

        $futureBookingsHtml = '';
        if (count($htmlRows)) {
            $futureBookingsHtml = "
                <p><strong>Kommende bookings</strong></p>
                <table cellspacing=\"0\">" . implode("", $htmlRows) . " 
                </table>
            ";
        }

        $notification->addEmailRecipient($user->email, $user->title, [
            'date' => $date,
            'booking' => $booking, 
            'client' => $user,
            'headerLine1' => $user->title,
            'headerLine2' => $subject,
            'footerLine' => "Skickat " . strftime('%A %e %B %Y, %H.%M (%Z)'), 
            'subject' => $subject,
            'futureBookingsHtml' => $futureBookingsHtml,
        ]);

        $success = NULL;
        try {
            
            $success = $notification->send();
            
        } catch (\phpmailerException $e){
            print $e->errorMessage();
            error_log('Mailer Error: ' . $e->errorMessage());
            $app->halt(500);
        }

        return $success;
            
    }

    
}
