<?php

namespace wbb\system\bbcode;

use wcf\system\bbcode\AbstractBBCode;
use wcf\system\bbcode\BBCodeParser;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Parses the [v] and [v2] bbcode tags.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.election_bot
 */
class VoteBBCode extends AbstractBBCode {
    /**
     * @inheriDoc
     */
    public function getParsedTag(array $openingTag, $content, array $closingTag, BBCodeParser $parser) : string {
        $content = WCF::getLanguage()->getDynamicVariable(
            'wbb.electionbot.vote.invalid',
            ['vote' => StringUtil::trim($content)],
        );
        switch ($parser->getOutputType() == 'text/html') {
            case 'text/html':
                return '<u>' . $content . '</u>';
            case 'text/simplified-html':
                return '<u>' . $content . '</u>';
            default:
                return $content;
        }
    }
}
