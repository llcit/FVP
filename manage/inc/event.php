<?php
	function getExisting() {
	    global $pdo;
	    $sql = "
	        SELECT e.`id`,prog.`id` AS `progId`,prog.`name` AS `progName`,prog.`progYrs`,
	        DATE_FORMAT(e.`start_date`,'%M %Y') as `date`, 
	        e.`start_date`,e.`end_date`,e.`phase`,e.`city`,e.`country`,COUNT(pres.`id`) AS `numVideos`
	        FROM `events` e 
	        JOIN `programs` prog on prog.`id`=e.`program_id`
	        LEFT JOIN `presentations` pres on pres.`event_id`=e.`id`
	        WHERE 1 
	        GROUP BY e.`id` 
	        ORDER BY `start_date` DESC
	        ";
	    $stmt = $pdo->prepare($sql);
	    $stmt->execute();
	    return $stmt->fetchAll();
	}
	function getSavedEvent($event_id) {
    global $pdo;
    $sql = "
        SELECT e.`id`,p.`id` AS `progId`, p.`name` AS `progName`,p.`progYrs`, 
        e.`start_date`,e.`end_date`,e.`phase`,e.`city`,e.`country`
        FROM `events` e 
        JOIN `programs` p on p.`id`=e.`program_id`
        WHERE e.`id` = '$event_id'
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchObject();    
	}
	function save($vals) {
    global $pdo;
    try {
      if ($vals['post_id'] == '') $vals['post_id'] = null;
      $startDate = date ('Y-m-d H:i:s', strtotime($vals['start_date']));
      $endDate = date ('Y-m-d H:i:s', strtotime($vals['end_date']));
      $sql = "
          REPLACE INTO `events`(`id`,`program_id`,`start_date`,`end_date`,`phase`,`city`,`country`)
          VALUES(?,?,?,?,?,?,?);
          ";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$vals['post_id'],$vals['program_id'],$startDate,$endDate
                      ,$vals['phase'],$vals['city'],$vals['country']]);
      return 'success';
    } catch(PDOException $e) {
        return $e->getMessage();
    }
	}
	function remove($event_id) {
	    global $pdo;
	    try {
	        $sql = "
	            DELETE FROM `events` WHERE `id` = '$event_id';
	            ";
	        $stmt = $pdo->prepare($sql);
	        $stmt->execute();
	        return 'success';
	    } catch(PDOException $e) {
	        return $e->getMessage();
	    }
	}
  function formatList($existingEvents) {
    $eventList = "<div class='fv_table_wrapper'>
                    <table class='fv_table table-striped'>
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

                          <a href='#' data-href='javascript:remove(".$event['id'].")' data-toggle='modal' data-target='#confirm-remove' class='btn btn-sm btn-danger' class='deleteButton'>
                            <i class='far fa-times-circle' aria-hidden='true' data-toggle='tooltip' data-placement='top' title='Delete User'></i>
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
                          <a href='javascript:manage(".$event['id'].")' class='btn btn-sm btn-primary'data-toggle='tooltip' data-placement='top' title='Edit User'>
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
  function buildManager($event_id) {
    if ($event_id) {
      $savedEvent = getSavedEvent($event_id);
    }

    $eventManager = "
     	<div class='float-right fv_buttonWrapper'>
	      <span>
	        <a href='javascript:cancel();' class='btn btn-danger' id='cancelButton'>
	          <i class='fa fa-window-close' aria-hidden='true'></i>
	          Cancel
	        </a>
	      </span>
	      <span>
	        <a href='javascript:save();' class='btn btn-primary disabled' id='saveButton'>
	        <i class='fa fa-save' aria-hidden='true'></i>
	          Save Program
	        </a>
	      </span>
	    </div>
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
    $phases = ['End of Program','Mid-Program'];
    $phaseSelect = "
      <div class='fv_inputWrapper'>
        <label for='phases' style='width:110px;'>Phase:</label>
        <select class='form-control fv_inline_select' id='phase' name='phase'>
          <option value=''>Select Phase</option>
    ";
    foreach($phases as $phase) {
      $selected_phase = ($phase == $savedEvent->phase) ? 'SELECTED' : '';
      $phaseSelect .= "
          <option value='".$phase."' $selected_phase>".$phase."</option>
      ";
    }
    $phaseSelect .= "
        </select>
      </div>
    ";
    $eventManager .= $phaseSelect;
    if($savedEvent->start_date){
			$startDate = new DateTime($savedProgram->start_date);
			$displayStartDate = $startDate->format('m/d/Y');
		}
		else {
			$displayStartDate = null;
		}
    $startDateSelect = "
        <div class='fv_inputWrapper'>
          <label for='start_date' style='width:110px;'>Start Date:</label>
          <input style=\"font-family:'Font Awesome 5 Free' !important;\" placeholder='&#xf271; Click to add date' class='form-control fv_inline_select date' 
            type='text' id='start_date' name='start_date' value='".$displayStartDate."'>
        </div>
    ";
    $eventManager .= $startDateSelect; 
     if($savedEvent->end_date){
			$endDate = new DateTime($savedProgram->end_date);
			$displayEndDate = $startDate->format('m/d/Y');
		}
		else {
			$displayEndDate = null;
		}  
    $endDateSelect = "
        <div class='fv_inputWrapper'>
          <label for='end_date' style='width:110px;'>End Date:</label>
          <input style=\"font-family:'Font Awesome 5 Free' !important;\" placeholder='&#xf271; Click to add date' class='form-control fv_inline_select date' 
            type='text' id='end_date' name='end_date' value='".$displayEndDate."'>
        </div>
    ";
    $eventManager .= $endDateSelect;    
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