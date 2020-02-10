<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2/10/2020
 * Time: 10:48 PM
 */

namespace Omt\TranslationManager\Libs;

use Illuminate\Support\Facades\Session;

class TenantLibs
{
    /**
     * Get current tenant info of session user. No using in job/command
     * @return int
     */
    public static function getCurrentTenantInfo()
    {
        return Session::get('tenant_info');
    }

    /**
     * Get current tenant id of session user. No using in job/command
     * @return int
     */
    public static function getCurrentTenantId()
    {
        $tenant_info = self::getCurrentTenantInfo();
        if (!empty($tenant_info) && !empty($tenant_info->id)) {
            return $tenant_info->id;
        }
        return 0;
    }
}