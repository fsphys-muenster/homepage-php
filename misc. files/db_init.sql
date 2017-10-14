-- Initialize the database with the required tables

-- For MySQL/MariaDB:
-- SET sql_mode = 'ANSI,TRADITIONAL';

-- global tables
CREATE TABLE IF NOT EXISTS "settings__int" (
	"key"   VARCHAR(255) NOT NULL,
	"value" INT NOT NULL,
	PRIMARY KEY ("key")
) -- ENGINE=InnoDB
COMMENT='Global site settings can be defined here (type: INT)';

CREATE TABLE IF NOT EXISTS "settings__str" (
	"key"   VARCHAR(255) NOT NULL,
	"value" VARCHAR(255) NOT NULL,
	PRIMARY KEY ("key")
) -- ENGINE=InnoDB
COMMENT='Global site settings can be defined here (type: string)';

CREATE TABLE IF NOT EXISTS "localization__de" (
	"key"   VARCHAR(255) NOT NULL,
	"value" VARCHAR(1000) NOT NULL,
	PRIMARY KEY ("key")
) -- ENGINE=InnoDB
COMMENT='Localization data for language “de” (German)';

CREATE TABLE IF NOT EXISTS "localization__en" (
	"key"   VARCHAR(255) NOT NULL,
	"value" VARCHAR(1000) NOT NULL,
	PRIMARY KEY ("key")
) -- ENGINE=InnoDB
COMMENT='Localization data for language “en” (English)';

-- office hours schedule
CREATE TABLE IF NOT EXISTS "office_hours" (
	"day"        ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday',
		'Saturday', 'Sunday') NOT NULL,
	"start_time" TIME NOT NULL,
	"end_time"   TIME NOT NULL DEFAULT '00:00:00'
		COMMENT 'Value of 00:00:00 means that the end time is unspecified',
	"name"       VARCHAR(60) NOT NULL DEFAULT '',
	PRIMARY KEY ("day", "start_time", "end_time")
) -- ENGINE=InnoDB
COMMENT='Data for the office hours schedule (during semester)';

CREATE TABLE IF NOT EXISTS "office_hours_break" (
	"date"       DATE NOT NULL,
	"start_time" TIME NOT NULL DEFAULT '11:00:00',
	"end_time"   TIME NOT NULL DEFAULT '13:00:00',
	"name"       VARCHAR(60) NOT NULL DEFAULT '',
	"show"       BOOL NOT NULL DEFAULT FALSE
		COMMENT 'Boolean: TRUE if entry should be shown in the public part of'
		' the website, FALSE otherwise',
	PRIMARY KEY ("date","start_time","end_time")
) -- ENGINE=InnoDB
COMMENT='Data for the office hours schedule (during break)';

CREATE TABLE IF NOT EXISTS "semester_dates" (
	"summer_winter" ENUM('SS', 'WS') NOT NULL,
	"year"          INT NOT NULL
		COMMENT 'Year in which the semester starts, i.e. WS 2015 = WS'
		' 2015/2016',
	"lecture_start" DATE NOT NULL,
	"lecture_end"   DATE NOT NULL,
	PRIMARY KEY ("summer_winter", "year")
) -- ENGINE=InnoDB
COMMENT='Start and end dates for each semester';

-- member data
CREATE TABLE IF NOT EXISTS "members" (
	"member_id"    INT UNSIGNED NOT NULL AUTO_INCREMENT,
	"forenames"    VARCHAR(200) NOT NULL,
	"nickname"     VARCHAR(50) NOT NULL,
	"surname"	   VARCHAR(100) NOT NULL,
	"name_url"     VARCHAR(250) NOT NULL
		COMMENT
		'The suffix that will be used in the URL for this member’s page',
	"uni_email"    VARCHAR(100) NOT NULL
		COMMENT 'Without “@uni-muenster.de”, “@wwu.de” etc.',
	"uni_username" VARCHAR(50) DEFAULT NULL
		COMMENT 'Can be unspecified (NULL)',
	"member_start" DATE NOT NULL,
	"member_end"   DATE DEFAULT NULL
		COMMENT 'NULL means unspecified (i.e. until today)',
	"pgp_id"	   VARCHAR(100) NOT NULL,
	"pgp_url"      VARCHAR(300) NOT NULL,
	PRIMARY KEY ("member_id"),
	UNIQUE KEY ("uni_email"),
	UNIQUE KEY ("uni_username"),
	UNIQUE KEY ("name_url")
) -- ENGINE=InnoDB
COMMENT='Data about the members of the Physics Student Council';

CREATE TABLE IF NOT EXISTS "members__de" (
	"member_id"       INT UNSIGNED NOT NULL,
	"title"           VARCHAR(100) NOT NULL,
	"duties"          VARCHAR(300) NOT NULL,
	"program"         VARCHAR(300) NOT NULL,
	"additional_info" VARCHAR(1000) NOT NULL,
	PRIMARY KEY ("member_id"),
	FOREIGN KEY ("member_id") REFERENCES "members" ("member_id")
		ON DELETE CASCADE
) -- ENGINE=InnoDB
COMMENT='Localized member information for locale “de”';

