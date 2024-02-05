<?php

namespace brightlabs\craftsalesforce\controllers;

use Craft;
use craft\web\View;
use yii\web\Response;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use brightlabs\craftsalesforce\Salesforce;
use brightlabs\craftsalesforce\elements\Assignment;
use brightlabs\craftsalesforce\models\Assignment as AssignmentModel;
use craft\elements\db\ElementQueryInterface;
use yii\data\Pagination;

class AssignmentsController extends Controller
{
    protected array|bool|int $allowAnonymous = true;

    public function actionIndex(): Response
    {
        // return $this->asJson(['ping' => 'pong']);

        return $this->renderTemplate(
            'salesforce/_assignments',
            ['test' => '123'],
            View::TEMPLATE_MODE_CP
        );
    }

    public function actionEdit(?int $assignmentId = null, ?Assignment $assignment = null): Response
    {
        if (!$assignment) {
            // Are we editing an existing assignment?
            if ($assignmentId) {

                $assignment = Salesforce::getInstance()->assignment->getAssignmentById($assignmentId);
                if (!$assignment) {
                    throw new BadRequestHttpException("Invalid assignment ID: $assignmentId");
                }
            } else {
                // We're creating a new assignment
                $assignment = new Assignment();
            }
        }

        return $this->renderTemplate('salesforce/assignments/_edit', [
            'assignment' => $assignment,
        ]);
    }

