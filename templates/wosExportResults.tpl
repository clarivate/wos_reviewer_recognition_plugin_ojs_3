{**
 * plugins/generic/webOfScience/templates/wosExportResults.tpl
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * Export review to Web of Science Reviewer Recignition result page
 *}

<div id="wos-header">
    <div id="wos-background">
        <img src="{$baseUrl}/plugins/generic/webOfScience/templates/image/clarivate_logo_rgb_color.png"/>
    </div>
</div>
<div id="wos-content">
    <div id="wosExport">
        {if $status==201}
            <h2>
            {if $serverAction == 'DUPLICATE_REVIEW'}
                {translate key="plugins.generic.wosrrs.export.duplicate"}
            {else}
                {translate key="plugins.generic.wosrrs.export.successful"}
            {/if}
            </h2>
            <p>
                {if $serverAction == 'REVIEWER_CLAIMED'}
                    {translate key="plugins.generic.wosrrs.export.next.autoClaimed"}
                {elseif $serverAction == 'PARTNER_TO_EMAIL'}
                    {translate key="plugins.generic.wosrrs.export.next.partnerEmailed"} <br>
                    {translate key="plugins.generic.wosrrs.export.next.setAutoClaim"}
                {elseif $serverAction == 'REVIEWER_EMAILED'}
                    {translate key="plugins.generic.wosrrs.export.next.reviewerEmailed"} <br>
                    {translate key="plugins.generic.wosrrs.export.next.setAutoClaim"}
                {elseif $serverAction == 'REVIEWER_UNSUBSCRIBED'}
                    {translate key="plugins.generic.wosrrs.export.next.linkClick"} <br>
                    {translate key="plugins.generic.wosrrs.export.next.setAutoClaim"}
                {/if}
            </p>
        {else }
            <h3>{translate key="plugins.generic.wosrrs.export.error"}</h3>
            <p>
                {if $status==400}
                    {translate key="plugins.generic.wosrrs.export.error.400"}
                {elseif $status==500}
                    {translate key="plugins.generic.wosrrs.export.error.500"}
                {elseif $info }
                    {$info}
                {/if}
            </p>
        {/if}
    </div>
    {if $serverAction == 'PARTNER_TO_EMAIL' ||  $serverAction == 'REVIEWER_EMAILED' || $serverAction == 'REVIEWER_UNSUBSCRIBED'}
        <a href="{$claimURL}" target="_blank" class="pkp_button">{translate key="plugins.generic.wosrrs.export.claimReview"}</a>
    {/if}
</div>
