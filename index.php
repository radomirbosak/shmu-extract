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

    function print_record_header() {
return "<tr class=\"header\">
<td>Čas merania</td><td>Mesto</td><td>Teplota</td><td>Oblačnosť</td><td>Počasie</td><td>Rýchlosť vetra</td><td>Smer vetra</td>
</tr>\n";
    }

    function print_record_html(& $data, & $ar) {
    	$vysledok = "";
    	$phpdate = strtotime( $data['cas']);
		$cas = date( 'd.m.Y - H:i', $phpdate );
		$vysledok .= "<tr>";
		$vysledok .=  "<td>" . $cas . "</td>";
		$vysledok .=  "<td>" . $ar[$data['mesto']] . "</td>";
		$vysledok .=  "<td>" . $data['teplota'] . " °C</td>";
		$vysledok .=  "<td>" . $ar[$data['oblacnost']] . "</td>";
		$vysledok .=  "<td>" . $ar[$data['pocasie']] . "</td>";
		$vysledok .=  "<td>" . $data['rychlostvetra'] . " m/s</td>";
		$vysledok .=  "<td>" . $ar[$data['smervetra']] . "</td>";
		$vysledok .=  "</tr>\n" ;
		return $vysledok;
    }
    function tri_teploty($a,$b,$c,$name,$hide) {

    	if ($hide == 1) {
    		$hidename = 'show';
    		$hidestyle = "display: none;";
    	} else {
    		$hidename = 'hide';
    		$hidestyle = '';
    	}
    	return "<td>Priemerná teplota: " . $a . "°C</td>" .
    		"<td> Maximálna teplota: " . $b . "°C</td>" .
    		"<td> Minimálna teplota: " . $c . "°C</td>" .
    		"<td><span class=\"hide\" id=\"b-" . $name . "\" onclick=\"toggleTable('" . $name . "');\">" . $hidename . "</span></td>";
    }
    function print_rok_begin($triplet,& $p) {
    	$hide = 1; $hidestyle = '';
    	$py = $p[$triplet['y']];
    	$priemer = round($py['sucet'] / $py['pocet'], 1);
    	$name = '' . $triplet['y'];
    	if ($p['current']['y'] == $triplet['y'])
    		$hide = 0;
    	if ($hide == 1) $hidestyle = 'display: none;';
    	return "<tr><td>Rok " . $triplet['y'] ."</td>" . tri_teploty($priemer,$py['max'],$py['min'], $name, $hide) ."</tr><tr id=\"t-" . $name . "\" style=\"" . $hidestyle . "\"><td colspan=\"5\"><table class=\"rok\">";
    }
    function print_rok_end($triplet,& $p) {
    	return "</table></td></tr>";
    }

    function print_mesiac_begin($triplet,& $p) {
    	$hide = 1; $hidestyle = '';
    	$name = '' . $triplet['y'] . '-' . $triplet['m'];
		$pm = $p[$triplet['y']][$triplet['m']];
		//var_dump( $p[$triplet['y']]);
		//die();
		$priemer = round($pm['sucet'] / $pm['pocet'], 1);
		if ($p['current']['y'] == $triplet['y'] && $p['current']['m'] == $triplet['m'])
    		$hide = 0;
    	if ($hide == 1) $hidestyle = 'display: none;';
    	return "<tr><td>Mesiac " . $triplet['m'] ."</td>" . tri_teploty($priemer,$pm['max'],$pm['min'], $name, $hide) ."</tr><tr id=\"t-" . $name . "\" style=\"" . $hidestyle . "\"><td colspan=\"5\"><table class=\"mesiac\">";
    }
    function print_mesiac_end($triplet,& $p) {
    	return "</table></td></tr>";
    }

    function print_den_begin($triplet,& $p) {
    	$hide = 1; $hidestyle = '';
    	$name = '' . $triplet['y'] . '-' . $triplet['m'] . '-' . $triplet['d'];
    	$pd = $p[$triplet['y']][$triplet['m']][$triplet['d']];
    	$priemer = round($pd['sucet'] / $pd['pocet'], 1);
    	if ($p['current']['y'] == $triplet['y'] && $p['current']['m'] == $triplet['m'] && $p['current']['d'] == $triplet['d'])
    		$hide = 0;
    	if ($hide == 1) $hidestyle = 'display: none;';
    	return "<tr><td>Deň " . $triplet['d'] ."</td>" . tri_teploty($priemer,$pd['max'],$pd['min'], $name, $hide) ."</tr><tr id=\"t-" . $name . "\" style=\"" . $hidestyle . "\"><td class=\"td-preden\" colspan=\"5\"><table class=\"den\">" . print_record_header();
    }
    function print_den_end($triplet,& $p) {
    	return "</table></td></tr>";
    }

?><!DOCTYPE html>
<html>
<head>
<title>Počasie - Bonifác</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--<meta charset="utf-8" />-->

<script type="text/javascript">
function toggleTable(name) {
	tbl = document.getElementById('t-' + name);
	btn = document.getElementById('b-' + name);
	if (tbl.style.display == 'none') {
		tbl.style.display = '';
		btn.textContent = 'hide';
	} else {
		tbl.style.display = 'none';
		btn.textContent = 'show';
	}
}	
</script>
</head>
<body>
<style type="text/css">
table {
	border: 1px solid gray;
	border-collapse: collapse;
}


