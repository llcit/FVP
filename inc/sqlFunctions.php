<?php

    function getUser($pdo,$username) {
        $sql ="SELECT u.`first_name`,u.`last_name`, a.`roles` 
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
    function getShowcaseVideos(){
        $queryStatement = "
            SELECT pres.`id`,pres.`extension`,pres.`description`,u.`first_name`,u.`last_name`,i.`name` as `institution`,
                   prog.`name` as `program`,prog.`language`,prog.`progYrs`,e.`date`, pres.`phase`, pres.`type`,e.`city`,e.`country`
            FROM `presentations` pres 
            JOIN `users` u on u.`id` = pres.`user_id` 
            JOIN `events` e on e.`id`= pres.`event_id` 
            JOIN `programs` prog on prog.`id` = e.`program_id`
            JOIN `affiliations` a on (a.`user_id` = u.`id` and a.`program_id`=prog.`id`) 
            JOIN `institutions` i on i.`id`=a.`domestic_institution_id` 
            WHERE `is_showcase` = 1  
            order by prog.`language`
            ";
        $sqlData = doSQLQuery($queryStatement,3); 
        return $sqlData;
    }

   function getVideos($pid=null,$filters=null) {
    global $pdo;
    $matchVals = [
        'programs' => ['table_handle'=>'prog','field'=>'name'],
        'years' => ['table_handle'=>'prog','field'=>'progYrs'],
        'locations' => ['table_handle'=>'e','field'=>'city'],
        'institutions' => ['table_handle'=>'i','field'=>'name'],
        'types' => ['table_handle'=>'pres','field'=>'type'],
        'periods' => ['table_handle'=>'pres','field'=>'phase']
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
               i.`name` as `institution`,prog.`name` as `program`,prog.`progYrs`,e.`date`, 
               pres.`phase`, pres.`type`,e.`city`,e.`country`
        FROM `presentations` pres 
        JOIN `users` u on u.`id` = pres.`user_id` 
        JOIN `events` e on e.`id`= pres.`event_id` 
        JOIN `programs` prog on prog.`id` = e.`program_id`
        JOIN `affiliations` a on (a.`user_id` = u.`id` and a.`program_id`=prog.`id`) 
        JOIN `institutions` i on i.`id`=a.`domestic_institution_id` 
        WHERE $where 
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchObject();
}
function getUniqueVals($table,$field) {
        $queryStatement = "
        SELECT DISTINCT(`$field`)
        FROM `$table` 
        WHERE 1
        ORDER BY `$field`
        ";
    $unique = doSQLQuery($queryStatement,1); 
    return $unique;
}

/* ********************************** /SPECIFIC SQL QUERIES ********************************** */
