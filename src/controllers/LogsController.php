<?php

namespace brightlabs\craftsalesforce\controllers;

use Craft;
use brightlabs\craftsalesforce\elements\Log;
use brightlabs\craftsalesforce\Salesforce;
use craft\web\Controller;
use yii\web\Response;
use craft\web\View;
use yii\web\BadRequestHttpException;

class LogsController extends Controller
{
    protected array|bool|int $allowAnonymous = true;

    public function actionIndex(): Response
    {
        return $this->renderTemplate(
            'salesforce/logs/_index',
            [],
            View::TEMPLATE_MODE_CP
        );
    }

    public function actionEdit(?int $logId = null, ?Log $log = null): Response
    {
        if (!$log) {
            if ($logId) {
                $log = Salesforce::getInstance()->log->getLogById($logId);
                if (!$log) {
                    throw new BadRequestHttpException("Invalid log ID: $logId");
                }
            } else {
                $log = new Log();
            }
        }

        return $this->renderTemplate('salesforce/logs/_edit', [
            'log' => $log
        ]);
    }

    public function actionSave(): ?Response
    {
        $logDetails = $this->request->getBodyParam('logDetails');
        $logErrors = $this->request->getBodyParam('logErrors');

        $log = new Log();

        $syncDateTime = new date('jS M Y g:i:s a');
        $log->title = $syncDateTime;
        $log->logDetails = $logDetails;
        $log->logErrors = $logErrors;

        if (!Salesforce::getInstance()->log->saveLog($log)) {
            if ($this->request->acceptsJson) {
                return $this->asJson(['errors' => $log->getErrors()]);
            }

            $this->setFailFlash(Craft::t('salesforce', 'Couldn\'t save log.'));

            Craft::$app->urlManager->setRouteParams([
                'log' => $log
            ]);

            return null;
        }

        if ($this->request->acceptsJson) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('salesforce', 'Log saved.'));
        return $this->redirectToPostedUrl($log);
    }
}