    public  function actionSave(): ?Response
    {
        $assignmentId = $this->request->getBodyParam('assignmentId');
        $title = $this->request->getBodyParam('title');
        $salesforceId = $this->request->getBodyParam('salesforceId');
        $hybridVolunteeringNature = $this->request->getBodyParam('hybridVolunteeringNature');
        $workplace = $this->request->getBodyParam('workplace');
        $duration = $this->request->getBodyParam('duration');
        $startDate = $this->request->getBodyParam('startDate');
        $positionDescriptionUrl = $this->request->getBodyParam('positionDescriptionUrl');
        $applicationCloseDate = $this->request->getBodyParam('applicationCloseDate');
        $positionSummary = $this->request->getBodyParam('positionSummary');
        $baseAllowance = $this->request->getBodyParam('baseAllowance');
        $livingAllowance = $this->request->getBodyParam('livingAllowance');
        $specialConditions = $this->request->getBodyParam('specialConditions');
        $sector = $this->request->getBodyParam('sector');
        $country = $this->request->getBodyParam('country');
        $publish = $this->request->getBodyParam('publish');
        $recruitmentStartDate = $this->request->getBodyParam('recruitmentStartDate');
        $recruitmentEndDate = $this->request->getBodyParam('recruitmentEndDate');
        $jsonContent = $this->request->getBodyParam('jsonContent');

        $isFresh = $this->request->getParam('fresh');

        if ($assignmentId && !$isFresh) {
            $assignment = Salesforce::getInstance()->assignment->getAssignmentById($assignmentId);
            if (!$assignment) {
                throw new BadRequestHttpException("Invalid assignment ID: $assignmentId");
            }
        } else {
            $assignment = new Assignment();
        }

        $assignment->title = $title;
        $assignment->salesforceId = $salesforceId;
        $assignment->hybridVolunteeringNature = $hybridVolunteeringNature;
        $assignment->workplace = $workplace;
        $assignment->duration = $duration;
        $assignment->startDate = $startDate;
        $assignment->positionDescriptionUrl = $positionDescriptionUrl;
        $assignment->applicationCloseDate = $applicationCloseDate;
        $assignment->positionSummary = $positionSummary;
        $assignment->baseAllowance = $baseAllowance;
        $assignment->livingAllowance = $livingAllowance;
        $assignment->specialConditions = $specialConditions;
        $assignment->sector = $sector;
        $assignment->country = $country;
        $assignment->publish = $publish;
        $assignment->recruitmentStartDate = $recruitmentStartDate;
        $assignment->recruitmentEndDate = $recruitmentEndDate;
        $assignment->json = $jsonContent;

        if (!Salesforce::getInstance()->assignment->saveAssignment($assignment)) {
            if ($this->request->acceptsJson) {
                return $this->asJson(['errors' => $assignment->getErrors()]);
            }

            $this->setFailFlash(Craft::t('salesforce', 'Couldn\'t save assignment.'));

            Craft::$app->urlManager->setRouteParams([
                'assignment' => $assignment
            ]);

            return null;
        }

        if ($this->request->acceptsJson) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('salesforce', 'Assignment saved.'));
        return $this->redirectToPostedUrl($assignment);
    }

    public function actionGet($q=null, $types=null, $sectors=null, $countries=null, $sort='Sort by Closing Date (Soonest)', $page=0) {

        $pageLimit = 10;
        $assignmentElement = Assignment::find();

        if (!empty($q)) {
            $assignmentElement->search($q);
        }

        if (!empty($types)) {
            $assignmentElement->filterByTypes(explode(',', $types));
        }

        if (!empty($sectors)) {
            $assignmentElement->filterBySectors(explode(',', $sectors));
        }

        if (!empty($countries)) {
            $assignmentElement->filterByCountries(explode(',', $countries));
        }

        $assignmentElement = $this->sortResults($assignmentElement, $sort);

        $query =  $assignmentElement->isPublic();
        $countQuery = clone $query;
        $pages = new Pagination([
            'totalCount' => $countQuery->count(),
            'defaultPageSize' => $pageLimit
        ]);

        $pages->setPage($page);

        $assignments = $query->offset($pages->offset)
            ->limit($pages->limit)
            ->all();

        $response = [
            'q' => $q,
            'types' => $types,
            'sectors' => $sectors,
            'countries' => $countries,
            'assignments' => array_map(function($assignment) {
                return (object) [
                    'salesforceId' => $assignment->salesforceId,
                    'title' => $assignment->title,
                    'hybridVolunteeringNature' => $assignment->hybridVolunteeringNature,
                    'workplace' => $assignment->workplace,
                    'duration' => $assignment->duration,
                    'startDate' => $assignment->startDate,
                    'positionDescriptionUrl' => $assignment->positionDescriptionUrl,
                    'applicationCloseDate' => $assignment->applicationCloseDate,
                    'positionSummary' => mb_convert_encoding(substr(strip_tags($assignment->positionSummary), 0, 250), 'UTF-8') . '...',
                    'sector' => $assignment->sector,
                    'country' => $assignment->country,
                    'publish' => $assignment->publish,
                    'recruitmentStartDate' => $assignment->recruitmentStartDate,
                    'recruitmentEndDate' => $assignment->recruitmentEndDate,
                    'url' => $assignment->url
                ];
            }, $assignments),
            'pagination' => (object)[
                'totalCount' => (int) $pages->totalCount,
                'currentPage' => (int) $page,
                'resultRange' => $this->getResultRange($pages, $pageLimit,  $page),
                'pages' => $this->getPageNumbers($pages),
                'links' => $this->getLinks($pages)
            ]
        ];

        return $this->asJson($response);
    }

    protected function sortResults(ElementQueryInterface $assignmentElement, $sort=''): ElementQueryInterface
    {
        if (empty($sort) || $sort == 'Closing Date (Soonest)') {

            $assignmentElement->sortByClosingDateSoon();
        }

        if ($sort == 'Closing Date (Furthest)') {
            $assignmentElement->sortByClosingDateFurthest();
        }

        if ($sort == 'Relevance') {
            // todo: no idea how to sort this
        }

        if ($sort == 'Duration (Shortest)') {
            $assignmentElement->sortByDurationShortest();
        }

        if ($sort == 'Duration (Longest)') {
            $assignmentElement->sortByDurationLongest();
        }

        if ($sort == 'Country') {
            $assignmentElement->sortByCountry();
        }

        if ($sort == 'Sector') {
            $assignmentElement->sortBySector();
        }

        return $assignmentElement;
    }

    protected function getPageNumbers(Pagination $pages): object
    {
        $numbers = [];

        for ($i=0; $i < $pages->totalCount/$pages->defaultPageSize; $i++) {
            $numbers[] = $i;
        }

        return (object)[
            'numbers' => $numbers,
        ];
    }

    protected function getLinks(Pagination $pages): object
    {
        $links = $pages->getLinks(true);

        return (object) [
            'self' => $this->getPageNumberFromLink($links['self'] ?? '') - 1,
            'first' => $this->getPageNumberFromLink($links['first'] ?? '') - 1,
            'next' => $this->getPageNumberFromLink($links['next'] ?? '') - 1,
            'prev' => $this->getPageNumberFromLink($links['prev'] ?? '') - 1,
            'last' => $this->getPageNumberFromLink($links['last'] ?? '') - 1
        ];
    }

    protected function getPageNumberFromLink($link): int
    {
        return explode('&page=', $link ?? false)[1] ?? -1;
    }

    protected function getResultRange($pages, $pageLimit, $currentPage): object
    {
        $start = 0;
        $end = 0;

        if ($pages->totalCount == 0) {
            $start = 0;
            $end = 0;
        } else if ($pages->totalCount <= $pageLimit) {
            $start = 1;
            $end = (int) $pages->totalCount;
        } else if ($currentPage == floor(($pages->totalCount/$pageLimit))) {
            $start = ($pageLimit * $currentPage) + 1;
            $end = (int) $pages->totalCount;
        } else {
            $start = ($pageLimit * $currentPage) + 1;
            $end = ($pageLimit * $currentPage) + $pageLimit;
        }

        return (object) [
            'start' => $start,
            'end' => $end
        ];
    }
}