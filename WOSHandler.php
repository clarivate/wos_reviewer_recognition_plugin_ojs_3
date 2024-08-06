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

use DateTime;
use PKP\db\DAORegistry;
use PKP\core\Core;
use PKP\core\PKPString;
use PKP\facades\Locale;
use PKP\submission\SubmissionComment;
use PKP\reviewForm\ReviewFormElement;

use APP\facades\Repo;
use APP\handler\Handler;

use APP\plugins\generic\webOfScience\classes\WOSReview;
use APP\plugins\generic\webOfScience\classes\WOSReviewsDAO;

use Request;
use TemplateManager;

class WOSHandler extends Handler
{

    /** @var webOfSciencePlugin The Web of Science plugin */
    static webOfSciencePlugin $plugin;

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
     * @param array $args
     * @param Request $request
     */
    function exportReview(array $args, $request)
    {
        $plugin = self::$plugin;
        $templateManager = TemplateManager::getManager();
        $templateManager->addStyleSheet('publons-base', $request->getBaseUrl() . '/' . $plugin->getStyleSheet());
        $templateManager->addStyleSheet('publons-font', 'https://fonts.googleapis.com/css?family=Roboto');

        $reviewId = intval($args[0]);
        $user = $request->getUser();

        $WOSReviewsDao = DAORegistry::getDAO('WOSReviewsDAO');
        $exported = $WOSReviewsDao->getWOSReviewIdByReviewId($reviewId);

        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
        $reviewAssignment = $reviewAssignmentDao->getById($reviewId);
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

//            $locale = AppLocale::getLocale();
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

            $headers = ["Authorization: Token " . $auth_token, 'Content-Type: application/json'];
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

            if (isset($_SERVER["HTTP_WOS_URL"])) {
                $url = $_SERVER["HTTP_WOS_URL"] . "/api/v2/review/";
            } else {
                $url = "https://publons.com/api/v2/review/";
            }
            $options = array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $json_data
            );
            $response = $this->_curlPost($options);

            // If success then save into database
            if (($response['status'] >= 200) && ($response['status'] < 300)) {
                $wosReviewsDAO->insertObject($wosReview);
            }

            $templateManager->assign('status', $response['status']);
            if ($response['status'] == 201) {
                $templateManager->assign('serverAction', $response['result']['action']);
                if (isset($_SERVER["HTTP_WOS_URL"])) {
                    $claimUrl = $_SERVER["HTTP_WOS_URL"] . "/wos/op/review-claim/integration/" . $response['result']['token'];
                } else {
                    $claimUrl = "https://www.webofscience.com/wos/op/review-claim/integration/" . $response['result']['token'];
                }
                $templateManager->assign('claimURL', $claimUrl);
            }
            $templateManager->assign('result', $response['result']);
            $templateManager->assign('error', $response['error']);
            return $templateManager->fetchJson($plugin->getTemplateResource('wosExportResults.tpl'));
        }

    }

    /**
     * Post a request to a resource using CURL.
     *
     * @param array $options
     * @return array
     */
    function _curlPost(array $options): array
    {
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $httpResult = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $httpError = curl_error($curl);
        curl_close($curl);
        return [
            'status' => $httpStatus,
            'result' => json_decode($httpResult, true),
            'error' => $httpError
        ];
    }

    /**
     * Check if cURL is available
     *
     * @return bool
     */
    function curlInstalled(): bool
    {
        return function_exists('curl_version');
    }

}
