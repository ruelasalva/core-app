<?php

/**
 * Servicio de acceso a buzones de comunicaciones.
 *
 * Centraliza la relacion cuenta -> usuario/grupo/rol para que la interfaz de
 * comunicaciones no exponga secretos ni conversaciones de buzones no asignados.
 */
class Service_Core_Communications_MailboxAccess
{
    protected $allowed_types = ['user', 'group', 'role'];
    protected $allowed_levels = ['owner', 'delegate', 'viewer'];

    public function accounts_for_user($user_id)
    {
        if (!\DBUtil::table_exists('core_communication_accounts')) {
            return [];
        }

        $user_id = (int) $user_id;
        $account_ids = $this->account_ids_for_user($user_id);

        $query = \DB::select()
            ->from('core_communication_accounts');

        if ($this->is_super_admin($user_id)) {
            // Grupo 100 puede revisar todas las cuentas desde administracion.
        } else {
            $query->where('active', '=', 1);
            if (empty($account_ids)) {
                return [];
            }
            $query->where('id', 'in', $account_ids);
        }

        $rows = $query->order_by('code', 'asc')->execute()->as_array();
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mask_account_for_user($row, $user_id);
        }

        return $items;
    }

    public function can_view_account($user_id, $account_id)
    {
        return $this->can_access($user_id, $account_id, 'view');
    }

    public function can_view_conversation($user_id, $conversation_id)
    {
        $conversation_id = (int) $conversation_id;
        if ($conversation_id <= 0) {
            return false;
        }

        $ids = $this->conversation_ids_for_user((int) $user_id);
        if ($ids === null) {
            return true;
        }

        return in_array($conversation_id, $ids, true);
    }

    public function can_send_from_account($user_id, $account_id)
    {
        return $this->can_access($user_id, $account_id, 'send');
    }

    public function can_sync_account($user_id, $account_id)
    {
        return $this->can_access($user_id, $account_id, 'sync');
    }

    public function can_manage_account($user_id, $account_id)
    {
        return $this->can_access($user_id, $account_id, 'manage');
    }

    public function default_sender_for_user($user_id)
    {
        $assignments = $this->assignments_for_user((int) $user_id, true);
        foreach ($assignments as $assignment) {
            if ((int) $assignment['default_sender'] === 1 && (int) $assignment['can_send'] === 1) {
                return $this->account_by_id((int) $assignment['account_id']);
            }
        }

        return [];
    }

    public function assign_account($account_id, $assignment)
    {
        if (!\DBUtil::table_exists('core_communication_account_assignments')) {
            return [
                'success' => false,
                'message' => 'Falta la tabla de asignaciones de cuentas.',
                'errors' => ['missing_assignment_table'],
            ];
        }

        $account_id = (int) $account_id;
        $account = $this->account_by_id($account_id);
        if (empty($account)) {
            return [
                'success' => false,
                'message' => 'Cuenta de correo no encontrada.',
                'errors' => ['account_not_found'],
            ];
        }

        $type = $this->allowed_value(\Arr::get($assignment, 'assignment_type', 'user'), $this->allowed_types, 'user');
        $value = trim((string) \Arr::get($assignment, 'assignment_value', ''));
        $level = $this->allowed_value(\Arr::get($assignment, 'access_level', 'viewer'), $this->allowed_levels, 'viewer');

        $errors = $this->validate_assignment_subject($type, $value);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Revisa la asignacion del buzon.',
                'errors' => $errors,
            ];
        }

        $data = [
            'account_id' => $account_id,
            'assignment_type' => $type,
            'assignment_value' => $value,
            'access_level' => $level,
            'can_send' => $this->flag($assignment, 'can_send'),
            'can_receive' => $this->flag($assignment, 'can_receive', 1),
            'can_sync' => $this->flag($assignment, 'can_sync'),
            'can_manage' => $this->flag($assignment, 'can_manage'),
            'default_sender' => $this->flag($assignment, 'default_sender'),
            'active' => $this->flag($assignment, 'active', 1),
            'updated_at' => time(),
        ];

        if ((int) $data['default_sender'] === 1 && $type === 'user') {
            \DB::update('core_communication_account_assignments')
                ->set(['default_sender' => 0, 'updated_at' => time()])
                ->where('assignment_type', '=', 'user')
                ->where('assignment_value', '=', $value)
                ->execute();
        }

        $existing = \DB::select('id')
            ->from('core_communication_account_assignments')
            ->where('account_id', '=', $account_id)
            ->where('assignment_type', '=', $type)
            ->where('assignment_value', '=', $value)
            ->execute()
            ->current();

        if ($existing) {
            \DB::update('core_communication_account_assignments')
                ->set($data)
                ->where('id', '=', (int) $existing['id'])
                ->execute();
            $assignment_id = (int) $existing['id'];
        } else {
            $data['created_at'] = time();
            list($assignment_id,) = \DB::insert('core_communication_account_assignments')->set($data)->execute();
        }

        \Log::info('Asignacion de buzon actualizada. account_id='.$account_id.' assignment_id='.$assignment_id);

        return [
            'success' => true,
            'message' => 'Asignacion guardada correctamente.',
            'data' => ['assignment_id' => (int) $assignment_id],
            'errors' => [],
        ];
    }

    public function revoke_assignment($assignment_id)
    {
        if (!\DBUtil::table_exists('core_communication_account_assignments')) {
            return [
                'success' => false,
                'message' => 'Falta la tabla de asignaciones de cuentas.',
                'errors' => ['missing_assignment_table'],
            ];
        }

        $assignment_id = (int) $assignment_id;
        $row = \DB::select('id')
            ->from('core_communication_account_assignments')
            ->where('id', '=', $assignment_id)
            ->execute()
            ->current();

        if (!$row) {
            return [
                'success' => false,
                'message' => 'Asignacion no encontrada.',
                'errors' => ['assignment_not_found'],
            ];
        }

        \DB::update('core_communication_account_assignments')
            ->set(['active' => 0, 'updated_at' => time()])
            ->where('id', '=', $assignment_id)
            ->execute();

        \Log::info('Asignacion de buzon revocada. assignment_id='.$assignment_id);

        return [
            'success' => true,
            'message' => 'Asignacion desactivada correctamente.',
            'errors' => [],
        ];
    }

    public function mask_account_for_user(array $account, $user_id)
    {
        unset(
            $account['password'],
            $account['password_encrypted'],
            $account['smtp_password'],
            $account['smtp_password_encrypted'],
            $account['api_key'],
            $account['api_key_encrypted'],
            $account['api_secret'],
            $account['secret_value'],
            $account['imap_password'],
            $account['new_imap_password'],
            $account['imap_password_encrypted'],
            $account['token'],
            $account['access_token'],
            $account['refresh_token'],
            $account['secret'],
            $account['client_secret']
        );

        $account_id = (int) \Arr::get($account, 'id', 0);
        $account['can_view'] = $this->can_view_account($user_id, $account_id) ? 1 : 0;
        $account['can_send'] = $this->can_send_from_account($user_id, $account_id) ? 1 : 0;
        $account['can_receive'] = $this->can_access($user_id, $account_id, 'receive') ? 1 : 0;
        $account['can_sync'] = $this->can_sync_account($user_id, $account_id) ? 1 : 0;
        $account['can_manage'] = $this->can_manage_account($user_id, $account_id) ? 1 : 0;
        $account['default_sender'] = $this->is_default_sender($user_id, $account_id) ? 1 : 0;

        return $account;
    }

    public function account_ids_for_user($user_id)
    {
        $assignments = $this->assignments_for_user((int) $user_id, false);
        $ids = [];
        foreach ($assignments as $assignment) {
            $ids[] = (int) $assignment['account_id'];
        }

        $ids = array_values(array_unique(array_filter($ids)));
        if (empty($ids) || !\DBUtil::table_exists('core_communication_accounts')) {
            return $ids;
        }

        $rows = \DB::select('id')
            ->from('core_communication_accounts')
            ->where('id', 'in', $ids)
            ->where('active', '=', 1)
            ->execute()
            ->as_array();

        return array_values(array_unique(array_filter(array_map(function ($row) {
            return (int) \Arr::get($row, 'id', 0);
        }, $rows))));
    }

    public function conversation_ids_for_user($user_id)
    {
        if ($this->is_super_admin($user_id)) {
            return null;
        }

        $account_ids = $this->account_ids_for_user($user_id);
        if (empty($account_ids) || !\DBUtil::table_exists('core_communication_messages')) {
            return [];
        }

        $rows = \DB::select('conversation_id')
            ->from('core_communication_messages')
            ->where('active', '=', 1)
            ->where('account_id', 'in', $account_ids)
            ->group_by('conversation_id')
            ->execute()
            ->as_array();

        return array_values(array_unique(array_filter(array_map(function ($row) {
            return (int) \Arr::get($row, 'conversation_id', 0);
        }, $rows))));
    }

    public function assignments()
    {
        if (!\DBUtil::table_exists('core_communication_account_assignments')) {
            return [];
        }

        $rows = \DB::select()
            ->from('core_communication_account_assignments')
            ->order_by('account_id', 'asc')
            ->order_by('assignment_type', 'asc')
            ->execute()
            ->as_array();

        $items = [];
        foreach ($rows as $row) {
            $row['label'] = $this->assignment_label((string) $row['assignment_type'], (string) $row['assignment_value']);
            $items[] = $row;
        }

        return $items;
    }

    protected function can_access($user_id, $account_id, $capability)
    {
        $user_id = (int) $user_id;
        $account_id = (int) $account_id;
        if ($account_id <= 0) {
            return false;
        }

        if ($this->is_super_admin($user_id)) {
            return true;
        }

        if (!$this->account_is_active($account_id)) {
            return false;
        }

        $assignments = $this->assignments_for_user($user_id, false);
        foreach ($assignments as $assignment) {
            if ((int) $assignment['account_id'] !== $account_id) {
                continue;
            }

            if ($capability === 'view') {
                return true;
            }
            if ($capability === 'receive' && (int) $assignment['can_receive'] === 1) {
                return true;
            }
            if ($capability === 'send' && (int) $assignment['can_send'] === 1) {
                return true;
            }
            if ($capability === 'sync' && (int) $assignment['can_sync'] === 1) {
                return true;
            }
            if ($capability === 'manage' && (int) $assignment['can_manage'] === 1) {
                return true;
            }
        }

        return false;
    }

    protected function assignments_for_user($user_id, $send_only)
    {
        if (!\DBUtil::table_exists('core_communication_account_assignments')) {
            return [];
        }

        $groups = $this->groups_for_user($user_id);
        $roles = $this->roles_for_user($user_id);

        $query = \DB::select()
            ->from('core_communication_account_assignments')
            ->where('active', '=', 1)
            ->where_open()
                ->where_open()
                    ->where('assignment_type', '=', 'user')
                    ->where('assignment_value', '=', (string) $user_id)
                ->where_close();

        if (!empty($groups)) {
            $query->or_where_open()
                ->where('assignment_type', '=', 'group')
                ->where('assignment_value', 'in', array_map('strval', $groups))
                ->or_where('assignment_value', 'in', array_map('intval', $groups))
            ->or_where_close();
        }

        if (!empty($roles)) {
            $query->or_where_open()
                ->where('assignment_type', '=', 'role')
                ->where('assignment_value', 'in', $roles)
            ->or_where_close();
        }

        $query->where_close();

        if ($send_only) {
            $query->where('can_send', '=', 1);
        }

        return $query->execute()->as_array();
    }

    protected function is_default_sender($user_id, $account_id)
    {
        foreach ($this->assignments_for_user((int) $user_id, true) as $assignment) {
            if ((int) $assignment['account_id'] === (int) $account_id && (int) $assignment['default_sender'] === 1) {
                return true;
            }
        }

        return false;
    }

    protected function account_by_id($account_id)
    {
        if (!\DBUtil::table_exists('core_communication_accounts')) {
            return [];
        }

        $row = \DB::select()
            ->from('core_communication_accounts')
            ->where('id', '=', (int) $account_id)
            ->execute()
            ->current();

        return $row ? $this->mask_account_for_user($row, $this->current_user_id()) : [];
    }

    protected function account_is_active($account_id)
    {
        if (!\DBUtil::table_exists('core_communication_accounts')) {
            return false;
        }

        $row = \DB::select('id')
            ->from('core_communication_accounts')
            ->where('id', '=', (int) $account_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        return (bool) $row;
    }

    protected function validate_assignment_subject($type, $value)
    {
        $errors = [];
        if ($value === '') {
            $errors[] = 'El destinatario de la asignacion es obligatorio.';
            return $errors;
        }

        if ($type === 'user' && \DBUtil::table_exists('users')) {
            $exists = \DB::select('id')->from('users')->where('id', '=', (int) $value)->execute()->current();
            if (!$exists) {
                $errors[] = 'El usuario indicado no existe.';
            }
        }

        if ($type === 'group' && \DBUtil::table_exists('users_groups')) {
            $exists = \DB::select('id')->from('users_groups')->where('id', '=', (int) $value)->execute()->current();
            if (!$exists) {
                $errors[] = 'El grupo indicado no existe.';
            }
        }

        return $errors;
    }

    protected function assignment_label($type, $value)
    {
        if ($type === 'user' && \DBUtil::table_exists('users')) {
            $row = \DB::select('username', 'email')->from('users')->where('id', '=', (int) $value)->execute()->current();
            if ($row) {
                return trim((string) ($row['username'] ?: $row['email']));
            }
        }

        if ($type === 'group' && \DBUtil::table_exists('users_groups')) {
            $row = \DB::select('name')->from('users_groups')->where('id', '=', (int) $value)->execute()->current();
            if ($row) {
                return (string) $row['name'];
            }
        }

        return $type === 'role' ? 'Rol: '.$value : $value;
    }

    protected function groups_for_user($user_id)
    {
        $groups = [];
        if (\DBUtil::table_exists('users')) {
            $row = \DB::select('group_id')->from('users')->where('id', '=', (int) $user_id)->execute()->current();
            if ($row && (int) $row['group_id'] > 0) {
                $groups[] = (int) $row['group_id'];
            }
        }

        if (\Auth::check()) {
            foreach ((array) \Auth::get_groups() as $group) {
                $value = isset($group[1]) ? $group[1] : null;
                $groups[] = is_object($value) ? (int) $value->id : (int) $value;
            }
        }

        return array_values(array_unique(array_filter($groups)));
    }

    protected function roles_for_user($user_id)
    {
        $roles = [];
        if (\DBUtil::table_exists('users_user_roles') && \DBUtil::table_exists('users_roles')) {
            $rows = \DB::select(['r.name', 'name'])
                ->from(['users_user_roles', 'ur'])
                ->join(['users_roles', 'r'], 'left')->on('ur.role_id', '=', 'r.id')
                ->where('ur.user_id', '=', (int) $user_id)
                ->execute()
                ->as_array();

            foreach ($rows as $row) {
                $roles[] = (string) \Arr::get($row, 'name', '');
            }
        }

        return array_values(array_unique(array_filter($roles)));
    }

    protected function is_super_admin($user_id)
    {
        $groups = $this->groups_for_user((int) $user_id);
        return in_array(100, $groups, true);
    }

    protected function current_user_id()
    {
        if (!\Auth::check()) {
            return 0;
        }
        $data = \Auth::get_user_id();
        return isset($data[1]) ? (int) $data[1] : 0;
    }

    protected function allowed_value($value, array $allowed, $default)
    {
        $value = trim((string) $value);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    protected function flag(array $data, $key, $default = 0)
    {
        return (int) \Arr::get($data, $key, $default) === 1 ? 1 : 0;
    }
}
