##Scripts & Plugins for Vanilla Racktables

This Repository contains some Scripts and Plugins for the Asset-Management Software
Racktables.
 
####Scripts:

#####hyper.php:

This Script is for adding a Hypervisor to all Server of a certain Name-Pattern


#####switchnameexp.php:

This Script is for exporting a config List for HP Procurve switches to configure Port Names 

(although all Switches are exported, you need to be careful here)


####Plugins:

#####local_portgenerator.php:

This is a modified version of the Original local_portgenerator.

It features a Sidebar for Fast-Adding many Ports of different types.

Installation Instructions are included in the file.

 
#####ping.php:

This is a modified Version of the Ping plugin found on the contribs-site.

It features support for HAWK, a ICMP Ping tool that runs periodically and feeds a 

database. Installation Instructions are included in the file. 


##Modified Racktables

It also contains a "Patch" for Racktables for extended functionality, like
a new Status for Hardware that is not mounted yet, an Export for labelling Cables,
and "monitoring" for Servers with no Management Address.
To achieve this the base of Racktables was modified, and is not updatable through the
Update Cycle of Racktables.

To use these functions, you need to make some changes to the Racktables Database:

For the new State you need to alter the object_state_information Table and the rackspace Table:

```mysql
CREATE TABLE object_state_information(
`to_mount` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no',
`id` int( 10 ) unsigned NOT NULL ,
PRIMARY KEY ( `id` ) ,
FOREIGN KEY ( id ) REFERENCES Object( id ) ON DELETE CASCADE ON UPDATE CASCADE
);
```

```mysql
ALTER TABLE `rackspace` CHANGE `state` `state` ENUM( 'A', 'U', 'T', 'M' ) 
CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A';
```


Warning: The Table names could be different(The capitalization can be different)


For the Management-"Monitoring" you need to add a new Network. At the Moment, this Range is fixed:

	1.1.0.0/20

Also, you need to Insert a Value to the Config Table:
```mysql
INSERT INTO `Config` (
`varname` ,
`varvalue` ,
`vartype` ,
`emptyok` ,
`is_hidden` ,
`is_userdefined` ,
`description`
)
VALUES (
'LAST_IP_RAC', '16842754', 'uint', 'no', 'no', 'no', 'Last IP used for dummy Network'
);
```
Furthermore, a new Column is needed in object_state_information:
```mysql
ALTER TABLE `object_state_information` ADD `has_rac` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no';
```















