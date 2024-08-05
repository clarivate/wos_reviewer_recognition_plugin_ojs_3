<?php

/**
 * @file WebOfSciencePlugin.php
 *
 * @class WebOfSciencePlugin
 */

namespace APP\plugins\generic\webOfScience;

use APP\core\Application;

use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\core\JSONMessage;
use PKP\plugins\PluginRegistry;
use PKP\db\DAORegistry;
use PKP\facades\Locale;

use APP\facades\Repo;
use APP\template\TemplateManager;
use APP\plugins\generic\webOfScience\WOSHandler;
use APP\plugins\generic\webOfScience\classes\WOSReview;
use APP\plugins\generic\webOfScience\classes\WOSReviewsDAO;
use APP\plugins\generic\webOfScience\classes\WOSMigration;
use APP\plugins\generic\webOfScience\classes\form\WOSForm;

class WebOfSciencePlugin extends GenericPlugin {

    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled()) {
            DAORegistry::registerDAO('WOSReviewsDAO', new WOSReviewsDAO());
            Hook::add('TemplateManager::display', [$this, 'handleTemplateDisplay']);
            Hook::add('TemplateManager::fetch', [$this, 'handleTemplateFetch']);
            Hook::add('LoadHandler', [$this, 'handleRequest']);
        }
        return $success;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.wosrrs.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.wosrrs.description');
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs): array
    {
        $router = $request->getRouter();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'connect',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, [
                            'verb' => 'connect',
                            'plugin' => $this->getName(),
                            'category' => 'generic'
                        ]),
                        $this->getDisplayName()
                    ),
                    __('plugins.generic.wosrrs.settings.connection'),
                    null
                ),
            ] : [],
            parent::getActions($request, $actionArgs)
        );
    }

    /**
     * @see GenericPlugin::manage()
     */
    function manage($args, $request) {
//        $templateManager = TemplateManager::getManager($request);
//        $templateManager->registerPlugin('function', 'plugin_url', [$this, 'smartyPluginUrl']);
        switch ($request->getUserVar('verb')) {
            case 'connect':
                $context = $request->getContext();
                $form = new WOSForm($this, $context->getId());
                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    function handleRequest($hookName, $params): bool
    {
        $page = $params[0];
        if ($page == 'reviewer' && $this->getEnabled()) {
            $op = $params[1];
            if ($op == 'exportReview') {
                define('HANDLER_CLASS', WOSHandler::class);
                WOSHandler::setPlugin($this);
                return true;
            }
        }
        return false;
    }

    /**
     * @copydoc Plugin::getInstallMigration()
     */
    public function getInstallMigration(): WOSMigration
    {
        return new WOSMigration();
    }

    /**
     * Get the stylesheet for this plugin
     *
     * @return string
     */
    function getStyleSheet(): string
    {
        return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'wos.css';
    }

    /**
     * Hook callback: register output filter to add data citation to submission
     * summaries; add data citation to reading tools' suppfiles and metadata views.
     * @see TemplateManager::display()
     */
    function handleTemplateDisplay($hookName, $args): bool
    {
        $request = Application::get()->getRequest();
        if($this->getEnabled()) {
            $templateManager = $args[0];
            // Assign our private stylesheet, for front and back ends.
            $templateManager->addStyleSheet(
                'webOfScience',
                $request->getBaseUrl() . '/' . $this->getStyleSheet(),
                ['contexts' => ['frontend', 'backend']]
            );
        }
        return false;
    }

    function handleTemplateFetch($hookName, $args): bool
    {
        if ($this->getEnabled()) {
            $templateManager = $args[0];
            $template = $args[1];
            $filterName = '';
            if ($template == 'reviewer/review/reviewCompleted.tpl') {
                $filterName = 'completedSubmissionOutputFilter';
            } elseif ($template == 'reviewer/review/step3.tpl') {
                $filterName = 'step3SubmissionOutputFilter';
            }
            if ($filterName !== '') {
                $templateManager->registerFilter('output', [$this, $filterName]);
            }
        }
        return false;
    }

    function step3SubmissionOutputFilter($output, $templateManager) {
        $plugin = PluginRegistry::getPlugin('generic', $this->getName());
        $submission = $templateManager->getTemplateVars('submission');
        $journalId = $submission->getJournalId();
        $auth_token = $plugin->getSetting($journalId, 'auth_token');
        // Only display if the plugin has been set up
        if($auth_token) {
            preg_match_all('/<div class="section formButtons form_buttons ">/s', $output, $matches, PREG_OFFSET_CAPTURE);
            preg_match('/id="wos-info"/s', $output, $done);
            if ( ! is_null(array_values(array_slice($matches[0], -1))[0][1])) {
                $match = array_values(array_slice($matches[0], -1))[0][1];
                $beforeInsertPoint = substr($output, 0, $match);
                $afterInsertPoint = substr($output, $match - strlen($output));
                $newOutput = $beforeInsertPoint;
                if (empty($done)){
                    $newOutput .= $templateManager->fetch($plugin->getTemplateResource('wosNotificationStep.tpl'));
                }
                $newOutput .= $afterInsertPoint;
                $output = $newOutput;
            }
        }
        $templateManager->unregisterFilter('output', 'step3SubmissionOutputFilter');
        return $output;
    }

    /**
     * Output filter adds Web of Science export step to submission process
     *
     * @param $output
     * @param $templateManager
     * @return mixed|string
     * @throws \Exception
     */
    function completedSubmissionOutputFilter($output, $templateManager): mixed
    {
        $plugin = PluginRegistry::getPlugin('generic', $this->getName());
        $submission = $templateManager->getTemplateVars('submission');
        $journalId = $submission->getJournalId();
        $auth_token = $plugin->getSetting($journalId, 'auth_token');
        // Only display if the plugin has been set up
        if($auth_token) {
            $reviewAssignment = $templateManager->getTemplateVars('reviewAssignment');
            $reviewId = $reviewAssignment->getId();
            $wosReviewsDAO = DAORegistry::getDAO('WOSReviewsDAO');
            $published = $wosReviewsDAO->getWOSReviewIdByReviewId($reviewId);
            $templateManager->unregisterFilter('output', [$this, 'completedSubmissionOutputFilter']);
            $request = Application::get()->getRequest();
            $router = $request->getRouter();
            $templateManager->assign(
                'exportReviewAction',
                new LinkAction(
                    'exportReview',
                    new AjaxModal(
                        $router->url($request, null, null, 'exportReview', ['reviewId' => $reviewId]),
                        __('plugins.generic.wosrrs.settings.connection')
                    ),
                    __('plugins.generic.wosrrs.settings.connection'),
                    null
                )
            );
            $templateManager->assign('reviewId', $reviewId);
            $templateManager->assign('published', $published);
            $output .= $templateManager->fetch($plugin->getTemplateResource('wosExportStep.tpl'));
        }
        return $output;
    }

}

if ( ! PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\webOfScience\WebOfSciencePlugin', '\WebOfSciencePlugin');
}

