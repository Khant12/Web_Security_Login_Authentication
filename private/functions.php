<?php

function get_random_string($length)
{
	$array = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
	$text = "";

	$length = rand(4, $length);

	for($i=0; $i<$length; $i++){

		$random = rand(0, 61);
		$text .= $array[$random];

	}
	return $text;


}


function esc($word){

	return addslashes($word);  
}

function check_login($connection){

	if(isset($_SESSION['url_address']))
	{

		$arr['url_address'] = $_SESSION['url_address'];

		$query = "select * from users where url_address = :url_address limit 1";
		$stm = $connection->prepare($query);
		$check = $stm->execute($arr);

		if($check){

			$data = $stm->fetchAll(PDO::FETCH_OBJ);

			if(is_array($data) && count($data) > 0){

				return $data[0];
					
			}

		}
	}

	header("Location: login.php");
	die;

}



function getIpAddr() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}


?>


