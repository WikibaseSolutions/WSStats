# WSStats
This MediaWiki extension counts pageviews by user

* Version 0.8.0 : Clean Up
* Version 0.1.9 : Fetch Title changes
* Version 0.1.8 : Removed dbprefix class variable
* Version 0.1.7 : Show top visited pages with date range. Show as csv option
* Version 0.1.6 : Filter results on user or anonymous
* Version 0.1.5 : Added more configuration options
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
$wgWSStats['count_all_usergroups'] = true;
````

***NOTE**: If you have set $wgWSStats['count_all']=true; then $wgWSStats['skip_user_groups'] is ignored.*
''

Skip page with certain text in their referer url. Default action=edit and veaction=edit are ignored. This configuration option is case sensitive.
````
$wgWSStats = array();
$wgWSStats['ignore_in_url'][] = 'Template:Test';
$wgWSStats['ignore_in_url'][] = 'action=edit';
````

#Usage

=== Ask number of hits for page id : 9868 ===

```
{{#wsstats:id=9868}}
```

=== Ask number of hits for page id : 714 since start date 2018-02-01 ===

```
{{#wsstats:id=714
|start date=2018-02-01}}
```

=== Ask number of hits for page id : 714 since start date 2018-02-01 and end date 2018-02-14 ===

```
{{#wsstats:id=714
|start date=2018-02-01
|end date=2018-02-08}}
```

=== Filter results on registered users or anonymous users ===

```
{{#wsstats:id=714
|start date=2018-02-01
|end date=2018-02-08
|type=only anonymous}}
```

```
{{#wsstats:id=714
|start date=2018-02-01
|end date=2018-02-08
|type=only user}}
```

=== Get the top ten pages sorted by hits ===

```
{{#wsstats:stats}}
```

=== Get the top ten pages sorted by hits in a date range ===

```
{{#wsstats:stats
|start date=2018-02-01
|end date=2018-02-08}}
```

=== Get the top ten pages sorted by hits and show as csv ===

```
{{#wsstats:stats
|format:csv}}
```