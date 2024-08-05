{**
 * plugins/generic/webOfScience/wosExportStep.tpl
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * Sync review with Web of Science Reviewer Recognition step shown on step 4 - completed page
 *}

</div>
<div id="wos-export" class="section">
    <h3>{translate key="plugins.generic.wosrrs.notice.title"}</h3>

        {if $reviewAssignment->getRecommendation() === null || $reviewAssignment->getRecommendation() === ''}
        <button type="submit" class="wos-button" disabled>
            <span title="{translate key="plugins.generic.wosrrs.button.completeReview"}">
                <img src="{$baseUrl}/plugins/generic/webOfScience/templates/image/clarivate_logo_rgb_top_black.png" height="30" width="30">
                {translate key="plugins.generic.wosrrs.button.completeReview"}
            </span>
        </button>
        {elseif !$published}
        <div class="pkp_linkActions">
            {assign var=contextId value="reviewStep4"}
            {assign var=staticId value=$contextId|concat:"-":$exportReviewAction->getId():"-button"}
            {assign var=buttonId value=$staticId|concat:"-"|uniqid}
            <a href="#" id="{$buttonId|escape}" title="{translate key="plugins.generic.wosrrs.button.submitExportReview"}" class="pkp_controllers_linkAction pkp_linkaction_{$exportReviewAction->getId()} pkp_linkaction_icon_{$exportReviewAction->getImage()}">
                <button id="sendToWOS" type="submit" class="wos-button" style="cursor: pointer;">
                    <img src="{$baseUrl}/plugins/generic/webOfScience/templates/image/clarivate_logo_rgb_top_color.png" height="30" width="30">
                    {translate key="plugins.generic.wosrrs.button.submitExportReview"}
                </button>
            </a>
        </div>
        <script>
            // Attach the action handler to the button.
            $(function() {ldelim}
                $('#{$buttonId}').pkpHandler(
                    '$.pkp.controllers.linkAction.LinkActionHandler',
                        {include file="linkAction/linkActionOptions.tpl" action=$exportReviewAction selfActivate=$selfActivate staticId=$staticId}
                    );
            {rdelim});
        </script>
        {else}
        <button type="submit" class="wos-button" disabled>
            <span title="{translate key="plugins.generic.wosrrs.button.publishedReview"}">
                <img src="{$baseUrl}/plugins/generic/webOfScience/templates/image/clarivate_logo_rgb_top_black.png" height="30" width="30">
                {translate key="plugins.generic.wosrrs.button.publishedReview"}
            </span>
        </button>
        {/if}