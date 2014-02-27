<?php 
//
// Originally Written by Tommy Botten Jensen
// patched for Racktables 0.20.3 by Vladimir Kushnir
// patched for Racktables 0.20.5 by Rob Walker
// rewritten for HAWK ICMP for Racktables 0.20.6 by Florian Baier
// The purpose of this plugin is to map your IP ranges with the reality of your
// network using ICMP.
//
//
// Modified by Florian Baier
// 27.02.2014
//
//
// History
// Version 1: Initial release after rewrite
//
// Requirements:
// You need the 'hawk' Daemon and Database running on your System (http://iphawk.sourceforge.net/)
// This Plugin just uses the results from hawk and is independent from it.
// The former Plugin did a Ping-Test every Time it was called, here you can control
// the frequency of the Pings, resulting in a much smoother experience, as you don't
// have to wait for the ping to be finished. This leads especially in larger Environments to a fast access to this feature
// Installation:
// 1) Create database in your MySQL Installation according to the instructions on the hawk website
// 2) Start the daemon according to the hawk website
// 3) Wait until the hawk Database is populated
// 4) Put this script into the plugins/ Folder
// 5) Add all networks you stated in the hawk config File (if not already existing)
// 6) You should be able to see the Ping results under the "Hawk ICMP Ping" Tab (after selecting a Network in ipv4 Space)
//
// The Legend on the side Bar is customizable, as well as the Time the Script evaluates a System as inactive
// 
//
// Depot Tab for objects.
$tab['ipv4net']['ping'] = 'Hawk ICMP Ping';
$tabhandler['ipv4net']['ping'] = 'PingTab';
$ophandler['ipv4net']['ping']['importPingData'] = 'importPingData';


function importPingData() {
 // Stub connection for now :(
}


function PingTab($id) {
	
  $time_inactive = 86400;  //Time for a System to be shown as inactive for initially 1 Day (in seconds)
  $time_inactive_long = 604800;  //Time for a System to be shown as inactive for initially 1 Week (in seconds)
  $msg_inactive = "Is inactive for at least 1 Day:";
  $msg_inactive_long = "Is inactive for at least 7 Days:"; 
  $huser = 'your_hawk_db_user';
  $hpass = 'your_hawk_db_pass';
  $hdb = new PDO('mysql:host=localhost;dbname=hawk', $huser, $hpass);
  
  
  
  //--MySQL Fetch Block--//
  
  $fetch_ip = $hdb->prepare('SELECT ip, hostname, lastping FROM ip');
  $fetch_ip->execute();
  $ip_arr = $fetch_ip->fetchAll();

  //write a new ip array so i can search indexes faster  
  for($a=0;$a<count($ip_arr);$a++){
  	$ip_pure[$a]=$ip_arr[$a]['ip'];
  }
  
  //--EOF Fetch Block  --//
  
  
if (isset($_REQUEST['pg']))
$page = $_REQUEST['pg'];
else
$page=0;
global $pageno, $tabno;
$maxperpage = getConfigVar ('IPV4_ADDRS_PER_PAGE');
$range = spotEntity ('ipv4net', $id);
loadIPAddrList ($range);
echo "<center><h1>${range['ip']}/${range['mask']}</h1><h2>${range['name']}</h2></center>\n";

echo "<table class=objview border=0 width='100%'><tr><td class=pcleft>";
startPortlet ('ICMP Ping List:');
$startip = ip4_bin2int ($range['ip_bin']);
$endip = ip4_bin2int (ip_last ($range));
$realstartip = $startip;
$realendip = $endip;	
$numpages = 0;
if ($endip - $startip > $maxperpage)
{
$numpages = ($endip - $startip) / $maxperpage;
$startip = $startip + $page * $maxperpage;
$endip = $startip + $maxperpage - 1;
}
echo "<center>";
if ($numpages)
echo '<h3>' . ip4_format (ip4_int2bin ($startip)) . ' ~ ' . ip4_format (ip4_int2bin ($endip)) . '</h3>';
for ($i=0; $i<$numpages; $i++)
if ($i == $page)
echo "<b>$i</b> ";
else
echo "<a href='".makeHref(array('page'=>$pageno, 'tab'=>$tabno, 'id'=>$id, 'pg'=>$i))."'>$i</a> ";
echo "</center>";

echo "<table class='widetable' border=0 cellspacing=0 cellpadding=5 align='center'>\n";
echo "<tr><th>Address</th><th>DNS Name</th><th>Last Pinged</th></tr>\n";
$idx = 1;
$box_counter = 1;
$cnt_ok = $cnt_noreply = $cnt_mismatch = $cnt_inactive = 0;
for ($ip = $startip; $ip <= $endip; $ip++)
{
$ip_bin = ip4_int2bin($ip);
$addr = isset ($range['addrlist'][$ip_bin]) ? $range['addrlist'][$ip_bin] : array ('name' => '', 'reserved' => 'no');
$straddr = ip4_format ($ip_bin);

//removed fping commands

//Table for IPs
$current_key = array_search($straddr,$ip_pure);
if($current_key !== FALSE){
	if($ip_arr[$current_key][2]!=0){
		$date = date_create();
		$time_diff=date_timestamp_get($date)-$ip_arr[$current_key][2];
		if($time_diff>=$time_inactive_long){		echo '<tr class=trerror';$cnt_mismatch++;}
		else {
			if ($time_diff>=$time_inactive){echo "<tr class=trerror";$cnt_inactive++;}
			else {echo "<tr class=trok";$cnt_ok++; }}
		}
	else {echo '<tr class=trnoreply';$cnt_noreply++;}
	echo "><td class='tdleft";
	echo "'><a href='".makeHref(array('page'=>'ipaddress', 'ip'=>$straddr))."'>${straddr}</a></td>";
	echo '<td class=tdleft>'.$ip_arr[$current_key][1].'</td><td class=tderror>';
	if($ip_arr[$current_key][2]!=0){
		echo date("M j G:i:s T Y", $ip_arr[$current_key][2]);
	}
	else echo ('Not available');
	echo "</td></tr>\n";
	$idx++;
}
//EOF Table



	} //EOF FOR THROUGH IP-ARRAY
echo "</td></tr>";
echo "</table>";
echo "</form>";
finishPortlet();

echo "</td><td class=pcright>";

startPortlet ('stats');
echo "<table border=0 width='100%' cellspacing=0 cellpadding=2>";
echo "<tr class=trok><th class=tdright>OKs:</th><td class=tdleft>${cnt_ok}</td></tr>\n";
echo "<tr class=trnoreply><th class=tdright>Did not reply:</th><td class=tdleft>${cnt_noreply}</td></tr>\n";
echo "<tr class=trinactive><th class=tdright>".$msg_inactive."</th><td class=tdleft>${cnt_inactive}</td></tr>\n";
echo "<tr class=trerror><th class=tdright>".$msg_inactive_long."</th><td class=tdleft>${cnt_mismatch}</td></tr>\n";
echo "</table>\n";
finishPortlet();

echo "</td></tr></table>\n";
}
?>