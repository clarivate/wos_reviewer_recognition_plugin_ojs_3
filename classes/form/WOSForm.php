<?php

/**
 * @file plugins/generic/webOfScience/classes/form/WOSForm.php
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class WOSForm
 * @ingroup plugins_generic_webOfScience
 *
 * @brief Plugin settings: connect to a Web of Science Reviewer Recognition service
 */

namespace APP\plugins\generic\webOfScience\classes\form;

use Exception;
use PKP\form\Form;
use PKP\form\validation\FormValidator;
use PKP\form\validation\FormValidatorPost;
use PKP\form\validation\FormValidatorCSRF;
use PKP\notification\PKPNotification;

use APP\core\Application;
use APP\template\TemplateManager;
use APP\notification\NotificationManager;
use APP\plugins\generic\webOfScience\WebOfSciencePlugin;

class WOSForm extends Form {

    /** @var $_plugin object */
    var object $_plugin;

    /** @var $_journalId int */
    var int $_journalId;

    /**
     * Constructor
     *
     * @param $plugin WebOfSciencePlugin
     * @param $journalId int
     * @see Form::Form()
     */
    function __construct($plugin, $journalId) {
        $this->_plugin = $plugin;
        $this->_journalId = $journalId;
        parent::__construct($plugin->getTemplateResource('wosSettingsForm.tpl'));
        $this->addCheck(new FormValidator($this, 'auth_token', FormValidator::FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.wosrrs.settings.authTokenRequired'));
        $this->addCheck(new FormValidator($this, 'auth_key', FormValidator::FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.wosrrs.settings.journalTokenRequired'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * @see Form::initData()
     */
    function initData(): void {
        $this->setData('auth_token', $this->_plugin->getSetting($this->_journalId, 'auth_token'));
        $this->setData('auth_key', $this->_plugin->getSetting($this->_journalId, 'auth_key'));
    }

    /**
     * @see Form::readInputData()
     */
    function readInputData(): void {
        $this->readUserVars(['auth_token', 'auth_key']);
    }

    /**
     * Fetch the form
     *
     * @copydoc Form::fetch()
     * @throws Exception
     */
    function fetch($request, $template = null, $display = false): ?string {
        $templateManager = TemplateManager::getManager($request);
        $templateManager->assign('pluginName', $this->_plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    /**
     * @see Form::execute()
     */
    function execute(...$functionArgs): void {
        $this->_plugin->updateSetting($this->_journalId, 'auth_token', $this->getData('auth_token') , 'string');
        $this->_plugin->updateSetting($this->_journalId, 'auth_key', $this->getData('auth_key'), 'string');
        $notificationManager = new NotificationManager();
        $notificationManager->createTrivialNotification(
            Application::get()->getRequest()->getUser()->getId(),
            PKPNotification::NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('plugins.generic.wosrrs.notifications.settingsUpdated')]
        );
        parent::execute(...$functionArgs);
    }

}
