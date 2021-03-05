  function setContext(context) {
    $('#context').val(context);
    $('#manage').val(0);
    $('#save').val(0);
    $('#remove').val(0);
    $('#post_id').val(0);
    $('#student_program_id').val(0);
    $('#auto_send').prop( "checked", false );
    $('#manageForm').submit();
  }
  function manage(id) {
    $('#manage').val(1);
    $('#post_id').val(id);
    $('#manageForm').submit();
  }
  function manageStudents(program_id) {
    $('#context').val('student');
    $('#post_id').val(program_id);
    $('#manageForm').submit();
  }
  function viewVideos(uname) {
    window.open('../personal.php?uname='+uname, 'videoView');
    return false;
  }
  function importRoster() {
    $("input[type='file']").trigger('click');
  }
  function downloadTemplate() {
    var doc_uri = "./Roster_template.csv";
    window.location = doc_uri;
  }
  function save() {
    // prevent double save on refresh -- set cookie to true on button press
    $.cookie('doSave', true, { path: '/', expires: 1});
    $('#save').val(1);
    $('#manageForm').submit();
  }
  function remove(id) {
    $('#remove').val(1);
    $('#post_id').val(id);
    $('#manageForm').submit();
  }
  function cancel() {
    $('#manage').val(0);
    $('#save').val(0);
    $('#remove').val(0);
    $('#post_id').val(0);
    $('#student_program_id').val(0);
    $('#manageForm').submit();        
  }
  function sendInvite(user_id) {
    $('#send').val(1);
    $('#post_id').val(user_id);
    $('#manageForm').submit(); 
  }
  $( document ).ready(function() {
    $( function() {
      $( "#start_date" ).datepicker();
      $( "#end_date" ).datepicker();
    } );
    $('input').keypress(function() {
      enableSave();
    });
    $('input').change(function() {
      enableSave();
    });
    $('select').change(function() {
      enableSave();
    });
    $('#confirm-remove').on('show.bs.modal', function(e) {
      $(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
    });
    // activate tooltip
    $(function () {
      $('[data-toggle="tooltip"]').tooltip()
    });
    $('input[type="file"]').on('change', function() {
      $('#manageForm').attr("enctype", "multipart/form-data")
        .attr("encoding", "multipart/form-data");
      $('#context').val('roster');
      $('#manageForm').submit(); 
    })
    if ($('#context').val() != 'roster') {
      timerID=setTimeout(function(){$(".success").hide();},2500);
    }
  });
  function enableSave() {
    var enable = false;
    var dateString = new RegExp('^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$');
    switch(context) {
      case 'event':
        if(
            $("#program_id option:selected").val() > 0 && 
            $("#phase option:selected").val() != '' &&
            dateString.test($("#start_date").val()) &&
            dateString.test($("#end_date").val()) &&
            $("#location option:selected").val() != '' 
          ) {
          enable = true;
        }
        break;
      case 'program':
        if(
            $("#language option:selected").val() != '' && 
            $("#timespan option:selected").val() != '' &&
            dateString.test($("#start_date").val()) &&
            dateString.test($("#end_date").val())
          ) {
          enable = true;
        }
        break;
      case 'user':
        if(
            $("#first_name").val() != '' && 
            $("#last_name").val() != '' && 
            isEmail($("#email").val()) &&
            $("#role option:selected").val() != '' 
          ) {
          enable = true;
        }
        break;
      case 'student':
        if(
            $("#first_name").val() != '' && 
            $("#last_name").val() != '' && 
            isEmail($("#email").val()) && 
            $("#institution option:selected").val() != ''
          ) {
          enable = true;
        }
        break;
    }
    if(enable) {
      $('#saveButton').removeClass('disabled');
    }
  }
  // toggle ui view for new/existing locations
  // element starts off hidden, so initial action is to show the new location fields
  var location_toggle = 'show';
  function showAddLocation() {
    if (location_toggle=='show') {
      console.log('Show');
      $('#location_action_icon').removeClass('fa-plus');
      $('#location_action_icon').addClass('fa-times-circle');
      $('#location_action_button').removeClass('btn-primary');
      $('#location_action_button').addClass('btn-danger');
      $('#location_addNew').show();
      $('#location_select').hide();
    }
    else {
      console.log('Hide');
      $('#location_action_icon').removeClass('fa-times-circle');
      $('#location_action_icon').addClass('fa-plus');
      $('#location_action_button').removeClass('btn-danger');
      $('#location_action_button').addClass('btn-primary');
      $('#location_addNew').hide();
      $('#location_select').show();
    }
    // switch toggle
    location_toggle = (location_toggle=='show') ? 'hide' : 'show';
  }
  function addLocation() {
    $('#new_location_wrapper').hide();
    $('#location').append($('<option>', {
        value: $('#city').val() + ', ' + $('#country').val(),
        text: $('#city').val() + ', ' + $('#country').val()
    }));
    $("#location").val($('#city').val() + ', ' + $('#country').val());
    $('#location_select').show();        
  }