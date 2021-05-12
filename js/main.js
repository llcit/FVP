  var doDelete = false;
  function playVideo(element_id,embedded) {
    if (!doDelete) {
      var id_parts = element_id.match(/(.+)\_(.+)/);
      var video_id = id_parts[2];
      var backTick = (embedded) ? '..' : '.';
      window.location = backTick + "/player/index.php?v="+video_id ;
    }
  }
  function writeDetails(data_str) {
    var data = $.parseJSON(data_str);
    console.log(data);
    var details = [];
    details.push('<li>' + data.first_name + ' ' + data.last_name + '</li>');
    details.push('<li>Program: ' + data.program + '</li>');
    details.push('<li>Program Year: ' + data.progYrs + '</li>');
    details.push('<li>Program Period: ' + data.phase + '</li>');
    details.push('<li>Domestic Institution: ' + data.institution + '</li>');
    details.push('<li>Overseas Location: ' + data.city + ', ' + data.country + '</li>');
    details.push('<li>Performance Type: ' + data.type + '</li>');
    $('.modal-title').empty();
    $('.modal-title').append(details.join(''));
  }
  function deleteVideo(id) {
    doDelete = true;
    if (confirm("Are you sure you want to delete this video?")) {
      $("#deleteVideo").val(id);
      $("#deleteForm").submit();
    } 
  }
  function displayMessage(msg) {
    if($("#userMsg")) {
      $("#userMsg").html("<p>"+msg+"</p>");
      $("#userMsg").show();
    }
  }
  function enableSubmit() {
    $("#submitConsent").prop('disabled', false);
  }
  function grantConsent(userName) {
    if ($("#userName").val() == userName) {
      $("#uploadForm").submit();
    }
    else {
      alert("The page is expecting '" + userName + "'.  You typed '" + $("#userName").val() + "' Please enter your name carefully to match this!");
      return false;
    }

  }
  function isEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
  }

  function copyToClipboard(id) {
    var copyText = document.getElementById('cl_'+id);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");  
  }