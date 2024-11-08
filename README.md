# WSStats
This MediaWiki 1.39.x extension counts pageviews by user

* Version 2.0.1 : Fixed sql table https://github.com/WikibaseSolutions/WSStats/pull/8 add indexing to user and page https://github.com/WikibaseSolutions/WSStats/pull/9
* Version 2.0.0 : REL 1.39 only. Added statistics for Special Pages. Lua equivalent functions for statistics. Special Page added.
* Version 1.0.8 : Removed global references
* Version 1.0.7 : Added statistics over time for pages
* Version 1.0.6 : Fixed path to sql tables
* Version 1.0.5 : Rewrote database queries to use MW database abstraction layer.
* Version 1.0.4 : Security fix! Unhandled user input. Update to 1.0.4 as soon as possible. 
* Version 1.0.3 : Top X list changed as it no longer shows deleted pages
* Version 1.0.2 : Catched request for title on a non-existing page
* Version 1.0.1 : Code clean-up and i18n messages added
* Version 1.0.0 : Added support for setting limit and export as WSArrays
* Version 0.8.2 : Added support for filtering only unique visitors
* Version 0.8.1 : Added support for AdminLinks
* Version 0.8.0 : Clean Up
* Version 0.1.9 : Fetch Title changes
* Version 0.1.8 : Removed dbprefix class variable
* Version 0.1.7 : Show top visited pages with date range. Show as csv option
* Version 0.1.6 : Filter results on user or anonymous
* Version 0.1.5 : Added more configuration options
* Version 0.1.3 : Fixed error in MySQL
* Version 0.1.2 : Skip usergroup results
* Version 0.1.1 : Initial release

## Installation

Create a folder called WSStats in the MediaWiki extensions folder. Copy the content from bitbucket inside this new folder.

Add following to LocalSettings.php
````
# WSStats extensions
wfLoadExtension( 'WSStats' );
````

Run the [update script](https://www.mediawiki.org/wiki/Manual:Update.php) which will automatically create the necessary database tables that this extension needs.

Navigate to Special:Version on your wiki to verify that the extension is successfully installed.

## Upgrading
If you are upgrading from a version before 2.0, then you must visit the Special Page : Special:WSStats. If the database tables need updating, it will show you there and you can update the tables.
You will have to update the tables for statistics to be accurate. This is because we now allow to get statistics based on page titles, while the previous version of WSStats did not store titles.

# Configuration

By default Anonymous users and sysops are skipped from stats recording. To change this add following to LocalSettings.php..

Start with
````
$wgWSStats = array();
````
Allow statistics for anonymous users:
````
# Record anonymous users
$wgWSStats['skip_anonymous'] = false;
````

By default Special Pages are counted as well. To omit Special pages set the following : 
````
# Special Pages statistics
$wgWSStats['countSpecialPages'] = false; // defaults to true
````

To skip users in certain groups, just add the groupname to "skip_user_groups" :
````
# Record anonymous users
$wgWSStats['skip_anonymous'] = false;

# Skip if user is in following groups
$wgWSStats['skip_user_groups'][] = 'sysop';
$wgWSStats['skip_user_groups'][] = 'admin';

# Allow all usergroups, including sysop
$wgWSStats['skip_user_groups'] = [];
````


Skip page with certain text in their referer url. Default action=edit and veaction=edit are ignored. This configuration option is case sensitive:
````
$wgWSStats = array();
$wgWSStats['ignore_in_url'][] = 'Template:Test';
$wgWSStats['ignore_in_url'][] = 'action=edit';
````

# Using the parser function
To retrieve statistics you can use the following parser functions :


#### Ask number of hits for page id : 9868
This returns a number
```
{{#wsstats:id=9868}}
```

You can also ask for statistics based on title, instead of Page Id's.
#### Ask number of hits for page with title : Main Page
This returns a number
```
{{#wsstats:title=Main Page}}
```

About dates. Dates are in a format of YYYY-MM-DD. Internally WSStats works with Date and Time.
This means a date of **2023-10-30** will internally become **2023-10-30 00:00:00**. You can also search by date and time. See the example about this.


#### Ask number of hits for page id : 714 since start date 2018-02-01
This returns a number
```
{{#wsstats:id=714
|start date=2018-02-01}}
```

#### Ask number of hits for page id : 714 since start date 2018-02-01 and end date 2018-02-14
This returns a number
```
{{#wsstats:id=714
|start date=2018-02-01
|end date=2018-02-08}}
```

#### You can also get statistics based on date and time Get number of hits for page id : 714 from start date 2023-10-30 14:00:00 and end date 2023-10-30 16:00:00
This returns a number
```
{{#wsstats:id=714
|start date=2023-10-30 14:00:00
|end date=2023-10-30 16:00:00
}}
```

#### Filter results on registered users or anonymous users
This returns a number
```
{{#wsstats:id=714
|start date=2023-10-30 14:00:00
|end date=2023-10-30 16:00:00
|type=only anonymous}}
```

```
{{#wsstats:id=714
|start date=2018-02-01
|end date=2018-02-08
|type=only user}}
```

#### Get the top ten pages sorted by hits
This returns a table
```
{{#wsstats:stats}}
```

#### Get the top ten pages sorted by hits in a date range
This returns a table from 2018-02-01 00:00:00 up to 2018-02-08 00:00:00 ( so not including 2018-02-08 )
```
{{#wsstats:stats
|start date=2018-02-01
|end date=2018-02-08}}
```

#### Get the top ten pages sorted by hits and show as csv
This returns a csv
```
{{#wsstats:stats
|format:csv}}
```

#### Get the top ten pages sorted by hits and insert in a WSArrays variable
This returns nothing but only sets WSArray key. Nothing happens when the WSArrays extension is not installed
```
{{#wsstats:stats
|format:wsarrays
|name=<wsarray key name>}}
```
```
Get the result from WSArrays:
{{#caprint:<wsarray key name>}} 
```


#### Get statistics of one page during a time period and limit to 100 results
New since version 1.0.7

This returns a table
```
{{#wsstats:stats
|id=1
|start date=2018-02-01
|end date=2021-02-08
|limit 100
}}
```


#### For all queries you can add a unique identifier to only return unique views
This returns a table
```
{{#wsstats:stats
|start date=2018-02-01
|end date=2018-02-08
|unique}}
```
This returns a table
```
{{#wsstats:stats
|unique}}
```

#### For all top ten stats queries you can add limit to get less or more than ten results
This returns a table
```
{{#wsstats:stats
|unique
|limit=20}}
```

# Using the lua functions
New in version 2.0

There are two Lua function you can use. 

For the parser function that returns a table ( {{#wsstats:stats.. ) you can use wsstats.stats().

For the parser function that returns a number ( {{wsstats:... ) you can use wsstats.stat();

All the arguments are the same as for the parser functions, except :

* start date = startDate
* end date = endDate

### Example

If you create a Module called WSStats and you add the following content :
```lua
local p = {}

function p.stats(frame)
  stats = mw.wsstats.stats( frame.args )
  return stats

end

function p.stat(frame)
  stat = mw.wsstats.stat( frame.args )
  return stat

end

return p
```

You can then do calls like :
```
{{#invoke:wsstats|stats}} // returns a Lua table
```
```
{{#invoke:wsstats|stat|id=1|startDate=2023-09-25|endDate=2023-09-26}}
```