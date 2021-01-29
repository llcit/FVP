<?php
    function getUser($pdo,$username) {
        $sql ="SELECT u.`id`,u.`first_name`,u.`last_name`, a.`role` 
               FROM `users` u
               JOIN `affiliations` a ON a.`user_id` = u.`id` 
               WHERE (u.`username`=:username)
               ";
        $query= $pdo->prepare($sql);
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->execute();
        if($query->rowCount() > 0) {
            $result = $query->fetch(PDO::FETCH_OBJ);
            return($result); 
        }  
    }
   function getVideos($id=null,$id_type=null,$filters=null) {
    global $pdo;
    $matchVals = [
        'programs' => ['table_handle'=>'prog','field'=>'name'],
        'years' => ['table_handle'=>'prog','field'=>'progYrs'],
        'locations' => ['table_handle'=>'e','field'=>'city'],
        'institutions' => ['table_handle'=>'i','field'=>'name'],
        'types' => ['table_handle'=>'pres','field'=>'type'],
        'periods' => ['table_handle'=>'e','field'=>'phase'],
        'is_showcase'=> ['table_handle'=>'pres','field'=>'is_showcase']
    ];
    $where = '';
    $and = '';
    if ($id) {
        $where = "pres.`$id_type`='$id'";
    }
    else {
        foreach($filters as $key=>$value) {
            $filterList = '';
            if ($filters[$key]) {
                $comma ="";
                foreach($filters[$key] as $selectedVal) {
                    $filterList .= $comma . "'$selectedVal'";
                    $comma =",";
                }
                $where .= " $and ".$matchVals[$key]['table_handle'].".`".$matchVals[$key]['field']."` in($filterList)";
                $and = 'and';
            }
        }
    }
    if ($where == '') $where = '1';
    $sql = "
        SELECT pres.`id`,pres.`extension`,pres.`description`,pres.`duration`,pres.`transcript_raw`,pres.`transcript_final`,pres.`grant_internal`,pres.`grant_public`,
               pres.`translation_raw`,pres.`translation_final`,pres.`annotations`,u.`first_name`,u.`last_name`,
               pres.`user_id`,i.`name` as `institution`,prog.`name` as `program`,prog.`progYrs`,prog.`language`,
               DATE_FORMAT(e.`start_date`,'%M %Y') as `date`, pres.`type`,e.`phase`,e.`city`,e.`country`,pres.`is_showcase`
        FROM `presentations` pres 
        LEFT JOIN `users` u on u.`id` = pres.`user_id` 
        LEFT JOIN `events` e on e.`id`= pres.`event_id` 
        LEFT JOIN `programs` prog on prog.`id` = e.`program_id`
        LEFT JOIN `affiliations` a on (a.`user_id` = u.`id` and a.`program_id`=prog.`id`) 
        LEFT JOIN `institutions` i on i.`id`=a.`domestic_institution_id` 
        WHERE $where 
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}
function getUniqueVals($table,$field) {
    global $pdo;
    $sql = "
        SELECT DISTINCT(`$field`)
        FROM `$table` 
        WHERE 1
        ORDER BY `$field`
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}
function getEvents() {
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
function getPrograms() {
    global $pdo;
    $sql = "
        SELECT *
        FROM `programs` p 
        WHERE 1
        ORDER BY `name`,`progYrs` ASC
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();    
}
function getLocations() {
    global $pdo;
    $sql = "
        SELECT `city`,`country`
        FROM `events` e 
        WHERE 1
        GROUP BY `city`,`country`
        ORDER BY `country`,`city` ASC
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();    
}
function saveEvent($vals) {
    global $pdo;
    try {
        if ($vals['event_id'] == '') $vals['event_id'] = null;
        $startDate = date ('Y-m-d H:i:s', strtotime($vals['start_date']));
        $endDate = date ('Y-m-d H:i:s', strtotime($vals['start_date']));
        $sql = "
            REPLACE INTO `events`(`id`,`program_id`,`start_date`,`end_date`,`phase`,`city`,`country`)
            VALUES(?,?,?,?,?,?,?);
            ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$vals['event_id'],$vals['program_id'],$startDate,$endDate
                        ,$vals['phase'],$vals['city'],$vals['country']]);
        return 'success';
    } catch(PDOException $e) {
        return $e->getMessage();
    }
}
function deleteEvent($event_id) {
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
function getUserEvents($user_id) {
    global $pdo;
    $sql = "
        SELECT e.`id` AS `event_id`,prog.`id` AS `progId`, prog.`name` AS `progName`,prog.`progYrs`, 
        e.`start_date`,e.`end_date`,e.`phase`,e.`city`,e.`country`
        FROM `affiliations` a 
        JOIN `programs` prog ON prog.`id`=a.`program_id`
        JOIN `events` e ON e.`program_id` = prog.`id` 
        WHERE a.`user_id` = '$user_id'
        GROUP BY e.`id`;
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();      
}
function getPresentationId($user_id,$event_id,$presentation_type) {
    global $pdo;
    $data = [];
    $sql ="
        SELECT `id`,`grant_internal`,`grant_public` 
        FROM presentations 
        WHERE (user_id=? AND event_id=? AND type=?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id,$event_id,$presentation_type]); 
    if($stmt->rowCount() > 0) {
        // presentation exists-- overwrite
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        $data['pid'] = $result->id;
        $data['grant_internal'] = $result->grant_internal;
        $data['grant_public'] = $result->grant_public;
    } 
    return $data;
}
function getLanguage($event_id) {
    global $pdo;
    $sql = "
        SELECT p.`language`
        FROM `events` e 
        JOIN `programs` p on p.`id`=e.`program_id`
        WHERE e.`id` = '$event_id'
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetchObject();
    return $row->language;  
}
function getPid($access_code) {
    global $pdo;
    $sql = "
        SELECT p.`id`
        FROM `presentations` p 
        WHERE p.`access_code`='$access_code'
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchObject();
}
function registerVideo($request,$extension) {
    global $pdo;
    // new presentation
    $sql = "INSERT INTO presentations (user_id,event_id,type,extension,access_code,grant_internal,grant_public,date_added) 
            VALUES (:user_id,:event_id,:presentation_type,:extension,:access_code,:grant_internal,:grant_public,NOW())";
    $stmt= $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $request['user_id']);
    $stmt->bindValue(':event_id', $request['event_id']);
    $stmt->bindValue(':presentation_type', $request['presentation_type']);
    $stmt->bindValue(':extension', $extension);
    $stmt->bindValue(':access_code', $request['access_code']);
    $stmt->bindValue(':grant_internal', $request['grant_internal']);
    $stmt->bindValue(':grant_public', $request['grant_public']);
    $stmt->execute();
    $pid = $pdo->lastInsertId();
    return $pid;
}
function finalizePresentation($data) {
    global $pdo; 
    try { 
        $setString = '';
        $whereString = '';
        $comma = '';
        foreach($data as $key=>$value) {
            if($key == 'id') {
                $whereString = "`$key`='$value'";
            }
            else {
                $setString .= $comma . "`$key`='$value'";
                $comma = ',';
            }
        }
        $sql = "UPDATE presentations SET $setString WHERE $whereString";
        $stmt= $pdo->prepare($sql)->execute();  
    }catch (Exception $e) {
      echo json_encode(array("error" => "$e"));
    }
}
function updatePresentationStatus($videoId,$status) {
    global $pdo; 
    try { 
        $sql = "UPDATE presentations SET `$status` = 1 WHERE `id`=$videoId";
        $stmt= $pdo->prepare($sql)->execute();  
    }catch (Exception $e) {
      echo json_encode(array("error" => "$e"));
    }
}
function getExistingUser($emailId,$token=null) {
    global $pdo;
    $tokenString = '';
    try { 
        if ($token) {
            $tokenString = " and `reset_link_token`='$token'";
        }
        $sql = "
           SELECT * FROM `users`
           WHERE `email`='$emailId' $tokenString
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchObject();
    }catch (Exception $e) {
      echo json_encode(array("error" => "$e"));
    }
}
function updatePassword($password,$emailId,$token=null,$expDate=null) {
    global $pdo; 
    try { 
    $sql = "UPDATE users set  password='" . $password . "', reset_link_token='" . $token . "' ,exp_date='" . $expDate . "' WHERE email='" . $emailId . "'";
    $stmt= $pdo->prepare($sql)->execute(); 
    return true; 
    }catch (Exception $e) {
      echo json_encode(array("error" => "$e"));
    }
}
function deleteObjectFromDB($id) {
    global $pdo;
    try {
        $sql = "
            DELETE FROM `presentations` WHERE `id` = '$id';
            ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return 'success';
    } catch(PDOException $e) {
        return $e->getMessage();
    }  
}
function initLogging($pid) {
    global $pdo;
    // new presentation
    $sql = "INSERT INTO `upload_logs` (id,presentation_id,video_uploaded,log_time) 
            VALUES (:id,:presentation_id,:video_uploaded,NOW())";
    $stmt= $pdo->prepare($sql);
    $stmt->bindValue(':id', null);
    $stmt->bindValue(':presentation_id', $pid);
    $stmt->bindValue(':video_uploaded', 1);
    $stmt->execute();
    $lid = $pdo->lastInsertId();
    return $lid;
}
function updateLog($log_id,$logData) {
    global $pdo; 
    try { 
        $updateString = '';
        $comma = '';
        foreach($logData as $key=>$value) {
            $updateString .= $comma . "`$key` = '$value'";
            $comma = ',';
        }
        $updateString .= "$comma `log_time`=NOW()";
        $sql = "UPDATE `upload_logs` SET " . $updateString . " WHERE `id`='" . $log_id ."'";
        $stmt= $pdo->prepare($sql)->execute();  
    }catch (Exception $e) {
      echo json_encode(array("error" => "$e"));
    }
}
function getExecOffset($language) {
    global $pdo;
    $transcriptField = ($language == 'Russian') ? 'google_exec_time' : 'watson_exec_time';
    try { 
        $sql = "
           SELECT AVG(`$transcriptField`/`ffmpeg_exec_time`) AS `offset` 
           FROM `upload_logs`
           WHERE `$transcriptField`>0
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchObject()->offset;
    }catch (Exception $e) {
      echo json_encode(array("error" => "$e"));
    }
}