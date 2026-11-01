{if !$__wbbThreadQuickReply|empty && $electionBotElections|isset}

{if $board->getPermission('canUseElection')}
<template id="electionVoteDialog">
    {if $electionBotElections|count > 1}<dl>
        <dt>
            <label for="electionVoteDialogElection">{lang}wbb.electionbot.votecount.insert.election{/lang}</label>
        </dt>
        <dd>
            <select id="electionVoteDialogElection">
            {foreach from=$electionBotElections item=$election}
                <option value="{@$election->electionID}">{@$election->getTitle()}</option>
            {/foreach}
            </select>
        </dd>
    </dl>{/if}
    <dl>
        <dt>
            <label for="electionVoteDialogInput">{lang}wbb.electionbot.vote.insert.for{/lang}</label>
        </dt>
        <dd>
            <input type="text" id="electionVoteDialogInput" class="long" maxlength="255">
            <small>{lang}wbb.electionbot.vote.insert.for.description{/lang}</small>
        </dd>
    </dl>
</template>
{/if}

<script data-relocate="true">
require([
    'WoltLabSuite/Core/Ajax/Backend',
    'WoltLabSuite/Core/Component/Ckeditor',
    'WoltLabSuite/Core/Component/Ckeditor/Event',
    'WoltLabSuite/Core/Component/Dialog',
    'WoltLabSuite/Core/Ui/Search/Input',
], function (Backend, Ckeditor, CkeditorEvent, Dialog, UiSearchInput) {
    'use strict';

    const wysiwygId = '{if $wysiwygSelector|isset}{@$wysiwygSelector|encodeJS}{else}text{/if}';
    const ckeditorEl = document.getElementById(wysiwygId);

    CkeditorEvent.listenToCkeditor(ckeditorEl).bbcode(({ bbcode }) => {
        if (bbcode === 'v') {
            const dialog = Dialog.dialogFactory().fromId('electionVoteDialog').asPrompt();
            const input = dialog.content.querySelector('#electionVoteDialogInput');
            new UiSearchInput(input, {
                ajax: {
                    className: "wbb\\data\\election\\ParticipantAction",
                    parameters: { data: { threadID: document.body.dataset.threadId } },
                },
                callbackSelect(item) { return true; },
                delay: 500,
                minLength: 1,
            });
            dialog.addEventListener('primary', () => {
                const ckeditor = Ckeditor.getCkeditor(ckeditorEl);
                const electionSelect = dialog.content.querySelector('#electionVoteDialogElection');
                if (electionSelect) {
                    ckeditor.insertText('[v=\'' + electionSelect.value + '\']' + input.value.trim() + '[/v]');
                } else {
                    ckeditor.insertText('[v]' + input.value.trim() + '[/v]');
                }
            });
            dialog.show('{jslang}wbb.electionbot.vote.insert{/jslang}');
            input.focus();
            return true;
        }
        if (bbcode === 'votecount' || bbcode === 'votehistory') {
            const url = new URL('{link controller="ElectionBotVoteCount" application="wbb" encode=false}{/link}');
            url.searchParams.set('threadID', document.body.dataset.threadId);
            if (bbcode === 'votehistory') {
                url.searchParams.set('history', 1);
            }
            Dialog.dialogFactory().usingFormBuilder().fromEndpoint(url).then(
                ({ ok, result }) => {
                    if (ok) {
                        Ckeditor.getCkeditor(ckeditorEl).insertHtml(result.html);
                    }
                },
                async ({ response }) => {
                    const json = await response.json();
                    Dialog.dialogFactory().fromHtml(json.message).asAlert()
                        .show('{jslang}wcf.global.error.title{/jslang}');
                }
            );
            return true;
        }
        return false;
    });

    CkeditorEvent.listenToCkeditor(ckeditorEl).setupConfiguration(({ configuration, features }) => {
        const items = [];
        {if $board->getPermission('canUseElection')}
        configuration.woltlabBbcode.push({
            name: 'v',
            label: '{jslang}wcf.editor.button.election.vote{/jslang}',
            icon: 'person-booth;false'
        });
        items.push('woltlabBbcode_v');
        {/if}
        {if $electionBotElections|count}
        configuration.woltlabBbcode.push({
            name: 'votecount',
            label: '{jslang}wcf.editor.button.election.votecount{/jslang}',
            icon: 'square-poll-horizontal;false'
        });
        items.push('woltlabBbcode_votecount');
        configuration.woltlabBbcode.push({
            name: 'votehistory',
            label: '{jslang}wcf.editor.button.election.votehistory{/jslang}',
            icon: 'clock-rotate-left;false'
        });
        items.push('woltlabBbcode_votehistory');
        {/if}
        if (items.length > 1) {
            configuration.woltlabToolbarGroup['election'] = {
                icon: 'check-to-slot;false',
                label: '{jslang}wcf.editor.button.election{/jslang}'
            };
            configuration.toolbar.push({ label: 'woltlabToolbarGroup_election', items });
        }

        {if $board->getPermission('canUseElection')}
        if (!features.mention) {
            console.warn('CKeditor does not support mentions, but support is needed for election bot plugin');
            return;
        }

        function createFeed(marker, bbcode) {
            return {
                feed: async (query) => {
                    if (query.length > 20) return [];
                    const url = new URL('{link controller="ElectionBotSuggestions" application="wbb" encode=false}{/link}');
                    url.searchParams.set('threadID', document.body.dataset.threadId);
                    url.searchParams.set('query', query);
                    const result = await Backend.prepareRequest(url.toString())
                        .get()
                        .allowCaching()
                        .disableLoadingIndicator()
                        .fetchAsJson();
                    return result.map((item) => ({
                        id: marker + item.label,
                        text: item.label,
                        objectId: 0,
                        type: bbcode,
                        item,
                    }));
                },
                itemRenderer: ({ item }) => {
                    const el = document.createElement('span');
                    el.className = 'ckeditor5__mention';
                    if (!item.active) {
                        el.style.textDecoration = 'line-through';
                    }
                    if (item.icon.length > 0) {
                        el.innerHTML += item.icon + ' ';
                    }
                    el.appendChild(document.createTextNode(item.label));
                    return el;
                },
                marker,
                minimumCharacters: 2,
            };
        }
        const mention = { feeds: [createFeed('!', 'v'), createFeed('?', 'v2')] };
        // I dont understand why, but this line is needed for the editor to recognize that `mention` is set
        configuration.mention = mention;
        // WoltLabSuite/Core/Component/Ckeditor/Mention will override `configuration.mention`
        // but that can be prevented by defining a getter and setter method for it
        Object.defineProperty(configuration, 'mention', {
            get: function() { return mention; },
            set: function(val) {
                mention.feeds = mention.feeds.concat(val.feeds);
                if (val.dropdownLimit !== undefined) mention.dropdownLimit = val.dropdownLimit;
            },
        });
        {/if}
    });
});
</script>
{/if}

