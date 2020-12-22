function generateFiles(files) {
  var confirm = [];
  $.each(files, function() {
    file = $(this)[0];
      $.ajax({
        url: "./S3LinkGen.php?id=" + file.id + "&ext=" + file.ext,
        context: document.body,
        error: function(xhr, error) {
                 console.debug(xhr); 
                 console.debug(error);
               }
      }).done(function(signedUrl) {
        if (signedUrl.match(/https\:\/\/s3\.amazonaws\.com\//)) {
          $('#' + file.type).src=signedUrl;
          confirm[file.type] = 1;
        }
        else {
          confirm[file.type] = 0;
        }
      })
    }
  );
  return confirm;
}