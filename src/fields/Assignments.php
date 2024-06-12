<?php

namespace brightlabs\craftsalesforce\fields;

use brightlabs\craftsalesforce\elements\Assignment;
use brightlabs\craftsalesforce\elements\db\AssignmentQuery;
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
use craft\fields\Entries;
use craft\fields\MultiSelect;
use craft\elements\ElementCollection;

/**
 * Assignments field type
 */
class Assignments extends Entries
{
    public static function displayName(): string
    {
        return Craft::t('salesforce', 'Assignments');
    }

    /**
     * @inheritdoc
     */
    public static function elementType(): string
    {
        return Assignment::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add an assignment');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', AssignmentQuery::class, ElementCollection::class, Assignment::class);
    }

}
