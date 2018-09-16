# WSStats
This MediaWiki extension counts pageviews by user

Version 0.1.1


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
