<?php
	$fileName = null;
	function saveTmpRoster() {
		global $fileName;
	 if (isset($_FILES["rosterFile"])) {
	    if ($_FILES["file"]["error"] > 0) {
	      echo "There was an error uploading your file: " . $_FILES["file"]["error"] . "<br />";
	    }
	    else {
	      $fileName = $_FILES["rosterFile"]["name"];
        move_uploaded_file($_FILES["rosterFile"]["tmp_name"], "./tmpRosters/" . $fileName);
	    }
	  } 
	  else {
	    echo "No file selected <br />";
	  }
	}

	function save($vals) {
    global $pdo;
    try {
    	if ($vals['rosterData']) {
    		$program_id = $vals['post_id'];
    		$rosterData = json_decode($vals['rosterData']);
    		foreach ($rosterData as $student) {
    			$user = getUser($student->email);
    			if ($user) {
    				$user_id = $user->id;
    			}
    			else {
    				$user_id = null;
    			}
					$sql = "
					    REPLACE INTO `users`(`id`,`first_name`,`last_name`,`email`,`username`)
					    VALUES(?,?,?,?,?);
					    ";
					$stmt = $pdo->prepare($sql);
					$stmt->execute([$user_id,$student->first_name,$student->last_name,$student->email,$student->email]);
		      $institution = getInstitution($student->institution);
		      if ($institution) {
		      	$institution_id = $institution->id;
		      }
    			else {
    				$institution_id = null;
    			}
		      if (!$institution_id) {
						$sql = "
						    INSERT INTO `institutions`(`id`,`name`)
						    VALUES(?,?);
						    ";
						$stmt = $pdo->prepare($sql);
						$stmt->execute([null,$student->institution]);
						$institution_id = $pdo->lastInsertId();
		      }
		      $affiliation = getAffiliation($user->id, $program_id);
		      if (!$affiliation) {
						$sql = "
						    INSERT INTO `affiliations`(`id`,`user_id`,`program_id`,`domestic_institution_id`,`role`)
						    VALUES(?,?,?,?,?);
						    ";
						$stmt = $pdo->prepare($sql);
						$stmt->execute([null,$user_id,$program_id,$institution_id,'student']);		
		      }
    		}
    	}
    } catch(PDOException $e) {
        return $e->getMessage();
    }
    return 'success';
	}	
	function getExisting() {
		global $fileName;
		if (isset($fileName) && $file = fopen("./tmpRosters/" . $fileName , 'r')) {
			$students = [];
			$line = 0;
			$nameRegEx = "/^[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð .’'-]+$/u";
			while(!feof($file)) {
				$lineText = fgets($file);
				if ($lineText != '') {
					$lineData = explode(',',$lineText);
					if ($line == 0) {
						if (!preg_match("/First\ Name/", $lineData[0])) {
							die('Did not find expected column names');
						}
					}
					else  {
						$validationFails = [];
						$lineData[0] = trim($lineData[0]);
						if (!preg_match($nameRegEx, $lineData[0])) {
							array_push($validationFails,'first_name');
						}
						$lineData[1] = trim($lineData[1]);
						if (!preg_match($nameRegEx, $lineData[1])) {
							array_push($validationFails,'last_name');
						}
						$lineData[2] = trim($lineData[2]);
						if (!filter_var($lineData[2], FILTER_VALIDATE_EMAIL)) {
							array_push($validationFails,'email');
						}
						$lineData[3] = trim($lineData[3]);
						if (!preg_match($nameRegEx, $lineData[3])) {
							array_push($validationFails,'institution');
						}
						$students[$line]['first_name'] = $lineData[0];
						$students[$line]['last_name'] = $lineData[1];
						$students[$line]['email'] = $lineData[2];
						$students[$line]['institution'] = $lineData[3];
						$students[$line]['validationFails'] = $validationFails;
					}
				  $line++;
				}
			}
			fclose($file);
		}
		return $students;
	}
	function formatList($existingStudents) {
		$valid = true;
		$saveData = [];
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
                        <th>
                          Institution
                        </th>
                      <tr>                                                   
                      ";
    foreach($existingStudents as $student) {
    	if ($student['validationFails']) {
    		$valid = false;
    		$errorClass = [];
    		foreach($student['validationFails'] as $fail) {
    			$errorClass[$fail] = "CLASS='error'";
    		}
    	}
    	else {
    		$errorClass = [];
    	}
      $studentList .= "<tr>
                        <td ".$errorClass['first_name'].">".$student['first_name']."</td>
                        <td ".$errorClass['last_name'].">".$student['last_name']."</td>
                        <td ".$errorClass['email'].">".$student['email']."</td>
                        <td ".$errorClass['institution'].">".$student['institution']."</td>
                      </tr>
                      ";
      array_push($saveData, [
      												'first_name'=>$student['first_name'],
      												'last_name'=>$student['last_name'],
      												'email'=>$student['email'],
      												'institution'=>$student['institution']
      											]);
    }
    $studentList .= " </table>
                  </div>
    ";
    if($valid) {
    	$studentList .= "
    		<input type=hidden id='rosterData' name='rosterData' value='".json_encode($saveData, JSON_UNESCAPED_SLASHES)."'>
    		<script>
    			$('#actionButton').removeClass('disabled');
    		</script>
    	";
    }
    else {
    	$errorMsg = "
				<div class = 'msg error' style='margin:20px;'>
          There are errors in your roster.  Please check your CSV file and make sure the highlighted names or emails do not contain invalid characters and reupload.
          <div style='margin-top:25px;margin-right:auto;margin-left:auto;max-width:140px;'>
            <span class='fileName'></span> 
            <input type='file' id='rosterFile' name='rosterFile' class='rosterFile'  accept='.csv'>
             <a class='btn btn-primary' href='javascript:importRoster();' > 
              <i class='fas fa-file-import' aria-hidden='true'></i>
              Reimport Roster 
            </a>
          </div>
        </div>
    	";
    }
    return $errorMsg . $studentList;
  }
?>