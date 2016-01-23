/*
 * jQuery throttle / debounce - v1.1 - 3/7/2010
 * http://benalman.com/projects/jquery-throttle-debounce-plugin/
 * 
 * Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */
(function(b,c){var $=b.jQuery||b.Cowboy||(b.Cowboy={}),a;$.throttle=a=function(e,f,j,i){var h,d=0;if(typeof f!=="boolean"){i=j;j=f;f=c}function g(){var o=this,m=+new Date()-d,n=arguments;function l(){d=+new Date();j.apply(o,n)}function k(){h=c}if(i&&!h){l()}h&&clearTimeout(h);if(i===c&&m>e){l()}else{if(f!==true){h=setTimeout(i?k:l,i===c?e-m:e)}}}if($.guid){g.guid=j.guid=j.guid||$.guid++}return g};$.debounce=function(d,e,f){return f===c?a(d,e,false):a(d,f,e!==false)}})(this);



// This example displays an address form, using the autocomplete feature
// of the Google Places API to help users fill in the information.

var placeSearch, from, to, date, time;

function initAutocomplete() {
  // Create the autocomplete object, restricting the search to geographical
  // location types.
  from = new google.maps.places.Autocomplete(
      (document.getElementById('from')),
      {types: ['geocode'], language: 'sv'}
  );

  to = new google.maps.places.Autocomplete(
      (document.getElementById('to')),
      {types: ['geocode'], language: 'sv'}
  );


  date = $("#date");
  time = $("#time");
  // When the user selects an address from the dropdown, populate the address
  // fields in the form.
  from.addListener('place_changed', updateOffer);
  to.addListener('place_changed', updateOffer);

  var debounced = jQuery.debounce( 1000, false, updateOffer );
  date.bind('change', debounced);
  time.bind('change', debounced);
  
}

function updateOffer() {

  if ($("#from").val() != '' && $("#to").val() != '' && date.val() != ''  && time.val() != '') {



    var originLat;
    var originLng;
    var destinationLat;
    var destinationLng;

    try {
      
      var originPlace = from.getPlace();
      originLat = originPlace.geometry.location.lat();
      originLng = originPlace.geometry.location.lng();


    } catch (error) {
      
      $('#alertFetchingOfferError').show();
      $('#alertFetchingOfferErrorMessage').html("Ursprungadressen hittades inte");
      return false;
    }

    try {
      var destinationPlace = to.getPlace();
      destinationLat = destinationPlace.geometry.location.lat();
      destinationLng = destinationPlace.geometry.location.lng();

    } catch (error) {
        $('#alertFetchingOfferError').show();
        $('#alertFetchingOfferErrorMessage').html("Måladressen hittades inte");
       return false;
    }



    $("#alertFetchingOffer").show();
    
    $.getJSON( thisUrl + '/getOffer/' + $("#from").val() + '/' + $("#to").val() + '/' + date.val() + '/' + time.val(), {
      "latLng": [originLat, originLng, destinationLat, destinationLng] 
    }, function( data ) {
        
        $("#alertFetchingOffer").hide();
        if (data.success == "yes") {
            $("#offerFrom").html(data.offerFrom);
            $("#offerTo").html(data.offerTo);
            $("#offerDate").html(data.offerDate);
            $("#offerDuration").html(data.offerDuration);
            $("#offerDistance").html(data.offerDistance);
            $("#offerPrice").html(data.offerPrice + ':- kr');
            $("#offer").show();
            $('#alertFetchingOfferError').hide();
            window.parent.postMessage(1350, "*");
        } else {
            $('#alertFetchingOfferError').show();
            $('#alertFetchingOfferErrorMessage').html(data.errorMessage);
            $("#offer").hide();
        }
    });
    

  

    var originCity = '';
    var destinationCity = '';
    
    try {

      var originAddressComponents = from.getPlace().address_components;
      var destinationAddressComponents = to.getPlace().address_components;
      
      $.each(originAddressComponents, function(index, item){
        if (typeof item.types[0] != 'undefined' && item.types[0] == 'locality') {
          originCity = item.long_name;
          return false;
        }
      });
      if (originCity == '') {
        $.each(originAddressComponents, function(index, item){
          originCity = item.long_name;
          return false;
        });
      }
      
      $.each(destinationAddressComponents, function(index, item){
        
        if (typeof item.types[0] != 'undefined' && item.types[0] == 'locality') {
          destinationCity = item.long_name;
        }
      });

      if (destinationCity == '') {
        $.each(destinationAddressComponents, function(index, item){
          destinationCity = item.long_name;
          return false;
        });
      }


    } catch (error) {

    } 
    
    if (originCity) {
      $('#originCity').val(originCity);
    }
    if (destinationCity) {
      $('#destinationCity').val(destinationCity);
    }
    
    var mapImgSrc = "https://maps.googleapis.com/maps/api/staticmap?size=499x212&maptype=roadmap&markers=color:blue%7CDESTINATION&markers=ORIGIN&key=AIzaSyC3bTjkqfQI6lQwlbxjt89otbJpHDrr5lg";
    
    var liveSrc = mapImgSrc.replace("ORIGIN", encodeURIComponent($("#from").val())).replace("DESTINATION", encodeURIComponent($("#to").val()));

    $("#mapImg").attr("src", liveSrc);

  }  
}
/*
function fillInAddress() {
  // Get the place details from the autocomplete object.
  var place = autocomplete.getPlace();

  for (var component in componentForm) {
    document.getElementById(component).value = '';
    document.getElementById(component).disabled = false;
  }

  // Get each component of the address from the place details
  // and fill the corresponding field on the form.
  for (var i = 0; i < place.address_components.length; i++) {
    var addressType = place.address_components[i].types[0];
    if (componentForm[addressType]) {
      var val = place.address_components[i][componentForm[addressType]];
      document.getElementById(addressType).value = val;
    }
  }
}
*/
// Bias the autocomplete object to the user's geographical location,
// as supplied by the browser's 'navigator.geolocation' object.
function geolocate() {
  return false;
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      var geolocation = {
        lat: position.coords.latitude,
        lng: position.coords.longitude
      };
      var circle = new google.maps.Circle({
        center: geolocation,
        radius: position.coords.accuracy
      });
      autocomplete.setBounds(circle.getBounds());
    });
  }
}


