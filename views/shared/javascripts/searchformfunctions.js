$ = jQuery;

$(window).load(function () {
    
    getAddr = function(addr, f){
        if(typeof addr != 'undefined' && addr != null) {
            geocoder.geocode( { address: addr, }, function(results, status) {
              if (status == google.maps.GeocoderStatus.OK) {
                f(results);
              }
            });
        }
        return -1;
    }
    
    function getGoogleCoordinates(solrquery, and, func){
        
        if (google){
            // Find the geolocation for the address
            var address = jQuery('#geolocation-address').val();
            var radius = jQuery('#geolocation-radius').val();
            var nl_long_denominator = radius / 111.0;
            var nl_lng_denominator = radius / 60.0;
            if (jQuery.trim(address).length > 0) {
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({'address': address}, function(results, status) {
                    // If the point was found, then put the marker on that spot
                    if (status == google.maps.GeocoderStatus.OK) {
                        var gLatLng = results[0].geometry.location;
                        
                        var add_to_query = and + "latitude" + ':{[' + (gLatLng.lat()-nl_long_denominator) + " TO " + (gLatLng.lat()+nl_long_denominator) + ']}';
                        add_to_query += " AND longitude" + ':{[' + (gLatLng.lng()-nl_lng_denominator) + " TO " + (gLatLng.lng()+nl_lng_denominator) + ']}';
                        
                        func(solrquery + add_to_query);
                    } else {
                        return solrquery;
                        // If no point was found, give us an alert
                        alert('Error: \"' + address + '\" was not found!');
                    }
                });
            }
        }
        func(solrquery);
    }
    
    // a function for taking over the submit function by rebuiding the form input to a solr query
    function SearchFormScoopAndSubmit(){
        
        var solrquery = "";
        var and = "";
        var q = (typeof $("#freequery").val() == 'undefined') ? '' : $("#freequery").val();
        
        $(".facet-search-value").each(function(index, value){
            if ($( this ).val() !== ""){
                solrquery += and + $( this ).attr("id") + ':{' + $( this ).val() + '}';
                and = " AND ";
            }
        });
        
        geoquery = getGoogleCoordinates(solrquery, and, function(finalsolrquery){
            var modified_arguments = "q=" + q + "&" + "free=" + finalsolrquery;
            window.location.href = search_url + "?" + modified_arguments;
        });
        
    }
    
    function addAdvancedSearch(button) {
        //Copy the div that is already on the search form
        var buttonParentTd = button.parent();
        var buttonParentTr = buttonParentTd.parent();
        var oldDiv = buttonParentTr.children().last().children().last();

        //Clone the div and append it to the form
        //Passing true should copy listeners, interacts badly with Prototype.
        var div = oldDiv.clone();

        oldDiv.parent().append(div);

        var inputs = div.find('input');
        var selects = div.find('select');

        //Find the index of the last advanced search formlet and inc it
        //I.e. if there are two entries on the form, they should be named advanced[0], advanced[1], etc
        var inputName = inputs.last().attr('name');

        //Reset the selects and inputs
        inputs.val('');

        //Add the event listener.
        div.find('button.remove_search').click(function () {
            button = $( this );
            removeAdvancedSearch(button);
        });

        handleRemoveButtons(buttonParentTr);
    }

    /**
     * Check the number of advanced search elements on the page and only enable
     * the remove buttons if there is more than one.
     */
    function handleAllRemoveButtons() {
        var trs = $('tr');
        trs.each(function(i){
            handleRemoveButtons($( this ));
        });
    }

    /**
     * Check the number of advanced search elements on the page and only enable
     * the remove buttons if there is more than one.
     */
    function handleRemoveButtons(buttonParentTr) {
        var removeButtons = $('.remove_search');
        if (buttonParentTr.find(removeButtons).length <= 1) {
            buttonParentTr.find(removeButtons).hide();
        } else {
            buttonParentTr.find(removeButtons).show();
        }
    }

    /**
     * Callback for removing an advanced search row.
     *
     * @param {Element} button The clicked delete button.
     */
    function removeAdvancedSearch(button) {
        var buttonParentTd = button.parent();
        var buttonParentTr = buttonParentTd.parent();
        $(button).parent().remove();
        handleRemoveButtons(buttonParentTr);
    }
    
    function addAutocompleteToSearchFields(SearchFieldinputSelector, autocompleteChoicesUrl){
        $(SearchFieldinputSelector).each(function (field){
            var that = $(this);

            that.autocomplete({
                source: function (request, response) {
                    autocompleteChoicesOptions = {};
                    autocompleteChoicesOptions["id"] = that.attr("id");;
                    autocompleteChoicesOptions["term"] = request.term;
                    $.getJSON(autocompleteChoicesUrl,
                        autocompleteChoicesOptions,
                        function (data) {
                            response(data);
                        }
                    );
                },
                focus: function () {
                    return false;
                },
                select: function (event, ui) {
                    that.val(ui.item.label);
                    return false;
                }
                
            });
        });
    }

    var addButton = $('.add_search');
    var removeButtons = $('.remove_search');
    var searchFormSubmitButton = $("#search-form-submit");

    var options = {
        types: []
    };
    var input = document.getElementById('geolocation-address');
    var autocomplete = new google.maps.places.Autocomplete(input, options);

//    var actioninput = document.getElementById('action-geolocation-address');
//    var actionautocomplete = new google.maps.places.Autocomplete(actioninput, options);

    handleAllRemoveButtons();
    
    addButton.click(function (e) {
        button = $( this );
        addAdvancedSearch(button);
        addAutocompleteToSearchFields("input.facet-search-value", autocompleteChoicesUrl);
    });

    removeButtons.click(function () {
        button = $( this );
        removeAdvancedSearch(button);
    });
    
    searchFormSubmitButton.click(function (e) {
        e.preventDefault();
        SearchFormScoopAndSubmit();
    });
    
    addAutocompleteToSearchFields("input.facet-search-value", autocompleteChoicesUrl);
    
});