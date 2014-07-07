<?php
//Skript um allen Servern eines bestimmten Namens den Hypervisor zuzuordnen
require_once 'init.php';
$filter_string = "";
$query = usePreparedSelectBlade('SELECT id FROM Object WHERE name LIKE "'.$filter_string.'"');
$res = $query -> fetchAll();
foreach($res as $row){
	usePreparedExecuteBlade('INSERT IGNORE INTO AttributeValue SET object_id='.$row[0].', object_tid=4, attr_id = 26, string_value=NULL, uint_value=1501, float_value=NULL');
}
?>