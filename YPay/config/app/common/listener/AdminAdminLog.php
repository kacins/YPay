<?php
declare (strict_types = 1);
namespace app\common\listener;
class AdminAdminLog
{
    public function handle()
    {
        app('app\common\model\AdminAdminLog')->record();
    }
}