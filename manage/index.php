<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <?php
      include "../inc/db_pdo.php";
      include "../inc/dump.php";
      include "../inc/sqlFunctions.php";
      include "../inc/navLinks.php";
      $SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
			$pageTitle = "Flagship Video Project";
			$subTitle = "Manage Filming Events";
			$titleText = "You may select an existing event to edit or add a new event.  You may only delete an event if it  does not have any videos uploaded to it.";
			session_start();
			if (!isset($_SESSION['username'])) { 
		    exit(header("location:./login.php"));
		  } 
		  else {
        if ($_POST['manageEvent'] == 1) {
          $pageContent = buildEventManager($_POST['event_id']);
          if ($_POST['event_id']) {
            $subTitle = "Edit Existing Event";
            $titleText = "You may change any of the event details listed below and click the save button to save your changes.";
          }
          else {
            $subTitle = "Add New Event";
            $titleText = "Pleas provide all of the event details listed below and click the save button to save your changes.";
          }
        }
        else {
          if ($_POST['saveEvent'] == 1) {
            $response = saveEvent($_POST);
            if ($response == 'success') {
              $msg = "
                <div class='msg success'>
                  Event successfully saved!
                </div>
              ";
            }
            else {
              $msg = "
                <div class='msg error'>
                  There was a problem saving your event!
                  <p>Error: $response</p>
                </div>
              ";
            }
          }
          if ($_POST['deleteEvent'] == 1) {
            $response = deleteEvent($_POST['event_id']);
            if ($response == 'success') {
              $msg = "
                <div class='msg success'>
                  Event successfully deleted!
                </div>
              ";
            }
            else {
              $msg = "
                <div class='msg error'>
                  There was a problem deleting your event!
                  <p>Error: $response</p>
                </div>
              ";
            }
          }
          $existingEvents = getEvents();
          $eventList = formatEvents($existingEvents);
  		  	$user = getUser($pdo,$_SESSION['username']);
          $navLinks = writeNavLinks($user->role,'header');
          if ($user->role == 'admin' || $user->role == 'staff') {
            $userName = "<h5 style='display:inline'>" . $user->first_name . " " . $user->last_name . "</h5>";
            $welcomeMsg = "
              $userName 
              <a href='".$SETTINGS['base_url']."/logout.php' class='btn btn-xs btn-icon btn-danger'>
                <i class='fa fa-sign-out-alt' aria-hidden='true'></i>
              </a>
            ";
            $pageContent = "
              <div class='panel'>
                <h4 style='display:inline;'>
                  Existing Events
                </h4>
                <a class='btn btn-primary pull-right' href='javascript:manageEvent();'> 
                <i class='fa fa-plus-circle' aria-hidden='true'></i>
                Add Event 
                </a>
                $eventList
              </div>
            ";
          }
          else {
            $pageContent = "
              <div class = 'msg error'>
                Permission denied! You must be staff or admin to access this page.
              </div>
              <p style='width:100%;text-align:center;margin-top:30px;'>
                <a href='../index.php'>Retun to Home</a>
            ";
          }
        }
		  }

    ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.15.1/moment.min.js"></script>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
    <script data-require="bootstrap@*" data-semver="3.1.1" src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js"></script>
    <!-- Able Player CSS -->
    <link rel="stylesheet" href="../css/main.css" type="text/css"/>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
    
    <script>
      function manageEvent(id) {
        $('#manageEvent').val(1);
        $('#event_id').val(id);
        $('#eventForm').submit();
      }
      function saveEvent() {
        $('#saveEvent').val(1);
        $('#eventForm').submit();
      }
      function deleteEvent(id) {
        $('#deleteEvent').val(1);
        $('#event_id').val(id);
        $('#eventForm').submit();        
      }
      $( document ).ready(function() {
        $( function() {
          $( "#start_date" ).datepicker();
          $( "#end_date" ).datepicker();
        } );
        $('input').change(function() {
          $('#saveEventButton').attr('disabled', false);
        });
        $('select').change(function() {
          $('#saveEventButton').attr('disabled', false);
        });
        $('#confirm-delete').on('show.bs.modal', function(e) {
          $(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
        });
      });
    </script>
  </head>
  <body>
    <div class="panel panel-default">
      <div class="panel-heading fv_heading">
        <img src='../img/logo_lf.png'>
        <span class='pageTitle'>
        		<?php echo($pageTitle); ?>
        </span>
        <span class='pull-right'>
          <img src='../img/logo_ac.png'>
        </span>
      </div>
      <div class='fv_subHeader'>
        <?php echo($navLinks); ?>
        <?php echo($welcomeMsg); ?>
      </div>
      <form method="post" id='eventForm' action=''>
        <div class="container">
          <?php if($msg) echo("<div style='width:100%;margin-top:30px;'>$msg</div>"); ?>
           <div class="row fv_main">
              <div class="card fv_card">
                  <div class="card-body fv_card_body" style='border-bottom:solid 1px gray;'>
                     <h2 class="card-title"><?php echo($subTitle); ?></h2>
                     <p class="card-text"><?php echo($titleText); ?></p>
                  </div>
                  <div class='fv_pageContent'>
                    <?php echo($pageContent); ?>
                  </div>
              </div>
            </div>
        </div>
        <input type=hidden name='event_id' id='event_id' value='<?php echo($event_id); ?>'>
        <input type='hidden' id='manageEvent' name='manageEvent' value =0>
        <input type='hidden' id='saveEvent' name='saveEvent' value =0>
        <input type='hidden' id='deleteEvent' name='deleteEvent' value =0>
      </form>
      <div class="footer">
        <p> </p>
      </div>
    </div>

    <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="deleteEvent" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    Delete Event
                </div>
                <div class="modal-body">
                    Are you sure you want to permanently delete this event?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-danger btn-ok">Delete</a>
                </div>
            </div>
        </div>
    </div>

  </body>
  <?php 
    function formatEvents($existingEvents) {
      $eventList = "<div class='fv_table_wrapper'>
                      <table class='fv_table table-responsive table-striped'>
                        <tr>
                          <th>
                            Program
                          </th>
                          <th>
                            Date
                          </th> 
                          <th>
                            Location
                          </th>
                          <th>
                          </th>
                        <tr>                                                   
                        ";
      foreach($existingEvents as $event) {
        if ($event['numVideos'] == 0) {
          $deleteButton = "

                            <a href='#' data-href='javascript:deleteEvent(".$event['id'].")' data-toggle='modal' data-target='#confirm-delete' class='btn btn-sm btn-danger' class='deleteButton'>
                              <i class='far fa-times-circle' aria-hidden='true'></i>
                            </a>
          ";
        }
        else {
          $deleteButton = "";
        }
        $eventList .= "<tr>
                          <td>".$event['progName']." " .$event['progYrs']."</td>
                          <td>".$event['date']."</td>
                          <td>".$event['city'].",".$event['country']."</td>
                          <td>
                            <a href='javascript:manageEvent(".$event['id'].")' class='btn btn-sm btn-primary'>
                              <i class='fa fa-cog' aria-hidden='true'></i>
                            </a>
                            $deleteButton
                          </td>
                        </tr>
                        ";
      }
      $eventList .= " </table>
                    </div>
      ";
      return $eventList;
    }
    function buildEventManager($event_id) {

      if ($event_id) {
        $savedEvent = getSavedEvent($event_id);
      }
      $eventManager = "
        <div class='form-group'>
      ";
      $programs = getPrograms();
      $programSelect = "
          <label for='programs' style='width:110px;'>Program:</label>
          <select class='form-control fv_inline_select' id='program_id' name='program_id'>
            <option value=''>Select Program</option>
      ";
      foreach($programs as $program) {
        $selected_prog = ($program['id'] == $savedEvent->progId) ? 'SELECTED' : '';
        $programSelect .= "
            <option value='".$program['id']."' $selected_prog>".$program['name']." ".$program['progYrs']."</option>
        ";
      }
      $programSelect .= "
          </select>
      ";
      $eventManager .= $programSelect;
      $eventManager .= "
        <div class='pull-right'>
          <a href='javascript:saveEvent();' class='btn btn-primary' id='saveEventButton' disabled>
          <i class='fa fa-save' aria-hidden='true'></i>
            Save Event
          </a>
        </div>
      ";
      $phases = ['End of Program','Mid-Program'];
      $phaseSelect = "
        <div class='fv_inputWrapper'>
          <label for='phases' style='width:110px;'>Phase:</label>
          <select class='form-control fv_inline_select' id='phase' name='phase'>
            <option value=''>Select Phase</option>
      ";
      foreach($phases as $phase) {
        $selected_prog = ($phase == $savedEvent->phase) ? 'SELECTED' : '';
        $phaseSelect .= "
            <option value='".$phase."' $selected_prog>".$phase."</option>
        ";
      }
      $phaseSelect .= "
          </select>
        </div>
      ";
      $eventManager .= $phaseSelect;
      $eventManager .= "
        <div class='pull-right'>
          <a href='./index.php' class='btn btn-danger' id='cancelButton'>
            <i class='fa fa-window-close' aria-hidden='true'></i>
            Cancel
          </a>
        </div>
      ";
      $startDate = "
          <div class='fv_inputWrapper'>
            <label for='start_date' style='width:110px;'>Start Date:</label>
            <input style=\"font-family:'Font Awesome 5 Free' !important;\" placeholder='&#xf271; Click to add date' class='form-control fv_inline_select date' 
              type='text' id='start_date' name='start_date' value='".$savedEvent->start_date."'>
          </div>
      ";
      $eventManager .= $startDate;   
      $endDate = "
          <div class='fv_inputWrapper'>
            <label for='end_date' style='width:110px;'>End Date:</label>
            <input style=\"font-family:'Font Awesome 5 Free' !important;\" placeholder='&#xf271; Click to add date' class='form-control fv_inline_select date' 
              type='text' id='end_date' name='end_date' value='".$savedEvent->end_date."'>
          </div>
      ";
      $eventManager .= $endDate;    
      $locations = getLocations();
      $citySelect = "
          <div class='fv_inputWrapper'>
            <label for='city' style='width:110px;'>City:</label>
            <select class='form-control fv_inline_select' id='city' name='city'>
              <option value=''>Select City</option>
      ";
      $countrySelect = "
          <div class='fv_inputWrapper'>
            <label for='country' style='width:110px;'>Country:</label>
            <select class='form-control fv_inline_select' id='country' name='country'>
              <option value=''>Select Country</option>
      ";
      foreach($locations as $location) {
        $selected_city = ($location['city'] == $savedEvent->city) ? 'SELECTED' : '';
        $selected_country = ($location['country'] == $savedEvent->country) ? 'SELECTED' : '';
        $citySelect .= "
            <option value='".$location['city']."' $selected_city>".$location['city']."</option>
        ";
        $countrySelect .= "
            <option value='".$location['country']."' $selected_country>".$location['country']."</option>
        ";
      }
      $citySelect .= "
            </select>
          </div>
      ";
      $countrySelect .= "
            </select>
          </div>
      ";
      $eventManager .= $citySelect;
      $eventManager .= $countrySelect;
      $eventManager .= "
        </div>
      ";
      return $eventManager;
    }
  ?>
</html>
