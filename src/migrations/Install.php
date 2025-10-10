<?php

namespace brightlabs\craftsalesforce\migrations;

use Craft;
use craft\db\Migration;
use craft\models\CategoryGroup;
use craft\fields\Categories as CategoriesField;

/**
 * Install migration.
 */
class Install extends Migration
{
    protected $table = '{{%salesforce_assignments}}';
    protected $tableLog = '{{%salesforce_logs}}';

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {

        if ($this->db->tableExists($this->table)) {
            return true;
        }


        // Create the Assignments table:
        $this->createTable($this->table, [
            'id' => $this->primaryKey(),
            'salesforceId' => $this->char(100)->notNull(),
            'positionId' => $this->char(100)->notNull(),
            'hybridVolunteeringNature' => $this->char(255)->null(),
            'workplace' => $this->char(255)->null(),
            'duration' => $this->integer()->null(),
            'startDate' => $this->char(10)->null(),
            'positionDescriptionUrl' => $this->tinyText()->null(),
            'applicationCloseDate' => $this->char(10)->null(),
            'positionSummary' => $this->longText()->null(),
            'baseAllowance' => $this->longText()->null(),
            'livingAllowance' => $this->longText()->null(),
            'specialConditions' => $this->longText()->null(),
            'sector' => $this->char('255')->null(),
            'country' => $this->char(100)->null(),
            'publish' => $this->char(100)->null(),
            'jsonContent' => $this->longText()->notNull(),
            'recruitmentStartDate' => $this->dateTime()->null(),
            'recruitmentEndDate' => $this->dateTime()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'filterStories' => $this->mediumText()->null(),
            'filterCountry' => $this->char(255)->null(),
            'filterSector' => $this->char(255)->null(),
            'filterTheme' => $this->char(255)->null(),
            'uid' => $this->uid(),
        ]);

        // Give it a foreign key to the elements table:
        $this->addForeignKey(
            null,
            $this->table,
            'id',
            '{{%elements}}',
            'id',
            'CASCADE',
            null
        );

        $this->createTable($this->tableLog, [
            'id' => $this->primaryKey(),
            'logDetails' => $this->longText()->null(),
            'logErrors' => $this->longText()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(
            null,
            $this->tableLog,
            'id',
            '{{%elements}}',
            'id',
            'CASCADE',
            null
        );

        // Get or create sectors category group
        $sectorsGroup = Craft::$app->getCategories()->getGroupByHandle('sectors');

        if (!$sectorsGroup) {
            $sectorsGroup = new CategoryGroup();
            $sectorsGroup->name = 'Sectors';
            $sectorsGroup->handle = 'sectors';

            // Create site settings for the group
            $allSites = Craft::$app->getSites()->getAllSites();
            $siteSettings = [];

            foreach ($allSites as $site) {
                $siteSettings[$site->id] = [
                    'hasUrls' => false,
                    'uriFormat' => null,
                    'template' => null,
                ];
            }

            $sectorsGroup->setSiteSettings($siteSettings);
            Craft::$app->getCategories()->saveGroup($sectorsGroup);
        }

        // Create or update sectors field
        $field = Craft::$app->getFields()->getFieldByHandle('assignmentSectors');

        if (!$field) {
            $field = new CategoriesField();
            $field->name = 'Sectors';
            $field->handle = 'assignmentSectors';
            $field->groupId = $sectorsGroup->id;

            $field->branchLimit = null; // No limit on categories
            $field->selectionLabel = ''; // Optional selection label
            $field->localizeRelations = false; // Don't localize relations

            // Set the source to the sectors group
            $field->source = "group:{$sectorsGroup->uid}";

            Craft::$app->getFields()->saveField($field);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // Place uninstallation code here...
        $this->dropAllForeignKeysToTable($this->table);
        $this->dropTableIfExists($this->table);

        $this->dropAllForeignKeysToTable($this->tableLog);
        $this->dropTableIfExists($this->tableLog);

        return true;
    }
}
