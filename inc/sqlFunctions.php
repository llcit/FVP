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
   function getVideos($pid=null,$filters=null) {
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
    if ($pid) {
        $where = "pres.`id`='$pid'";
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
        SELECT pres.`id`,pres.`extension`,pres.`duration`,pres.`transcript_raw`,pres.`transcript_final`,
               pres.`translation_raw`,pres.`translation_final`,pres.`annotations`,u.`first_name`,u.`last_name`,
               i.`name` as `institution`,prog.`name` as `program`,prog.`progYrs`,prog.`language`,
               DATE_FORMAT(e.`start_date`,'%M %Y') as `date`, pres.`type`,e.`phase`,e.`city`,e.`country`
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
function getUserVideos($id) {
    global $pdo;
    $sql = "
        SELECT * 
        FROM `presentations` 
        WHERE `user_id` = '$id'
        ORDER BY `event_id` DESC
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchObject();
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
        e.`start_date`,e.`end_date`,e.`phase`,e.`city`,e.`country`,pres.`id` AS `presId`,pres.`type` AS `presType`
        FROM `affiliations` a 
        JOIN `programs` prog ON prog.`id`=a.`program_id`
        JOIN `events` e ON e.`program_id` = prog.`id` 
        LEFT JOIN `presentations` pres on pres.`user_id`=a.`user_id`
        WHERE a.`user_id` = '$user_id'
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();      
}
function getPresentationId($user_id,$event_id) {
    global $pdo;
    $sql ="SELECT id FROM presentations WHERE (user_id=? AND event_id=?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id,$event_id]); 
    if($stmt->rowCount() > 0) {
        // presentation exists-- overwrite
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        $pid = $result->id;
    } else {  
        $pid = null;
    }
    return $pid;
}
