<?php
$selected_raw=$_POST['select'];
$selected=cleanSelected($selected_raw);
$selected_ids;
require_once('connect.php');

for($a=0;$a<count($selected);$a++){
$selected_ids[$a]=getIDbySelection($selected[$a],$r_0_cleaned,$keys);
}

//for($a=0;$a<count($selected);$a++){
//$selected_ids[$a]=getIDbySelection($selected[$a],$r_0_cleaned,$keys);
//}
//print_r($selected_ids);

$c=0;
for($a=0;$a<count($selected);$a++)$selected[$a]=trim($selected[$a]);

for($a=0;$a<count($selected);$a++){
	for($b=0;$b<count($r_test);$b++){
		if((strpos($r_test[$b][0],$selected[$a])!==FALSE)&&(strpos($r_test[$b][0],$selected[$a])==0)){
			$r_final[$c][0]=$r_test[$b][0];
			$r_final[$c][1]=$r_test[$b][1];
			$c++;}
		}
	}
download_send_headers("label_export_" . date("Y-m-d") . ".csv");
echo array2csv($r_final);
die();


function getIDbySelection($selected1,$r_0,$keys){
        for($b=0;$b<count($keys);$b++){
	$ind=$keys[$b];
	$id=$r_0[$ind][0];
	$name=$r_0[$ind][1];
		if(strpos($name, $selected1)!==FALSE)return $id;	
	}

}

function cleanSelected($selected){
$keys=array_keys($selected);
for($a=0;$a<count($keys);$a++){
	$ind=$keys[$a];
	$pos=strpos($selected[$ind],' - ');
	if($pos!==FALSE)$selected[$ind]=substr_replace($selected[$ind],'',0,$pos+2);
}
return $selected;
}		

?>
