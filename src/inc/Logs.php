<?php

namespace brightlabs\craftsalesforce\inc;

use Craft;
use brightlabs\craftsalesforce\elements\Log;
use craft\console\Controller;
use craft\helpers\Console;

class Logs {
    /**
     * Remove old logs from database
     *
     * @param int $days
     * @return void
     */
    public static function prune($days=7)
    {
        $results = Log::find()
        ->where(['type' => 'brightlabs\craftsalesforce\elements\Log'])
        ->andFilterWhere(['<=', 'elements.dateCreated', date('Y-m-d H:i:s', strtotime("-{$days} days"))])
        ->all();

        foreach ($results as $result) {
            Craft::$app->elements->deleteElement($result);
        }
    }

    public static function log($message='', &$logEntries, $optionsParam=[])
    {
        $options = [
            'fgColor' => Console::FG_BLACK,
            'writeToTerminal' => true,
            'writeToDatabase' => true,
        ];

        $options = array_replace($options, $optionsParam);

        if ($options['writeToTerminal']) {
            $controller = new Controller('logs', 'logs');
            $controller->stdout("{$message} \n", $options['fgColor']);
        }

        if ($options['writeToDatabase']) {
            $logEntries[] = $message . " \n";
        }

    }
}