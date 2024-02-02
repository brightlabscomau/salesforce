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

    public function actionGet($q=null, $types=null, $sectors=null, $countries=null) {

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

        $assignments =  $assignmentElement->limit(20)
        ->isPublic()
        ->all();

        return $this->asJson([
            'types' => $types,
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
                    'positionSummary' => substr(strip_tags($assignment->positionSummary), 0, 50) . '...',
                    'sector' => $assignment->sector,
                    'country' => $assignment->country,
                    'publish' => $assignment->publish,
                    'recruitmentStartDate' => $assignment->recruitmentStartDate,
                    'recruitmentEndDate' => $assignment->recruitmentEndDate,
                    'url' => $assignment->url
                ];
            }, $assignments)
        ]);
    }
}