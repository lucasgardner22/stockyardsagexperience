<?php

use craft\config\GeneralConfig;
use craft\helpers\App;

return GeneralConfig::create()
    ->devMode(App::env('CRAFT_DEV_MODE') ?? false)
    ->allowAdminChanges(App::env('CRAFT_ALLOW_ADMIN_CHANGES') ?? false)
    ->omitScriptNameInUrls()
    ->aliases(['@web' => App::env('PRIMARY_SITE_URL'), '@webroot' => dirname(__DIR__) . '/web']);