
jQuery(document).ready(function() {

	var APP_Attachment = {
		init: function() {
			window.appFileCount = typeof window.appFileCount == 'undefined' ? 0 : window.appFileCount;
			this.maxFiles = parseInt(AppPluploadConfig.number);

			jQuery('#app-attachment-upload-filelist').on('click', 'a.attachment-delete', this.removeAttachment);

			this.attachUploader();
			this.hideUploadBtn();
		},
		hideUploadBtn: function() {

			if ( APP_Attachment.maxFiles !== 0 && window.appFileCount >= APP_Attachment.maxFiles ) {
				jQuery('#app-attachment-upload-pickfiles').hide();
			}
		},
		showUploadBtn: function() {

			if ( APP_Attachment.maxFiles !== 0 && window.appFileCount < APP_Attachment.maxFiles ) {
				jQuery('#app-attachment-upload-pickfiles').show();
			}
		},
		attachUploader: function() {
			if ( typeof plupload === 'undefined' ) {
				return;
			}

			var attachUploader = new plupload.Uploader(AppPluploadConfig.plupload);

			jQuery('#app-attachment-upload-pickfiles').click(function(e) {
				attachUploader.start();
				e.preventDefault();
			});

			attachUploader.init();

			attachUploader.bind('FilesAdded', function(up, files) {
				jQuery.each(files, function(i, file) {
					jQuery('#app-attachment-upload-filelist').append(
						'<div id="' + file.id + '" class="app-attachment-upload-progress">' +
						file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
						'</div>');

					window.appFileCount += 1;
					APP_Attachment.hideUploadBtn();
				});

				up.refresh();
				attachUploader.start();
			});

			attachUploader.bind('UploadProgress', function(up, file) {
				jQuery('#' + file.id + " b").html(file.percent + "%");
			});

			attachUploader.bind('Error', function(up, err) {
				jQuery('#app-attachment-upload-filelist').append(
					'<div class="error">' + err.message + (err.file ? ' File: ' + err.file.name : '') + '</div>'
				);

				up.refresh();
			});

			attachUploader.bind('FileUploaded', function(up, file, response) {
				var resp = jQuery.parseJSON(response.response);
				if ( resp.success ) {
					jQuery('#app-attachment-upload-filelist ul').append(resp.html);
				} else {
					window.appFileCount -= 1;
					APP_Attachment.showUploadBtn();
				}
				jQuery('#' + file.id).remove();
			});
			attachUploader.bind('StateChanged', function() {
				if ( attachUploader.files.length === ( attachUploader.total.uploaded + attachUploader.total.failed ) ) {
					jQuery('input[type="submit"]').prop('disabled', false);
				} else {
					jQuery('input[type="submit"]').prop('disabled', true);
				}
			});
		},
		removeAttachment: function(e) {
			e.preventDefault();

			if ( confirm(AppPluploadConfig.confirmMsg) ) {
				var el = jQuery(this),
				data = {
					'attach_id' : el.data('attach_id'),
					'nonce' : AppPluploadConfig.nonce,
					'action' : 'app_plupload_handle_delete'
				};

				jQuery.post(AppPluploadConfig.ajaxurl, data, function() {
					el.parent().parent().remove();

					window.appFileCount -= 1;
					APP_Attachment.showUploadBtn();
				});
			}
		}
	};


	APP_Attachment.init();

});
