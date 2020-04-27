<?php
require_once('/var/www/simplesaml/lib/_autoload.php');
$as = new SimpleSAML_Auth_Simple('default-sp');
$as->requireAuth();
$attr=$as->getAttributes();
$name=$as->getAuthData("saml:sp:NameID");

// 遷移元のSPをクッキーから取得
// get the source SP by cookie
if (isset($_COOKIE["sp"])){
	$sp = $_COOKIE["sp"];
}
if(isset($_COOKIE["ret_url2"])){
	$ret_url2 = $_COOKIE["ret_url2"];
}
?>
<html>
<body>
<h1>Registration of new IdP</h1>
<h2>Please enter your migration ID</h2>
<form action="https://ap.local/sample/movin2.php" method="post">
<input type="text" name="mig_id" >
<input type="submit" value="submit" >
</form>
</body>
</html>
