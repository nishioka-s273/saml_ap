<?php
require_once('/var/www/simplesaml/lib/_autoload.php');
$as = new SimpleSAML_Auth_Simple('default-sp');
$as->requireAuth();
$attr=$as->getAttributes();
$name=$as->getAuthData("saml:sp:NameID");

include('db_login.php');
$connection = mysqli_connect($db_host, $db_username, $db_password);
if(!$connection){
  die ("[error1] Could not connect to the database: <br />". mysqli_error());
}
$db_select = mysqli_select_db($connection, $db_database);
if(!$db_select){
  die ("[error2] Could not select the database: <br />". mysqli_error());
}

$sp = '';
$ret_url2 = '';

// 遷移元のSPをクッキーから取得
// get the source SP by cookie
if (isset($_COOKIE["sp"])){
	$sp = $_COOKIE["sp"];
}
if (isset($_COOKIE["ret_url2"])){
	// 引継完了後に遷移するSPのURL
	// redirect URL of SP after migration completion
	$sp_url = $_COOKIE["ret_url2"];
}

//$sp_url = "https://".$sp.".local/sample/complete.php";

$mig_id = $_POST["mig_id"];

$uid_idp = $attr['uid'][0].'_ap';

$idp = $attr['idp'][0];

// ユーザが入力した引継IDに該当するAP内ユーザを検索
// Search the user corresponding to the migration ID
$query = "SELECT uid FROM users WHERE mig_id_ap = '$mig_id'";
$result = mysqli_query($connection, $query);
if(!$result){
	die ("[error3] Could not query the database: <br />".mysqli_error());
}
else{
	$result_row = mysqli_fetch_row($result);
 	$uid_ap = $result_row[0];
	// 該当の引継IDが存在しない場合引継IDが間違っている
	// if no user corresponding the migration ID, the user may entered wrong migration ID
	if($uid_ap == NULL){
		echo "You have entered wrong migration ID";
	}
	else{
		// 検索結果のユーザに対して，新しいIdPから受け取ったユーザIDを登録する
		// register the user ID from new IdP as uid_idp, for the user correspoinding to the migration ID
		$query2 = "UPDATE users SET uid_idp = '$uid_idp' WHERE mig_id_ap = '$mig_id'";
		$result2 = mysqli_query($connection, $query2);
		if(!$result2){
			die ("[error4] Could not query the database: <br />".mysqli_error());
		}
		else{
			// 該当ユーザの遷移元SPに対する引継IDを取得する
			// get the migration ID for source SP
			$query3 = "SELECT mig_id_".$sp." FROM users WHERE mig_id_ap = '$mig_id'";
			$result3 = mysqli_query($connection, $query3);
			if(!$result3){
				die ("[error5] Could not query the database: <br>".mysqli_error());
			}
			else{
				$result_row3 = mysqli_fetch_row($result3);
				$mig_id_sp = $result_row3[0];
				// SPの引継IDが存在しない場合，登録できていない
				// if no migration ID for the SP exists, registration has not completed
				if($mig_id_sp == NULL){
					echo "You have not registered this SP for this migration service.";
				}
				else{
					// 引越し完了かどうか，引越し先IdPなどを登録する
					// register the destination IdP and mig_comp(if the migration has completed or not)
					$query5 = "UPDATE users SET mig_comp = 1 WHERE mig_id_ap = '$mig_id'";
					$query6 = "UPDATE users SET dstIdP = '$idp' WHERE mig_id_ap = '$mig_id'";
					$result5 = mysqli_query($connection, $query5);
					$result6 = mysqli_query($connection, $query6);
					if(!$result5 or !$result6) {
						die ("[error6] Could not query the database: <br />".mysqli_error());
					}
					else {
						echo "Migration for IdP has completed!<br />";

						// 該当の引継IDをSPに送信する
						// send the migration ID to SP
						$htmlchar = '<form action="'.$sp_url.'" method="post"><input type="hidden" name="mig_id_sp" value="'.$mig_id_sp.'"><input type="submit" value="Return to SP and Complete the Migration"></form>';
						echo $htmlchar;
					}
				}
			}
		} 
	}
}
?>
