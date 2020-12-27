function generateFile(type,id,ext,language) {
  var signed;
  var url = "../inc/S3LinkGen.php?type=" + type + "&id=" + id + "&ext=" + ext;
  console.log(url);
  var response = $.ajax({
    url: url,
    context: document.body,
    error: function(xhr, error) {
             console.debug('xhr',xhr); 
             console.debug('error',error);
           }
  }).done(function(signedUrl) {
    if (signedUrl.match(/https\:\/\/s3\.amazonaws\.com\//)) {
      writeHTML(type,id,signedUrl,ext,language);
    }
    else {
      return null;
    }
  })
  function writeHTML(type,id,signedUrl,ext,language=null) {
    if (type == 'video') {
      $("#video1").append("<source type='video/"+ext+"' id='video' src='"+signedUrl+"'>");
    }
    else if (type == 'thumb') {
      $("#thumb_" + id).append("<img src = '"+signedUrl+"' class='thumb'>");
    }
    else {
      var label;
      if (type == 'translation') {
        label = 'English';
      }
      else if (type == 'transcript') {
        label = language;
      }
      var la = label.substr(0,2).toLowerCase();
      $("#video1").append("<track kind='captions' src='"+signedUrl+"' srclang='"+la+"' label='"+label+"'/>");

    }
  }
}