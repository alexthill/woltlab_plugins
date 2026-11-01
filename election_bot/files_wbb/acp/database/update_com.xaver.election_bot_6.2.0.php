<?php

use wcf\system\database\table\PartialDatabaseTable;
use wcf\system\database\table\column\DefaultFalseBooleanDatabaseTableColumn;
use wcf\system\database\table\column\NotNullInt10DatabaseTableColumn;

return [
    PartialDatabaseTable::create('wbb1_election')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('reminder'),
            DefaultFalseBooleanDatabaseTableColumn::create('isReminded'),
        ]),
];
