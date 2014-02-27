
<?php 









$tab['rackspace']['LabelExport'] = ' Label Export';
$tabhandler['rackspace']['LabelExport'] = 'lexport_tabhandler';



function lexport_tabhandler($object_id) {
        global $lm_cache;
        $target = makeHrefProcess(portlist::urlparams('op','update'));
        addJS('js/jquery.jeditable.mini.js');
        $lm_cache['allowcomment'] = permitted(NULL, NULL, 'set_reserve_comment');
        $lm_cache['allowlink'] = permitted(NULL, NULL, 'set_link');
        if($lm_cache['allowcomment'])
                addJS('$(document).ready(function() { $(".editcmt").editable("'.$target.'",{placeholder : "add comment"}); });' , TRUE);

        if($lm_cache['allowlink'])
                addJS('$(document).ready(function() { $(".editcable").editable("'.$target.'",{placeholder : "edit cableID"}); });' , TRUE);
	

	$children = getEntityRelatives ('children', 'object', $object_id);
//------------------------------------------------------//

				
//$fetch_0 = $dbxlink->prepare('SELECT id, name, has_problems FROM Object');
$fetch_0 = usePreparedSelectBlade('SELECT id, name, has_problems FROM Object');
//$fetch_0->execute();
$r_0 = $fetch_0->fetchAll();
$keys=array_keys($r_0);

$fetch_mnt = usePreparedSelectBlade('SELECT id, to_mount FROM object_state_information');
$r_mnt = $fetch_mnt->fetchALL();


for ($x=0; $x<count($r_0);$x++){
$fetch_1 = usePreparedSelectBlade('SELECT id, name, type FROM Port WHERE object_id='.$r_0[$x][0]);      
	$r_1[$r_0[$x][0]]= $fetch_1->fetchAll();                                                                                                                                           //$r_1[objekt-id][Port-Nummer][Properties];
}

$keys=array_keys($r_0);

$r_0_mod=FilterNames($r_0,$keys,$r_mnt);

$keys_mod=array_keys($r_0_mod);

$b=0;
for ($a=0;$a<count($keys_mod);$a++){
$ind=$keys_mod[$a];
$id=$r_0_mod[$ind][0];
if(hasPorts($r_1, $id))$r_names[$b++]=$r_0_mod[$ind][1];
}
if(isset($r_names))natsort($r_names);


startPortlet('Label Export');  
?>


<html>



<style type="text/css">
	div.tablelabel{ margin-top:25px; margin-left:25px;

<!--border-style: solid; border-width: 155px 120px;
    -moz-border-image: url(8bit-Nick-Cage.jpg)i 155 120 round;
 -webkit-border-image: url(8bit-Nick-Cage.jpg) 155 120 round;
      -o-border-image: url(8bit-Nick-Cage.jpg) 155 120 round; 
         border-image: url(8bit-Nick-Cage.jpg) 155 120 fill round;-->
 }
	select {
		width: 300px;
	}
	input.type-search{
			width: 300px;}
</style>







<div class="tablelabel">
<table border="0" width="100%">
<tr>
<td>
<form action="http://racktables.she.local/index.php?page=rackspace&tab=LabelExport" method="post">
<input type="checkbox" class="ckbox" name="prob" value="test" <?php if(isset($_POST['prob']))echo ' checked'; ?>  >&nbsp;Has Problems<br>
<input type="checkbox" class="ckbox" name="mnt" value="mnt" <?php if(isset($_POST['mnt']))echo ' checked'; ?>> &nbsp;To Mount<br>
<br><br>
<input type="submit" value="Filtern">
</form>
</td>
<td>

<form action="inc/intermediate.php" method="post">
  <p>
    <input class="type-search" type="text" id="searchInput" /><br>
    <select name="select[]" size="20" multiple="multiple">
      <?php
	$k=array_keys($r_names);
	for($a=0;$a<count($r_names);$a++){
	echo('<option>'.$r_names[$k[$a]].'</option>');
	}
           ?>
    </select>
  </p>
<p><input type="submit" value="Get Labels!" /></p>
</form>
</td>
</tr>

</table>

<div align="left">
<br>
<a href="http://wiki.she.de/index.php/Racktables-Labels" target="_blank">Anleitung zur Benutzung</a>
</div>
</div>
<script src="js/surch.js" type="text/javascript"></script>
<script src="js/jquery-1.4.4.min.js" type="text/javascript"></script>
</html>



<?php
finishPortlet();	
		
//------------------------------------------------------//

		
		
		
        return;

} /* tabhandler */

?>
