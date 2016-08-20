-- Initialize the database with the required tables

-- For MySQL:
-- SET sql_mode = 'ANSI,TRADITIONAL';

CREATE TABLE IF NOT EXISTS "localization__de" (
	"key"   VARCHAR(255) NOT NULL,
	"value" VARCHAR(255) NOT NULL,
	PRIMARY KEY ("key")
)
COMMENT='Localization data for language “de” (German)';

CREATE TABLE IF NOT EXISTS "localization__en" (
	"key"   VARCHAR(255) NOT NULL,
	"value" VARCHAR(255) NOT NULL,
	PRIMARY KEY ("key")
)
COMMENT='Localization data for language “en” (English)';

CREATE TABLE IF NOT EXISTS "office_hours" (
	"day"        ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday',
		'Saturday', 'Sunday') NOT NULL,
	"start_time" TIME NOT NULL,
	"end_time"   TIME NOT NULL DEFAULT '00:00:00'
		COMMENT 'Value of 00:00:00 means that the end time is unspecified',
	"name"       VARCHAR(60) NOT NULL DEFAULT '',
	PRIMARY KEY ("day", "start_time", "end_time")
)
COMMENT='Stores data for the office hours schedule (during semester)';

CREATE TABLE IF NOT EXISTS "office_hours_break" (
	"date"       DATE NOT NULL,
	"start_time" TIME NOT NULL DEFAULT '11:00:00',
	"end_time"   TIME NOT NULL DEFAULT '13:00:00',
	"name"       VARCHAR(60) NOT NULL DEFAULT '',
	"show"       BOOL NOT NULL DEFAULT FALSE
		COMMENT 'Boolean: TRUE if entry should be shown in the public part of'
		' the website, FALSE otherwise',
	PRIMARY KEY ("date","start_time","end_time")
)
COMMENT='Stores data for the office hours schedule (during break)';

CREATE TABLE IF NOT EXISTS "semester_dates" (
	"summer_winter" ENUM('SS', 'WS') NOT NULL,
	"year"          INT(11) NOT NULL
		COMMENT 'Year in which the semester starts, i.e. WS 2015 = WS'
		' 2015/2016',
	"lecture_start" DATE NOT NULL,
	"lecture_end"   DATE NOT NULL,
	PRIMARY KEY ("summer_winter","year")
)
COMMENT='Start and end dates for each semester';

CREATE TABLE IF NOT EXISTS "mlp_steuerseminar" (
	"id"           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	"first_name"   VARCHAR(60) NOT NULL,
	"surname"      VARCHAR(60) NOT NULL,
	"email"        VARCHAR(60) NOT NULL,
	"phone_number" VARCHAR(60) NOT NULL,
	"study_course" VARCHAR(60) NOT NULL,
	"semester"     TINYINT(3) UNSIGNED NOT NULL,
	"comment"      TEXT NOT NULL,
	PRIMARY KEY ("id")
)
COMMENT='Registration data for MLP tax seminar (not currently in use)'
AUTO_INCREMENT=1;

