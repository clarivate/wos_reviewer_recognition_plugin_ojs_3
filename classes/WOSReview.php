<?php

/**
 * @file plugins/generic/webOfScience/classes/WOSReview.php
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class WOSReview
 * @see WOSReviewsDAO
 *
 * @brief Basic class describing a review for the Web of Science.
 */

namespace APP\plugins\generic\webOfScience\classes;

use PKP\core\DataObject;

class WOSReview extends DataObject {

	/**
	 * Get the submission ID of the referral
     *
	 * @return int
	 */
	function getSubmissionId(): int
	{
		return $this->getData('submissionId');
	}

	/**
	 * Set the submission ID of the review
     *
	 * @param $submissionId int
	 */
	function setSubmissionId(int $submissionId): void
    {
		$this->setData('submissionId', $submissionId);
	}

	/**
	 * Get the journal ID of the review
     *
	 * @return int
	 */
	function getJournalId(): int
    {
		return $this->getData('journalId');
	}

	/**
	 * Set the journal ID of the review
     *
	 * @param $journalId int
	 */
	function setJournalId(int $journalId): void
    {
		$this->setData('journalId', $journalId);
	}

	/**
	 * Get the reviewer ID of the review
     *
	 * @return int
	 */
	function getReviewerId(): int
    {
		return $this->getData('reviewerId');
	}

	/**
	 * Set the reviewer ID of the review
     *
	 * @param $reviewerId int
	 */
	function setReviewerId(int $reviewerId): void
    {
		$this->setData('reviewerId', $reviewerId);
	}

	/**
	 * Get the ID of the review
     *
	 * @return int
	 */
	function getReviewId(): int
	{
		return $this->getData('reviewId');
	}

	/**
	 * Set the review ID of the review
     *
	 * @param $reviewId int
	 */
	function setReviewId(int $reviewId): void
    {
		$this->setData('reviewId', $reviewId);
	}

	/**
	 * Get the date added a review into the Web of Science
     *
	 * @return string
	 */
	function getDateAdded(): string
    {
		return $this->getData('dateAdded');
	}

	/**
	 * Set the date added a review into the Web of Science.
     *
	 * @param $dateAdded string
	 */
	function setDateAdded(string $dateAdded): void
    {
        $this->setData('dateAdded', $dateAdded);
	}

	/**
	 * Get the localized title of the article
     *
	 * @return string
	 */
	function getLocalizedTitle(): string
    {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get the title of the article
     *
	 * @param $locale string
	 * @return string
	 */
	function getTitle(string $locale): string
    {
		return $this->getData('title', $locale);
	}

	/**
	 * Set the title of the article
     *
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle(string $title, string $locale): void
    {
        $this->setData('title', $title ? $title : '', $locale);
	}

	/**
	 * Get the title_en of the article
     *
	 * @return string
	 */
	function getTitleEn(): string
    {
		return $this->getData('titleEn');
	}

	/**
	 * Set the title_en of the article
     *
	 * @param $title string
	 */
	function setTitleEn(string $title): void
    {
        $this->setData('titleEn', $title);
	}

	/**
	 * Get the content of the review for the Web of Science
     *
	 * @return string
	 */
	function getLocalizedContent(): string
    {
		return $this->getLocalizedData('content');
	}

	/**
	 * Get the content of the review for the Web of Science
     *
	 * @param $locale string
	 * @return string
	 */
	function getContent(string $locale): string
    {
		return $this->getData('content', $locale);
	}

	/**
	 * Set the content of the review for the Web of Science.
     *
	 * @param $content string
	 * @param $locale string
	 */
	function setContent(string $content, string $locale): void
    {
        $this->setData('content', $content ? $content : '', $locale);
	}

}
