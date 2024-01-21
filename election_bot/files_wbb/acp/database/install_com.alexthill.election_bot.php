<?php

use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\column\DefaultTrueBooleanDatabaseTableColumn;
use wcf\system\database\table\column\NotNullInt10DatabaseTableColumn;
use wcf\system\database\table\column\NotNullVarchar255DatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\index\DatabaseTableForeignKey;
use wcf\system\database\table\index\DatabaseTableIndex;
use wcf\system\database\table\index\DatabaseTablePrimaryIndex;

return [
    DatabaseTable::create('wbb1_election')
        ->columns([
            ObjectIdDatabaseTableColumn::create('electionID'),
            NotNullInt10DatabaseTableColumn::create('threadID'),
            NotNullVarchar255DatabaseTableColumn::create('name'),
            NotNullInt10DatabaseTableColumn::create('deadline'),
            NotNullInt10DatabaseTableColumn::create('extension'),
            NotNullInt10DatabaseTableColumn::create('phase'),
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
            NotNullInt10DatabaseTableColumn::create('electionID'),
            NotNullInt10DatabaseTableColumn::create('userID'),
            NotNullInt10DatabaseTableColumn::create('postID'),
            NotNullVarchar255DatabaseTableColumn::create('voter'),
            NotNullVarchar255DatabaseTableColumn::create('voted'),
            NotNullInt10DatabaseTableColumn::create('time'),
            NotNullInt10DatabaseTableColumn::create('phase'),
            NotNullInt10DatabaseTableColumn::create('count'),
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
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['postID'])
                ->referencedTable('wbb1_post')
                ->referencedColumns(['postID'])
                ->onDelete('CASCADE'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['voteID']),
        ]),
    DatabaseTable::create('wbb1_election_voter')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('electionID'),
            NotNullVarchar255DatabaseTableColumn::create('voter'),
            NotNullInt10DatabaseTableColumn::create('count'),
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
