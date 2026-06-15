<?php

/**
 * SERVICE CORE_WORKSPACE_PREFERENCES
 *
 * Lee y guarda preferencias del usuario para Workspace.
 */
class Service_Core_Workspace_Preferences
{
    public function for_user($user_id)
    {
        if (!\DBUtil::table_exists('core_workspace_user_preferences')) {
            return $this->defaults();
        }

        $row = \DB::select()
            ->from('core_workspace_user_preferences')
            ->where('user_id', '=', (int) $user_id)
            ->execute()
            ->current();

        return $row ? $row : $this->defaults();
    }

    public function save($user_id, array $settings)
    {
        if (!\DBUtil::table_exists('core_workspace_user_preferences')) {
            return false;
        }

        $data = [
            'user_id' => (int) $user_id,
            'settings_json' => json_encode($settings),
            'updated_at' => time(),
        ];

        $exists = \DB::select('id')
            ->from('core_workspace_user_preferences')
            ->where('user_id', '=', (int) $user_id)
            ->execute()
            ->current();

        if ($exists) {
            \DB::update('core_workspace_user_preferences')->set($data)->where('id', '=', (int) $exists['id'])->execute();
            return true;
        }

        $data['created_at'] = time();
        \DB::insert('core_workspace_user_preferences')->set($data)->execute();
        return true;
    }

    protected function defaults()
    {
        return [
            'compact_mode' => 0,
            'active_profile_code' => 'generic',
            'active_preset_code' => 'generic',
            'settings_json' => null,
        ];
    }
}

