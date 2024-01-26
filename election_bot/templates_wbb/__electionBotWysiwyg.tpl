{if !$__wbbThreadQuickReply|empty && $electionBotElections|isset}

{if $board->getPermission('canUseElection')}
<template id="electionVoteDialog">
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
    'WoltLabSuite/Core/Ui/User/Search/Input',
], function (Backend, Ckeditor, CkeditorEvent, Dialog, UserSearchInput) {
    'use strict';

    const wysiwygId = '{if $wysiwygSelector|isset}{@$wysiwygSelector|encodeJS}{else}text{/if}';
    const ckeditorEl = document.getElementById(wysiwygId);

    CkeditorEvent.listenToCkeditor(ckeditorEl).bbcode(({ bbcode }) => {
        if (bbcode === 'v') {
            const dialog = Dialog.dialogFactory().fromId('electionVoteDialog').asPrompt();
            const input = dialog.content.querySelector('#electionVoteDialogInput');
            new UserSearchInput(input, {
                callbackSelect(item) {
                    // some a**** clears the input after this callback, therefore delay the update of the input value
                    queueMicrotask(() => input.value = item.dataset.label);
                },
                delay: 200,
                preventSubmit: false
            });
            dialog.addEventListener('primary', () => {
                const ckeditor = Ckeditor.getCkeditor(ckeditorEl);
                ckeditor.insertText('[v]' + input.value.trim() + '[/v]');
            });
            dialog.show('{jslang}wbb.electionbot.vote.insert{/jslang}');
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
                    Dialog.dialogFactory().fromHtml(json.message).asAlert().show('{jslang}wcf.global.error.title{/jslang}');
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
            icon: 'bars;false'
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
        const feed = {
            feed: async (query) => {
                if (query.length > 20) {
                    return [];
                }
                const url = new URL(window.WSC_API_URL + 'index.php?editor-get-mention-suggestions/');
                url.searchParams.set('query', query);
                const result = await Backend.prepareRequest(url.toString())
                    .get()
                    .allowCaching()
                    .disableLoadingIndicator()
                    .fetchAsJson();
                return result.filter((item) => item.type === 'user').map((item) => ({
                    id: '!' + item.username,
                    text: item.username,
                    icon: item.avatarTag,
                    objectId: item.userID,
                    type: 'v',
                }));
            },
            itemRenderer: (item) => {
                const el = document.createElement('span');
                el.className = 'ckeditor5__mention';
                el.innerHTML = item.icon + ' ' + item.text;
                return el;
            },
            marker: '!',
            minimumCharacters: 3,
        };
        const mention = { feeds: [feed] };
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