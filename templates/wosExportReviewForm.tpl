{**
 * plugins/generic/webOfScience/templates/wosExportReviewForm.tpl
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * Connect to Web of Science Reviewer Recognition
 *}

{strip}
    {assign var="pageTitle" value="plugins.generic.wosrrs.displayName"}
{/strip}

<script>
    $(function() {ldelim}
        // Attach the form handler
        $('#wosExportReviewForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<div>
    <form class="pkp_form" id="wosExportReviewForm" method="post" action="{$pageURL}">
        <div id="wos-header">
            <div id="wos-background">
                <img src="{$baseUrl}/plugins/generic/webOfScience/templates/image/clarivate_logo_rgb_color.png"/>
            </div>
        </div>
        <div id="wos-content">
            <div id="wosExport">
                <h2 class="mb-2">{translate key="plugins.generic.wosrrs.confirmation.title"}</h2>
                <p>{translate key="plugins.generic.wosrrs.confirmation.termsAndConditions"}</p>
            </div>
        </div>
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="wosExportReviewFormNotification"}
        {fbvFormArea id="wosExportReviewFormArea"}
        {/fbvFormArea}
        {fbvFormButtons submitText="plugins.generic.wosrrs.button.submitExportReview" }
    </form>
</div>
