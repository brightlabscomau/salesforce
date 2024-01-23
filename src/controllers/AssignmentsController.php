<?php

namespace brightlabs\craftsalesforce\controllers;

use craft\web\Controller;
use yii\web\Response;
use craft\web\View;

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
}