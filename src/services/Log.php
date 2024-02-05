<?php

namespace brightlabs\craftsalesforce\services;

use Craft;
use yii\base\Component;
use brightlabs\craftsalesforce\elements\Log as LogElement;

class Log extends Component
{
    public function getLogById($logId)
    {
        return Craft::$app->getElements()->getElementById($logId, LogElement::class);
    }

    public function saveLog(LogElement $log)
    {
        return Craft::$app->elements->saveElement($log, false);
    }

    public function deleteLog(LogElement $log)
    {
        return Craft::$app->elements->deleteElement($log, false);
    }
}