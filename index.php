<?php
session_start();

include('SimpleCommands.class.php');


$simpleConsole = new SimpleCommands('C:\wamp\www\SimpleCommands\commands');
if (isset($_POST['btn_executar'])){
	if (!empty($_POST['txt_console'])){
		if ($simpleConsole->validateCommand($_POST['txt_console']) == true){
			$_SESSION['log'] = $_SESSION['log'].$simpleConsole->executeCommand($_POST['txt_console']);
		} else {
			echo '1<br />';
		}
	}
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>SimpleCommands V1.0 Alpha</title>
	</head>
	<body>
		<textarea style="width: 98%; height: auto; margin-bottom: 20px; margin-top: 10px;" ><?php	echo utf8_decode($_SESSION['log']); ?></textarea>
		
		<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST" style="margin-right: 10px; width: auto;">
			<input type="text" name="txt_console" id="txt_executar" style="margin-right: 30px; margin-left: 10px;" />
			<input style=" margin-right: 10px;" type="submit" name="btn_executar" id="btn_executar" />
		</form>
	</body>
</html>