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
            'country' => $this->char(100)->notNull(),
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

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // Place uninstallation code here...
        // $this->dropAllForeignKeysToTable($this->table);
        // $this->dropTableIfExists($this->table);

        return true;
    }
}
