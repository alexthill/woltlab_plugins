{if $__wcf->getSession()->getPermission('user.board.thread.canViewWhoWrote')}
<script data-relocate="true">
    (function() {
        const container = document.querySelector('.whoWroteInfoBox > div > .inlineList');
        const list = document.querySelectorAll('.whoWroteInfoBox > div > ul > li.moreWrites');
        if (list.length > 0) {
            const btn = document.createElement('button');
            btn.className = 'button small';
            btn.innerHTML = '{jslang}wbb.alexthill.who_wrote.more{/jslang}';
            btn.addEventListener('click', () => {
                if (list[0].style.display === 'none') {
                    forEach(list, (element) => element.style.display = '');
                    btn.innerHTML = '{jslang}wbb.alexthill.who_wrote.less{/jslang}';
                } else {
                    forEach(list, (element) => element.style.display = 'none');
                    btn.innerHTML = '{jslang}wbb.alexthill.who_wrote.more{/jslang}';
                }
            });
            container.appendChild(btn);
        }

        {if $whoWroteByName|count >= 3}
        const selectList = document.getElementById('whoWroteSelectList');
        const selectBtn = document.getElementById('whoWroteSelectBtn');
        const showBtn = document.getElementById('whoWroteShowPostsBtn');
        selectBtn.addEventListener('click', () => {
            if (selectList.style.display === 'none') {
                selectList.style.display = '';
                showBtn.style.display = '';
                selectBtn.innerHTML = '{jslang}wbb.alexthill.who_wrote.multiSelectHide{/jslang}';
            } else {
                selectList.style.display = 'none';
                showBtn.style.display = 'none';
                selectBtn.innerHTML = '{jslang}wbb.alexthill.who_wrote.multiSelect{/jslang}';
            }
        });
        const selectedIds = new Set();
        for (const checkbox of selectList.querySelectorAll('input[type=checkbox]')) {
            if (checkbox.checked) {
                selectedIds.add(checkbox.value);
            }
            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    selectedIds.add(checkbox.value);
                } else {
                    selectedIds.delete(checkbox.value);
                }
                if (selectedIds.size === 0) {
                    showBtn.classList.add('disabled');
                } else {
                    showBtn.classList.remove('disabled');
                    showBtn.href = showBtn.dataset.baseLink + '&users=' + Array.from(selectedIds).join(',');
                }
            });
        }
        if (selectedIds.size !== 0) {
            showBtn.classList.remove('disabled');
            showBtn.href = showBtn.dataset.baseLink + '&users=' + Array.from(selectedIds);
        }
        {/if}
    })();
</script>
<section class="box whoWroteInfoBox">
    <h2 class="boxTitle">{lang}wbb.alexthill.who_wrote.title{/lang}</h2>
    <div class="boxContent">
        <ul class="inlineList commaSeparated">
            {assign var=counter value=1}
            {foreach from=$whoWrote item=$user}
                <li{if WBB_WHO_WROTE_COUNT !== 0 && $counter > WBB_WHO_WROTE_COUNT} class="moreWrites" style="display:none;"{/if}>
                    {if $user['userID'] != 0}<a class="userLink"
                        href="{link controller='ThreadUserPosts' application='wbb' id=$threadID}users={@$user['userID']}{/link}"
                        data-object-id="{@$user['userID']}">{$user['username']}</a>
                    {else}
                        {lang}wcf.user.guest{/lang}
                    {/if}
                    {if $__wcf->getSession()->getPermission('user.board.thread.canViewWhoWroteCount')}({#$user['count']}){/if}
                </li>
                {assign var=counter value=$counter + 1}
            {/foreach}
        </ul>
        {if $whoWroteByName|count >= 3}
            <button id="whoWroteSelectBtn" class="button small">{lang}wbb.alexthill.who_wrote.multiSelect{/lang}</button>
            <a id="whoWroteShowPostsBtn" class="button small disabled" style="display:none;" data-base-link="{link controller='ThreadUserPosts' application='wbb' id=$threadID}{/link}">{lang}wbb.alexthill.who_wrote.showPosts{/lang}</a>
            <ul id="whoWroteSelectList" style="display:none;">
                {foreach from=$whoWroteByName key=$username item=$userID}
                    <li><input type="checkbox" value="{@$userID}" id="whoWroteCheck{@$userID}"><label for="whoWroteCheck{@$userID}">{$username}</label></li>
                {/foreach}
            </ul>
        {/if}
    </div>
</section>
{/if}