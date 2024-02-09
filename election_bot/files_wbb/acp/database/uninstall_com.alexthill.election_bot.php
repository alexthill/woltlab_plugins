<?php

use wcf\system\database\table\PartialDatabaseTable;
use wcf\system\database\table\index\DatabaseTableForeignKey;

return [
    PartialDatabaseTable::create('wbb1_election_vote')
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['electionID'])
                ->referencedTable('wbb1_election')
                ->referencedColumns(['electionID'])
                ->onDelete('CASCADE')
                ->drop(),
    PartialDatabaseTable::create('wbb1_election_voter')
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['electionID'])
                ->referencedTable('wbb1_election')
                ->referencedColumns(['electionID'])
                ->onDelete('CASCADE')
                ->drop(),
        ]),
];
