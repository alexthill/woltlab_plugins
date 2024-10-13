<?php

use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\column\NotNullInt10DatabaseTableColumn;
use wcf\system\database\table\column\NotNullVarchar255DatabaseTableColumn;
use wcf\system\database\table\index\DatabaseTableForeignKey;
use wcf\system\database\table\index\DatabaseTableIndex;

return [
    DatabaseTable::create('wbb1_election_participant_alias')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('participantID'),
            NotNullInt10DatabaseTableColumn::create('threadID'),
            NotNullVarchar255DatabaseTableColumn::create('alias'),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['participantID'])
                ->referencedTable('wbb1_election_participant')
                ->referencedColumns(['participantID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['threadID'])
                ->referencedTable('wbb1_thread')
                ->referencedColumns(['threadID'])
                ->onDelete('CASCADE'),
        ])
        ->indices([
            DatabaseTableIndex::create('thread_alias_unique')
                ->type(DatabaseTableIndex::UNIQUE_TYPE)
                ->columns(['threadID', 'alias']),
        ]),
];
