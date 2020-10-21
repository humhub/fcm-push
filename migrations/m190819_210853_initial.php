<?php

use yii\db\Migration;

/**
 * Class m190819_210853_initial
 */
class m190819_210853_initial extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('fcmpush_user', [
            'id' => $this->primaryKey(),
            'token' => $this->string(255),
            'user_id' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->defaultValue(new \yii\db\Expression('NOW()')),
            'updated_at' => $this->dateTime()
        ]);

        $this->addForeignKey('f_user', 'fcmpush_user', 'user_id', 'user', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190819_210853_initial cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190819_210853_initial cannot be reverted.\n";

        return false;
    }
    */
}
