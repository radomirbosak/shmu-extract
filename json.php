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
	/*
	pocko:
	(cas, mesto, teplota, oblacnost, pocasie, rychlostvetra, smervetra)
	*/
	$sql = "SELECT * FROM texty ORDER BY id DESC;";
	$stmt = $con->prepare($sql);
	$q = $stmt->execute();
	$vsetky =  $stmt->fetchAll(PDO::FETCH_ASSOC);

	$ar = []; // empty array

	$sql = "SELECT * FROM pocko ORDER BY cas DESC;";
	$stmt = $con->prepare($sql);
	$q = $stmt->execute();
	$dalsie = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$ar['texty'] = $vsetky;
	$ar['data'] = $dalsie;
	echo json_encode($ar);
?>
