function generateFile(type,id,ext,language) {
  var signed;
  var url = base_url + "/inc/S3LinkGen.php?type=" + type + "&id=" + id + "&ext=" + ext;
  // avoid CORS issues with captions
  if (type == 'translation' || type == 'transcript') {
    writeHTML(type,id,ext,null,language);
  }
  var response = $.ajax({
    url: url,
    context: document.body,
    error: function(xhr, error) {
             console.debug('xhr',xhr); 
             console.debug('error',error);
           }
  }).done(function(signedUrl) {
    if (signedUrl.match(/https\:\/\/s3\.amazonaws\.com\//)) {
      writeHTML(type,id,ext,signedUrl,language);
    }
    else {
      return null;
    }
  })
  function writeHTML(type,id,ext,signedUrl=null,language=null) {
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
      console.log("Add:",type);
      $("#video1").append("<track kind='captions' src='"+ base_url + "/inc/S3LinkGen.php?type=" + type + "&id=" + id + "&ext=" + ext
        + "' srclang='"+la+"' label='"+label+"'/>");

    }
  }
}