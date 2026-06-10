<?php

/**
 * SERVICE CORE AUTH PASSWORD POLICY
 *
 * Politicas minimas de cambio forzado de contrasena para usuarios ORMAuth.
 */
class Service_Core_Auth_PasswordPolicy
{
    public function must_change($user_id)
    {
        if ((int) $user_id < 1 || !$this->users_has_column('password_must_change')) {
            return false;
        }

        $row = \DB::select('password_must_change')
            ->from('users')
            ->where('id', '=', (int) $user_id)
            ->execute()
            ->current();

        return $row && (int) $row['password_must_change'] === 1;
    }

    public function reset_password($user_id, $password, $force_change, $actor_user_id = null)
    {
        $this->validate_password($password);

        $salt = bin2hex(random_bytes(8));
        $hash = \Auth::instance()->hash_password($password.$salt);
        $now = time();

        $data = [
            'password' => $hash,
            'salt' => $salt,
        ];

        if ($this->users_has_column('password_must_change')) {
            $data['password_must_change'] = $force_change ? 1 : 0;
        }
        if ($this->users_has_column('password_reset_at')) {
            $data['password_reset_at'] = $now;
        }
        if ($this->users_has_column('password_reset_by')) {
            $data['password_reset_by'] = $actor_user_id ? (int) $actor_user_id : null;
        }
        if ($this->users_has_column('updated_at')) {
            $data['updated_at'] = $now;
        }

        \DB::update('users')
            ->set($data)
            ->where('id', '=', (int) $user_id)
            ->execute();

        $this->clear_user_permission_cache($user_id);
    }

    public function change_forced_password($user_id, $password)
    {
        $this->validate_password($password);

        $salt = bin2hex(random_bytes(8));
        $hash = \Auth::instance()->hash_password($password.$salt);
        $now = time();

        $data = [
            'password' => $hash,
            'salt' => $salt,
        ];

        if ($this->users_has_column('password_must_change')) {
            $data['password_must_change'] = 0;
        }
        if ($this->users_has_column('password_changed_at')) {
            $data['password_changed_at'] = $now;
        }
        if ($this->users_has_column('updated_at')) {
            $data['updated_at'] = $now;
        }

        \DB::update('users')
            ->set($data)
            ->where('id', '=', (int) $user_id)
            ->execute();

        $this->clear_user_permission_cache($user_id);
    }

    public function validate_password($password)
    {
        if ((string) $password === '') {
            throw new \InvalidArgumentException('Captura la nueva contrasena.');
        }

        if (strlen((string) $password) < 12) {
            throw new \InvalidArgumentException('La nueva contrasena debe tener al menos 12 caracteres.');
        }
    }

    protected function users_has_column($column)
    {
        try {
            return \DBUtil::field_exists('users', [$column]);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function clear_user_permission_cache($user_id)
    {
        try {
            \Cache::delete('auth.permissions.user_'.(int) $user_id);
        } catch (\Exception $e) {
            // Cache may not exist yet.
        }
    }
}
