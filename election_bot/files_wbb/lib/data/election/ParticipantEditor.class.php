<?php

namespace wbb\data\election;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit a participant.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 *
 * @method static   Participant     create(array $parameters = [])
 * @method          Participant     getDecoratedObject()
 * @mixin           Participant
 */
class ParticipantEditor extends DatabaseObjectEditor {
    /**
     * @inheritDoc
     */
    protected static $baseClass = Participant::class;
}
