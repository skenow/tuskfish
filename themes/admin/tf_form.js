// Shows/hides form fields that are relevant/irrelevant to the content type.
$(document).ready(function() {
    var allowedProperties = ['teaserContainer', 'descriptionContainer', 'captionContainer',
        'creatorContainer', 'dateContainer', 'imageContainer', 'languageContainer',
        'mediaContainer', 'parentContainer', 'publisherContainer', 'rightsContainer',
        'tagsContainer', 'metaHeader', 'metaTitleContainer', 'seoContainer',
        'metaDescriptionContainer'];
    $("#type").change(function () {
        $.each(allowedProperties, function (i, value) {
            $('#' + value).show();
        });
        if ($("#type").val() === 'TfishTag') {
            var disabledProperties = [
                'creatorContainer', 'dateContainer', 'languageContainer',
                'rightsContainer', 'publisherContainer', 'tagsContainer'];
            $.each(disabledProperties, function (i, value) {
                $('#' + value).hide();
            });
        }
        if ($("#type").val() === 'TfishBlock') {
            var disabledProperties = [
                'teaserContainer', 'creatorContainer', 'parentContainer', 'rightsContainer',
                'publisherContainer', 'metaHeader', 'metaTitleContainer', 'seoContainer',
                'metaDescriptionContainer'];
            $.each(disabledProperties, function (i, value) {
                $('#' + value).hide();
            });
        }
    });

    // Set flag that media file should be deleted from server.
    $('#media').on('fileclear', function(tf_deleteMedia) {
        document.getElementById("format").value="";
        
        // Not required on data entry form as no media has been uploaded.
        if (document.getElementById("deleteMedia")) {
            document.getElementById("deleteMedia").value = "1";
        }
 
        checkMedia();
    });

    // Updates the format (mimetype) property when media file is changed.
    $('#media').on('change', function(event) {
        var filename = document.getElementById("media").files[0].name;
        var extension = getFileExtension(filename);
        var mimetype = getMimeType(extension);
        var format = document.getElementById("format");                    
        document.getElementById("format").value = mimetype;
        checkMedia();
    });

    // Validate media file mimetype on changing the content type.
    $('#type').change(function(tf_resetMedia) {
        checkMedia();
    });
});

// Validate the media file if content object type or selected file changes.
// Shows / hides a warning if the media file is inappropriate for the content
// type.
function checkMedia() {
    var allowedExtensionTypes = [""];
    var mediaMimeType = $('#format').val() ? $('#format').val() : '';

    switch($("#type").val()) {
        case '': // No media file.
            break;

        case 'TfishAudio':
            allowedExtensionTypes.push(
                "audio/ogg",
                "audio/ogg",
                "audio/mpeg",
                "audio/x-wav"
            );
            break;

        case 'TfishImage':
            allowedExtensionTypes.push(
                "image/gif",
                "image/jpeg",
                "image/png"
            );
            break;

        case 'TfishVideo':
            allowedExtensionTypes.push(
                "video/mp4",
                "video/ogg",
                "video/webm"
            );
            break;

        default:
            allowedExtensionTypes.push(
                "application/msword", // Documents.
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "application/pdf",
                "application/vnd.ms-powerpoint",
                "application/vnd.openxmlformats-officedocument.presentationml.presentation",
                "application/vnd.oasis.opendocument.text",
                "application/vnd.oasis.opendocument.spreadsheet",
                "application/vnd.oasis.opendocument.presentation",
                "application/vnd.ms-excel",
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                "image/gif", // Images.
                "image/jpeg",
                "image/png",
                "audio/mpeg", // Audio.
                "audio/ogg",
                "audio/ogg",
                "audio/x-wav",
                "video/mp4", // Video.
                "video/ogg",
                "video/webm",
                "application/zip", // Archives.
                "application/x-gzip",
                "application/x-tar"
            );
            break;
    }

    // Show or hide the mimetype warning.
    if ($.inArray(mediaMimeType, allowedExtensionTypes) === -1) {
        // Mimetype is bad, show the alerts.
        $('.alert').removeClass('d-none');
        $('.alert').removeClass('hide');
        $('.alert').addClass('d-block');
        $('.alert').addClass('show');
        $('.alert2').removeClass('d-none');
        $('.alert2').removeClass('hide');
        $('.alert2').addClass('d-inline');
        $('.alert2').addClass('show');   
    } else {
        // Mimetype is good, or there is no media file, hide the alerts.
        $('.alert').removeClass('d-block');
        $('.alert').removeClass('show');
        $('.alert').addClass('d-none');
        $('.alert').addClass('hide');
        $('.alert2').removeClass('d-inline');
        $('.alert2').removeClass('show');
        $('.alert2').addClass('d-none');
        $('.alert2').addClass('hide');
    }
}

// Gets the file extension of the media file (used to set mimetype).
function getFileExtension(filename) {
    return filename.slice((filename.lastIndexOf(".") - 1 >>> 0) + 2);
}

// Get the appropriate mimetype, given a file extension.
function getMimeType(extension) {
    var mimeTypes = {};

    // Documents.
    mimeTypes.doc = "application/msword";
    mimeTypes.docx = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
    mimeTypes.pdf = "application/pdf";
    mimeTypes.ppt = "application/vnd.ms-powerpoint";
    mimeTypes.pptx = "application/vnd.openxmlformats-officedocument.presentationml.presentation";
    mimeTypes.odt = "application/vnd.oasis.opendocument.text";
    mimeTypes.ods = "application/vnd.oasis.opendocument.spreadsheet";
    mimeTypes.odp = "application/vnd.oasis.opendocument.presentation";
    mimeTypes.xls = "application/vnd.ms-excel";
    mimeTypes.xlsx = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";

    // Images.
    mimeTypes.gif = "image/gif";
    mimeTypes.jpg = "image/jpeg";
    mimeTypes.png = "image/png";

    // Audio.
    mimeTypes.mp3 = "audio/mpeg";
    mimeTypes.oga = "audio/ogg";
    mimeTypes.ogg = "audio/ogg";
    mimeTypes.wav = "audio/x-wav";

    // Video.
    mimeTypes.mp4 = "video/mp4";
    mimeTypes.ogv = "video/ogg";
    mimeTypes.webm = "video/webm";

    // Archives.
    mimeTypes.zip = "application/zip";
    mimeTypes.gz = "application/x-gzip";
    mimeTypes.tar = "application/x-tar";

    return mimeTypes[extension];
}