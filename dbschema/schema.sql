CREATE TABLE agency
(
id serial NOT NULL,
title varchar(255) NOT NULL,
short_title varchar(50) NOT NULL,
default_vehicle varchar(50) NOT NULL CHECK (default_vehicle IN ('bus', 'train')) default 'bus',
description text,
created_date timestamp NOT NULL,
last_updated_date timestamp NOT NULL default now(),
PRIMARY KEY(id),
UNIQUE(title),
UNIQUE (short_title)
);

CREATE TABLE version
(
id serial NOT NULL,
active boolean NOT NULL default false,
was_active boolean NOT NULL default false,
changes_present boolean NOT NULL default false,
images_generated boolean NOT NULL default false,
created_date timestamp NOT NULL,
last_updated_date timestamp NOT NULL default now(),
PRIMARY KEY(id)
);

/*
Default vehicle type for:
BART - train
AC Transit - bus
SF Muni - bus
*/
CREATE TABLE route
(
id serial NOT NULL,
agency_id integer NOT NULL REFERENCES agency(id) ON DELETE CASCADE,
tag varchar(20) NOT NULL, /* BART tags are colours */
color varchar(100) NOT NULL,
title varchar(255) NOT NULL,
short_title varchar(100) NOT NULL,
vehicle_type varchar(50) NOT NULL CHECK (vehicle_type IN ('bus', 'cablecar', 'streetcar', 'metro', 'train')) default 'bus',
position integer NOT NULL CHECK (position > 0),
lat_min numeric,
lat_max numeric,
lon_min numeric,
lon_max numeric,
version integer NOT NULL references version(id) ON DELETE CASCADE,
created_date timestamp NOT NULL,
last_updated_date timestamp NOT NULL default now(),
PRIMARY KEY(id),
UNIQUE(agency_id, tag, version)
);

CREATE TABLE direction
(
id serial NOT NULL,
route_id integer NOT NULL REFERENCES route (id) ON DELETE CASCADE,
title varchar(255) NOT NULL,
pretty_title varchar(255) NOT NULL,
name varchar(100) NOT NULL,
pretty_name varchar(100) NOT NULL,
tag varchar(20) NOT NULL,
use_for_ui boolean NOT NULL default false,
show boolean NOT NULL default false,
version integer NOT NULL references version(id) ON DELETE CASCADE,
created_date timestamp NOT NULL,
last_updated_date timestamp NOT NULL default now(),
PRIMARY KEY(id)
);

CREATE TABLE stop
(
id serial NOT NULL,
agency_id integer NOT NULL REFERENCES agency(id) ON DELETE CASCADE,
tag varchar(100) NOT NULL,
latitude numeric NOT NULL,
longitude numeric NOT NULL,
title varchar(255) NOT NULL,
pretty_title varchar(255) NOT NULL,
flip_stop_tag varchar(100),
version integer NOT NULL references version(id) ON DELETE CASCADE,
created_date timestamp NOT NULL,
last_updated_date timestamp NOT NULL default now(),
PRIMARY KEY(id),
UNIQUE (agency_id, tag, version)
);

CREATE TABLE stop_direction_map
(
stop_id integer NOT NULL REFERENCES stop (id) ON DELETE CASCADE,
direction_id integer NOT NULL REFERENCES direction (id) ON DELETE CASCADE,
position integer NOT NULL CHECK (position > 0),
version integer NOT NULL references version(id) ON DELETE CASCADE,
created_date timestamp NOT NULL,
last_updated_date timestamp NOT NULL default now(),
UNIQUE (stop_id, direction_id, version)
);

/* ************************** Default data ********************** */

/* Supported agencies */
INSERT INTO agency (title, short_title, default_vehicle, created_date) VALUES ('AC Transit', 'actransit', 'bus', NOW());
INSERT INTO agency (title, short_title, default_vehicle, created_date) VALUES ('SF Muni', 'sf-muni', 'bus', NOW());
INSERT INTO agency (title, short_title, default_vehicle, created_date) VALUES ('BART', 'bart', 'train', NOW());
INSERT INTO agency (title, short_title, default_vehicle, created_date) VALUES ('Emery-Go-Round', 'emery', 'bus', NOW());
