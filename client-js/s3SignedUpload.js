$.fn.uploadHandler = function(s3presignedApiUri) {
	var fileupload = this,
		fileupload0 = fileupload[0];

	function readfiles(files) {
		var fileCounter=files.length;

		for (var file, i = 0; i < fileCounter; i++) {
			file = files[i];
			// if(files[i].name && !files[i].name.match(/.(gif|jpe?g|png|svg)$/i)) {
			// 	alert("images only");
			// 	return;
			// }
			$.ajax({
				url : s3presignedApiUri,
				data: 'file='+ file.name + '&mime=' + file.type,
				type : "GET",
				dataType : "json",
				cache : false,
			})
			.done(function(data) {
				if(data.error) {
					console.error(data.error); // REMOVE ME FOR PRODUCTION USE
				} else {
					uploadFile(file, data.url);
				}
			})
			.fail(function(e) {
				console.error("S3 Upload not supported",e); // REMOVE ME FOR PRODUCTION USE
			});
		}


	}

	function uploadFile(file, s3presignedUrl) {
		$.ajax({
			url : s3presignedUrl,
			type : "PUT",
			data : file,
			dataType : "text",
			cache : false,
			contentType : file.type,
			processData : false
		})
		.done(function() {
			var newFileUrl = s3presignedUrl.split('?')[0].substr(6);
			console.info("s3-upload done: ", newFileUrl); // REMOVE ME FOR PRODUCTION USE

			////////////////////////////////////////////
			// do something here with the file url //
			////////////////////////////////////////////
		})
		.fail(function(e) {
			console.error("s3-upload failed",e); // REMOVE ME FOR PRODUCTION USE
		});
	}


	fileupload0.onchange = function () {
		if(this.files.length) {
			readfiles(this.files);
		}
	};

};