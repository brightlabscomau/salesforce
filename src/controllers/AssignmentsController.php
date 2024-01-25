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
        $country = $this->request->getBodyParam('country');
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
        $assignment->country = $country;
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
}