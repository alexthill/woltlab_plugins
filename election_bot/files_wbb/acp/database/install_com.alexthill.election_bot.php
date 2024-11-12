<?php

use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\column\DefaultTrueBooleanDatabaseTableColumn;
use wcf\system\database\table\column\DefaultFalseBooleanDatabaseTableColumn;
use wcf\system\database\table\column\FloatDatabaseTableColumn;
use wcf\system\database\table\column\IntDatabaseTableColumn;
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
            NotNullVarchar255DatabaseTableColumn::create('name0'),
            NotNullInt10DatabaseTableColumn::create('deadline'),
            NotNullInt10DatabaseTableColumn::create('extension'),
            NotNullInt10DatabaseTableColumn::create('phase'),
            DefaultTrueBooleanDatabaseTableColumn::create('isActive'),
            DefaultFalseBooleanDatabaseTableColumn::create('silenceBetweenPhases'),
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
            IntDatabaseTableColumn::create('userID')->length(10),
            IntDatabaseTableColumn::create('postID')->length(10),
            NotNullVarchar255DatabaseTableColumn::create('voter'),
            NotNullVarchar255DatabaseTableColumn::create('voted'),
            NotNullInt10DatabaseTableColumn::create('time'),
            NotNullInt10DatabaseTableColumn::create('phase'),
            FloatDatabaseTableColumn::create('count')->notNull(),
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
            NotNullInt10DatabaseTableColumn::create('electionID'),
            NotNullVarchar255DatabaseTableColumn::create('voter'),
            FloatDatabaseTableColumn::create('count')->notNull(),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['electionID'])
                ->referencedTable('wbb1_election')
                ->referencedColumns(['electionID'])
                ->onDelete('CASCADE'),
        ])
        ->indices([
            DatabaseTableIndex::create('voter_unique')
                ->type(DatabaseTableIndex::UNIQUE_TYPE)
                ->columns(['electionID', 'voter']),
        ]),
    DatabaseTable::create('wbb1_election_participant')
        ->columns([
            ObjectIdDatabaseTableColumn::create('participantID'),
            NotNullInt10DatabaseTableColumn::create('threadID'),
            NotNullVarchar255DatabaseTableColumn::create('name'),
            NotNullVarchar255DatabaseTableColumn::create('extra'),
            NotNullInt10DatabaseTableColumn::create('color'),
            DefaultTrueBooleanDatabaseTableColumn::create('active'),
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
                ->columns(['participantID']),
        ]),
];
