<?php

use yii\db\Migration;

/**
 * Class m230205_141256_fcm_senderid
 */
class m230205_141256_fcm_senderid extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        if (in_array('sender_id', $this->db->getTableSchema('fcmpush_user', true)->columnNames)) {
            return;
        }

        $senderId = $this->db->createCommand(
            'SELECT value FROM setting WHERE module_id = :moduleId AND name = :name',
            [':moduleId' => 'fcm-push', ':name' => 'senderId']
        )->queryScalar();

        // Allow null
        $this->addColumn('fcmpush_user', 'sender_id', $this->string()->null()->after('user_id'));

        if (empty($senderId)) {
            $this->delete('fcmpush_user');
        } else {
            $this->update('fcmpush_user', ['sender_id' => $senderId]);
        }

        $this->alterColumn('fcmpush_user', 'sender_id', $this->string()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230205_141256_fcm_senderid cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230205_141256_fcm_senderid cannot be reverted.\n";

        return false;
    }
    */
}
