/**
 * JavaScript for the Options page in the admin menu.
 */
function address_lookup() {
    var city = jQuery('#bfc_city').val();
    var province = jQuery('#bfc_province').val();
    var country = jQuery('#bfc_country').val();

    var address = city + " " + province + " " + country;
    var geocoder = new google.maps.Geocoder();

    geocoder.geocode( {'address': address }, function(results, status) {
        var latlong_div = jQuery('#bfc_latlong_results');
        latlong_div.empty();

        if (status == google.maps.GeocoderStatus.OK) {    
            var googles_address = results[0].formatted_address;
            var latitude        = results[0].geometry.location.lat();
            var longitude       = results[0].geometry.location.lng();

            latlong_div.append(jQuery('<p>').text("Google Maps says this is your location:"));
            
            var list = jQuery('<ul>');
            list.append(jQuery('<li>').text("Location: " + googles_address));
            list.append(jQuery('<li>').text("Latitude: " + latitude));
            list.append(jQuery('<li>').text("Longitude: " + longitude));
            latlong_div.append(list);

            jQuery('#bfc_latitude').val(latitude);
            jQuery('#bfc_longitude').val(longitude);
        }
        else {
            // Geocode not successful. 
            latlong_div.append(jQuery('<p>').text(
                "Uh-oh! Something went wrong looking up your address with Google. " +
                    "We got an error code of: " + status));
        }
    });
}

jQuery(document).ready(function() {
    jQuery('#bfc_city').change(address_lookup);
    jQuery('#bfc_province').change(address_lookup);
    jQuery('#bfc_country').change(address_lookup);

    address_lookup();
});


