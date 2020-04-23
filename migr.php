<?php
require_once('/var/www/simplesaml/lib/_autoload.php');
$as = new SimpleSAML_Auth_Simple('default-sp');
$as->requireAuth();
$attr=$as->getAttributes();
$name=$as->getAuthData("saml:sp:NameID");
?>
<html>
<body>
<h1>IdPお引越しページ</h1>
<h2>IdPのお引越しを行います</h2>
<?php
include('db_login.php');

$connection = mysqli_connect($db_host, $db_username, $db_password);
if(!connection){
	die ("[error1] Could not connnect to the database: <br />".mysqli_error());
}

$db_select = mysqli_select_db($connection, $db_database);
if(!$db_select){
	die ("[error2] Could not select the database: <br />".mysqli_error());
}

$uid_idp = $attr['uid'][0].'_ap';
//$user_id = $idp_user_id.'_ap';
$idp = $attr['idp'][0];
$sp = $_POST["sp"];
$ret_url = 'https://'.$sp.'.local/sample';
$ret_url2 = 'https://'.$sp.'.local/sample/comp.php';
$ret_url3 = 'https://'.$sp.'local/samlple/start.php';

// ログイン中のユーザに該当するIDが存在するか確認する
$query = "SELECT uid FROM users WHERE uid_idp = '$uid_idp'";
$result = mysqli_query($connection, $query);
if(!$result){
	die ("[error3] Could not query the database: <br />".mysqli_error());
}
else{
	$result_row = mysqli_fetch_row($result);
	$uid = $result_row[0];
	if ($uid == NULL){
		// 事前にユーザ登録がなされていない または新しいIdPからのログイン
		echo "引継 ID をお持ちであれば，入力してください<br>";
		$input_form = '<form action="https://ap.local/sample/movin2.php" method="post"><input type="text" name="mig_id" ><input type="submit" value="送信"></form>';
		echo $input_form;
		if($sp != NULL){
			echo "<a href=".$ret_url3.">SPに戻る</a>";
		}

		// 遷移元のSPをクッキーに保存する(暫定として1時間)
		setcookie("sp", $sp, time() + 60*60);
	}
	else{
		// APの引継IDがすでに発行済であるか確認する
		$query2 = "SELECT mig_id_ap FROM users WHERE uid = '$uid'";
		$result2 = mysqli_query($connection, $query2);
		if(!$result2){
			die ("[error4] Could not query the database: <br />".mysqli_error());
		}
		else{
			$result_row2 = mysqli_fetch_row($result2);
			$mig_id_ap = $result_row2[0];
			if($mig_id_ap == NULL){
				// APの引継IDが未発行の場合，引継IDを生成する
				$mig_id = substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyz'), 0, 8);
				$query3 = "UPDATE users SET mig_id_ap = '$mig_id' WHERE uid = '$uid'";
                $result3 = mysqli_query($connection, $query3);
				if(!$result3){
					die ("[error5] Could not query the database: <br />".mysqli_error());
				}
			}
			else{
				// 引継IDがすでに発行済であればそれを用いる
				$mig_id = $mig_id_ap;
			}
		}
		echo '<br />引継ID : '.$mig_id;
        echo "<p>引継IDをメモして新しいIdPでログインしてください</p>";
		$htmlchar = '<a href="logout.php">新しいIdPでログイン<br /></a>';
		echo $htmlchar;
		echo '<a href='.$ret_url.'>引継作業を完了せずに SP に戻る<br /></a>';

        // 遷移元のSPをクッキーに保存する(暫定として1時間)
        setcookie("sp", $sp, time() + 60*60);

		// 既に引越し済のユーザであるか確認する
		$query4 = "SELECT mig_id_".$sp.", mig_comp, srcIdP, dstIdP FROM users WHERE uid = '$uid'";
		$result4 = mysqli_query($connection, $query4);
		if (!$result4) {
			die ("[error6] Could not query the database: <br />".mysqli_error());
		}
		else {
			$result_row4 = mysqli_fetch_row($result4);
			$mig_id_sp = $result_row4[0]; // SPの引継ID
			$mig_comp = $result_row4[1];  // 引越しが完了していれば1
			$srcIdP = $result_row4[2];    // 引越し元IdP
			$dstIdP = $result_row4[3];    // 引越し先IdP
			// 引越し済みユーザである場合
			if ($mig_comp == 1 and $mig_id_sp != NULL and $srcIdP != NULL and $dstIdP != NULL) {
				echo $mig_id_sp." + ".$mig_comp." + ".$srcIdP."+".$dstIdP."<br />";
				echo "※あなたは既に".$srcIdP."から".$dstIdP."へのお引越しを完了しています。<br />";
				echo "このお引越情報で".$sp."のお引越しも行いますか？<br />";
				$htmlchar0  = '<form action="'.$ret_url2.'"  method="post"><input type="hidden" name="mig_id_sp" value="'.$mig_id_sp.'"><input type="submit" value="はい"></form>';
				echo $htmlchar0;
			}
			else {
				// 引越し元IdPを登録する
	            $query5 = "UPDATE users SET srcIdP = '$idp' WHERE uid = '$uid'";
				$result5 = mysqli_query($connection, $query5);
				if (!result5) {
					die ("[error7] Could not query the database: <br />".mysqli_error());
				}
			}
		}
	}
}
?>
</body>
</html>
