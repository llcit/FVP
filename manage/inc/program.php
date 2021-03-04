<?php
	function getExisting() {
	    global $pdo;
	    $sql = "
	        SELECT p.*,COUNT(a.`id`) AS `studentCount`
	        FROM `programs` p 
	        LEFT JOIN `affiliations` a on (a.`program_id`=p.`id` and a.`role`='student')
	        WHERE 1 
	        GROUP BY p.`id` 
	        ORDER BY `start` DESC
	        ";
	    $stmt = $pdo->prepare($sql);
	    $stmt->execute();
	    return $stmt->fetchAll();
	}

	function getSavedProgram($program_id) {
    global $pdo;
    $sql = "
        SELECT *
        FROM `programs` p 
        WHERE p.`id` = '$program_id'
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchObject();    
	}
	function save($vals) {
    global $pdo;
    try {
     if ($vals['post_id'] == '') $vals['post_id'] = null;
      $programName =  $vals['language'] . " Overseas Flaghip";
      // gross hack -- see note below on TODO for this
      if ($vals['timespan'] == 'AY') {
      	$progYrs = $vals['timespan'] . " " . date('Y',strtotime($vals['start_date'])) . "-" . date('Y',strtotime($vals['end_date']));
      }
      else {
      	$progYrs = $vals['timespan'] . " " . date('Y',strtotime($vals['start_date']));
      }  
      $start = date ('Y-m-d', strtotime($vals['start_date']));
      $end = date ('Y-m-d', strtotime($vals['end_date']));
      $sql = "
          REPLACE INTO `programs`(`id`,`name`,`start`,`end`,`language`,`progYrs`)
          VALUES(?,?,?,?,?,?);
          ";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$vals['post_id'],$programName,$start,$end,
                      $vals['language'],$progYrs]);
      return 'success';
    } catch(PDOException $e) {
        return $e->getMessage();
    }
	}
	function remove($program_id) {
	    global $pdo;
	    try {
	        $sql = "
	          DELETE FROM `programs` WHERE `id` = '$program_id';
	        ";
	        $stmt = $pdo->prepare($sql);
	        $stmt->execute();
	        return 'success';
	    } catch(PDOException $e) {
	        return $e->getMessage();
	    }
	}
	function formatList($existingPrograms) {
    $programList = "<div class='fv_table_wrapper'>
                    <table class='fv_table table-striped'>
                      <tr>
                        <th>
                          Program Name
                        </th>
                        <th>
                          Start Date
                        </th> 
                        <th>
                          End Date
                        </th>
                        <th>
                        	Years
                        </th>
                        <th colspan=2>
                          Students
                        </th>
                      <tr>                                                   
                      ";
    foreach($existingPrograms as $program) {
      if ($program['studentCount'] == 0) {
        $deleteButton = "
                          <a href='#' data-href='javascript:remove(".$program['id'].")' data-toggle='modal' data-target='#confirm-remove' class='btn btn-sm btn-danger' class='deleteButton'>
                            <i class='far fa-times-circle' aria-hidden='true' data-toggle='tooltip' data-placement='top' title='Delete Program'></i>
                          </a>
        ";
      }
      else {
        $deleteButton = "";
      }
      $programList .= "<tr>
                        <td>".$program['name']."</td>
                        <td>".$program['start']."</td>
                        <td>".$program['end']."</td>
                        <td>".$program['progYrs']."</td>
                        <td style='text-align:center;min-width:100px;;'>
                          <a class='mngStudents' href='javascript:manageStudents(".$program['id'].")' data-toggle='tooltip' data-placement='top' title='Manage Students'>".$program['studentCount']."</a>
                        </td>
                        <td align=right>
                          <a href='javascript:manage(".$program['id'].")' class='btn btn-sm btn-primary' data-toggle='tooltip' data-placement='top' title='Edit Program'>
                            <i class='fa fa-cog' aria-hidden='true'></i>
                          </a>
                          $deleteButton
                        </td>
                      </tr>
                      ";
    }
    $programList .= " </table>
                  </div>
    ";
    return $programList;
  }
  function buildManager($program_id) {
  	global $SETTINGS;
  	$savedProgram = getSavedProgram($program_id);
    $programManager = "
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
  	// ---------- language ---------- 
  	$languages = $SETTINGS['languages'];
  	$languageSelect = "
	  		<div class='fv_inputWrapper'>
	        <label for='language' style='width:110px;'>Language:</label>
	        <select class='form-control fv_inline_select' id='language' name='language'>
	          <option value=''>Select Language</option>
    ";
    foreach($languages as $language) {
      $selected_lang = ($language == $savedProgram->language) ? 'SELECTED' : '';
      $languageSelect .= "
          	<option value='".$language."' $selected_lang>".$language."</option>
      ";
    }
    $languageSelect .= "
	        </select>
	      </div>
    ";
    $programManager .= $languageSelect;
    // ---------- /language ---------- 
	  // ----------  timespan ---------- 
		$checked=[];
		if ($savedProgram->progYrs) {
			// gross hack -- need to fix the db structure to store timespan in place of progYrs
			// progYrs should only be assembled for display from timespan and start/end
			// this will have a big impact on the progYrs filter in the archive, so 
			// relegating to TODO
			$parseProgYrs = preg_match("/(.*)\ ((\d\-)*)/", $savedProgram->progYrs,$matches);
			$selectedTimespan = $matches[1];
		}
		$timespans = [
										[
											'label' => 'Academic Year',
											'value' => 'AY'
										],
										[
											'label' => 'Calendar Year',
											'value' => 'CY'
										],
										[
											'label' => 'Summer',
											'value' => 'Summer'
										]
									];
		$timespanSelect = "
				<div class='fv_inputWrapper'>
	        <label for='timespan' style='width:110px;'>Timespan:</label>
	        <select class='form-control fv_inline_select' id='timespan' name='timespan'>
	          <option value=''>Select Timespan</option>
	  ";
	  foreach($timespans as $timespan) {
	    $selected_timespan = ($timespan['value'] == $selectedTimespan) ? 'SELECTED' : '';
	    $timespanSelect .= "
	          <option value='".$timespan['value']."' $selected_timespan>".$timespan['label']."</option>
	    ";
	  }
	  $timespanSelect .= "
	      	</select>
	      </div>
	  ";
		$programManager .= $timespanSelect;
		// ---------- /timespan ---------- 
     // ---------- start ----------
    if($savedProgram->start){
			$startDate = new DateTime($savedProgram->start);
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
    $programManager .= $startDateSelect;  
    // ---------- /start ----------
    // ---------- end ---------- 
    if($savedProgram->end){
			$endDate = new DateTime($savedProgram->end);
			$displayEndDate = $endDate->format('m/d/Y');
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
    $programManager .= $endDateSelect;    
    // ---------- /end ----------
    
    $programManager .= "
      </div>
    ";
    return $programManager;
  }
?>