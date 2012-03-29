Intro
=====
This code stores static transit data (bus stop locations, transit routes, 
vehicle types, etc.) gathered from NextBus (for AC Transit and SF Muni)
and BART, and stores it in a common format for use by the Transporter
iPhone app. It does NOT store arrivals information (e.g. the N-Judah will arrive at Embarcadero Station in 4 minutes). A cron job runs daily to check whether there have been
transit system changes, and sends a notification if there have been.
When this happens, the iPhone app needs to be updated manually and
resubmitted to the App Store. Eventually, we'd like to automate 
app updates and do them over the air.

There are also 3 override files used to clean up the data for use in the Transporter iPhone app. These files are used every time a new version of the static transit data is created

1. `Directions.xml`
Each route (e.g. N-Judah) has multiple directions (e.g. inbound, outbount). In fact most routes have more than two directions since they run differently at different times of day. In most cases the NextBus API indicates which two directions should be shown to the user, when selected the direction they want to go, in the routeConfig file with the flag "useForUI". But some routes have more than 2 directions with useForUI=TRUE. Directions.xml indicates which two directions to use, when there are more than 2 to choose from.

2. `ReverseStops.xml`
A reverse stop is the stop for a particular route that is across the street (or platform) from the one you're standing at. So, the reverse stop of the N-Judah/Inbound at Embarcadero is the N-Judah/Outbound at Embarcadero. This is good to know because it makes it easy for a user to quickly switch directions if they navigated to the stop/route they wanted, but chose the wrong direction.

NextBus doesn't give us this information so we have to deduce it ourselves algorithmically. This isn't that hard to do using the names/directions/routes of stops, but there are ambiguous cases where the algorithm fails. This hand-written file addresses these failures. 

NOTE: I haven't looked at this file in a long time and I'm not convinced it is completely accurate.

3. `SFMuniVehicle.xml`
This file is a copy of the routeList file for SF Muni vehicle except that it has one extra attribute for each route: vehicle. This attribute can take one of four values (streetcar, metro, bus, cablecar). NextBus does not give us this information, so we need to create it ourselves. It is used in the Lines screen of the iPhone app for SF Muni, so that we can separate the different transit vehicles on that screen.

This code was written by Thejo Kote, a classmate of Ljuba's (the iPhone app's creator) at the UC Berkeley iSchool.

Documentation for the APIs:

* [BART API](http://api.bart.gov/docs/overview/index.aspx)
* [NextBus API](http://www.nextbus.com/xmlFeedDocs/NextBusXMLFeed.pdf)

Kronos server installation instructions:

The Kronos server is a PHP application with a PostgreSQL backend. This document
contains deployment instructions. I make the assumption that it will be
deployed and run on a Linux variant of some kind.

Prerequisites
=============
- PostgreSQL server - 8.3 or above
- Apache with mod_php5 - Test that PHP scripts can be executed by creating
a script with the following code and loading it in a browser:

```
<?php
//Refer to http://php.net/phpinfo for details
phpinfo();
?>
```

The page should provide details about the PHP installation, installed extensions
etc. Knowing that should help in determining whether the next step is necessary.

PHP extension dependencies
==========================
- gd (for image manipulation - should be available as part of the package
    management system of the server.)
- zip (to create and manipulate zip files - should be available as part of the
    package management system of the server.)
- PDO (and the pgsql driver) - The PDO extension is usually available as part of
    the package management system. The driver has to be installed separately.
- APC (not mandatory, but recommended for improved performance)

The last two can be installed using [pecl](http://pecl.php.net):

```
pecl install pdo_pgsql
pecl install apc
```

To run the `pecl` command the `php5-devel` and `php5-pear` packages need to be installed.
To install `pdo_pgsql` the `postgresql-devel` package is required.
To install `apc` the `apache2-devel` package is required.

Database setup
==============
- Create a database named "kronos" without the quotes.
- Create a user who has permissions to read from and write to the database.
- Install the tables and default data: `pgsql -d kronos -f KRONOS_ROOT/dbschema/schema.sql`

Deployment
==========
- Check out the Kronos server code on github
    - Ideally, don't check it out into the Apache document root.
    - Let's assume the directory in which the contents of the repo 
	  are is KRONOS_ROOT
- Edit `KRONOS_ROOT/config/config.php`
    - Change the database connection and other config values based on the
        environment you're deploying in
- The KRONOS_ROOT/www directory is the document root of the application. Only
    this directory should be exposed to the outside world through Apache. In a
    development environment, you can just load the admin interface as, for
    example, `http://127.0.0.1/kronos/www/index.php` (assuming you checked out the
    code into a directory named "kronos" in the Apache document root). In
    production you'll have to setup a virtual host for the domain or sub-domain
    under which the Kronos server will run and set KRONOS_ROOT/www/ as the
    document root of the virtual host.

In production, I've set up an Apache virtual host for the domain exergydesign.com
So, the admin interface is available at http://exergydesign.com/index.php or
http://exergydesign.com

Updating the data from NextBus and BART APIs
============================================
The script KRONOS_ROOT/www/update.php is where this happens. Reading the code
and following the included files should help you understand how the process works.
During development adding a new version is as simple as loading
`http://127.0.0.1/kronos/www/update.php` in the browser. Since the entire update
process takes about 5 minutes, it will appear as though the browser has hung.
This is normal. You'll see an output of 1 on the page (or more, if you've added
any debug print statements in the code) after the script completes execution.

On the server, the same script needs to be executed once a day. It is done
through a cron job like so:

`0 19 * * * curl http://exergydesign.com/update.php >> /dev/null 2>&1`

The `KRONOS_ROOT/www/update.php` script communicates with NextBus and BART,
updates the database with a new version and creates the XML files. It does not
generate the images. That happens in `KRONOS_ROOT/www/map_images.php`
Images are generated only when requested from the admin interface via an Ajax call.

Miscellaneous
=============
- The application logs to `KRONOS_ROOT/logs/kronos.log` - This can be changed in
    configuration file.
- To understand how the daily update works start from `KRONOS_ROOT/www/update.php`
- To understand how the admin interface works start from `KRONOS_ROOT/www/index.php`
    The admin interface is very simple. It's just a bunch of PHP scripts reading
    and writing to the database.
- The override files reside in `KRONOS_ROOT/files/` - The admin interface writes
    to them directly when they are edited in the admin UI. So, Apache should have
    permission to write to them.

