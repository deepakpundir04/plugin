jQuery(document).on('click', '#myfiles', function(e){
    e.preventDefault();
	// var fd = new FormData();
	var fileInputElement = document.getElementById("myfile");

    console.log(ajax_object.we_value);
    
    var individual_file = fileInputElement.files[0];

 	if(!isImage(individual_file)){
 		alert("Incorrect file format");
 	}
 	else{

		file_data = document.getElementById("myfile");	
	    form_data = new FormData();
	    form_data.append('file', individual_file);
	    form_data.append('action', 'handle_apt_ajax_request')
	    form_data.append('security', ajax_object.we_value)

	    jQuery.ajax({
	        type: 'POST',
	        url: ajax_object.ajax_url,
	        action: 'handle_apt_ajax_request',
			data:  form_data,
	        contentType: false,
	        processData: false,
	        success: function(response){
	        	response = JSON.parse(response)
	        	console.log(response)
	        	if(response.code !== 200){
	        		alert(response.msg);
	        	}
	        	else if(response.code === 200){
	        		document.getElementById("upload_url").value = response.data.url;
	        	}
	            console.log(response.code);
	        }
	    });
	}
});

function isImage(file) {
  var filename = file.name
  var ext = filename.split('.');
  ext = ext[ext.length -1];

  switch (ext.toLowerCase()) {
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'pdf':
      return true;
  }
  return false;
}