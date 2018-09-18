# WSStats
This MediaWiki extension counts pageviews by user

* Version 0.1.3 : Fixed error in MySQL
* Version 0.1.2 : Skip usergroup results
* Version 0.1.1 : Initial release

##Installation

Create a folder called WSStats in the MediaWiki extensions folder. Copy the content from bitbucket inside this new folder.

Add following to LocalSettings.php
````
# WSStats extensions
require_once( "$IP/extensions/WSStats/WSStats.php" );
````

#Configuration

By default Anonymous users and sysops are skipped from stats recording. To change this add following to LocalSettings.php..

````
$wgWSStats = array();

# Record anonymous users
$wgWSStats['skip_anonymous'] = false;
````

To skip users in certain groups, just add the groupname to "skip_user_groups" :
````
$wgWSStats = array();

# Record anonymous users
$wgWSStats['skip_anonymous'] = false;

# Skip if user is in following groups
$wgWSStats['skip_user_groups'][] = 'sysop';
$wgWSStats['skip_user_groups'][] = 'admin';
````

Count all hits
````
$wgWSStats = array();
$wgWSStats['count_all'] = true;
````

***NOTE**: If you have set $wgWSStats['count_all']=true; then $wgWSStats['skip_user_groups'] and $wgWSStats['skip_anonymous'] are ignored.*

##For internal use

=== Overal WSStats ===

```
{{#wsstats:stats}}
```

=== Calling WSStats with no arguments ===

```
{{#wsstats:}}
```

=== Ask number of hits for page id : 9868 ===

```
{{#wsstats:id=9868}}
```

=== Ask number of hits for page id : 714 (homepage) since start date 2018-02-01 ===

```
{{#wsstats:id=714
|start date=2018-02-01}}
```

=== Ask number of hits for page id : 714 (homepage) since start date 2018-02-01 and end date 2018-02-14 ===

```
{{#wsstats:id=714
|start date=2018-02-01
|end date=2018-02-08}}
```
