<?php
namespace App\Http\Controllers;

use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;


class LogController extends Controller
{
    //function that renders the input file
    public function index(Request $request)
    {
//Check if $request contains the file
        if ($request->hasFile('file') && ($request->file('file')->isValid())) {
            ini_set('memory_limit', '2048M');
            $file = Input::file('file');
            //Read input file
            $fh = fopen($file, 'r');
//            Create sql file to minimise database connections
            $ofh = fopen('data.sql', 'w+');
            $ofh2 = fopen('data2.sql', 'w+');
//            Add initial requests to sql file
            fwrite($ofh, 'TRUNCATE TABLE logs;' . PHP_EOL);
            fwrite($ofh, 'CREATE TEMPORARY TABLE TEMP (
                `id` BIGINT( 20 ) NOT NULL AUTO_INCREMENT ,
 `from_ip` BIGINT( 20 ) NOT NULL ,
 `to_ip` BIGINT( 20 ) NOT NULL ,
 `port` INT( 11 ) NOT NULL ,
 `protocol` INT( 11 ) NOT NULL ,
 `from_8_subnet` BIGINT( 20 ) NOT NULL ,
 `from_16_subnet` BIGINT( 20 ) NOT NULL ,
 `from_24_subnet` BIGINT( 20 ) NOT NULL ,
 `to_8_subnet` BIGINT( 20 ) NOT NULL ,
 `to_16_subnet` BIGINT( 20 ) NOT NULL ,
 `to_24_subnet` BIGINT( 20 ) NOT NULL ,
PRIMARY KEY (  `id` ) ,
INDEX  `all` (  `id` ,  `from_ip` ,  `to_ip` ,  `port` ,  `protocol` , `from_8_subnet` ,  `from_16_subnet` , `from_24_subnet` ,  `to_8_subnet` ,  `to_16_subnet` , `to_24_subnet` )
);' . PHP_EOL);
            fwrite($ofh, "SET GLOBAL innodb_change_buffering = 'none';" . PHP_EOL);

//            Prepare variables to parse the input file
            $i = 0;
            $sql = '';
            $subnetA = ip2long('255.0.0.0');
            $subnetB = ip2long('255.255.0.0');
            $subnetC = ip2long('255.255.255.0');
            //            Parse the input log file
            while (!feof($fh)) {
                $line = fgetcsv($fh, null, ' ');
                $i++;
//                create the line
//                check if line is valid: contains 4 values and last two of them - numerics
                if (is_array($line) && count($line)==4 && ctype_digit($line[2]) && ctype_digit($line[3])){
//                    Convert IPs to long to use bitwise functions and simplify database storage and operations
                    $line[0] = ip2long($line[0]);
                    $line[1] = ip2long($line[1]);
//                    check if first and second values are correct IP-addresses
                    if($line[0] && $line[1]) {
                        $line[4] = $line[0] & $subnetA;
                        $line[5] = $line[0] & $subnetB;
                        $line[6] = $line[0] & $subnetC;
                        $line[7] = $line[1] & $subnetA;
                        $line[8] = $line[1] & $subnetB;
                        $line[9] = $line[1] & $subnetC;
                    } else {
//                        error
                        return 'error';
                    }
//                    Put result to string to use later
                    $sql .= '(' . implode(',', $line) . '),';
                } else {
//                    check if there is just an empty string in the file
                    if($line[0]!='') {
//                    error
                        return 'error';
                    }
                }
//                Prevent too large variables
                if ($i > 2000) {
                    $i = 0;
                    $sql = trim($sql, ',');
//                    Create request to write the data to MySQL table
                    fwrite($ofh, 'INSERT INTO TEMP (from_ip, to_ip, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet, to_16_subnet, to_24_subnet) VALUES' . $sql . ';' . PHP_EOL);
                    $sql = '';
                }
            }
//            check if we have the data, which is not in the file yet
            if ($sql) {
                $sql = trim($sql, ',');

//                    Create request to write the data to MySQL table
                fwrite($ofh, 'INSERT INTO TEMP (from_ip, to_ip, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet, to_16_subnet, to_24_subnet) VALUES' . $sql . ';' . PHP_EOL);
                unset($sql);
            }
//permissiveness calculation formula handler
            $srcIPTol = 1;
            $dstIPTol = 1;
            $portTol = 1;
            for ($j = 0; $j <= 10; $j++) {
                switch ($j) {
//                    this case left for the chance that we have tolerance in the initial rules set not equal to 0
                    case 0:
                        $calculatedTolerance = $this->calculateTolerance($srcIPTol, $dstIPTol, $portTol);
                        //            Request to clear duplicates from initial file and count number of occurrences
                        fwrite($ofh, 'Insert into logs
SELECT null, t.from_ip, t.to_ip, t.port, t.protocol, t.from_8_subnet, t.from_16_subnet, t.from_24_subnet, t.to_8_subnet, t.to_16_subnet, t.to_24_subnet, COUNT(*), null, 0, 1, '.$calculatedTolerance.'
FROM TEMP t
group by t.from_ip, t.to_ip, t.port, t.protocol, t.from_8_subnet, t.from_16_subnet, t.from_24_subnet, t.to_8_subnet, t.to_16_subnet, t.to_24_subnet;' . PHP_EOL);
                        fwrite($ofh, 'DROP TABLE TEMP;' . PHP_EOL);
                        break;
                    case 1:
                        $dstIPTol = 0.75;
                        $calculatedTolerance = $this->calculateTolerance($srcIPTol, $dstIPTol, $portTol);
                        //            Create first rules set (10 permissiveness)
                        fwrite($ofh2, 'Insert into logs
select null, from_ip, to_24_subnet, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet, to_16_subnet, to_24_subnet, SUM(hits), null, 1, SUM(weight), '.$calculatedTolerance.'
from logs
where parent IS null
group by
from_ip, to_24_subnet, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet, to_16_subnet, to_24_subnet
having count(from_ip)>1;' . PHP_EOL);
//            Update lines, which have a parent with permissiveness = 10
                        fwrite($ofh2, 'UPDATE logs l
INNER JOIN logs l1 ON
l.from_ip = l1.from_ip
AND l.port = l1.port
and l.protocol = l1.protocol
and l.to_24_subnet = l1.to_ip
and l.to_ip != l1.to_ip
and l.parent is null
SET l.parent = l1.id;' . PHP_EOL);
                        break;
                    case 2 :
                        $dstIPTol = 0.5;
                        $calculatedTolerance = $this->calculateTolerance($srcIPTol, $dstIPTol, $portTol);
                        //            Create second rules set (20 permissiveness)
                        fwrite($ofh2, 'Insert into logs select null, from_ip, to_16_subnet, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet, to_16_subnet, 0, SUM(hits), null, 2, SUM(weight), '.$calculatedTolerance.'
 from logs
where parent IS null
group by
from_ip, to_16_subnet, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet, to_16_subnet
having count(from_ip)>1;' . PHP_EOL);
                        //            Update lines, which have a parent with permissiveness = 20
                        fwrite($ofh2, 'UPDATE logs l
INNER JOIN logs l1 ON
l.from_ip = l1.from_ip
AND l.port = l1.port
and l.protocol = l1.protocol
and l.to_16_subnet = l1.to_ip
and l.to_ip != l1.to_ip
and l.parent is null
SET l.parent = l1.id;' . PHP_EOL);
                        break;
                    case 3 :
                        $dstIPTol = 0.25;
                        $calculatedTolerance = $this->calculateTolerance($srcIPTol, $dstIPTol, $portTol);
                        //            Create third rules set (30 permissiveness)
                        fwrite($ofh2, 'Insert into logs select null, from_ip, to_8_subnet, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet, 0, 0, SUM(hits), null, 3, SUM(weight), '.$calculatedTolerance.'
 from logs
where parent IS null
group by
from_ip, to_8_subnet, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet
having count(from_ip)>1;' . PHP_EOL);
                        //            Update lines, which have a parent with permissiveness = 30
                        fwrite($ofh2, 'UPDATE logs l
INNER JOIN logs l1 ON
l.from_ip = l1.from_ip
AND l.port = l1.port
and l.protocol = l1.protocol
and l.to_8_subnet = l1.to_ip
and l.to_ip != l1.to_ip
and l.parent is null
SET l.parent = l1.id;' . PHP_EOL);
                        break;
                    case 4 :
                        $dstIPTol = 0;
                        $calculatedTolerance = $this->calculateTolerance($srcIPTol, $dstIPTol, $portTol);
                        //            Create fourth rules set (40 permissiveness)
                        fwrite($ofh2, 'Insert into logs select null, from_ip, 0, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, 0, 0, 0, SUM(hits), null, 4, SUM(weight), '.$calculatedTolerance.'
 from logs
where parent IS null
group by
from_ip, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet
having count(from_ip)>1;' . PHP_EOL);
                        //            Update lines, which have a parent with permissiveness = 40


                        fwrite($ofh2, 'UPDATE logs l
INNER JOIN logs l1 ON
l.from_ip = l1.from_ip
and l.protocol = l1.protocol
AND l.port = l1.port
and l.to_8_subnet != 0
and l.to_ip != 0
and l.to_ip != l1.to_ip
and l1.to_ip = 0
and l.parent is null
SET l.parent = l1.id;' . PHP_EOL);
                        break;
                    case 5 :
                        $dstIPTol = 0;
                        $srcIPTol = 0.75;
                        $calculatedTolerance = $this->calculateTolerance($srcIPTol, $dstIPTol, $portTol);
                        //            Create fifth rules set (50 permissiveness)
                        fwrite($ofh2, 'Insert into logs select null, from_24_subnet, 0, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, 0, 0, 0, SUM(hits), null, 5, SUM(weight),'.$calculatedTolerance.'
 from logs
where parent IS null
group by
port, protocol, from_8_subnet, from_16_subnet, from_24_subnet
having count(from_ip)>1;' . PHP_EOL);
                        //            Update lines, which have a parent with permissiveness = 50
                        fwrite($ofh2, 'UPDATE logs l, (SELECT ID, from_ip, to_ip, port, protocol
                        FROM logs
                       WHERE to_ip = 0 AND parent IS null AND from_ip = from_24_subnet) l1
   SET l.parent = l1.id
 WHERE l.port = l1.port
and l.protocol = l1.protocol
and l.from_24_subnet = l1.from_ip
and l.from_ip != l1.from_ip
and l.parent is null;' . PHP_EOL);
                        break;
                    case 6 :
                        $dstIPTol = 0;
                        $srcIPTol = 0.5;
                        $calculatedTolerance = $this->calculateTolerance($srcIPTol, $dstIPTol, $portTol);
                        //            Create sixth rules set (60 permissiveness)
                        fwrite($ofh2, 'Insert into logs select null, from_16_subnet, 0, port, protocol, from_8_subnet, from_16_subnet, 0, 0, 0, 0, SUM(hits), null, 6, SUM(weight),'.$calculatedTolerance.'
 from logs
where parent IS null
group by
port, protocol, from_8_subnet, from_16_subnet
having count(from_ip)>1;' . PHP_EOL);
                        //            Update lines, which have a parent with permissiveness = 60
                        fwrite($ofh2, 'UPDATE logs l, (SELECT ID, from_ip, to_ip, port, protocol
                        FROM logs
                       WHERE to_ip = 0 AND parent IS null AND from_ip = from_16_subnet) l1
   SET l.parent = l1.id
 WHERE l.port = l1.port
and l.protocol = l1.protocol
and l.from_16_subnet = l1.from_ip
and l.from_ip != l1.from_ip
and l.parent is null;' . PHP_EOL);
                        break;
                    case 7 :
                        $dstIPTol = 0;
                        $srcIPTol = 0.25;
                        $calculatedTolerance = $this->calculateTolerance($srcIPTol, $dstIPTol, $portTol);
                        //            Create seventh rules set (70 permissiveness)
                        fwrite($ofh2, 'Insert into logs select null, from_8_subnet, 0, port, protocol, from_8_subnet, 0, 0, 0, 0, 0, SUM(hits), null, 7, SUM(weight), '.$calculatedTolerance.'
 from logs
where parent IS null
group by
port, protocol, from_8_subnet
having count(from_ip)>1;' . PHP_EOL);
                        //            Update lines, which have a parent with permissiveness = 70
                        fwrite($ofh2, 'UPDATE logs l, (SELECT ID, from_ip, to_ip, port, protocol
                        FROM logs
                       WHERE to_ip = 0 AND parent IS null AND from_ip = from_8_subnet) l1
   SET l.parent = l1.id
 WHERE l.port = l1.port
and l.protocol = l1.protocol
and l.from_8_subnet = l1.from_ip
and l.from_ip != l1.from_ip
and l.parent is null;' . PHP_EOL);
                        break;
                    case 8 :
                        $dstIPTol = 0;
                        $srcIPTol = 0;
                        $calculatedTolerance = $this->calculateTolerance($srcIPTol, $dstIPTol, $portTol);
                        //            Create eighth rules set (80 permissiveness)
                        fwrite($ofh2, 'Insert into logs select null, 0, 0, port, protocol, 0, 0, 0, 0, 0, 0, SUM(hits), null, 8, SUM(weight), '.$calculatedTolerance.'
 from logs
where parent IS null
group by
port, protocol
having count(from_ip)>1;' . PHP_EOL);
                        //            Update lines, which have a parent with permissiveness = 80
                        fwrite($ofh2, 'UPDATE logs l, (SELECT ID, from_ip, to_ip, port, protocol
                        FROM logs
                       WHERE to_ip = 0 AND parent IS null AND from_ip = 0 AND port !=0) l1
   SET l.parent = l1.id
 WHERE l.port = l1.port
and l.protocol = l1.protocol
and l.from_ip != 0
and l.from_ip != l1.from_ip
and l.parent is null;' . PHP_EOL);
                        break;
                    case 10 :
                        $srcIPTol = 0;
                        $dstIPTol = 0;
                        $portTol = 0;
                        $calculatedTolerance = $this->calculateTolerance($srcIPTol, $dstIPTol, $portTol);
                        //            Create last rules set (100 permissiveness)
                        fwrite($ofh2, 'Insert into logs select null, 0, 0, 0, protocol, 0, 0, 0, 0, 0, 0, SUM(hits), null, 10, SUM(weight), '.$calculatedTolerance.'
 from logs
where parent IS null
group by
protocol
having count(from_ip)>1;' . PHP_EOL);
                        //            Update lines, which have a parent with permissiveness = 100 (all lines within a specific protocol)
                        fwrite($ofh2, 'UPDATE logs l, (SELECT ID, protocol
                        FROM logs
                       WHERE parent IS null and tolerance=10) l1
   SET l.parent = l1.id
 WHERE l.protocol = l1.protocol
and l.parent is null
and l.tolerance<10;' . PHP_EOL);
                        break;
                }

            }
            fwrite($ofh2, "SET GLOBAL innodb_change_buffering = 'all';" . PHP_EOL);
//            close .sql files
            fclose($ofh);
            fclose($ofh2);
//            upload .sql file to database as a dump file (much faster than via separate queries, especially with a lot of data)
//creating set of unique ip-ip-port rules
            exec("mysql -u ".env('DB_USERNAME')." -p".env('DB_PASSWORD')." ".env('DB_DATABASE')." < data.sql");


//            return when finished
            return 'success';
        } else {
//           in case of error with file upload
            return 'error';
        }
    }

    public function part()
    {
//        creating level-based rules
        exec("mysql -u ".env('DB_USERNAME')." -p".env('DB_PASSWORD')." ".env('DB_DATABASE')." < data2.sql");
        return 'success';
    }
//    function to get the chart points
    public function getChartPoints()
    {
        $j = 0;
        $result = array();
        for ($k = 10; $k >= 0; $k--) {
//            request to get a certain point for a current grouping level (permissiveness)
            $point = DB::select("select sum(tolerance*weight)/(select count(*) from logs where tolerance = 0)*10 as permissiveness, count(*) as rules, max(tolerance) as level from logs where tolerance =" . $k . " or (tolerance<" . $k . " and parent in (select id from logs where tolerance >" . $k . "));");
//            Check for duplicate points (skip steps where no rules were created)
            if ($j == 0) {
                array_push($result, $point[0]);
                $j++;
            } else {
                if ($result[$j - 1] != $point[0]) {
                    array_push($result, $point[0]);
                    $j++;
                }
            }
        }
//        return array with chart points (three values: real permissiveness, number of rules for that permissiveness, level - as an id of step, where the point is created. used to get the table contents for this point)
        return $result;
    }

//    function to get the initial ids of rules to create the table at certain chart point
    public function getParentIds(Request $request)
    {
        $data = $request->all();
        $level = $data['lvl'];
        $ids = DB::select("Select id from logs where tolerance =" . $level . " or (tolerance<" . $level . " and parent in (select id from logs where tolerance >" . $level . "));");
        return json_encode($ids);
    }

//    function to get the table initial contents and children of a certain rule.
    public function getTree(Request $request)
    {
        $data = $request->all();
        $id = $data['id'];
        $id = str_replace(array('[', ']'), '', $id);
//        Check if all the elements needed (table initialization) or only children of a certain rule
        if (isset($data['node'])) {
            $rule = $data['node'];
        }
        if (!isset($rule) or $rule == '#') {
            $param = 'id';
            $range = $id;
        } else {
            $param = 'parent';
            $range = $rule;
        }
//        request to get the needed elements (initial parents for all table or children for a certain rule)
        $point = DB::select("select id, tolerance from logs where " . $param . " in (" . $range . ") ORDER BY tolerance DESC, hits DESC, id ASC;");
//        call to function that prepares result from database for javascript
//        return $this->createTreeData($point);
        return $this->createTree($point);
    }


//    function to create tree from the database output
    public function createTree($point)
    {
        $tree = array();
        foreach ($point as $line) {
//            check the level of rule to add/replace info for view
            $children = true;
            if ($line->tolerance == 0) {
                $children = false;
            }
//            create array for tree for a certain line
            $Title = 'Rule №' . $line->id;
            $treeData = ['id' => $line->id, 'children' => $children, 'text' => $Title];
//            Put the line to a resulting array
            array_push($tree, $treeData);
        }
//        return resulting tree array (ready for javascript handling)
        return json_encode($tree);
    }

//    function to generate downloadable file, based on current rules table state
    public function getOutputFile(Request $request)
    {
        $data = $request->all();
//        get IDs of rules to be put in the file
        $id = $data['id'];
        $id = str_replace(array('[', ']'), '', $id);
//        create the output file
        $output = fopen('rules.csv', 'w');
        DB::setFetchMode(\PDO::FETCH_ASSOC);
//        get the data for output file
        $rows = DB::select("select from_ip, to_ip, port, protocol from logs where id in (" . $id . ");");
        DB::setFetchMode(\PDO::FETCH_CLASS);
        foreach ($rows as $row) {
//            convert the IPs to standtrd, human-readable format
            $row['from_ip'] = long2ip($row['from_ip']);
            $row['to_ip'] = long2ip($row['to_ip']);
//            put line to output file
            fputcsv($output, $row);
        }
//        close the output file
        fclose($output);
//        return the file is ready to be downloaded
        return 'sucess';
    }

    public function getHumanOutputFile(Request $request)
    {
        $data = $request->all();
//        get IDs of rules to be put in the file
        $id = $data['id'];
        $id = str_replace(array('[', ']'), '', $id);
//        create the output file
        $output = fopen('humanRules.csv', 'w');
//        create table header
        $firstLine = ['Source IP', 'Source mask', 'Destination IP', 'Destination mask', 'Port', 'Protocol', 'Hits', 'Tolerance'];
        fputcsv($output, $firstLine);
//        get the data for output file
        $rows = DB::select("select from_ip, to_ip, port, protocol, tolerance, hits from logs where id in (" . $id . ");");
//        prepare data for output
        foreach ($rows as $row) {
//            generate human-readable masks
            switch ($row->tolerance) {
                case 0:
                    $row->to_mask = '/32';
                    $row->from_mask = '/32';
                    break;
                case 1:
                    $row->to_mask = '/24';
                    $row->from_mask = '/32';
                    break;
                case 2:
                    $row->to_mask = '/16';
                    $row->from_mask = '/32';
                    break;
                case 3:
                    $row->to_mask = '/8';
                    $row->from_mask = '/32';
                    break;
                case 4:
                    $row->to_mask = '/0';
                    $row->from_mask = '/32';
                    break;
                case 5:
                    $row->to_mask = '/0';
                    $row->from_mask = '/24';
                    break;
                case 6:
                    $row->to_mask = '/0';
                    $row->from_mask = '/16';
                    break;
                case 7:
                    $row->to_mask = '/0';
                    $row->from_mask = '/8';
                    break;
                case 8:
                    $row->to_mask = '/0';
                    $row->from_mask = '/0';
                    break;
                case 10:
                    $row->to_mask = '/0';
                    $row->from_mask = '/0';
                    $row->port = 'any';
                    break;
            }
//            convert the IPs to standard, human-readable format
            $row->from_ip = long2ip($row->from_ip);
            $row->to_ip = long2ip($row->to_ip);
//            create line
            $line = ['Source IP' => $row->from_ip, 'Source mask' => $row->from_mask, 'Destination IP' => $row->to_ip, 'Destination mask' => $row->to_mask, 'Port' => $row->port, 'Protocol' => $row->protocol, 'Hits' => $row->hits, 'Tolerance' => $row->tolerance*10];
//            put line to output file
            fputcsv($output, $line);
        }
//        close the output file
        fclose($output);
//        return the file is ready to be downloaded
        return 'sucess';
    }

    public function getTableContents(Request $request)
    {
        $data = $request->all();
        if (isset($data['id'])) {
            $id = $data['id'];
            $id = str_replace(array('[', ']'), '', $id);
        }

//        Check if all the elements needed (table initialization) or only children of a certain rule
        if (isset($data['parent'])) {
            $rule = $data['parent'];
        }

        if (!isset($rule)) {
            $param = 'id';
            $range = $id;
        } else {
            $param = 'parent';
            $range = $rule;
        }
//        request to get the needed elements (initial parents for all table or children for a certain rule)
        $point = DB::select("select id, from_ip, to_ip, port, protocol, hits, tolerance, parent, calculated_level*10 as permissiveness from logs where " . $param . " in (" . $range . ") ORDER BY tolerance DESC, hits DESC, id ASC;");

        $table = array();
        foreach ($point as $line) {
//            Convert IPs to human-readable format
            $line->from_ip = long2ip($line->from_ip);
            $line->to_ip = long2ip($line->to_ip);
//            check the level of rule to add/replace mask info for view
            switch ($line->tolerance) {
                case 1:
                    $line->to_ip .= '/24';
                    break;
                case 2:
                    $line->to_ip .= '/16';
                    break;
                case 3:
                    $line->to_ip .= '/8';
                    break;
                case 4:
                    $line->to_ip = 'any';
                    break;
                case 5:
                    $line->to_ip = 'any';
                    $line->from_ip .= '/24';
                    break;
                case 6:
                    $line->to_ip = 'any';
                    $line->from_ip .= '/16';
                    break;
                case 7:
                    $line->to_ip = 'any';
                    $line->from_ip .= '/8';
                    break;
                case 8:
                    $line->to_ip = 'any';
                    $line->from_ip = 'any';
                    break;
                case 10:
                    $line->to_ip = 'any';
                    $line->from_ip = 'any';
                    $line->port = 'any';
                    break;
            }
//            create array for table columns for a certain line
            $tableData = ['id' => $line->id, 'source' => $line->from_ip, 'destination' => $line->to_ip, 'protocol' => $line->protocol, 'port' => $line->port, 'hits' => $line->hits, 'permissiveness' => $line->permissiveness];
//           put data to the output array
            array_push($table, $tableData);
        }
        return json_encode($table);
    }
//Function that calculates needed permissiveness (change numeric coefficients to change weight of each value)
    public function calculateTolerance($sourceIPTolerance, $destinationIPTolerance, $portTolerance)
    {
        return (10 - (4 * $sourceIPTolerance + 4 * $destinationIPTolerance + 2 * $portTolerance));
    }

}