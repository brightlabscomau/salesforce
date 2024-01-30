<?php

namespace brightlabs\craftsalesforce\controllers;

use brightlabs\craftsalesforce\Salesforce;
use Craft;
use craft\web\Controller;
use yii\web\Response;
use craft\web\View;

class SettingsController extends Controller
{
    protected array|bool|int $allowAnonymous = true;

    public function actionIndex(): Response
    {
        return $this->renderTemplate(
            'salesforce/_settings',
            ['settings' => Salesforce::getInstance()->settings],
            View::TEMPLATE_MODE_CP
        );
    }

    public function actionSave(): Response
    {

        $settings = Salesforce::getInstance()->settings;

        $settings->salesforceApiVersion = $this->request->getBodyParam('salesforceApiVersion');
        $settings->salesforceInstanceUrl = $this->request->getBodyParam('salesforceInstanceUrl');
        $settings->salesforceClientId = $this->request->getBodyParam('salesforceClientId');
        $settings->salesforceClientSecret = $this->request->getBodyParam('salesforceClientSecret');

        $path = "plugins.salesforce.settings";

        Craft::$app->getProjectConfig()->set($path, [
            'salesforceApiVersion' => $settings->salesforceApiVersion,
            'salesforceInstanceUrl' => $settings->salesforceInstanceUrl,
            'salesforceClientId' => $settings->salesforceClientId,
            'salesforceClientSecret' => $settings->salesforceClientSecret,
        ]);

        return $this->redirectToPostedUrl();
    }

    public function actionFields(): Response
    {
        return $this->renderTemplate(
            'salesforce/_assignment-fields',
            [],
            View::TEMPLATE_MODE_CP
        );
    }
}