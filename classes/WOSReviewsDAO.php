<?php

/**
 * @file plugins/generic/webOfScience/classes/WOSReviewsDAO.php
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class WOSReviewsDAO
 *
 * @brief Operations for retrieving and modifying WOSReview objects.
 */

namespace APP\plugins\generic\webOfScience\classes;

use PKP\db\DAO;

class WOSReviewsDAO extends DAO {

    /**
     * Get a list of localized field names
     *
     * @return array
     */
    function getLocaleFieldNames(): array
    {
        return ['title', 'content'];
    }

    /**
     * Insert a new review
     *
     * @param $wosReview WOSReview
     * @return int
     */
    function insertObject(WOSReview $wosReview): int
    {
        $this->update(
            sprintf('
                INSERT INTO publons_reviews
                    (journal_id,
                    submission_id,
                    reviewer_id,
                    review_id,
                    title_en,
                    date_added)
                VALUES
                    (?, ?, ?, ?, ?, %s)',
                $this->datetimeToDB($wosReview->getDateAdded())
            ),
            [
                $wosReview->getJournalId(),
                $wosReview->getSubmissionId(),
                $wosReview->getReviewerId(),
                $wosReview->getReviewId(),
                $wosReview->getTitleEn()
            ]
        );
        $wosReview->setId($this->getInsertObjectId());
        $this->updateLocaleFields($wosReview);
        return $wosReview->getId();
    }

    /**
     * Update the localized settings for this object
     *
     * @param WOSReview $wosReview
     * @return void
     */
    function updateLocaleFields(WOSReview $wosReview): void
    {
        $this->updateDataObjectSettings('publons_reviews_settings', $wosReview, [
            'publons_reviews_id' => $wosReview->getId()
        ]);
    }

    /**
     * Update existing data about the review
     *
     * @param WOSReview $WOSReview
     * @return int|null
     */
    function updateObject(WOSReview $WOSReview): ?int
    {
        $result = $this->update(
            sprintf('UPDATE publons_reviews
                SET journal_id = ?,
                    submission_id = ?,
                    reviewer_id = ?,
                    review_id = ?,
                    title_en = ?,
                    date_added = %s
                WHERE   publons_reviews_id = ?',
                $this->datetimeToDB($WOSReview->getDateAdded())
            ),
            [
                (int) $WOSReview->getJournal(),
                (int) $WOSReview->getSubmissionId(),
                (int) $WOSReview->getReviewerId(),
                (int) $WOSReview->getReviewId(),
                $WOSReview->getTitleEn(),
                (int) $WOSReview->getId()
            ]
        );
        $this->updateLocaleFields($WOSReview);
        return $result;
    }

    /**
     * Delete data about the review
     *
     * @param $WOSReview WOSReview
     * @return bool|int|null
     */
    function deleteObject(WOSReview $WOSReview): bool|int|null
    {
        return $this->deleteObjectById($WOSReview->getId());
    }

    /**
     * Delete an object by ID
     *
     * @param $WOSReviewId int
     * @return int|null
     */
    function deleteObjectById(int $WOSReviewId): ?int
    {
        $this->update('DELETE FROM publons_reviews_settings WHERE publons_reviews_id = ?', (int) $WOSReviewId);
        return $this->update('DELETE FROM publons_reviews WHERE publons_reviews_id = ?', (int) $WOSReviewId);
    }

    /**
     * Get the ID of the last inserted review
     *
     * @return int
     */
    function getInsertObjectId(): int
    {
        return $this->getInsertId('publons_reviews', 'publons_reviews_id');
    }

    /**
     * Return a submitted book for review id for a given article and journal.
     *
     * @param $reviewId int
     * @return int|null
     */
    function getWOSReviewIdByReviewId(int $reviewId): ?int
    {
        $result = $this->retrieve('SELECT publons_reviews_id FROM publons_reviews WHERE review_id = ?', [$reviewId]);
        $row = $result->current();
        $WOSReviewId = $row ? $row->publons_reviews_id : null;
        unset($result);
        return $WOSReviewId;
    }

}
