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
        
        checkMedia();
    });
    
    // Check the media when form loads. The warning displays faster if initiated from this position.
    checkMedia();

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
        var mimeType = '';
        var allMimeTypes = getAllMimeType();
        
        if (allMimeTypes[extension]) {
            mimeType = allMimeTypes[extension];
        }

        var format = document.getElementById("format");                    
        document.getElementById("format").value = mimeType;
        checkMedia();
    });
});

// Validate the media file if content object type or selected file changes.
// Shows / hides a warning if the media file is inappropriate for the content
// type.
function checkMedia() {
    var mimeTypes = {};
    var mediaMimeType = $('#format').val() ? $('#format').val() : '';
    
    // If there is no media attachment then no need to show file type warnings.
    if (mediaMimeType === '') {
        hideAlerts();
        return;
    }
    
    // Get a list of mime types appropriate for this content type.
    switch($("#type").val()) {
        case 'TfishAudio':
            mimeTypes = getAudioMimeType();
            break;

        case 'TfishImage':
            mimeTypes = getImageMimeType();
            break;

        case 'TfishVideo':
            mimeTypes = getVideoMimeType();
            break;

        default:
            mimeTypes = getAllMimeType();
            break;
    }
    
    // You'd think Javascript would have a standard way to find values in objects, but you'd be wrong.
    var typeList = $.map(mimeTypes, function(value, index) {
        return [value];
    });
    
    // Show or hide the mimetype warning.
    if ($.inArray(mediaMimeType, typeList) === -1) {
        showAlerts(); // Mimetype is bad.
    } else {
        hideAlerts(); // Mimetype is good.
    }
}

// Show warning if media file type is inappropriate for this content type.
function showAlerts() {
    $('.alert').removeClass('d-none');
    $('.alert').removeClass('hide');
    $('.alert').addClass('d-block');
    $('.alert').addClass('show');
    $('.alert2').removeClass('d-none');
    $('.alert2').removeClass('hide');
    $('.alert2').addClass('d-inline');
    $('.alert2').addClass('show');
}

// Hide warning if media file type is inappropriate for this content type.
function hideAlerts() {
    $('.alert').removeClass('d-block');
    $('.alert').removeClass('show');
    $('.alert').addClass('d-none');
    $('.alert').addClass('hide');
    $('.alert2').removeClass('d-inline');
    $('.alert2').removeClass('show');
    $('.alert2').addClass('d-none');
    $('.alert2').addClass('hide');
}

// Gets the file extension of the media file (used to set mimetype).
function getFileExtension(filename) {
    return filename.slice((filename.lastIndexOf(".") - 1 >>> 0) + 2);
}

// Get an audio mimetype.
function getAudioMimeType() {
    var audioMimeType = {}; 
    
    audioMimeType.mp3 = "audio/mpeg";
    audioMimeType.oga = "audio/ogg";
    audioMimeType.ogg = "audio/ogg";
    audioMimeType.wav = "audio/x-wav";
    
    return audioMimeType;
}

// Get an image mimetype.
function getImageMimeType() {
    var imageMimeType = {};
    
    imageMimeType.gif = "image/gif";
    imageMimeType.jpg = "image/jpeg";
    imageMimeType.png = "image/png";
    
    return imageMimeType;
}

// Get a video mimetype.
function getVideoMimeType() {
    var videoMimeType = {};
    
    videoMimeType.mp4 = "video/mp4";
    videoMimeType.ogv = "video/ogg";
    videoMimeType.webm = "video/webm";
    
    return videoMimeType;
}

// Get the appropriate mimetype, given a file extension.
function getAllMimeType() {
    var audioMimeTypes = getAudioMimeType();
    var imageMimeTypes = getImageMimeType();
    var videoMimeTypes = getVideoMimeType();
    
    var allMimeTypes = Object.assign({}, audioMimeTypes, imageMimeTypes, videoMimeTypes);

    // Add documents.
    allMimeTypes.doc = "application/msword";
    allMimeTypes.docx = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
    allMimeTypes.pdf = "application/pdf";
    allMimeTypes.ppt = "application/vnd.ms-powerpoint";
    allMimeTypes.pptx = "application/vnd.openxmlformats-officedocument.presentationml.presentation";
    allMimeTypes.odt = "application/vnd.oasis.opendocument.text";
    allMimeTypes.ods = "application/vnd.oasis.opendocument.spreadsheet";
    allMimeTypes.odp = "application/vnd.oasis.opendocument.presentation";
    allMimeTypes.xls = "application/vnd.ms-excel";
    allMimeTypes.xlsx = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";

    // Add archives.
    allMimeTypes.zip = "application/zip";
    allMimeTypes.gz = "application/x-gzip";
    allMimeTypes.tar = "application/x-tar";

    return allMimeTypes;
}