<?php
	function getExisting($student_program_id) {
	    global $pdo;
	    $sql = "
	        SELECT u.*,i.`name` AS `institution`, COUNT(p.`id`) AS `numVideos`
	        FROM `users` u 
	        JOIN `affiliations` a ON a.`user_id`=u.`id` 
          JOIN `institutions` i ON i.`id`=a.`domestic_institution_id` 
          LEFT JOIN `presentations` p ON p.`user_id` = u.`id`
	        WHERE a.`program_id` = '$student_program_id' AND a.`role`='student' 
	        GROUP BY u.`id` 
	        ORDER BY u.`last_name`,u.`first_name` 
	        ";
	    $stmt = $pdo->prepare($sql);
	    $stmt->execute();
	    return $stmt->fetchAll();
	}

function getSavedUser($user_id) {
    global $pdo;
    $sql = "
        SELECT u.*,a.`domestic_institution_id` 
        FROM `users` u 
        JOIN `affiliations` a on a.`user_id`=u.`id`
        WHERE u.`id` = '$user_id'
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchObject();    
  }
	function save($vals) {
    global $pdo;
    try {
     if ($vals['post_id'] == '') $vals['post_id'] = null;
      if ($vals['post_id']) {
        $user_id = $vals['post_id'];
      }
      else {
        $user_id = null;
      }
      $sql = "
          REPLACE INTO `users`(`id`,`first_name`,`last_name`,`email`,`username`)
          VALUES(?,?,?,?,?);
          ";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$user_id,$vals['first_name'],$vals['last_name'],$vals['email'],$vals['email']]);
      if (!$user_id) {
        $user_id = $pdo->lastInsertId();
      }
      $affiliation = getAffiliation($user_id, $vals['student_program_id']);
      if ($affiliation) {
        $a_id = $affiliation->id;
      }
      else {
        $a_id = null;
      }
      $sql = "
          REPLACE INTO `affiliations`(`id`,`user_id`,`program_id`,`domestic_institution_id`,`role`)
          VALUES(?,?,?,?,?);
          ";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$a_id,$user_id,$vals['student_program_id'],$vals['institution'],'student']);
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
                          First
                        </th>
                        <th>
                          Last
                        </th> 
                        <th>
                          Email
                        </th>
                        <th>
                          Institution
                        </th>
                        <th colspan=3>
                          Videos
                        </th>
                      <tr>                                                   
                      ";
    if ($existingStudents) {
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
                          <td>".$student['institution']."</td>
                          <td>
                            <a class='mngStudents' href='javascript:viewVideos(\"".$student['username']."\")' data-toggle='tooltip' data-placement='top' title='View Videos'>".$student['numVideos']."
                            </a>
                          </td>
                          <td style='min-width:135px;'>
                            <a href='javascript:manage(".$student['id'].")' class='btn btn-sm btn-primary'data-toggle='tooltip' data-placement='top' title='Edit Student'>
                              <i class='fa fa-cog' aria-hidden='true'></i>
                            </a>
                            <a href='javascript:sendInvite(".$thisUser['id'].")' data-href='javascript:remove(".$thisUser['id'].")' class='btn btn-sm btn-success fv_mng_btn' data-toggle='tooltip' data-placement='top' title='Send Invite'>
                              <i class='fa fa-paper-plane' aria-hidden='true'></i>
                            </a>
                            $deleteButton
                          </td>
                        </tr>
                        ";
        }
      $studentList .= " </table>
                    </div>
      ";
    }
    else {
      $studentList = "
        <div class='msg' style='margin: 50px auto 20px auto;text-align:center;'>
          There are no students affiliated with this program.
        </div>
      ";
    }
    return $studentList;
  }
  function buildManager($user_id,$student_program_id) {
    global $SETTINGS;
    $savedStudent = getSavedUser($user_id);
    if (!$savedStudent) {
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
         <input type='text' class='fv_text_box fv_inline_select' id='first_name' name='first_name' placeholder='First Name' value='".$savedStudent->first_name."' />
      </div>
      <div class='fv_inputWrapper'>
         <label for='last_name' style='min-width:80px;'>Last Name:</label>
         <input type='text' class='fv_text_box fv_inline_select' id='last_name' name='last_name' placeholder='Last Name' value='".$savedStudent->last_name."'/>
      </div>
      <div class='fv_inputWrapper'>
         <label for='email' style='min-width:80px;'>Email:</label>
         <input type='text' class='fv_text_box fv_inline_select' id='email' name='email' placeholder='Email' value='".$savedStudent->email."'/>
      </div>
    ";
    $institutions = getInstitutions();
    $institutionSelect = "
        <div id='institution_select' class='fv_inputWrapper'>
          <label for='institution' style='min-width:80px;'>Institution:</label>
          <select class='form-control fv_inline_select' id='institution' name='institution'>
            <option value=''>Select Institution</option>
    ";
    foreach($institutions as $institution) {
      $selected_institution = ($institution['id'] == $savedStudent->domestic_institution_id) ? 'SELECTED' : '';
      $institutionSelect .= "
          <option value='".$institution['id']."' $selected_institution>".$institution['name']."</option>
      ";
    }
    $institutionSelect .= "
          </select>
    ";
    $studentManager .= $institutionSelect;
    $studentManager .= "
      </div>
      <input type=hidden name='student_program_id' id='student_program_id' value='$student_program_id'>
    ";
    return $studentManager;
  }
?>