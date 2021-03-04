<?php
	function getExisting($student_program_id) {
	    global $pdo;
	    $sql = "
	        SELECT u.*, COUNT(p.`id`) AS `numVideos`
	        FROM `users` u 
	        JOIN `affiliations` a ON a.`user_id`=u.`id` 
          LEFT JOIN `presentations` p ON p.`user_id` = u.`id`
	        WHERE a.`program_id` = '$student_program_id' AND a.`role`='student' 
	        GROUP BY u.`id` 
	        ORDER BY u.`last_name`,u.`first_name` 
	        ";
	    $stmt = $pdo->prepare($sql);
	    $stmt->execute();
	    return $stmt->fetchAll();
	}

	function getSavedStudent($user_id) {
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
	function formatList($existingStudents) {
    $studentList = "<div class='fv_table_wrapper'>
                    <table class='fv_table table-striped'>
                      <tr>
                        <th>
                          First Name
                        </th>
                        <th>
                          Last Name
                        </th> 
                        <th>
                          Email
                        </th>
                        <th colspan=3>
                          Videos
                        </th>
                      <tr>                                                   
                      ";
    foreach($existingStudents as $student) {
      if ($student['numVideos'] == 0) {
        $deleteButton = "
                          <a href='#' data-href='javascript:remove(".$program['id'].")' data-toggle='modal' data-target='#confirm-remove' class='btn btn-sm btn-danger' class='deleteButton'>
                            <i class='far fa-times-circle' aria-hidden='true' data-toggle='tooltip' data-placement='top' title='Delete Student'></i>
                          </a>
        ";
      }
      else {
        $deleteButton = "";
      }
      $studentList .= "<tr>
                        <td>".$student['first_name']."</td>
                        <td>".$student['last_name']."</td>
                        <td>".$student['email']."</td>
                        <td>
                          <a class='mngStudents' href='javascript:viewVideos(\"".$student['username']."\")' data-toggle='tooltip' data-placement='top' title='View Videos'>".$student['numVideos']."
                          </a>
                        </td>
                        <td>
                          <a href='javascript:manage(".$student['id'].")' class='btn btn-sm btn-primary'data-toggle='tooltip' data-placement='top' title='Edit Student'>
                            <i class='fa fa-cog' aria-hidden='true'></i>
                          </a>
                          $deleteButton
                        </td>
                      </tr>
                      ";
    }
    $studentList .= " </table>
                  </div>
    ";
    return $studentList;
  }
  function buildManager($user_id) {
    global $SETTINGS;
    $savedStudent = getSavedStudent($user_id);
    if (!$savedUser) {
      $autoSendCheck = "
      <span>
         <label for='auto_send' style='min-width:80px;'>Auto-send Invite Email:</label>
         <input type='checkbox' class='checkbox' style='margin-right:40px;'id='auto_send' name='auto_send' CHECKED>
      </span>

      ";
    }
    $studentManager = "
      <div class='float-right fv_buttonWrapper'>
        $autoSendCheck
        <span>
          <a href='javascript:cancel();' class='btn btn-danger' id='cancelButton'>
            <i class='fa fa-window-close' aria-hidden='true'></i>
            Cancel
          </a>
        </span>
        <span>
          <a href='javascript:save();' class='btn btn-primary disabled' id='saveButton'>
          <i class='fa fa-save' aria-hidden='true'></i>
            Save User
          </a>
        </span>
      </div>
      <div class='form-group'>
    ";
    $studentManager .= "
      <div class='fv_inputWrapper'>
         <label for='first_name' style='min-width:80px;'>First Name:</label>
         <input type='text' class='fv_text_box fv_inline_select' id='first_name' name='first_name' placeholder='First Name' value='".$savedUser->first_name."' />
      </div>
      <div class='fv_inputWrapper'>
         <label for='last_name' style='min-width:80px;'>Last Name:</label>
         <input type='text' class='fv_text_box fv_inline_select' id='last_name' name='last_name' placeholder='Last Name' value='".$savedUser->last_name."'/>
      </div>
      <div class='fv_inputWrapper'>
         <label for='email' style='min-width:80px;'>Email:</label>
         <input type='text' class='fv_text_box fv_inline_select' id='email' name='email' placeholder='Email' value='".$savedUser->email."'/>
      </div>
    ";
    $roleSelect = "
      <div class='fv_inputWrapper'>
         <label for='role' style='min-width:80px;'>User Role:</label>
          <select class='form-control fv_inline_select' id='role' name='role'>
            <option value=''>Select Role</option>
    ";
    $studentManager .= "
      </div>
    ";
    return $studentManager;
  }
?>