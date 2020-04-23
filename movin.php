<?php
require_once('/var/www/simplesaml/lib/_autoload.php');
$as = new SimpleSAML_Auth_Simple('default-sp');
$as->requireAuth();
$attr=$as->getAttributes();
$name=$as->getAuthData("saml:sp:NameID");

// 遷移元のSPをクッキーから取得
if(isset($_COOKIE["sp"])){
	$sp = $_COOKIE["sp"];
}
?>
<html>
<body>
<h1>新規IdPの登録</h1>
<h2>引継IDを入力してください</h2>
<form action="https://ap.local/sample/movin2.php" method="post">
<input type="text" name="mig_id" >
<input type="submit" value="送信" >
</form>
</body>
</html>
