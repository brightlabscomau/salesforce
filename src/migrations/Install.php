<?php

namespace brightlabs\craftsalesforce\migrations;

use Craft;
use craft\db\Migration;

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
