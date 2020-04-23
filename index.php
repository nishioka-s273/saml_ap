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
<h1>This is an IdP Migration Service</h1>
<p>migration ID from the SP:
<?php
    $mig_id = $_POST["mig_id"];  // SPから送られた引継ID; migration ID from the SP
    echo $mig_id."<br />";

	$uid_idp = $attr['uid'][0].'_ap';  // IdPから受け取るユーザID; user ID from the IdP
	echo 'user ID from the IdP : '.$uid_idp.'<br/>';
    $idp = $attr['idp'][0];  // どのIdPを利用しているか; which IdP the user uses
	
	$from_sp = $_POST["sp"];  // which SP the user came from
	$ret_url = "https://".$from_sp.".local/sample/start.php";  // 最終的に戻るSPのURL; return URL
    $sp = 'mig_id_'.$from_sp;

	// サイトを訪れているユーザがAP内にアカウントを持っているか確認する
	// Check if the user has an account in the AP
    $query="SELECT uid FROM users WHERE uid_idp = '$uid_idp'";
    $result=mysqli_query($connection, $query);
    if (!$result){
	die ("[error3] Could not query the database: <br />".mysqli_error());
    }
    else{
	$result_row = mysqli_fetch_row($result);
	$uid = $result_row[0];  // AP内にアカウントが存在すれば，そのIDを取ってくる; Get the ID if the account exists
	if ($uid == NULL){
		// AP内にアカウントを持っていないので新たにユーザを作成する
		// No account exists, then create new user
		// 最新のユーザIDの番号を取ってくる
		// Get the latest uid_num
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
			$uid_num = $latest_num + 1;  // 新たに作成するユーザIDの番号; uid_num for new user
			$uid = 'ap_user'.$uid_num;  //新たに作成するユーザID; uid for new user
			$query2 = "INSERT INTO users VALUES ('$uid', NULL, NULL, 0, NULL, 0, NULL, NULL, '$uid_idp', '$uid_num')";
			$result2 = mysqli_query($connection, $query2);
			if(!$result2){
				die ("[error5] Could not query the database: <br />".mysqli_error());
			}
			else{
				// 遷移元SPの引継IDがすでに存在するか確認する
				// Check if the migration ID from SP exists or not
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
						// Set the migration ID from SP if not exists
						$query4 = "UPDATE users SET $sp = '$mig_id' WHERE uid_idp = '$uid_idp'";
						$result4 = mysqli_query($connection, $query4);
						if(!query4){
							die ("[error7] Could not query the database: <br />".mysqli_error());
						}
						else{
							echo "User Registration Completed!";
						}
					}
					else{
						echo "Your are the registrated user";
					}
				}
			}
		}
	}	
	else{
		// SPの引継IDが存在するか確認する
		// Check if the migration ID from SP exists
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
				// Set the migration ID from SP if not exists
				$query6 = "UPDATE users SET $sp = '$mig_id' WHERE uid_idp = '$uid_idp'";
				$result6 = mysqli_query($connection, $query6);
				if(!$result6){
					die ("[error9] Could not query the database: <br />".mysqli_error());
				}
				else{
					echo "Service Registration Completed!";
				}
			}
			else{
				$query2 = "INSERT INTO users VALUES ('$uid', NULL, NULL, 0, NULL, 0, NULL, NULL, '$uid_idp', '$uid_num')";
				echo "You are the registrated user";
			}
		}
	}
    }	
	    
    mysqli_close($connection);
?></p>
<a href="<?php echo $ret_url; ?>">Back to SP</a>
</body>
</html>
