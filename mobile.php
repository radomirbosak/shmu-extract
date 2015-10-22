<?php 
	global $con;
    $host = 'localhost';
    $dbname = '***';
    $username = '***';
    $password = '***';

    // try connecting to DB
    try {
        $con = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        //echo "<!--Connected to $dbname at $host successfully.<br /> -->\n";
    } catch (PDOException $pe) {
        die("Could not connect to the database $dbname :" . $pe->getMessage());
    }


    function print_record_header($current_date) {
return "<tr class=\"header\">
<td>" . $current_date . "</td><td>Teplota</td><td>Rýchlosť vetra</td><td>Smer vetra</td>
</tr>\n";
    }

    function print_record_html(& $data, & $ar) {
    	$vysledok = "";
    	$phpdate = strtotime( $data['cas']);
		$cas = date( 'H:i', $phpdate );
		$vysledok .= "<tr>";
		$vysledok .=  "<td>" . $cas . "</td>";
		//$vysledok .=  "<td>" . $ar[$data['mesto']] . "</td>";
		$vysledok .=  "<td>" . $data['teplota'] . " °C</td>";
		//$vysledok .=  "<td>" . $ar[$data['oblacnost']] . "</td>";
		//$vysledok .=  "<td>" . $ar[$data['pocasie']] . "</td>";
		$vysledok .=  "<td>" . $data['rychlostvetra'] . " m/s</td>";
		$vysledok .=  "<td>" . $ar[$data['smervetra']] . "</td>";
		$vysledok .=  "</tr>\n" ;
		return $vysledok;
    }


?><!DOCTYPE html>
<html>
<head>
<title>Počasie - Bonifác</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!--<meta charset="utf-8" />-->
</head>
<body>
<style type="text/css">
table {
	border: 1px solid gray;
	border-collapse: collapse;
}

th {
	background: #BBBB88;
	padding: 20px;
}

table.den tr.header td {
	font-weight: bold;
	border-bottom: 2px solid gray;
}


table.den {
	/*width: 100%;*/
}

table.den tr:nth-child(even) { /*(even) or (2n 0)*/
	background: #A4D1FF;
}
table.den tr:nth-child(odd) { /*(odd) or (2n 1)*/
	background: #EAF4FF;
}

td.td-preden {
	padding-top: 0px;
}


td {
	padding: 10px;
}

span.hide {
	border: 1px solid gray;
	padding: .4em;
}


</style>
<table class="den">

<?php
	
	/*
	pocko:
	(cas, mesto, teplota, oblacnost, pocasie, rychlostvetra, smervetra)
	*/
	$sql = "SELECT * FROM texty ORDER BY id DESC;";
	$stmt = $con->prepare($sql);
	$q = $stmt->execute();
	$vsetky =  $stmt->fetchAll(PDO::FETCH_ASSOC);

	$ar = []; // empty array
	foreach ($vsetky as $data) {
		$ar[$data['id']] = $data['obsah'];
	}

	$mysqldate = date("Y-m-d H:00:00\n", strtotime('-10 hours'));

	$sql = "SELECT * FROM pocko WHERE cas > :cas ORDER BY cas DESC;";
	$stmt = $con->prepare($sql);
	$q = $stmt->execute([
		':cas' => $mysqldate,
		]);
	$dalsie = $stmt;

	echo print_record_header(date("d.m.Y"));

	foreach ($dalsie as $data) {
		$year = date('Y', strtotime($data['cas']));
		$month = date('n', strtotime($data['cas']));
		$day = date('j', strtotime($data['cas']));

		$triplet = array('y' => $year, 'm' => $month, 'd' => $day);


		echo print_record_html($data, $ar);

	}

	
?>
</table>
</body>
</html>
