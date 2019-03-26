<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%config}}`.
 */
class m190326_193859_create_config_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%config}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'hash' => $this->string()->notNull()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%config}}');
    }
}
