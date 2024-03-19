<?php

namespace brightlabs\craftsalesforce\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use yii\db\Schema;
use craft\fields\Dropdown;
use craft\fields\data\SingleOptionFieldData;

/**
 * Assignment Sectors field type
 */
class AssignmentSectors extends Dropdown
{
    public static function displayName(): string
    {
        return Craft::t('salesforce', 'Assignment Sectors');
    }

    public static function valueType(): string
    {
        return sprintf('\\%s', SingleOptionFieldData::class);
    }
}
