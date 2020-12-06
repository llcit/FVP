<?php

    function getShowcaseVideos(){
        $queryStatement = "
            SELECT pres.`id`,pres.`description`,u.`first_name`,u.`last_name`,i.`name` as `institution`,
                   prog.`name` as `program`,prog.`language`,prog.`progYrs`,e.`date`, pres.`phase`, pres.`type`,e.`city`,e.`country`
            FROM `presentations` pres 
            JOIN `users` u on u.`id` = pres.`user_id` 
            JOIN `events` e on e.`id`= pres.`event_id` 
            JOIN `programs` prog on prog.`id` = e.`program_id`
            JOIN `affiliations` a on (a.`user_id` = u.`id` and a.`program_id`=prog.`id`) 
            JOIN `institutions` i on i.`id`=a.`domestic_institution_id` 
            WHERE `is_showcase` = 1  
            GROUP BY pres.`id`
            order by prog.`language`
            ";
        $sqlData = doSQLQuery($queryStatement,3); 
        return $sqlData;
    }

    function getVideos($filters=null) {
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
    if ($where == '') $where = '1';
    $queryStatement = "
        SELECT pres.`id`,pres.`duration`,u.`first_name`,u.`last_name`,i.`name` as `institution`,
               prog.`name` as `program`,prog.`progYrs`,e.`date`, pres.`phase`, pres.`type`,e.`city`,e.`country`
        FROM `presentations` pres 
        JOIN `users` u on u.`id` = pres.`user_id` 
        JOIN `events` e on e.`id`= pres.`event_id` 
        JOIN `programs` prog on prog.`id` = e.`program_id`
        JOIN `affiliations` a on (a.`user_id` = u.`id` and a.`program_id`=prog.`id`) 
        JOIN `institutions` i on i.`id`=a.`domestic_institution_id` 
        WHERE $where 
        GROUP BY pres.`id`
        ";
    $sqlData = doSQLQuery($queryStatement,3); 
    return $sqlData;
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
