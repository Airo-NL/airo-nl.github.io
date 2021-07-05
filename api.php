<?php
class po {
	public function get ($param) {
		return table::GET([
			"id" => $param[id],
			"tableName" => "abisingen.api.po",
			"idname" => "PakbonID",
			"path" => "/po",
			"headerProperties" => [
				["Bedrijf","State"],
				["TotExcl"],
				["KlantID"]
			],
			"searchNames" => [
				"PakbonID",
				"KlantID"
			],
		]);
	}
}
class Klant {
	public static function omzet () {
		$res=query($q="SELECT * FROM abisingen.charts.orderregels WHERE jaar >= 2017 AND bedrijf = '".aim::$domain."'");
		//die($q);
		$rows=[];
		while($row=sqlsrv_fetch_object($res))array_push($rows,$row);
		header('Content-Type: application/json');
		die(json_encode($rows));
	}
	// public function get ($id = null) {
	// 	return table::GET([id => $param[id], tableName => "abisingen.api.klant", idname => ID, path => '/klant', headerProperties => [[KlantID,Firma],[Straat,Postcode,Plaats],[]], searchNames => [KlantID,Firma] ]);
	// }
}
class Excel {
	public function omzet () {
		die(aim()->to_table(
			'Jaar omzet',
			'SELECT * FROM abisingen.rpt.omzet WHERE jaar>2017 ORDER BY jaar DESC,maand DESC,bedrijf',
			['jaar','maand','bedrijf','nettoVerkoop','nettoInkoop']
		));
	}
	public function omzet_klant () {
		die(aim()->to_table(
			'Jaar omzet',
			'SELECT * FROM abisingen.rpt.omzet WHERE jaar>2017 ORDER BY jaar DESC,maand DESC,bedrijf',
			['jaar','maand','bedrijf','nettoVerkoop','nettoInkoop']
		));
	}
}
class Product {
	public function voorraad () {
    // debug(1);
		extract($_GET);
		if (!empty($ArtID)) {
			aim()->query("UPDATE abisingen.dbo.artikelen set ArtBeginVoorraad='$ArtBeginVoorraad' WHERE ArtID=$ArtID");
			return;
		}
		$res = aim()->query(
			"SELECT A.ArtID,P.MagLokatie,P.BestelCode,P.ArtNR,P.Merk,P.Tekst,A.Eenheid,P.Inhoud,P.InhoudEenheid,A.InkNetto,A.AantalStuks,A.Leverancier,A.ArtBeginVoorraad,P.Bedrijf
			FROM abisingen.dbo.artikelen A
			INNER JOIN abisingen.dbo.producten P ON P.ProdID=A.ProdID
			ORDER BY P.MagLokatie,P.ArtNR,P.Merk,P.Tekst"
		);
		$rows = array();
		while($row=sqlsrv_fetch_object($res))array_push($rows,$row);
		return $rows;
	}
}
if (isset($_GET['report_type'])) {
	echo '<link rel="stylesheet" href="https://aliconnect.nl/lib/css/web.css" />';
	echo '<style>textarea{display:block;width:100%;height:500px;}</style>';
	if ($_GET['report_type'] === 'klanten_actief') {
		echo "<h1>Klanten geleverd aan in 2020</h1>";
		echo "<p>Alle klanten die besteld hebben vanaf 1-1-2020</p>";
		if (isset($_GET['merk'])) {
			$q = "
			SELECT DISTINCT K.bedrijf,B.klantid,K.Firma,K.faktuurEmail,[Extra 1],[Extra 2],P.merk
						from abisingen.dbo.bonnen1 B
						inner join abisingen.dbo.klanten1 K ON K.klantid = B.klantid
						inner join abisingen.dbo.orderregels R ON R.pakbonid = B.pakbonid AND B.datum > '01-01-2020'
						inner join abisingen.dbo.artikelen A on A.artnr = R.artnr
						inner join abisingen.dbo.producten P on A.prodId = P.prodId and P.merk in ('".implode("','",explode(",",$_GET['merk']))."')
			";
		} else {
			$q = "
			select DISTINCT K.bedrijf,B.klantid,K.Firma,K.faktuurEmail,[Extra 1],[Extra 2]
			from abisingen.dbo.bonnen1 B
			inner join abisingen.dbo.orderregels R on R.pakbonid = B.pakbonid
			inner join abisingen.dbo.klanten1 K ON K.klantid = B.klantid
			where B.datum > '01-01-2020'
			";
		}
	}
	// die($q);
	$res=aim()->query($q);
	if (isset($_GET['maillist'])) {
		while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
			$row['bedrijf'] = strtolower($row['bedrijf']);
			$list[$row['bedrijf']][] = $row['faktuurEmail'];
			$list[$row['bedrijf']][] = $row['Extra 1'];
			$list[$row['bedrijf']][] = $row['Extra 2'];
		}
		foreach($list as $key => $value) {
			echo 'maillist ' . $key . '<textarea>' . implode(';',array_filter(array_unique($value))) . '</textarea><br>';
		}
	} else {
		echo "<table>";
		while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
			echo "<tr><td>".implode("</td><td>",array_values($row))."</td></tr>";
		}
		echo "</table>";
	}
	die();
}