CREATE TABLE IF NOT EXISTS "members__en" (
	"member_id"       INT UNSIGNED NOT NULL,
	"title"           VARCHAR(100) NOT NULL,
	"duties"          VARCHAR(300) NOT NULL,
	"program"         VARCHAR(300) NOT NULL,
	"additional_info" VARCHAR(1000) NOT NULL,
	PRIMARY KEY ("member_id"),
	FOREIGN KEY ("member_id") REFERENCES "members" ("member_id")
		ON DELETE CASCADE
) -- ENGINE=InnoDB
COMMENT='Localized member information for locale “en”';

CREATE TABLE IF NOT EXISTS "committees" (
	"committee_id"   INT UNSIGNED NOT NULL AUTO_INCREMENT,
	"category"       ENUM('student_body', 'department', 'central', 'other')
		NOT NULL DEFAULT 'other',
	"com_sort_key"   INT UNSIGNED NOT NULL DEFAULT 100000,
	PRIMARY KEY ("committee_id")
) -- ENGINE=InnoDB
COMMENT='Stores the possible committees where members can be active';

CREATE TABLE IF NOT EXISTS "committees__de" (
	"committee_id"   INT UNSIGNED NOT NULL,
	"committee_name" VARCHAR(300) NOT NULL,
	"html"           VARCHAR(1000) NOT NULL,
	PRIMARY KEY ("committee_id"),
	FOREIGN KEY ("committee_id") REFERENCES "committees" ("committee_id")
		ON DELETE CASCADE
) -- ENGINE=InnoDB
COMMENT='Localized committee information for locale “de”';

CREATE TABLE IF NOT EXISTS "committees__en" (
	"committee_id"   INT UNSIGNED NOT NULL,
	"committee_name" VARCHAR(300) NOT NULL,
	"html"           VARCHAR(1000) NOT NULL,
	PRIMARY KEY ("committee_id"),
	FOREIGN KEY ("committee_id") REFERENCES "committees" ("committee_id")
		ON DELETE CASCADE
) -- ENGINE=InnoDB
COMMENT='Localized committee information for locale “en”';

CREATE TABLE IF NOT EXISTS "member_committees" (
	"row_id"       INT UNSIGNED NOT NULL AUTO_INCREMENT,
	"member_id"    INT UNSIGNED NOT NULL,
	"committee_id" INT UNSIGNED NOT NULL,
	"start"        DATE NOT NULL,
	"end"          DATE DEFAULT NULL
		COMMENT 'NULL means unspecified (i.e. until today)',
	PRIMARY KEY ("row_id"),
	FOREIGN KEY ("member_id") REFERENCES "members" ("member_id")
		ON DELETE CASCADE,
	FOREIGN KEY ("committee_id") REFERENCES "committees" ("committee_id")
		ON DELETE CASCADE
) -- ENGINE=InnoDB
COMMENT='Data about which committees a member is in';

CREATE TABLE IF NOT EXISTS "member_committees__de" (
	"row_id"       INT UNSIGNED NOT NULL,
	"timespan_alt" VARCHAR(200) NOT NULL COMMENT 'Only used if not empty',
	"info"         VARCHAR(300) NOT NULL,
	PRIMARY KEY ("row_id"),
	FOREIGN KEY ("row_id") REFERENCES "member_committees" ("row_id")
		ON DELETE CASCADE
) -- ENGINE=InnoDB
COMMENT='Data about which committees a member is in (for locale “de”)';

CREATE TABLE IF NOT EXISTS "member_committees__en" (
	"row_id"       INT UNSIGNED NOT NULL,
	"timespan_alt" VARCHAR(200) NOT NULL COMMENT 'Only used if not empty',
	"info"         VARCHAR(300) NOT NULL,
	PRIMARY KEY ("row_id"),
	FOREIGN KEY ("row_id") REFERENCES "member_committees" ("row_id")
		ON DELETE CASCADE
) -- ENGINE=InnoDB
COMMENT='Data about which committees a member is in (for locale “en”)';

-- other
CREATE TABLE IF NOT EXISTS "mlp_steuerseminar" (
	"id"           INT UNSIGNED NOT NULL AUTO_INCREMENT,
	"first_name"   VARCHAR(60) NOT NULL,
	"surname"      VARCHAR(60) NOT NULL,
	"email"        VARCHAR(60) NOT NULL,
	"phone_number" VARCHAR(60) NOT NULL,
	"study_course" VARCHAR(60) NOT NULL,
	"semester"     INT UNSIGNED NOT NULL,
	"comment"      TEXT NOT NULL,
	PRIMARY KEY ("id")
) -- ENGINE=InnoDB
COMMENT='Registration data for MLP tax seminar (not currently in use)';

