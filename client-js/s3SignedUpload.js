$.fn.uploadHandler = function(s3presignedApiUri) {
	var fileupload = this,
		fileupload0 = fileupload[0];

	function readfiles(files) {
		var uploadFormData,
			fileCounter=files.length;

		for (var file, i = 0; i < fileCounter; i++) {
			file = files[i];
			// console.log("file:",files[i].name);
			uploadFormData = new FormData();
			// if(files[i].name && !files[i].name.match(/.(gif|jpe?g|png)$/i)) {
			// 	alert("text");
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
					console.error(data.error);
				} else {
					uploadFile(file, data.url);
				}
			})
			.fail(function(e) {
				console.error("S3 Upload not supported",e);
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
			console.info("s3-upload done: ", newFileUrl);

			////////////////////////////////////////////
			// do something here with the file url //
			////////////////////////////////////////////
		})
		.fail(function(e) {
			console.error("s3-upload failed",e);
		});
	}


	fileupload0.onchange = function () {
		if(this.files.length) {
			readfiles(this.files);
		}
	};

};