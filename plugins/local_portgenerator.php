<?php
//
// This extension for RackTables is used to generate ports based on object type and hardware type
//
// Version 1.1
//
// Created by Jeroen Benda
// 04-2010
// http://racktablescode.j-tools.net
//
// Please do not remove the credentials if you alter this file.
//
//-----------------------------------
//Version 1.2
//Revised by Jorge Sanchez
//04-2011
//------------------------------------
//Changes:
//------------------------------------
//****The php file has been changed to work with 0.18.7 and the 0.19.x****
//
//****Note: you need to place process.php into the wwwroot directory from 0.18.7 for local_portgenerator to work.****
//
//****Also, make sure to include port_generator in the local.php file****
//
//First: $result variable no longers equals function useSelectBlade but uses function usePreparedSelectBlade because of the changes since
//0.17.x files for rendering objects. Also with that, the FUNCTION included with useSelectBlade has been removed
//
//Second: The orientation of the table has been altered to have each dictionary entry be rendered
//in a cell instead of fifteen in one cell. This makes the table look more neat and easier to read
//
//Third: Information of how to use the port generator has been added such as an EXAMPLE, an EXPLANATION of the EXAMPLE, and a note to allow port generator to render
//to port after the link has been pressed.
//
//Fourth: the autoport dialog box has been moved from below the table to the top of the table for easier accessibility
//
//Fifth: for some reason, the label would not update when the link was pressed so no labels would be present even if you added them to the Port Generator. To fix this,
//at line 443, inside of the commitaddPort variable, needed to add $aPort['label'] to allow it to function correctly
//----------------------------------
//Working On
//----------------------------------
//One: the padding with whitespace for each needs to be incorporated so that each cell is the same size. This is due to the browser more than anything else.
//
//Version 1.3
//Revised by Jorge Sanchez
//04-2011
//------------------------------------
//Changes:
//------------------------------------
//***No longer needs the process.php file to work. ***
//
//Version 1.4
//Revised by Marian Stetina, Viktor Daniel
//02-2012
//------------------------------------
//Changes:
//------------------------------------
//*** Added support for PortInnerInterface ***
//
//Version 1.5f
//Revised by Florian Baier
//07-2014
//------------------------------------
//Changes:
//------------------------------------
//***Added a Panel for a direct adding of Ports with different port types***
//***Rewritten some Parts to fit 0.20.8***
//***Excluded all Port Types by default, fill in your desired***
//NOTE: You need to fill in all Port Type IDs you want to use at the bottom of this file
$tab['object']['portgenerator'] = 'Port generator';
$trigger['object']['portgenerator'] = 'localtrigger_PortGenerator';
$tabhandler['object']['portgenerator'] = 'localfunc_PortGenerator';
$ophandler['object']['portgenerator']['updateportgenerator'] = 'updateconfig_PortGenerator';
$ophandler['object']['portgenerator']['addporteditor'] = 'addports_full';
$ophandler['object']['ports']['addports'] = 'localexecute_PortGenerator';

