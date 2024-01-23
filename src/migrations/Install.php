<?php

namespace brightlabs\craftsalesforce\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Create the Assignments table:
        $this->createTable('{{%salesforce_assignments}}', [
            'id' => $this->primaryKey(),
            'country' => $this->char(100)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        // Give it a foreign key to the elements table:
        $this->addForeignKey(
            null,
            '{{%salesforce_assignments}}',
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

        return true;
    }
}
