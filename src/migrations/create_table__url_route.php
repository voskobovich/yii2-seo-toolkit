<?php

use yii\db\Migration;

class create_table__url_route extends Migration
{
    private $_tableName = '{{%url_route}}';

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($this->_tableName, [
            'id' => $this->primaryKey(),
            'path' => $this->string()->notNull(),
            'action_key' => $this->smallInteger()->notNull(),
            'object_key' => $this->smallInteger(),
            'object_id' => $this->integer(),
            'http_code' => $this->smallInteger(),
            'url_to' => $this->string(),
        ], $tableOptions);

        $this->createIndex('idx_url_route_path', $this->_tableName, ['path'], true);
        $this->createIndex('idx_url_fields', $this->_tableName, ['action_key', 'object_key', 'object_id']);
    }

    public function safeDown()
    {
        $this->dropTable($this->_tableName);
    }
}
