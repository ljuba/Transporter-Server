$(document).ready( function() {

    //Set a version as active
    $('.set_active').click( function() {
        var choice = confirm("Do you really want to change the active version?");

        if(choice) {
            var version = $(this).attr("value");
            window.location.replace("index.php?setactive=" + version);
        }
        return false;
    });

    //Generate images
    $('.generate_image').click( function() {
        var choice = confirm("Do you really want to generate images for this version?");

        if(choice) {
            var version = $(this).attr("value");
            $(this).text('Generating!');

            $.get('map_images.php', {version: version}, function(response) {
                if(response > 0) {
                    alert("The map images were successfully generated!");                    
                } else {
                    alert("The images could not be generated. Please try again.");
                }
            });
        }
        return false;
    });

    //Create a new version
    $('#new_version').click( function() {
        var choice = confirm("Do you really want to create a new version?");

        if(choice) {
            $(this).text('Working. Please wait!');

            $.get('update.php', function(response) {
                if(response > 0) {
                    alert("A new version was successfully created!");
                    window.location.reload();
                } else {
                    alert("The creation of a new version failed. Please check the logs for errors.");
                }
            });
        }
        return false;
    });


    //Retrieve file and place in textarea
    $("#file_select").change( function() {
        var fileType = $("#file_select").val();
        console.log("Received change event " + fileType);
        $("#file_contents").empty();

        $.get('file_operations.php', {filetype: fileType}, function(response) {
            //console.log(response);
            if(response == "-1") {
                alert("The file could not be retrieved!");
            } else {
                $("#file_contents").append(response);
            }
        });

        return false;
    });

    //Submit file contents to server
    $('#edit_file').click( function() {
       var xml = $('#file_contents').val();
       var fileType = $("#file_select option:selected").val();

       $.post('file_operations.php', {filetype: fileType,
                filecontents: $.URLEncode(xml)}, function(response) {
            if(response > 0) {
                alert("The file was successfully updated!");
            } else {
                alert("The file could not be updated. Please check if the XML is valid and try again.");
            }
        });

        return false;
    });
});

//http://0061276.netsolhost.com/tony/testurl.html
$.extend({URLEncode:function(c){var o='';var x=0;c=c.toString();var r=/(^[a-zA-Z0-9_.]*)/;
  while(x<c.length){var m=r.exec(c.substr(x));
    if(m!=null && m.length>1 && m[1]!=''){o+=m[1];x+=m[1].length;
    }else{if(c[x]==' ')o+='+';else{var d=c.charCodeAt(x);var h=d.toString(16);
    o+='%'+(h.length<2?'0':'')+h.toUpperCase();}x++;}}return o;},
URLDecode:function(s){var o=s;var binVal,t;var r=/(%[^%]{2})/;
  while((m=r.exec(o))!=null && m.length>1 && m[1]!=''){b=parseInt(m[1].substr(1),16);
  t=String.fromCharCode(b);o=o.replace(m[1],t);}return o;}
});


