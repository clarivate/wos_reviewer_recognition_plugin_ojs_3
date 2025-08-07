<?php

/**
 * @file plugins/generic/webOfScience/WOSHandler.php
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class WOSHandler
 * @ingroup plugins_generic_webOfScience
 *
 * @brief Handle Web of Science requests
 */

namespace APP\plugins\generic\webOfScience;

use APP\core\Application;
use DateTime;
use PKP\db\DAORegistry;
use PKP\core\Core;
use PKP\core\PKPString;
use PKP\facades\Locale;
use PKP\submission\SubmissionComment;
use PKP\reviewForm\ReviewFormElement;
use PKP\security\Role;
use PKP\security\authorization\ContextAccessPolicy;

use APP\facades\Repo;
use APP\handler\Handler;

use APP\plugins\generic\webOfScience\classes\WOSReview;
use APP\plugins\generic\webOfScience\classes\WOSReviewsDAO;

use APP\core\Request;
use APP\template\TemplateManager;

class WOSHandler extends Handler
{

    /** @var webOfSciencePlugin The Web of Science plugin */
    static webOfSciencePlugin $plugin;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(Role::ROLE_ID_REVIEWER, 'exportReview');
    }

    /**
     * @copydoc PKPHandler::authorize()
     * @return bool
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Set plugin
     *
     * @param $plugin
     * @return void
     */
    static function setPlugin($plugin): void
    {
        self::$plugin = $plugin;
    }

    /**
     * Confirm you want to export the review (GET) then export it (POST)
     *
     * @param array $args
     * @param Request $request
     * @return \PKP\core\JSONMessage|void
     * @throws \Exception
     */
    function exportReview(array $args, $request)
    {
        $plugin = self::$plugin;
        $templateManager = TemplateManager::getManager();
        $templateManager->addStyleSheet('publons-base', $request->getBaseUrl() . '/' . $plugin->getStyleSheet());

        $reviewId = intval($args[0]);
        $user = $request->getUser();

        $WOSReviewsDao = DAORegistry::getDAO('WOSReviewsDAO');
        $exported = $WOSReviewsDao->getWOSReviewIdByReviewId($reviewId);

        $reviewAssignment = Repo::reviewAssignment()->get($reviewId);
        $reviewerId = $reviewAssignment->getReviewerId();

        if ($exported) {
            // Check that the review hasn't been exported already
            $templateManager->assign('info', __('plugins.generic.wosrrs.export.error.alreadyExported'));
            return $templateManager->fetchJson($plugin->getTemplateResource('wosExportResults.tpl'));
        } elseif (($reviewAssignment->getRecommendation() === null) || ($reviewAssignment->getRecommendation() === '')) {
            // Check that the review has been submitted to the editor
            $templateManager->assign('info', __('plugins.generic.wosrrs.export.error.reviewNotSubmitted'));
            return $templateManager->fetchJson($plugin->getTemplateResource('wosExportResults.tpl'));
        } elseif ($user->getId() !== $reviewerId) {
            // Check that user is person who wrote review
            $templateManager->assign('info', __('plugins.generic.wosrrs.export.error.invalidUser'));
            return $templateManager->fetchJson($plugin->getTemplateResource('wosExportResults.tpl'));
        }

        $submission = Repo::submission()->get($reviewAssignment->getSubmissionId());
        $submissionCommentDao = DAORegistry::getDAO('SubmissionCommentDAO');

        if ($request->isGet()) {
            $router = $request->getRouter();
            $templateManager->assign('reviewId', $reviewId);
            $templateManager->assign('pageURL', $router->url($request, null, null, 'exportReview', ['reviewId' => $reviewId]));
            return $templateManager->fetchJson($plugin->getTemplateResource('wosExportReviewForm.tpl'));
        } elseif ($request->isPost()) {
            $journalId = $submission->getData('contextId');
            if($request->getContext()->getId() != $journalId) {
                $templateManager->assign('info', __('plugins.generic.wosrrs.export.error.400'));
                return $templateManager->fetchJson($plugin->getTemplateResource('wosExportResults.tpl'));
            }
            $submissionId = $submission->getId();
            $rtitle = $submission->getCurrentPublication()->getLocalizedTitle();
            $rtitle_en = $submission->getCurrentPublication()->getLocalizedTitle('en');
            $rname = $user->getFullName();
            $remail = $user->getEmail();

            $body = '';

            // Get the comments associated with this review assignment
            $submissionComments = $submissionCommentDao->getSubmissionComments($submissionId, SubmissionComment::COMMENT_TYPE_PEER_REVIEW, $reviewId);
            if ($submissionComments) {
                foreach ($submissionComments->toArray() as $comment) {
                    // If the comment is viewable by the author, then add the comment.
                    if ($comment->getViewable()) $body .= PKPString::html2text($comment->getComments()) . "\n";
                }
            }
            if ($reviewFormId = $reviewAssignment->getReviewFormId()) {
                $reviewFormResponseDao = DAORegistry::getDAO('ReviewFormResponseDAO');
                $reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');
                $reviewFormElements = $reviewFormElementDao->getByReviewFormId($reviewFormId)->toArray();
                foreach ($reviewFormElements as $reviewFormElement) if ($reviewFormElement->getIncluded()) {
                    $body .= PKPString::html2text($reviewFormElement->getLocalizedQuestion()) . ": \n";
                    $reviewFormResponse = $reviewFormResponseDao->getReviewFormResponse($reviewId, $reviewFormElement->getId());
                    if ($reviewFormResponse) {
                        $possibleResponses = $reviewFormElement->getLocalizedPossibleResponses();
                        if (in_array($reviewFormElement->getElementType(), $reviewFormElement->getMultipleResponsesElementTypes())) {
                            if ($reviewFormElement->getElementType() == ReviewFormElement::REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES) {
                                foreach ($reviewFormResponse->getValue() as $value) {
                                    $body .= "\t" . PKPString::html2text($possibleResponses[$value]) . "\n";
                                }
                            } else {
                                $body .= "\t" . PKPString::html2text($possibleResponses[$reviewFormResponse->getValue()]) . "\n";
                            }
                            $body .= "\n";
                        } else {
                            $body .= "\t" . $reviewFormResponse->getValue() . "\n\n";
                        }
                    }
                }
            }

            $auth_key = $plugin->getSetting($journalId, 'auth_key');
            $auth_token = $plugin->getSetting($journalId, 'auth_token');

            $body = str_replace("\r", '', $body);
            $body = str_replace("\n", '\r\n', $body);

            $dateRequested = new DateTime($reviewAssignment->getDateNotified());
            $dateCompleted = new DateTime($reviewAssignment->getDateCompleted());

            $locale = Locale::getLocale();

            $wosReview = new WOSReview();
            $wosReview->setJournalId($journalId);
            $wosReview->setSubmissionId($submissionId);
            $wosReview->setReviewerId($reviewerId);
            $wosReview->setReviewId($reviewId);
            $wosReview->setTitleEn($rtitle_en);
            $wosReview->setDateAdded(Core::getCurrentDate());
            $wosReview->setTitle($rtitle, $locale);
            $wosReview->setContent($body, $locale);

            $wosReviewsDAO = new WOSReviewsDAO();
            DAORegistry::registerDAO('WOSReviewsDAO', $wosReviewsDAO);

            $data = [
                'key' => $auth_key,
                'reviewer' => [
                    'name' => $rname,
                    'email' => $remail
                ],
                'publication' => [
                    'title' => $rtitle,
                    'abstract' => $submission->getCurrentPublication()->getLocalizedData('abstract')
                ],
                'request_date' => [
                    'day' => $dateRequested->format('d'),
                    'month' => $dateRequested->format('m'),
                    'year' => $dateRequested->format('Y')
                ],
                'complete_date' => [
                    'day' => $dateCompleted->format('d'),
                    'month' => $dateCompleted->format('m'),
                    'year' => $dateCompleted->format('Y')
                ]
            ];
            // Don't send content if it is empty
            if ($body !== '') $data["content"] = $body;

            $json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $json_data = str_replace("\\\\", '\\', $json_data);
            $templateManager->assign('json_data', $json_data);

            $url = 'https://publons.com/api/v2/review/';
            $httpClient = Application::get()->getHttpClient();
            try {
                $response = $httpClient->request('POST', $url, [
                    'headers' => [
                        'Authorization' => 'Token ' . $auth_token,
                        'Content-Type' => 'application/json'
                    ],
                    'body' => $json_data
                ]);
                $r_status = $response->getStatusCode();
                $r_body = json_decode($response->getBody());
                if (($r_status >= 200) && ($r_status < 300)) {
                    // If success then save into database
                    $wosReviewsDAO->insertObject($wosReview);
                }
                if($r_status == 201) {
                    $claimUrl = "https://www.webofscience.com/wos/op/review-claim/integration/" . $r_body->token;
                    $templateManager->assign('claimURL', $claimUrl);
                    $templateManager->assign('serverAction', $r_body->action);
                }
                $templateManager->assign('status', $r_status);
            } catch (\Throwable $e) {
                $templateManager->assign('status', $e->getCode());
            }
            return $templateManager->fetchJson($plugin->getTemplateResource('wosExportResults.tpl'));
        }

    }

}
