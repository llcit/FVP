<?php

/* ****************************** CONNECT TO THE DATABASE ****************************** */

$dbcnx = @mysqli_connect("localhost", "FVPUser", "1400KStreetStreetNW!");
if (!$dbcnx) {
    echo( mysqli_error($dbcnx) . "<P>Unable to connect to the " .
            "database server at this time.</P>" );
    exit();
}
if (! @mysqli_select_db($dbcnx,'FVP') ) {
    echo( "<P>Unable to locate FVP database at this time.</P>" );
    exit();
}
mysqli_query($dbcnx,"SET NAMES 'utf8'");

/* ****************************** /CONNECT TO THE DATABASE ****************************** */


/* ****************************** GENERIC SQL QUERY FUNCTION ****************************** */
function doSQLQuery($queryStatement,$dataFormat=null,$debug=null) {
    global $dbcnx;
    // ARGUMENTS:
    // $queryStatement: A fully formed SQL query
    // $dataFormat: determines what type of data structure in which to return the results of SELECT queries
    //      0 = scalar variable-- use only when expecting single instances of a single field
    //      1 = array-- use when expecting multiple instances of single field
    //      2 = a hash of values-- use when expecting a single instance of multiple fields
    //      3 = array of hashes-- use when expecting multiple instances of multiple fields (serves as default when no value is passed)
    // $debug: pass a value of 1 to turn on debug messages
    $queryVals = null;
    // error checking, get rid of any beginning whitespace
    $queryStatement = preg_replace("/^\s+(\w+\W*\w*)/" , "$1", $queryStatement );
    if ($debug) echo ("$queryStatement<BR>");
    if (!$debug || $debug < 2) {
        $startTime = microtime();
        $sqlQuery = mysqli_query($dbcnx,$queryStatement);
        if (!$sqlQuery) {
            echo("<P>Error in performing query: <HR>'$queryStatement'<HR>" .
                    mysqli_error($dbcnx) . "</P>");
            exit();
        }
        $endTime = microtime();
        $elapsedTime = $endTime - $startTime;
        // parse out the first word of query -- SELECT/REPLACE/UPDATE/INSERT
        $pieces = explode(" ", $queryStatement);
        if ($debug) {
            //$numberOfRecords = mysqli_num_rows($sqlQuery);
            echo ("<-----------[RESULTS]-----------><BR>"
                    //"<-----------[# OF RECORDS: $numberOfRecords]-----------><BR>" .
                    // "<-----------[QUERY TOOK $elapsedTime sec]-----------><BR>"
                 );
        }
        if(strtoupper($pieces[0]) == "SELECT" || strtoupper($pieces[0]) == "SHOW" ) {
            $innerArrayIndex = 0;
            $outerArrayIndex = 0;
            while ($row = mysqli_fetch_assoc($sqlQuery)) {
                foreach ($row as $key => $value) {
                    if ($dataFormat == 0) { // return a scalar value
                        $queryVals = "$value";
                        if ($debug) echo ("\$queryVals = '$value';<BR>");
                    }
                    if ($dataFormat == 1) { // return an array of values-- can be used with `list()`
                        $queryVals[$innerArrayIndex] = "$value";
                        if ($debug) echo ("\$queryVals[$innerArrayIndex] = '$value';<BR>");
                        $innerArrayIndex++; //(used in single array)
                    }
                    if ($dataFormat == 2) { // build a hash of values
                        $queryVals[$key] = "$value";
                        if ($debug) echo ("\$queryVals[$key] = '$value';<BR>");
                    }
                    if (!isset($dataFormat) || $dataFormat == 3) { // build array of hashes (default format)
                        $queryVals[$outerArrayIndex][$key] = "$value";
                        if ($debug) echo ("\$queryVals[$outerArrayIndex][$key] = '$value';<BR>");
                    }
                }
                $outerArrayIndex++; // increment after all fields have been processed in each row (used in array of hashes)
            }
            if ($debug && $queryVals == null) echo ("<FONT COLOR=RED><I>QUERY RETURNED " .
                    "NO RESULTS</I></FONT><BR>");


        }
        else if(strtoupper($pieces[0]) == "UPDATE" || strtoupper($pieces[0]) == "INSERT" || strtoupper($pieces[0]) == "REPLACE" || strtoupper($pieces[0]) == "DELETE") {
            // return the number of affected rows for updates and inserts
            $queryVals = mysqli_affected_rows($dbcnx);
            if ($debug) echo ("mysqli_affected_rows() = '".mysqli_affected_rows($dbcnx)."';<BR>");
        }
        if ($debug) echo ("<-----------[/RESULTS]-----------><BR>");
    }
    return $queryVals;
}
/* ****************************** /GENERIC SQL QUERY FUNCTION ****************************** */

?>