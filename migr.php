<?php
require_once('/var/www/simplesaml/lib/_autoload.php');
$as = new SimpleSAML_Auth_Simple('default-sp');
$as->requireAuth();
$attr=$as->getAttributes();
$name=$as->getAuthData("saml:sp:NameID");
?>
<html>
<body>
<h1>This is IdP Migration Page</h1>
<h2>You can change the IdP used for Single Sign-On</h2>
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
//$ret_url = 'https://'.$sp.'.local/sample/start.php';
$ret_url = $_POST["ret_url"];
//$ret_url2 = 'https://'.$sp.'.local/sample/complete.php';
$ret_url2 = $_POST["ret_url2"];

// ログイン中のユーザに該当するIDが存在するか確認する
// check if the user exists
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
		// Not registered user or log-in from new IdP
		// echo "引継 ID をお持ちであれば，入力してください<br>";
		echo "If you have your migration ID, please enter.<br>";
		$input_form = '<form action="https://ap.local/sample/movin2.php" method="post"><input type="text" name="mig_id" ><input type="submit" value="submit"></form>';
		echo $input_form;
		if($sp != NULL){
			echo "<br>※ If you don't have your migration ID, you may not be a registered user.<br>";
			echo "You need to migrate your IdP at the SP.<br>";
			echo "<a href=".$ret_url.">Back to SP</a>";
		}

		// 遷移元のSPおよびSPの引継完了画面URLをクッキーに保存する(暫定として1時間)
		// save the source SP and redirect URL in cookie (for 1 hour)
		setcookie("sp", $sp, time() + 60*60);
		setcookie("ret_url2", $ret_url2, time() + 60*60);
	}
	else{
		// APの引継IDがすでに発行済であるか確認する
		// check if the migration ID for AP has issued
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
				// if there is not migration ID for AP, issue it
				$mig_id = substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyz'), 0, 8);
				$query3 = "UPDATE users SET mig_id_ap = '$mig_id' WHERE uid = '$uid'";
                $result3 = mysqli_query($connection, $query3);
				if(!$result3){
					die ("[error5] Could not query the database: <br />".mysqli_error());
				}
			}
			else{
				// 引継IDがすでに発行済であればそれを用いる
				// if the migration ID exists, use it
				$mig_id = $mig_id_ap;
			}
		}
		echo '<br />Your migration ID : '.$mig_id;
        echo "<p>Please take a note of this migration ID, and log-in with new IdP</p>";
		$htmlchar = '<a href="logout.php">Log-in with New IdP<br /></a>';
		echo $htmlchar;
		echo "<p>You can also back to the SP in the case that your new IdP is not available now.<br>Please log-in with new IdP later. (The migration will not be completed now)<p>";
		echo '<a href='.$ret_url.'>Back to SP (without completing the migration)<br /></a>';

		// 遷移元のSPおよびSPの引継完了画面URLをクッキーに保存する(暫定として1時間)
		// save the source SP and redirect URL in cookie (for 1 hour)
		setcookie("sp", $sp, time() + 60*60);
        setcookie("ret_url2", $ret_url2, time() + 60*60);

		// 既に引越し済のユーザであるか確認する
		// check if this user has already migrated in another SP
		$query4 = "SELECT mig_id_".$sp.", mig_comp, srcIdP, dstIdP FROM users WHERE uid = '$uid'";
		$result4 = mysqli_query($connection, $query4);
		if (!$result4) {
			die ("[error6] Could not query the database: <br />".mysqli_error());
		}
		else {
			$result_row4 = mysqli_fetch_row($result4);
			$mig_id_sp = $result_row4[0]; // SPの引継ID; migration ID of SP
			$mig_comp = $result_row4[1];  // 引越しが完了していれば1; 1 if the migration completed
			$srcIdP = $result_row4[2];    // 引越し元IdP; migration-source IdP
			$dstIdP = $result_row4[3];    // 引越し先IdP: migration-destination IdP
			// 引越し済みユーザである場合
			// the case this user has completed the migration
			if ($mig_comp == 1 and $mig_id_sp != NULL and $srcIdP != NULL and $dstIdP != NULL) {
				//echo "※あなたは既に".$srcIdP."から".$dstIdP."へのお引越しを完了しています。<br />";
				echo "※ You have already completed the migration from ".$srcIdP." to ".$dstIdP.".<br />";
				//echo "このお引越情報で".$sp."のお引越しも行いますか？<br />";
				echo "Do you want to migrate the account for ".$sp.", too?<br />";
				$htmlchar0  = '<form action="'.$ret_url2.'"  method="post"><input type="hidden" name="mig_id_sp" value="'.$mig_id_sp.'"><input type="submit" value="Yes"></form>';
				echo $htmlchar0;
			}
			else {
				// 引越し元IdPを登録する
				// register the source-IdP
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
