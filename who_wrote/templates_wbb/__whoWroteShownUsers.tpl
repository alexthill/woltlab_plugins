{if $whoWroteShownUsers|isset}
    <li id="whoWroteShownUsers">
        {if $whoWroteShownUsers|count}{icon name='user-pen'} {lang}wbb.thread.who_wrote.showingUsers{/lang}
        {else}{icon name='user-xmark'} {lang}wbb.thread.who_wrote.showingAll{/lang}{/if}
        {lang}wbb.thread.who_wrote.backToThread{/lang}
    </li>
{/if}