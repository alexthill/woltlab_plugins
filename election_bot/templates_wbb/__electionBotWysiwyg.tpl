<script data-relocate="true">
// the wysiwyg event in wysiwyg.tpl is fired *before* the initialization code for the editor
// how am I supposed to extend the functionality of the editor, before its initialization
// by delaying its execution with the DOMContentLoaded event
// the programmers at woltlab must be geniuses
window.addEventListener('DOMContentLoaded', function () {
    require([
        'WoltLabSuite/Core/Component/Ckeditor/Event',
        'WoltLabSuite/Core/Ajax/Backend'
    ], function (CkeditorEvent, Backend) {
        'use strict';
        
        async function getPossibleMentions(query) {
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
                text: '!' + item.username,
                icon: item.avatarTag,
                type: 'v',
            }));
        }
        
        const wysiwygId = '{if $wysiwygSelector|isset}{@$wysiwygSelector|encodeJS}{else}text{/if}';
        const ckeditorEl = document.getElementById(wysiwygId);
        
        CkeditorEvent.listenToCkeditor(ckeditorEl).setupConfiguration(({ configuration, features }) => {
            if (!features.mention) {
                console.warn('CKeditor does not support mentions, but support is needed for election bot plugin');
                return;
            }
            const feed = {
                feed: (query) => getPossibleMentions(query),
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
            // I dont know why, but this line is needed for the editor to recognize that `mention` is set
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
        });
    });
});
</script>