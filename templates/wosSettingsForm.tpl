{**
 * plugins/generic/webOfScience/templates/wosSettingsForm.tpl
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * Connect to Web of Science Reviewer Recognition service
 *}

<script>
    $(function() {ldelim}
        // Attach the form handler.
        $('#wosConnectionForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<div>
    <form class="pkp_form" id="wosConnectionForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="connect" save=true}">
        {csrf}
        <p>{translate key="plugins.generic.wosrrs.settings.info"}</p>
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="wosConnectionFormNotification"}
        {fbvFormArea id="wosConnectionFormArea"}
            <table width="100%" class="data">
                <tr valign="top">
                    <td class="label">{fieldLabel name="auth_token" key="plugins.generic.wosrrs.settings.auth_token"}</td>
                    <td class="value">
                        {fbvElement type="text" id="auth_token" name="auth_token" value="$auth_token" label="plugins.generic.wosrrs.settings.auth_tokenDescription"}
                    </td>
                </tr>
                <tr valign="top">
                    <td class="label">{fieldLabel name="journalToken" key="plugins.generic.wosrrs.settings.journalToken"}</td>
                    <td class="value">
                        {fbvElement type="text" id="auth_key" name="auth_key" value="$auth_key" label="plugins.generic.wosrrs.settings.journalTokenDescription"}
                    </td>
                </tr>
            </table>
        {/fbvFormArea}
        {fbvFormButtons}
    </form>
</div>
<p>{translate key="plugins.generic.wosrrs.settings.ps"}</p>
