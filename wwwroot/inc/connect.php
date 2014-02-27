<?php 
require_once 'pre-init.php';
require_once 'init.php';
$z = 0;
$r_0;//Name and ID
$r_1;//Ports, ID als Index
$r_2;//Verknüpfungs-Array
$r_3;//Link-Array
$r_4;
$r_2_b;
$r_test;
$id_test;
$device_list = 4;






//DATA FETCH
$fetch_0 = $dbxlink->prepare('SELECT id, name, objtype_id FROM Object');
$fetch_0->execute();
$r_0 = $fetch_0->fetchAll();
for ($x=0; $x<count($r_0);$x++){
$fetch_1 = $dbxlink->prepare('SELECT id, name, type FROM Port WHERE object_id='.$r_0[$x][0]);      
	$fetch_1->execute();
	$r_1[$r_0[$x][0]]= $fetch_1->fetchAll();                                                                                                                                              //$r_1[objekt-id][Port-Nummer][Properties];
}
$fetch_3 = $dbxlink->prepare('SELECT porta, portb FROM Link');
$fetch_3->execute();
$r_3=$fetch_3->fetchAll();
//DATA FETCH EOF


$r_0_cleaned=cleanArraybyPort($r_0,$r_1);
$keys=array_keys($r_0_cleaned);

for($a=0;$a<count($keys);$a++){
	$pos=strpos($r_0_cleaned[$keys[$a]][1],' - ');
	if($pos!==FALSE)$r_0_cleaned[$keys[$a]][1]=substr_replace($r_0_cleaned[$keys[$a]][1],'',0,$pos+2);
}

for($a=0;$a<count($selected);$a++){
$selected_ids[$a]=getIDbySelection($selected[$a],$r_0_cleaned,$keys);
}



$r_0_selected=ReturnSelectedSystems($r_0_cleaned,$selected_ids);

$r_test=WriteDataArrayRight($r_0_selected,$r_0_cleaned,$r_1,$r_3);



for($a=0;$a<count($r_test);$a++){
$r_test[$a][0]=trim($r_test[$a][0]);
$r_test[$a][1]=trim($r_test[$a][1]);

}







function getIDbyPort($r_1,$r_0,$port){
$keys=array_keys($r_0);
$ct=count(array_keys($r_0));
for($a=0;$a<$ct;$a++){
if(array_key_exists($keys[$a],$r_1)==true){for($b=0;$b<count($r_1[$keys[$a]]);$b++){
      if(isset($r_1[$keys[$a]][$b][0]))if ($port == $r_1[$keys[$a]][$b][0]){return($r_0[$keys[$a]][0]);}
}}
}
}

function getLink($port,$r_3){
for($h=0;$h<count($r_3);$h++){
 if ($r_3[$h][0] == $port)return $r_3[$h][1];
 if ($r_3[$h][1] == $port)return $r_3[$h][0];
}
}

function array2csv(array &$array)
{
   if (count($array) == 0) {
     return null;
   }
   ob_start();
   $df = fopen("php://output", 'w');
   fputcsv($df, array_keys(reset($array)));
   foreach ($array as $row) {
      fputcsv($df, $row, $delimiter = ',',chr(0) );
   }
   fclose($df);
   return ob_get_clean();
}   

function download_send_headers($filename) {
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download  
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
}

function cleanArraybyPort($r_0,$r_1){
for ($i=0;$i<count($r_0);$i++){
	{
	$id=$r_0[$i][0];
	if(hasPorts($r_1,$id))$hilf[$id]=$r_0[$i];
	}
}
return $hilf;
}
function ReturnSelectedSystems($r_0, $selected_ids){
 $keys=array_keys($r_0);
 for($a=0;$a<count($keys);$a++){
	$ind=$keys[$a];
	for($b=0;$b<count($selected_ids);$b++){
		if($r_0[$ind][0]==$selected_ids[$b])$arr[$ind]=$r_0[$ind];
 }}
return $arr;
}

function WriteDataArrayRight($r_0_selected,$r_0,$r_1,$r_3){
$keys = array_keys($r_0);
$keys_s = array_keys($r_0_selected);
$arr;
$count=0;
for($a=0;$a<count($keys_s);$a++){
    $id_alias=$r_0_selected[$keys_s[$a]][0];
    $name_alias=$r_0_selected[$keys_s[$a]][1];
	for($b=0;$b<count($r_1[$id_alias]);$b++){
                $port=$r_1[$id_alias][$b][0];
                $port_name=$r_1[$id_alias][$b][1];
                $link=getLink($port,$r_3);
                $link_obj_id=getIDbyPort($r_1,$r_0,$link);
		if(array_key_exists($link_obj_id,$r_0)){$link_obj_name=$r_0[$link_obj_id][1];
		$link_name=getLinkName($link,$r_1,$keys);
		$arr[$count][0]=$name_alias.'_'.$port_name.'                          '.$link_obj_name.'_'.$link_name;
                $arr[$count][1]=$link_obj_name.'_'.$link_name.'                          '.$name_alias.'_'.$port_name;		
		$count++;
		}}
}
return $arr;
}

function getLinkName($port,$r_1,$keys){
for($a=0;$a<count($keys);$a++){
      if(array_key_exists($keys[$a],$r_1))for($b=0;$b<count($r_1[$keys[$a]]);$b++){
		if($port==$r_1[$keys[$a]][$b][0])return $r_1[$keys[$a]][$b][1];
		}	
	}
}

//function hasPorts($r_1, $id){
//if (!empty($r_1[$id]))return TRUE;
//}

































?>
