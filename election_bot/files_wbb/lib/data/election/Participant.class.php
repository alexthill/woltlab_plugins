<?php

namespace wbb\data\election;

use wcf\data\DatabaseObject;
use wcf\util\StringUtil;

/**
 * Represents a participant in the elections of a thread
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.election_bot
 *
 * @property-read   int         $participantID  unique id of the participant
 * @property-read   int         $threadID       id of the thread
 * @property-read   string      $name           name of participant
 * @property-read   string      $extra          some extra text
 * @property-read   int         $color          color to mark the participant
 * @property-read   bool        $active         whether the participant is activly participating
 */
class Participant extends DatabaseObject {

    const COLOR_OPTIONS = [
        1 => 'wcf.ckeditor.marker.success',
        2 => 'wcf.ckeditor.marker.warning',
        3 => 'wcf.ckeditor.marker.error',
        4 => 'wcf.ckeditor.marker.info',
    ];

    /**
     * database table for this object
     * @var string
     */
    protected static $databaseTableName = 'election_participant';


    /**
     * Get the css class corresponding to the given color code.
     */
    public static function colorToMarkerClass(int $color): string {
        return match ($color) {
            1 => 'marker-success',
            2 => 'marker-warning',
            3 => 'marker-error',
            4 => 'marker-info',
            default => '',
        };
    }

    /**
     * Get the css class to color in this participant.
     */
    public function getMarkerClass(): string {
        return Participant::colorToMarkerClass($this->color);
    }

    /**
     * Decorate the name with HTML markup according the participant's properties.
     */
    public function decorateName(): string {
        $class = $this->getMarkerClass();
        $name = StringUtil::encodeHTML($this->name);
        if (!$this->active) {
            $name = "<s>$name</s>";
        }
        return $class === '' ? $name : "<mark class=\"$class\">$name</mark>";
    }
}

