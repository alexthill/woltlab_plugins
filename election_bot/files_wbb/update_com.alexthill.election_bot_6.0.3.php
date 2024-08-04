<?php

use wcf\system\WCF;

$sql = "ALTER TABLE wbb1_election_vote MODIFY count FLOAT NOT NULL";
$statement = WCF::getDB()->prepareStatement($sql);
$statement->execute();

$sql = "ALTER TABLE wbb1_election_voter MODIFY count FLOAT NOT NULL";
$statement = WCF::getDB()->prepareStatement($sql);
$statement->execute();

