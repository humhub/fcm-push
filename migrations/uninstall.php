<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use yii\db\Migration;

class uninstall extends Migration
{
    public function up()
    {
        $this->dropTable('fcmpush_user');
    }
}
