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
        ]),
    PartialDatabaseTable::create('wbb1_election_voter')
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['electionID'])
                ->referencedTable('wbb1_election')
                ->referencedColumns(['electionID'])
                ->onDelete('CASCADE')
                ->drop(),
        ]),
    PartialDatabaseTable::create('wbb1_election_participant_alias')
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['participantID'])
                ->referencedTable('wbb1_election_participant')
                ->referencedColumns(['participantID'])
                ->onDelete('CASCADE')
                ->drop(),
        ])
];
