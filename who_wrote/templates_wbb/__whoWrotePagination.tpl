{if $whoWroteShownUsers|isset}
    {capture assign='__contentInteractionPagination'}
        {pages print=true assign=pagesLinks application='wbb' controller='ThreadUserPosts' object=$thread link="$whoWroteUsersParam&pageNo=%d"}
    {/capture}
    {assign var='__contentInteractionPagination' value=$__contentInteractionPagination|trim}
{/if}