$("#btnReset").click(function() {
  $("#from").focus();
  $("#from").val('');
  $("#to").val('');
  $("#offer").hide();
});

$("#btnBook").click(function() {
   
    var name = $("#name").val();
    var email = $("#email").val();
    var phone = $("#phone").val();
    var passengerCount = $("#passengerCount").val();
    var originCity = $("#originCity").val();
    var destinationCity = $("#destinationCity").val();

    if (!name) {
        $("#form-group-name").addClass("has-warning");
        return false;
    } else {
        $("#form-group-name").removeClass("has-warning");
    }
    if (!email) {
        $("#form-group-email").addClass("has-warning");
        return false;
    } else {
        $("#form-group-email").removeClass("has-warning");
    }
    if (!phone) {
        $("#form-group-phone").addClass("has-warning");
        return false;
    } else {
        $("#form-group-phone").removeClass("has-warning");
    }
    if (!passengerCount) {
        $("#form-group-passengerCount").addClass("has-warning");
        return false;
    } else {
        $("#form-group-passengerCount").removeClass("has-warning");
    }

    $("#alertBookingOffer").show();

    $.post( thisUrl + '/book/' , {
        "origin": $("#from").val(),
        "destination": $("#to").val(),
        "date": date.val(),
        "time": time.val(),
        "name": name,
        "email": email,
        "phone": phone,
        "passengerCount": passengerCount,
        "originCity": originCity,
        "destinationCity": destinationCity,
        "csrf_token": $("#csrf_token").val()
        

    }, function( data ) {
        $("#alertBookingOffer").hide();
        if (data.success == "yes") {
            
            alert("Din beställning har mottagits. Du kommer att få ett mejl med den slutliga bekräftelsen.");
            window.location = thisUrl;
            window.parent.postMessage(350 , "*");

        }
    }, 'json');
});


$(function() {
    window.parent.postMessage(350, "*");
});