//
// Check whether the variables are set or otherwise set the default values.
//
global $noPortGenerator;
if (!isset($noPortGenerator)) {
  $noPortGenerator = array();
}
global $tablePortGenerator;
if (!isset($tablePortGenerator)) {
  $tablePortGenerator = "AutoPort";
}
if (!defined("_portGeneratorHWType")) {
  define("_portGeneratorHWType",2);
}
if (!defined("_portGeneratorNumberOfPorts")) {
  define("_portGeneratorNumberOfPorts",6);
}
//
// Check whether the table exists. If not, create it
//
function checkForTable ()
{
  global $tablePortGenerator;
  $result = usePreparedSelectBlade ("SHOW TABLES LIKE '{$tablePortGenerator}'");
  if ($result==NULL) { print_r($dbxlink->errorInfo()); die(); }
  if (!($row = $result->fetch (PDO::FETCH_NUM))) {
    $q = "CREATE TABLE IF NOT EXISTS `{$tablePortGenerator}` (
`dict_key` int(10) unsigned NOT NULL auto_increment,
`autoportconfig` text NOT NULL,
UNIQUE KEY `dict_key` (`dict_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    $result = usePreparedSelectBlade ($q);
    if ($result==NULL) { print_r($dbxlink->errorInfo()); die(); }
  }
}

//
// function to check whether a tab Port generator should be shown
// If there are already ports configured or the object type id is in $noPortGenerator
// (which is an array of object type ids) then do not show the tab
//
function localtrigger_PortGenerator()
{
  global $noPortGenerator;
        assertUIntArg ('object_id', __FUNCTION__);
        $object = spotEntity ('object', $_REQUEST['object_id']);
  $record = getObjectPortsAndLinks ($object['id']);
  if (count($record)==0 && !in_array($object['objtype_id'],$noPortGenerator))
                //return 1;
                return 'std';
        else
        {
                return '';
        }
}

//
// This function checks whether there is a configuration available for the selected object type id
// (and if necessary for the hardware type id which is found as attribute id _portGeneratorHWType
function localverify_PortGenerator($object) {
  global $tablePortGenerator, $errorText, $lookFor, $portList, $genText, $valueConfiguration, $searchIt;
  checkForTable();
  $foundError = true;
  $record = getObjectPortsAndLinks ($object['id']);
  //
  // Make sure that there are no ports configured
  //
  if (count($record)==0) {
    //
    // Check whether the object type for the selected object has an attribute hardware type
    // If it does, use the hardware type for configuration. Otherwise use the generic object type
    //
    $lookFor = "Hardware type";
    $q = "SELECT * FROM AttributeMap WHERE attr_id="._portGeneratorHWType." AND objtype_id={$object['objtype_id']} ";
    $result = usePreparedSelectBlade ($q);
    if ($result==NULL) { print_r($dbxlink->errorInfo()); die(); }
    if ($row = $result->fetch (PDO::FETCH_NUM)) {
      //
      // There is a hardware type available for this object type. Check whether it is set
      // If it is, search for the specific port configuration for that hardware type
      // If it is not set, use the generic port configuration for the object type
      //
      $q = "SELECT uint_value, dict_value FROM AttributeValue, Dictionary ";
      $q .= "WHERE attr_id="._portGeneratorHWType." AND object_id={$object['id']} AND dict_key=uint_value ";
      $result = usePreparedSelectBlade ($q);
      if ($result==NULL) { print_r($dbxlink->errorInfo()); die(); }
      if ($row = $result->fetch (PDO::FETCH_NUM)) {
        $searchIt = $row[0];
        $searchText = "OK (based on hardware type)";
        $searchType = 3;
@        $genText = "{$object['objtype_name']}: {$row[1]}";
        $genText = str_replace(array("%GPASS%")," ",$genText);
      } else {
        $searchIt = $object['objtype_id'];
        $searchText = "Based on object type, hardware type not set";
        $searchType = 2;
        $genText = "<b>Anleitung</b> ";//{$object['objtype_name']}";
      }
    } else {
      $searchIt = $object['objtype_id'];
      $searchText = "OK (based on object type)";
      $searchType = 1;
      $genText = "{$object['objtype_name']}";
    }
    $lookFor = "Autoport configuration";
    $q = "SELECT autoportconfig FROM {$tablePortGenerator} WHERE dict_key={$searchIt} ";
    $result = usePreparedSelectBlade ($q);
    if ($result==NULL) { print_r($dbxlink->errorInfo()); die(); }
    //
    // Check if there is an autoport configuration for the requested key
    //
    if ($valueConfiguration = $result->fetch (PDO::FETCH_NUM)) {
      $lookFor = "Configuration";
      $q = "SELECT uint_value FROM AttributeValue ";
      $q .= "WHERE attr_id="._portGeneratorNumberOfPorts." AND object_id={$_REQUEST['object_id']} ";
      //
      // Check for the value of the number of ports. If it is not found set it to 0
      //
      $result = usePreparedSelectBlade ($q);
      if ($result==NULL) { print_r($dbxlink->errorInfo()); die(); }
      if ($valueNumberOfPorts = $result->fetch (PDO::FETCH_NUM)) {
      } else {
        $valueNumberOfPorts[0] = 0;;
      }
      //
      // $portList will contain the list of ports to be generated
      //
      $portList = array();
      //
      // $portOrders array will be filled with individual port generation schemes
      // <start port #>|<port count, use %n for number of ports>|<port name, use %u for number>|<port type id>[|<port label, use %u for number>]
      // The configuration contains a semicolon seperated list of the schemes
      //
      // An example of this would be:
      // 1|2|pwr%u|16;1|%n|eth%u|24|%u
      //
      $portOrders = explode(";",$valueConfiguration[0]);
      if (count($portOrders)>0) {
        $orderCnt = 0;
        foreach ($portOrders as $aPortOrder) {
          //
          // Split up each scheme and check for errors
          // If there are not errors, populate the $portList
          //
          $orderCnt++;
          $thisOrder = explode("|",$aPortOrder);
          if (count($thisOrder)==4 || count($thisOrder)==5) {
            if ($thisOrder[1]!="%n" || $valueNumberOfPorts[0]!=0) {
              if ($thisOrder[1]=="%n") {
                $thisOrder[1] = $valueNumberOfPorts[0];
              }
              if ($thisOrder[1]==1 || strpos($thisOrder[2],"%u")!==false) {
               if (preg_match ('/^([[:digit:]]+)-([[:digit:]]+)$/', $thisOrder[3], $matches))
                         $oif_id = $matches[2];
               else
                  $oif_id = $thisOrder[3];
                $q = "SELECT oif_id FROM PortInterfaceCompat WHERE oif_id='$oif_id'";
                $result = usePreparedSelectBlade ($q);
                if ($result==NULL) { print_r($dbxlink->errorInfo()); die(); }
                if ($row3 = $result->fetch (PDO::FETCH_NUM)) {
                  for ($i=1;$i<=$thisOrder[1];$i++) {
                    $ii = $thisOrder[0]+$i-1;
                    $name = str_replace("%u",$ii,$thisOrder[2]);
                    if (count($thisOrder)==5) {
                      $label = str_replace("%u",$ii,$thisOrder[4]);
                    } else {
                      $label = "";
                    }
                    $portList[] = array("name"=>$name,"port_id"=>$thisOrder[3],"port_name"=>$row3[0],"label"=>$label);
                  }
                } else {
                  $errorText = "Port type {$thisOrder[3]} is not found or not a port type.";
                }
              } else {
                $errorText = "Config part {$orderCnt} wants more than 1 port without using %u parameter.";
              }
            } else {
              $errorText = "Config part {$orderCnt} refers to <i>HW Number of Ports</i> but that is not defined or 0.";
            }
          } else {
            $errorText = "Config part {$orderCnt} does not have 4 parts seperated by a |";
          }
        }
        if (!isset($errorText)) {
          $foundError = false;
        }
      } else {
        $errorText = "Autoport configuration for this dictionary key ({$searchIt}) is empty.";
      }
    } else {
      $errorText = "Autoport configuration for this dictionary key ({$searchIt}) not found.";
    }
  } else {
    $errorText = "Port generator only works if no ports have been configured yet.";
  }
  return !$foundError;
}

function localfunc_PortGenerator()
{
  global $errorText, $lookFor, $portList, $genText, $valueConfiguration, $searchIt;
        assertUIntArg ('object_id', __FUNCTION__);
        $object = spotEntity ('object', $_REQUEST['object_id']);
        
        /// START PORTLET
        //////////////////
        print '<table>';
        print '<colgroup width="1000" span="3">';
        print '</colgroup>';
        print "<td>";
  startPortlet("Port generator");
  print "<center><br>";
  if (!localverify_PortGenerator($object)) {
    //
    // Autoport configuration did not work. Show this and show the error
    //
    print "{$lookFor} :&nbsp; &nbsp; &nbsp;Error\n";
    if (isset($errorText)) {
      print $errorText;
    }
  } else {
    //
    // Show the list of ports that will be generated and provide a link to actually do so
    //
    print "<a href='".makeHrefProcess(array('op'=>'addports','page'=>'object','object_id'=>$object['id'],'tab'=>'ports')).
      "'>Generate the ports for <b>{$object['name']}</b> as listed below NOW</a><br>\n";
    //print $genText."<p>";
    print "<table cellspacing=0 cellpadding='5' align='center' class='widetable'>";
    print "<tr><th>Port name&nbsp;&nbsp;</th><th>Port label&nbsp;&nbsp;</th><th>Port type</th></tr>\n";
    foreach ($portList as $aPort) {
	  $fetch_name = usePreparedSelectBlade('SELECT oif_name FROM PortOuterInterface WHERE id='.$aPort['port_name']);
	  $oif_pname = $fetch_name->fetchAll();
      print "<tr><td>{$aPort['name']}</td><td>{$aPort['label']}</td><td>{$oif_pname[0][0]}</td></tr>\n";
    }
    print "</table>";
  }
  print "<br></center>";
  finishPortlet();
  //
  // Check whether the user is allowed to do an update of the port configurator
  //
  // if you do not want this, make sure you add a
  // deny {$op_updateportgenerator}
  // for the apprioriate groups (or any allow first and deny for the rest
  //
  if (permitted('object','portgenerator',null,array(array ('tag' => '$op_updateportgenerator')))) {
    startPortlet("Update autoport configuration");
    //
    // Description of the config rules
    //
    print "<div align=left>";
    print "Generic".$genText."<p>\n";
    print "&lt;list1&gt;;&lt;list2&gt;;.... wobei &lt;listx&gt; ist:<br>";
    print "&lt;Start-Nummer&gt;|&lt;Anzahl Ports &gt;|";
    print "&lt;Portname, %u fÃ¼r Nummerierung&gt;|&lt;Port-Typ ID&gt;<br><br>";
    print "<b>BEISPIEL</b><br><br> 1|4|eth%u|24; <br><br>"; //an example of how to use port generator
    print "<b>ERKLÃ„RUNG</b><br><br> <b>1</b> = Start-Nummer,
<br><b>15</b> = Anzahl generierter Ports,         
<br><b>eth%u</b> = FÃ¤ngt bei <b>Start-Nummer</b> an und erstellt die gewÃ¼nschte <b>Anzahl Ports</b>,
<br><b>24</b> = Der Dictionary-Wert aus der Tabelle<br><br>"; //explains example
print "<b>ACHTUNG:</b>Bei der Generierung von SFP+-Ports muss die Form angepasst werden. Anstatt x|y|z|1084 <b>muss</b> x|y|z|9-1084 benutzt werden <br><br>";
    print "<b>BITTE BEACHTEN</b><br>Falls die gewÃ¤hlte Port-Typ-ID nicht aktiviert ist, fÃ¼hrt dies zu einer Foreign-Key-Violation.
<br>Um die Ports zu aktivieren, bitte in der <b>Configuration</b> die Ports unter <b>Enable port types</b>
aktivieren.<br><br><br>"; 
 print "</div>";
    //Very important to have a sucessful implementation of port generator
    //
    // The form that can update the configuration
    // On top of the table of ports avialabe instead of beneath it
        //
    printOpFormIntro ('updateportgenerator', array ('yId' => $searchIt));
    print "Autoport Configuration : <input type='text' size='60' name='yConfig' value='";
    if ($valueConfiguration) {
      print $valueConfiguration[0];
    }
    print "'><br><br>\n";
    print "<input type='submit' name='autoportconfig' value='";
    if ($valueConfiguration) {
      print "Update";
    } else {
      print "Create";
    }
    print "'>\n";
    print "</form>\n";
    print "</center><br>";
     
    print "<table border='2' rules=all>\n<tr>";
    $isfirst = true;
    $i = 0;
    //
    // List all available port types with their dictionary key
    //
    $q = "SELECT dict_key, dict_value FROM Dictionary WHERE chapter_id=2 ORDER BY dict_value ";
    $result = usePreparedSelectBlade ($q);//Changed for new configeration in versions after 0.17.x
    if ($result==NULL) { print_r($dbxlink->errorInfo()); die(); }
    while ($row4 = $result->fetch (PDO::FETCH_NUM)) {
	if (PortTypes($row4)==TRUE){
      if (!$isfirst && $i%12==0) { //Change from %12 to %10 to render table evenly
        print "</td></tr>\n"; //Change to </td></tr> so that each dictionary entry is nestled in its own cell
      } else {
      $isfirst = false;
      }
      if ($i%10==0) {
        print "<td align='left'></td>";   //print hardcoden
        $i=0;
     }
      $length = strlen($row4[1]);
   
      $padded_row = str_pad($row4[1], 32, " ", STR_PAD_RIGHT);//Does not work yet, will make each cell the same size
      print "<td><b>{$row4[0]}</b>:";//seperated values to make them easier to read
      print "{$padded_row}\n</td>";}
      $i++;
    }
    print "</td>\n";
    print "</tr></table>\n";
   
    finishPortlet();
    print "</td>";
    ///ENDPORTLET
    /////////////
    print "<td>";
    startPortlet("Autoport Editor");
///STYLE BEGIN
print '<style type="text/css">';
print 'label{display:block;}';
print 'input[type="radio"] { float: left; }';
print '</style>';
///STYLE END
printOpFormIntro('addporteditor');
print '<table width="100%" rules="rows" border="1">';
print "<colgroup>";
print '<col width="1*">';
print '<col width="1*">';
print "</colgroup>";
//MANAGEMENT BEGIN
print "<tr>";
print "<td>";
print "<p><b> Interface Type:</b></p>";
print "<p>";
print '<label><input type="radio" name="interface_type" value="1"> iDRAC</label><br>';
print '<label><input type="radio" name="interface_type" value="2"> MGMT</label><br>';
print '<label><input type="radio" name="interface_type" value="3"> iLO</label><br>';
print "</p>";
print "</td>";
print "<td>";
print '<p>Typ: <select name="mgmt_type" style="width: 122px; float: right">';
print '<option value="24">1000Base-T</option>';
print "</td>";
print "</tr>";
//MANAGEMENT END
//ETHERNET BEGIN
print "<tr>";
print "<td>";
print "<p><b>Ethernet: </b></p> <br>";
print '<p>Anzahl: <input type="text" name="eth_count" size="15" style="float: right"></p><br>';
print '<p>Name: <input type="text" name="eth_name" size="15" style="float: right"></p><br>';
print '<p>Start-Nummer: <input type="text" name="eth_start" size="15" value="0" style="float: right"></p><br>';
print "</td>";
print "<td>";
print "<br><br><br>";
print '<p>Typ: <select name="eth_type" style="width: 122px; float: right">';
print '<option value="24">1000Base-T</option>';
print "</td>";
print "</tr>";
//ETHERNET END
//FC BEGIN
print "<tr>";
print "<td>";
print "<p><b>Fibrechannel: </b></p> <br>";
print '<p>Ports per Slot: <input type="text" name="fc_count" size="15" style="float: right"></p><br>';
print '<p>Start-Nummer: <input type="text" name="fc_start" size="15" style="float: right"></p><br>';
print '<p>Slots: <input type="text" name="fc_slot" size="15" style="float: right"></p><br>';
print '<p>Slots mit Semikolon getrennt angeben, z.B. 5;6;8</p>';
print "</td>";
print "<td>";
print "<br><br><br>";
print '<p>Typ: <select name="fc_type" style="width: 122px; float: right">';
print '<option value="9-36">LWL 4GBit/s</option>';
print '<option value="9-36">LWL 8GBit/s</option>';
print '<option value="9-36">LWL 16GBit/s</option>';
print "</td>";
print "</tr>";
//FC END
//SAS BEGIN
print "<tr>";
print "<td>";
print "<p><b>SAS: </b></p> <br>";
print '<p>Anzahl: <input type="text" name="sas_count" size="15" style="float: right"></p><br>';
print '<p>Name: <input type="text" name="sas_name" size="15" style="float: right"></p><br>';
print '<p>Startnummer: <input type="text" name="sas_start" size="15" style="float: right"></p><br>';
print '<p>Anzahl Controller: <input type="text" name="sas_ctrler" size="15" style="float: right"></p><br>';
print "</td>";
print "<td>";
print "<br><br><br>";
print '<p>Typ: <select name="sas_type" style="width: 122px; float: right">';
print '<option value="24">SAS 6GBit/s</option>';
print "</td>";
print "</tr>";
//SAS END
print '<input type="hidden" name="objectid" value="'.$_REQUEST['object_id'].'">';
print "</table>";
print '<input type="submit" value=" Generate " width="300" height="50">';
print "</form>";
print "<br><br><br>";


    finishPortlet();
    print "</td>";
    print "</table>";	
  }
}

//
// The actual port generator
//
//$msgcode['localexecute_PortGenerator']['OK'] = 0;
//$msgcode['localexecute_PortGenerator']['ERR'] = 100;

function localexecute_PortGenerator()
{
  global $errorText, $portList;
  $linkok = localtrigger_PortGenerator();
  if ($linkok) {
    assertUIntArg ('object_id', __FUNCTION__);
    $object = spotEntity ('object', $_REQUEST['object_id']);
    if (localverify_PortGenerator($object)) {
      $cnt = 0;
      foreach ($portList as $aPort) {
        commitAddPort($_REQUEST['object_id'],$aPort['name'],$aPort['port_id'],$aPort['label'],"");
        $cnt++;
      }
    }
  } else {
    $errorText = "Port generator not allowed";
  }
  if ($linkok) {
    //return buildRedirectURL (__FUNCTION__, 'OK', array ("Successfully added {$cnt} ports"));
    return setMessage ('success', $message="Successfully added {$cnt} ports");
  } else {
    //return buildRedirectURL (__FUNCTION__, 'ERR', array ("Error adding the ports ({$errorText})"));
    return setMessage ('error', $message="Error adding the ports ({$errorText})");
  }
}

//
// Update the configuration scheme
//
//$msgcode['updateconfig_PortGenerator']['OK'] = 0;
//$msgcode['updateconfig_PortGenerator']['ERR'] = 100;


function updateconfig_PortGenerator()
{
  global $tablePortGenerator;
  checkForTable();
  $q = "SELECT autoportconfig FROM {$tablePortGenerator} WHERE dict_key={$_REQUEST['yId']} ";
  $result = usePreparedSelectBlade ($q);
  if ($result==NULL) { print_r($dbxlink->errorInfo()); die(); }
  if ($row = $result->fetch (PDO::FETCH_NUM)) {
    $q = "UPDATE {$tablePortGenerator} SET autoportconfig='{$_REQUEST['yConfig']}' WHERE dict_key={$_REQUEST['yId']} ";
  } else {
    $q = "INSERT INTO {$tablePortGenerator} (dict_key,autoportconfig) VALUES ({$_REQUEST['yId']},'{$_REQUEST['yConfig']}') ";
  }
  $result = usePreparedSelectBlade ($q);
  if ($result==NULL) { print_r($dbxlink->errorInfo()); die(); }
  if (true) {
    return setMessage('success',$message="Successfully updated auto port configuration");
  } else {
     print "false";
    return setMessage ('error', $message="Error in update to auto port configuration");
  }
}


function addports_full(){
$object_id = $_POST['objectid'];
print $object_id;
//MGMT ADD BEGIN
$mgmt_string;
$mgmt_brand = $_POST['interface_type'];
$mgmt_int   = $_POST['mgmt_type'];
switch($mgmt_brand){
	case 1: $mgmt_string = "iDRAC";
			break;
	case 2: $mgmt_string = "MGMT";
			break;
	case 3: $mgmt_string = "iLO";
			break;
	default:$mgmt_string = null;
			break;
}
if(isset($mgmt_string))commitAddPort($object_id, $mgmt_string, $mgmt_int, null, null);

//MGMT ADD END
$i = 0;
//ETH ADD BEGIN
$eth_string;
$eth_int   = $_POST['eth_type'];
$eth_count = $_POST['eth_count'];
$eth_start = $_POST['eth_start'];
$eth_name  = $_POST['eth_name'];
if($eth_count == "") $eth_count = null;
if($eth_start == "") $eth_start = null;
if($eth_name == "")  $eth_name  = "eth";
if(isset($eth_count) && isset($eth_start)){
	for($i = $eth_start; $i < ($eth_count + $eth_start); $i++){
		commitAddPort($object_id, $eth_name . $i, $eth_int, null,null);
	}
} 
//ETH ADD END
$i = 0;
//FC ADD BEGIN
$fc_bool_slot  = TRUE;
$fc_bool_count = TRUE;
$fc_string;
$fc_int   = $_POST['fc_type'];
$fc_count = $_POST['fc_count'];
$fc_slot  = $_POST['fc_slot'];
$fc_start = $_POST['fc_start'];
$fc_slot_exp = explode(';', $fc_slot);
if($fc_count == "")$fc_bool_count = FALSE;
if($fc_slot  == "")$fc_bool_slot  = FALSE;
if($fc_start == "")$fc_start=0;
if(fc_bool_count && $fc_bool_slot){
	for($i = 0; $i < count($fc_slot_exp); $i++){
		for($j = 0; $j < $fc_count; $j++){
			$fc_string = 'S' . $fc_slot_exp[$i] . chr(65 + $j);
			commitAddPort($object_id, $fc_string, $fc_int,$slot_div , null);
		}
	}
}
//FC ADD END
$i = 0;
//SAS ADD BEGIN
$sas_end = 0;
$sas_string;
$sas_int   = $_POST['sas_type'];
$sas_count = $_POST['sas_count'];
$sas_start = $_POST['sas_start'];
$sas_name  = $_POST['sas_name'];
$sas_ctrler= $_POST['sas_ctrler'];
if($sas_count == "") $sas_count = null;
if($sas_start == "") $sas_start = null;
if($sas_name == "")  $sas_name  = "SAS";
if($sas_ctrler == "")$sas_ctrler = 0;

if($sas_ctrler == 0){
	for($i = $sas_start; $i < ($sas_count + $sas_start); $i++){
		commitAddPort($object_id, $sas_name . $i, $sas_int, null, null);
	}
}else{
	$sas_div = $sas_count / $sas_ctrler;
	$sas_div = (int)$sas_div;
	for($c = 0; $c <= $sas_ctrler; $c++){
		for($d = 0; $d < $sas_div; $d++){
			if($sas_end == $sas_count)break 2;
			$sas_end++;
			$sas_string = ("C" . $c . "_" . $sas_name . $d);
			commitAddPort($object_id, $sas_string, $sas_int, null, null);

		}
	}
}

//SAS ADD END
}

//CONFIGURE PORT TYPES
function PortTypes($row){
$ports = array(/*List all wanted Port types here*/);
for($i=0;$i<count($ports);$i++)if($row[0]==$ports[$i])return TRUE;
}//EOF CONFIG PORT TYPES


?>