table.outer {
	width: 100%;
}

table.outer > tbody > tr:nth-child(4n+1), table.outer > tbody > tr:nth-child(4n+2) {
	background-color: #eeeeee;
}
table.outer > tbody > tr:nth-child(4n+3), table.outer > tbody > tr:nth-child(4n+4) {
	background-color: #f8f8f8;
}

th {
	background: #BBBB88;
	padding: 20px;
}

table.den tr.header td {
	font-weight: bold;
	border-bottom: 2px solid gray;
}

table.rok {
	/*background-color: Moccasin;*/
	width: 100%;
}
table.rok > tbody > tr:nth-child(4n+1), table.rok > tbody > tr:nth-child(4n+2) {
	background-color: #FFE4B5;
	width: 100%;
}
table.rok > tbody > tr:nth-child(4n+3), table.rok > tbody > tr:nth-child(4n+4) {
	background-color: #eFe4a5;
	width: 100%;
}

table.mesiac {
	/*background-color: DarkSeaGreen;*/
	width: 100%;

}

table.mesiac > tbody > tr:nth-child(4n+1), table.mesiac > tbody > tr:nth-child(4n+2) {
	background-color: #8FBC8F;
}
table.mesiac > tbody > tr:nth-child(4n+3), table.mesiac > tbody > tr:nth-child(4n+4){
	background-color: #aFcCaF;
}

table.den {
	width: 100%;
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
<table class="outer">

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

	$sql = "SELECT * FROM pocko ORDER BY cas DESC;";
	$stmt = $con->prepare($sql);
	$q = $stmt->execute();
	$dalsie = $stmt->fetchAll(PDO::FETCH_ASSOC);

	/*
		Teraz vypocitaj setky priemery
	*/
	$p = array();
	foreach ($dalsie as $data) {
		$y = date('Y', strtotime($data['cas']));
		$m = date('n', strtotime($data['cas']));
		$d = date('j', strtotime($data['cas']));

		$t = $data['teplota'];

		if (!array_key_exists($y, $p)) {
			$p[$y] = array();
			$p[$y]['sucet'] = $t;
			$p[$y]['pocet'] = 1;
			$p[$y]['min'] = $t;
			$p[$y]['max'] = $t;
		} else {
			$p[$y]['sucet'] += $t;
			$p[$y]['pocet'] += 1;
			if ($t < $p[$y]['min']) $p[$y]['min'] = $t;
			if ($t > $p[$y]['max']) $p[$y]['max'] = $t;
		}

		$py = & $p[$y];
		if (!array_key_exists($m, $py)) {
			$py[$m] = array();
			$py[$m]['sucet'] = $t;
			$py[$m]['pocet'] = 1;
			$py[$m]['min'] = $t;
			$py[$m]['max'] = $t;
		} else {
			$py[$m]['sucet'] += $t;
			$py[$m]['pocet'] += 1;
			if ($t < $py[$m]['min']) $py[$m]['min'] = $t;
			if ($t > $py[$m]['max']) $py[$m]['max'] = $t;
		}

		$pm = & $py[$m];
		if (!array_key_exists($d, $pm)) {
			$pm[$d] = array();
			$pm[$d]['sucet'] = $t;
			$pm[$d]['pocet'] = 1;
			$pm[$d]['min'] = $t;
			$pm[$d]['max'] = $t;
		} else {
			$pm[$d]['sucet'] += $t;
			$pm[$d]['pocet'] += 1;
			if ($t < $pm[$d]['min']) $pm[$d]['min'] = $t;
			if ($t > $pm[$d]['max']) $pm[$d]['max'] = $t;
		}
	}



	$last_year = date('Y', strtotime($dalsie[0]['cas']));
	$last_month = date('n', strtotime($dalsie[0]['cas']));
	$last_day = date('j', strtotime($dalsie[0]['cas']));

	$triplet = array('y' => $last_year, 'm' => $last_month, 'd' => $last_day);
	$p['current'] = $triplet;

	echo print_rok_begin($triplet, $p);
	echo print_mesiac_begin($triplet, $p);
	echo print_den_begin($triplet, $p);

	foreach ($dalsie as $data) {
		$year = date('Y', strtotime($data['cas']));
		$month = date('n', strtotime($data['cas']));
		$day = date('j', strtotime($data['cas']));

		$triplet = array('y' => $year, 'm' => $month, 'd' => $day);

		if ($year != $last_year) {
			echo print_den_end($triplet, $p);
			echo print_mesiac_end($triplet, $p);
			echo print_rok_end($triplet, $p);
			echo print_rok_begin($triplet, $p);
			echo print_mesiac_begin($triplet, $p);
			echo print_den_begin($triplet, $p);
		} else if ($month != $last_month) {
			echo print_den_end($triplet, $p);
			echo print_mesiac_end($triplet, $p);
			echo print_mesiac_begin($triplet, $p);
			echo print_den_begin($triplet, $p);
		} else if ($day != $last_day) {
			echo print_den_end($triplet, $p);
			echo print_den_begin($triplet, $p);
		}

		echo print_record_html($data, $ar);

		$last_year = $year;
		$last_month = $month;
		$last_day = $day;
	}
	echo print_den_end($triplet, $p);
	echo print_mesiac_end($triplet, $p);
	echo print_rok_end($triplet, $p);
	
?>
</table>
</body>
</html>
