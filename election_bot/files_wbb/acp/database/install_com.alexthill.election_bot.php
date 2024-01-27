<?php

use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\column\DefaultTrueBooleanDatabaseTableColumn;
use wcf\system\database\table\column\IntDatabaseTableColumn;
use wcf\system\database\table\column\NotNullIntDatabaseTableColumn;
use wcf\system\database\table\column\NotNullVarchar255DatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\index\DatabaseTableForeignKey;
use wcf\system\database\table\index\DatabaseTableIndex;
use wcf\system\database\table\index\DatabaseTablePrimaryIndex;

return [
    DatabaseTable::create('wbb1_election')
        ->columns([
            ObjectIdDatabaseTableColumn::create('electionID'),
            NotNullIntDatabaseTableColumn::create('threadID'),
            NotNullVarchar255DatabaseTableColumn::create('name'),
            NotNullIntDatabaseTableColumn::create('deadline'),
            NotNullIntDatabaseTableColumn::create('extension'),
            NotNullIntDatabaseTableColumn::create('phase'),
            DefaultTrueBooleanDatabaseTableColumn::create('isActive'),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['threadID'])
                ->referencedTable('wbb1_thread')
                ->referencedColumns(['threadID'])
                ->onDelete('CASCADE'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['electionID']),
        ]),
    DatabaseTable::create('wbb1_election_vote')
        ->columns([
            ObjectIdDatabaseTableColumn::create('voteID'),
            NotNullIntDatabaseTableColumn::create('electionID'),
            IntDatabaseTableColumn::create('userID')->length(10),
            IntDatabaseTableColumn::create('postID')->length(10),
            NotNullVarchar255DatabaseTableColumn::create('voter'),
            NotNullVarchar255DatabaseTableColumn::create('voted'),
            NotNullIntDatabaseTableColumn::create('time'),
            NotNullIntDatabaseTableColumn::create('phase'),
            NotNullIntDatabaseTableColumn::create('count'),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['electionID'])
                ->referencedTable('wbb1_election')
                ->referencedColumns(['electionID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['userID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('SET NULL'),
            DatabaseTableForeignKey::create()
                ->columns(['postID'])
                ->referencedTable('wbb1_post')
                ->referencedColumns(['postID'])
                ->onDelete('SET NULL'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['voteID']),
        ]),
    DatabaseTable::create('wbb1_election_voter')
        ->columns([
            NotNullIntDatabaseTableColumn::create('electionID'),
            NotNullVarchar255DatabaseTableColumn::create('voter'),
            NotNullIntDatabaseTableColumn::create('count'),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['electionID'])
                ->referencedTable('wbb1_election')
                ->referencedColumns(['electionID'])
                ->onDelete('CASCADE'),
        ])
        ->indices([
            DatabaseTableIndex::create('electionIdVoterUniqueIndex')
                ->type(DatabaseTableIndex::UNIQUE_TYPE)
                ->columns(['electionID', 'voter']),
        ]),
];
