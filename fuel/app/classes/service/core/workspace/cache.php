<?php

/**
 * SERVICE CORE_WORKSPACE_CACHE
 *
 * Wrapper defensivo de cache para widgets.
 */
class Service_Core_Workspace_Cache
{
    const LEVEL_NONE = 'none';
    const LEVEL_REQUEST = 'request';
    const LEVEL_USER = 'user';
    const LEVEL_COMPANY = 'company';
    const LEVEL_GLOBAL = 'global';
    const LEVEL_STATIC = 'static';

    public static function levels()
    {
        return [
            static::LEVEL_NONE,
            static::LEVEL_REQUEST,
            static::LEVEL_USER,
            static::LEVEL_COMPANY,
            static::LEVEL_GLOBAL,
            static::LEVEL_STATIC,
        ];
    }

    public function get($key)
    {
        try {
            return \Cache::get($key);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function set($key, $value, $ttl = 300)
    {
        try {
            \Cache::set($key, $value, (int) $ttl);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete($key)
    {
        try {
            \Cache::delete($key);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
