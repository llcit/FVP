<?php
/*START HERE:
1. Reconcile locations -- add new location?

*/
	function getExisting() {
	    global $pdo;
	    $sql = "
	        SELECT u.*,a.`role`,COUNT(pres.`id`) AS `numVideos`
	        FROM `users` u 
	        JOIN `affiliations` a on a.`user_id`=u.`id`
	        LEFT JOIN `presentations` pres on pres.`user_id`=u.`id`
	        WHERE a.`role` = 'admin' OR a.`role` = 'staff'  
	        GROUP BY u.`id`,a.`role` 
	        ORDER BY u.`last_name`,u.`first_name` DESC
	        ";
	    $stmt = $pdo->prepare($sql);
	    $stmt->execute();
	    return $stmt->fetchAll();
	}

	function getSavedUser($user_id) {
    global $pdo;
    $sql = "
        SELECT u.*,a.`role` 
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
     	if (!$vals['post_id']) {
	      $sql_u = "
	          INSERT INTO `users`(`first_name`,`last_name`,`username`,`email`)
	          VALUES(?,?,?,?);
	          ";
	      $stmt_u = $pdo->prepare($sql_u);
	      $stmt_u->execute([$vals['first_name'],$vals['last_name'],$vals['email'],$vals['email']]);
	      $user_id = $pdo->lastInsertId();
	      $sql_a = "
          INSERT INTO `affiliations`(`user_id`,`role`)
          VALUES(?,?);
          ";
	      $stmt_a = $pdo->prepare($sql_a);
	      $stmt_a->execute([$user_id,$vals['role']]);
	    }
	    else {
				$sql_u = "UPDATE `users` SET `first_name`=?,`last_name`=?,`username`=?,`email`=? WHERE id=?";
				$stmt_u= $pdo->prepare($sql_u);
				$stmt_u->execute([$vals['first_name'],$vals['last_name'],$vals['email'],$vals['email'],$vals['post_id']]);
				$sql_a = "UPDATE `affiliations` SET `role`=? WHERE `user_id`=?";
				$stmt_a= $pdo->prepare($sql_a);
				$stmt_a->execute([$vals['role'],$vals['post_id']]);
	    }
      return 'success';
    } catch(PDOException $e) {
        return $e->getMessage();
    }
	}
	function remove($user_id) {
	    global $pdo;
	    try {
	        $sql = "
	          DELETE FROM `users` WHERE `id` = '$user_id';
	        ";
	        $stmt = $pdo->prepare($sql);
	        $stmt->execute();
	        return 'success';
	    } catch(PDOException $e) {
	        return $e->getMessage();
	    }
	}
	function formatList($existingUsers) {
    $userList = "<div class='fv_table_wrapper'>
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
                        <th>
                        	Role
                        </th>
                        <th>
                        </th>
                      <tr>                                                   
                      ";
    foreach($existingUsers as $thisUser) {
      if ($user['numVideos'] == 0) {
        $deleteButton = "
                          <a href='#' data-href='javascript:remove(".$thisUser['id'].")' data-toggle='modal' data-target='#confirm-remove' class='btn btn-sm btn-danger fv_mng_btn'>
                            <i class='far fa-times-circle' aria-hidden='true' data-toggle='tooltip' data-placement='top' title='Delete User'></i>
                          </a>
        ";
      }
      else {
        $deleteButton = "";
      }
    	$inviteButton = "
                        <a href='javascript:sendInvite(".$thisUser['id'].")' data-href='javascript:remove(".$thisUser['id'].")' class='btn btn-sm btn-success fv_mng_btn' data-toggle='tooltip' data-placement='top' title='Send Invite'>
                          <i class='fa fa-paper-plane' aria-hidden='true'></i>
                        </a>
      	";
      $userList .= "<tr>
                        <td>".$thisUser['first_name']."</td>
                        <td>".$thisUser['last_name']."</td>
                        <td>".$thisUser['email']."</td>
                        <td>".ucfirst($thisUser['role'])."</td>
                        <td>
                          <a href='javascript:manage(".$thisUser['id'].")' class='btn btn-sm btn-primary fv_mng_btn' data-toggle='tooltip' data-placement='top' title='Edit User'>
                            <i class='fa fa-cog' aria-hidden='true'></i>
                          </a>
                          $deleteButton
                          $inviteButton
                        </td>
                      </tr>
                      ";
    }
    $userList .= " </table>
                  </div>
    ";
    return $userList;
  }
  function buildManager($user_id) {
  	global $SETTINGS;
  	$savedUser = getSavedUser($user_id);
    $userManager = "
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
	          Save User
	        </a>
	      </span>
	    </div>
    	<div class='form-group'>
    ";
    $userManager .= "
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
    $roles = ['admin','staff'];
    foreach($roles as $role) {
      $selected_role = ($role == $savedUser->role) ? 'SELECTED' : '';
      $roleSelect .= "
          	<option value='".$role."' $selected_role>".ucfirst($role)."</option>
      ";
    }
    $roleSelect .= "
           </select>
			</div>
		";
    $userManager .= $roleSelect;
    $userManager .= "
      </div>
    ";
    return $userManager;
  }
?>