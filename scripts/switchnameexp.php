<?php
require_once('init.php');
$fetch_link = $dbxlink->prepare('SELECT porta,portb FROM Link');
$fetch_link->execute();
$r_link = $fetch_link->fetchAll();

$fetch_p_full = $dbxlink->prepare('SELECT name,reservation_comment,id,object_id FROM Port');
$fetch_p_full->execute();
$r_p_full = $fetch_p_full->fetchAll();


$fetch_obj = $dbxlink->prepare('SELECT id,name FROM Object WHERE objtype_id=8');
$fetch_obj->execute();
$r_obj = $fetch_obj->fetchAll();

$fetch_obj_full = $dbxlink->prepare('SELECT id,name FROM Object');
$fetch_obj_full->execute();
$r_obj_full = $fetch_obj_full->fetchAll();
//print_r($r_obj_full);

for($i = 0; $i < count($r_obj); $i++){
$fetch_sw = $dbxlink->prepare('SELECT name,reservation_comment,id,object_id FROM Port WHERE object_id = '.$r_obj[$i][0]);
$fetch_sw->execute();
$r_sw = $fetch_sw->fetchAll();

$r_sw_rew = rewriteArray($r_sw);
sort($r_sw_rew);
$r_names;
//print_r($r_sw);
$count = 0;
for($j = 0; $j < count($r_sw_rew); $j++){
        $key = returnKey($r_sw, $r_sw_rew[$j]);
        if($r_sw[$key][1] === NULL){
		$p_string = getLinkName($r_sw[$key][2], $r_link, $r_sw, $r_obj, $r_p_full, $r_obj_full);		
                if($p_string !== FALSE){
		$r_names[$count] = ('int ' . $r_sw_rew[$j] . ' name "'. $p_string.'"') ;
                $count++;}
		}else{
				
		}
}
print('====================='.$r_obj[$i][1] .'=====================<br>');
for($k = 0; $k < count($r_names); $k++)print($r_names[$k].'<br>');
}









function getLinkName($p, $link, $r_sw, $r_obj, $r_p_full, $r_obj_full){
	$p_id = getLinkID($p, $link);
	if($p_id === FALSE)return FALSE;
	//print($p_id.'<br>');
	$obj_id   = getObjectbyPort($p_id, $r_p_full);
	//print('obj: ' .$obj_id.'<br>');
	$obj_name = getObjectName($obj_id, $r_obj_full);
	//print($obj_name.'<br>');
	$name_string = $obj_name.'_'.getPortName($p_id, $r_p_full);
	return $name_string;
}

function getObjectbyPort($p, $r_p_full){
	for($i = 0; $i<count($r_p_full); $i++){
		if($p == $r_p_full[$i][2])return $r_p_full[$i][3];
	}return false;
} 

function getObjectName($id, $r_obj_full){
	for($i = 0; $i < count($r_obj_full); $i++){
		if($id == $r_obj_full[$i][0]){
			return $r_obj_full[$i][1];}
	}return false;
}

function getLinkID($p, $link){
	for($i = 0; $i < count($link); $i++){
		if($p == $link[$i][0])return $link[$i][1];
		elseif($p == $link[$i][1])return $link[$i][0];
	}return false;
}

function getPortName($p, $r_p_full){
	for($i = 0; $i < count($r_p_full); $i++){
		if($p == $r_p_full[$i][2])return $r_p_full[$i][0];
	}return false;
}
function rewriteArray($r){
$count = 0;
$res = NULL;
for($i = 0; $i < count($r); $i++){
        $res[$count] = $r[$i][0];
        $count++;
}

return $res;
}

function returnKey($r, $name){
for($i = 0; $i < count($r); $i++){
        if($r[$i][0] == $name)return $i;
}
return FALSE;
}
?>
