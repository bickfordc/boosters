
var MIN_LENGTH = 3;

$( document ).ready(function() {
    
    $("#student").keyup(function() {
            var keyword = $("#student").val();
            if (keyword.length >= MIN_LENGTH) {
                    $.get( "searchStudent.php", { key: keyword } )
                    .done(function( data ) {
                            $('#results').html('');
                            var results = jQuery.parseJSON(data);
                            console.log(results)
                            $.each(results, function(key, value) {
                                $('#results').append('<div class="studentitem" data-id="' + key + '">' + value + '</div>');
                            })

                        $('.studentitem').click(function() {
                            //var text = $(this).html();
                            var text = $(this).text();
                            $('#student').val(text);
                            var id = $(this).attr('data-id');
                            document.getElementById('studentid').value = id;
                            //$('#studentid').attr('value', id);
                        })

                    });
            } else {
                    $('#results').html('');
            }
    });

    $("#student").blur(function(){
    		$("#results").fadeOut(500);
    	})
        .focus(function() {		
    	    $("#results").show();
    	});


    $("#card").keyup(function() {
            var keyword = $("#card").val();
            if (keyword.length >= MIN_LENGTH) {
                    $.get( "searchCard.php", { key: keyword } )
                    .done(function( data ) {
                        $('#cardresults').html('');
                        var results = jQuery.parseJSON(data);
                        $(results).each(function(key, value) {
                                $('#cardresults').append('<div class="carditem">' + value + '</div>');
                        })

                        $('.carditem').click(function() {
                            var cardtext = $(this).html();
                            $('#card').val(cardtext);
                        })

                    });
            } else {
                    $('#cardresults').html('');
            }
    });
    
    $("#card").blur(function(){
            $("#cardresults").fadeOut(500);
    })
    .focus(function() {		
        $("#cardresults").show();
    });

   $("#scripFamily").keyup(function() {
            var keyword = $("#scripFamily").val();
            if (keyword.length >= MIN_LENGTH) {
                    $.get( "searchScripFamily.php", { key: keyword } )
                    .done(function( data ) {
                        $('#scripFamilyResults').html('');
                        var results = jQuery.parseJSON(data);
                        $(results).each(function(key, value) {
                                $('#scripFamilyResults').append('<div class="carditem">' + value + '</div>');
                        });

                        $('.carditem').click(function() {
                            var famtext = $(this).html();
                            $('#scripFamily').val(famtext);
                        });

                    });
            } else {
                    $('#scripFamilyResults').html('');
            }
    });
    
    $("#scripFamily").blur(function(){
            $("#scripFamilyResults").fadeOut(500);
    })
    .focus(function() {		
        $("#scripFamilyResults").show();
    });

});
