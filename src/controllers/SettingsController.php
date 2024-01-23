<?php

namespace brightlabs\craftsalesforce\controllers;

use brightlabs\craftsalesforce\Salesforce;
use craft\web\Controller;
use yii\web\Response;
use craft\web\View;

class SettingsController extends Controller
{
    protected array|bool|int $allowAnonymous = true;

    public function actionIndex(): Response
    {
        $bearerToken = Salesforce::getInstance()->settings->bearerToken;

        return $this->renderTemplate(
            'salesforce/_settings',
            [],
            View::TEMPLATE_MODE_CP
        );
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