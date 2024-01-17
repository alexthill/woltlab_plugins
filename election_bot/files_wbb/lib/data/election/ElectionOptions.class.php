<?php

namespace wbb\data\election;

/**
 * Represents an election options.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 */
class ElectionOptions {

    public $start = false;
    
    public $end = false;
    
    public $changeDeadline = false;
    
    public $deadline = null;
    
    public static function fromParameters($parameters) {
        $options = new ElectionOptions();
        if (!empty($parameters['electionStart'])) {
            $options->start = true;
        }
        if (!empty($parameters['electionEnd'])) {
            $options->end = true;
        }
        if (!empty($parameters['electionChangeDeadline'])) {
            $options->changeDeadline = true;
        }
        if (($options->changeDeadline || $options->start) && !empty($parameters['electionDeadline'])) {
            $deadline = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $parameters['electionDeadline']);
            $options->deadline = $deadline;
        }
        return $options;
    }
}
