<?php

/**
 * SERVICE CORE_WORKSPACE_CACHE
 *
 * Wrapper defensivo de cache para widgets.
 */
class Service_Core_Workspace_Cache
{
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

