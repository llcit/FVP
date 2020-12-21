function generateFile(id,ext) {
  $.ajax({
  url: "./S3LinkGen.php?id=" + id + "&ext=" + ext,
  context: document.body,
  error: function(xhr, error) {
           console.debug(xhr); console.debug(error);
         }
}).done(function(signedUrl) {
    if (signedUrl.match(/https\:\/\/s3\.amazonaws\.com\//)) {
      var file = document.getElementById("videoMain").src=signedUrl;
      file.src=signedUrl;
    }
    else {
      var errorMessage = signedUrl.match(/.*\<pre\>(.*)\<\/pre\>/);
      //document.getElementById('#rps_playback_container_' + S3Key).innerHTML = '<p><b>Problem getting the file for playback!</b></p>' + errorMessage[1];
    }
  });
}