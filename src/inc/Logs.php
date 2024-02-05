<?php

namespace brightlabs\craftsalesforce\inc;

use Craft;
use brightlabs\craftsalesforce\elements\Log;

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
}