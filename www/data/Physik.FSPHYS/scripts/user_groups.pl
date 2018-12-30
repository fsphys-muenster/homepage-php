#!/usr/bin/env perl
use strict;
use warnings;
use Fcntl;
use SDBM_File;

use constant DB_PATH => '/www/data/groups';
die 'Must give exactly one argument, received ' . @ARGV unless @ARGV == 1;
my $user = $ARGV[0];
my %db;
tie %db, 'SDBM_File', DB_PATH, O_RDONLY, 0
	or die 'Couldn’t open SDBM file “' . DB_PATH . "”: $!; aborting";
my $result = $db{$user};
die "Username $user does not exist" unless $result;
print $result;

