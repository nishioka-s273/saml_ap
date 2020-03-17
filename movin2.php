<?php
require_once('/var/www/simplesaml/lib/_autoload.php');
$as = new SimpleSAML_Auth_Simple('default-sp');
$as->requireAuth();
$attr=$as->getAttributes();
$name=$as->getAuthData("saml:sp:NameID");

include('db_login.php');
$connection = mysql_connect($db_host, $db_username, $db_password);
if(!$connection){
  die ("Could not connect to the database: <br />". mysql_error());
}
$db_select = mysql_select_db($db_database);
if(!$db_select){
  die ("Could not select the database: <br />". mysql_error());
}

$sp = '';

// 遷移元のSPをクッキーから取得
if (isset($_COOKIE["sp"])){
	$sp = $_COOKIE["sp"];
}
// 引継完了後に遷移するSPのURL
$sp_url = "http://".$sp.".local/sample/comp.php";

$mig_id = $_POST["mig_id"];

$uid_idp = $attr['uid'][0];
// $new_uid = $uid.'_ap';

$idp = $attr['idp'][0];
//$uid_idp = 'uid_'.$idp;

// ユーザが入力した引継IDに該当するAP内ユーザを検索
$query = "SELECT uid FROM users WHERE mig_id_ap = '$mig_id'";
$result = mysql_query($query);
if(!$result){
	die ("Could not query the database: <br />".mysql.error());
}
else{
	$result_row = mysql_fetch_row($result);
 	$uid_ap = $result_row[0];
	// 該当の引継IDが存在しない場合引継IDが間違っている
	if($uid_ap == NULL){
		echo "引継IDが間違っています";
	}
	else{
		// 検索結果のユーザに対して，新しいIdPから受け取ったユーザIDを登録する
		$query2 = "UPDATE users SET uid_idp = '$uid_idp' WHERE mig_id_ap = '$mig_id'";
		$result2 = mysql_query($query2);
		if(!$result2){
			die ("Could not query the database: <br />".mysql.error());
		}
		else{
			// 該当ユーザの遷移元SPに対する引継IDを取得する
			$query3 = "SELECT mig_id_".$sp." FROM users WHERE mig_id_ap = '$mig_id'";
			$result3 = mysql_query($query3);
			if(!$result3){
				die ("Could not query the database: <br>".mysql_error());
			}
			else{
				$result_row3 = mysql_fetch_row($result3);
				$mig_id_sp = $result_row3[0];
				// SPの引継IDが存在しない場合，登録できていない
				if($mig_id_sp == NULL){
					echo "このSPは引越しサービスに登録されていません";
				}
				else{
					// 引越し完了かどうか，引越し先IdPなどを登録する
					$query5 = "UPDATE users SET mig_comp = 1 WHERE mig_id_ap = '$mig_id'";
					$query6 = "UPDATE users SET dstIdP = '$idp' WHERE mig_id_ap = '$mig_id'";
					$result5 = mysql_query($query5);
					$result6 = mysql_query($query6);
					if(!$result5 or !$result6) {
						die ("Could not query the database: <br />".mysql_error());
					}
					else {
						echo "IdPの引越しが完了しました！<br />";
						echo $mig_id_sp;

						// 該当の引継IDをSPに送信する
						$htmlchar = '<form action="'.$sp_url.'" method="post"><input type="hidden" name="mig_id_sp" value="'.$mig_id_sp.'"><input type="submit" value="SPに戻って引越しを終了する"></form>';
						echo $htmlchar;
					}
				}
			}
		} 
	}
}
?>
