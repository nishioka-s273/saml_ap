<?php
require_once('/var/www/simplesaml/lib/_autoload.php');
$as = new \SimpleSAML\Auth\Simple('default-sp');
$as->requireAuth();
$attr = $as->getAttributes();

include('db_login.php');

$connection = mysqli_connect($db_host, $db_username, $db_password);
if(!$connection){
  die ("[error1] Could not connect to the database: <br />". mysqli_error());
}

$db_select = mysqli_select_db($connection, $db_database);
if(!$db_select){
  die ("[error2] Could not select the database: <br />". mysqli_error());
}
?>
<html>
<body>
<h1>IdPお引越しサービス</h1>
<h2>お引越しサービスへの登録が完了しました！</h2>
<p>引継ID:
<?php
    $mig_id = $_POST["mig_id"];  // SPから送られた引継ID
    echo $mig_id."<br />";

	$uid_idp = $attr['uid'][0].'_ap';  // IdPから受け取るユーザID
	echo $uid_idp;
    $idp = $attr['idp'][0];  // どのIdPを利用しているか
    // $idp = 'uid_'.$_POST["idp"];  // どのIdPを利用しているか(uid_idpXXの形)

    $from_sp = $_POST["sp"];  // どのSPから来たか
    $ret_url = "https://".$from_sp.".local/sample/start.php";  // 最終的に戻るSPのURL
    $sp = 'mig_id_'.$from_sp;

    // サイトを訪れているユーザがAP内にアカウントを持っているか確認する
    $query="SELECT uid FROM users WHERE uid_idp = '$uid_idp'";
    $result=mysqli_query($connection, $query);
    if (!$result){
	die ("[error3] Could not query the database: <br />".mysqli_error());
    }
    else{
	$result_row = mysqli_fetch_row($result);
	$uid = $result_row[0];  // AP内にアカウントが存在すれば，そのIDを取ってくる
	if ($uid == NULL){
		// AP内にアカウントを持っていないので新たにユーザを作成する
		// 最新のユーザIDの番号を取ってくる
		$query1 = "SELECT uid_num FROM users ORDER BY uid_num DESC LIMIT 1";
		$result1 = mysqli_query($connection, $query1);
		if(!$result1) {
			die ("[error4] Could not query the database: <br />".mysqli_query());
		}
		else {
			$result_row1 = mysqli_fetch_row($result1);
			if ($result_row1[0] == NULL) {
				$latest_num = 0;
			}
			else {
				$latest_num = $result_row1[0];
			}
			$uid_num = $latest_num + 1;  // 新たに作成するユーザIDの番号
			$uid = 'ap_user'.$uid_num;  //新たに作成するユーザID
			$query2 = "INSERT INTO users VALUES ('$uid', NULL, NULL, 0, NULL, 0, NULL, NULL, '$uid_idp', '$uid_num')";
			$result2 = mysqli_query($connection, $query2);
			if(!$result2){
				die ("[error5] Could not query the database: <br />".mysqli_error());
			}
			else{
				// 遷移元SPの引継IDがすでに存在するか確認する
				$query3 = "SELECT $sp FROM users WHERE uid_idp = '$uid_idp'";
				$result3 = mysqli_query($connection, $query3);
				if(!result3){
					die ("[error6] Could not query the database: <br />".mysqli_error());
				}
				else{
					$result_row3 = mysqli_fetch_row($result3);
					$migid = $result_row3[0];
					if($migid == NULL){
						// SPの引継IDが存在しなければ新たに設定する
						$query4 = "UPDATE users SET $sp = '$mig_id' WHERE uid_idp = '$uid_idp'";
						$result4 = mysqli_query($connection, $query4);
						if(!query4){
							die ("[error7] Could not query the database: <br />".mysqli_error());
						}
						else{
							echo "ユーザ登録が完了しました";
						}
					}
					else{
						echo "ユーザ登録済です";
					}
				}
			}
		}
	}	
	else{
		// SPの引継IDが存在するか確認する
		$query5 = "SELECT $sp FROM users WHERE uid_idp = '$uid_idp'";
		$result5 = mysqli_query($connection, $query5);
		if(!result5){
			die ("[error8] Could not query the database: <br />".mysqli_error());
		}
		else{
			$result_row5 = mysqli_fetch_row($result5);
			$migid = $result_row5[0];
			if($migid == NULL){
				// SPの引継IDが存在しなければ新たに設定する
				$query6 = "UPDATE users SET $sp = '$mig_id' WHERE uid_idp = '$uid_idp'";
				$result6 = mysqli_query($connection, $query6);
				if(!$result6){
					die ("[error9] Could not query the database: <br />".mysqli_error());
				}
				else{
					echo "お引越しサービス登録が完了しました";
				}
			}
			else{
				$query2 = "INSERT INTO users VALUES ('$uid', NULL, NULL, 0, NULL, 0, NULL, NULL, '$uid_idp', '$uid_num')";
				echo $query2;
				echo "<br/>お引越しサービス登録済です";
			}
		}
	}
    }	
	    
    mysqli_close($connection);
?></p>
<a href="<?php echo $ret_url; ?>">SPに戻る</a>
</body>
</html>
