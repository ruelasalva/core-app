<?php

class Service_Core_Communications_RecipientResolver
{
    public function resolve($event_code, $channel_code, array $explicit_user_ids = [], array $explicit_emails = [])
    {
        $event_code = trim((string) $event_code);
        $channel_code = trim((string) $channel_code);

        $result = [
            'users' => [],
            'emails' => [],
            'groups' => [],
            'excluded_count' => 0,
            'rules' => [],
            'warnings' => [],
        ];

        $included_users = [];
        $included_emails = [];
        $included_groups = [];
        $excluded_users = [];
        $excluded_emails = [];

        foreach ($explicit_user_ids as $user_id) {
            $user_id = (int) $user_id;
            if ($user_id > 0) {
                $included_users[$user_id] = $user_id;
            }
        }

        foreach ($explicit_emails as $email) {
            $email = strtolower(trim((string) $email));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $included_emails[$email] = $email;
            }
        }

        foreach ($this->rules($event_code, $channel_code) as $rule) {
            $result['rules'][] = [
                'id' => (int) $rule['id'],
                'event_code' => (string) $rule['event_code'],
                'channel_code' => (string) $rule['channel_code'],
                'recipient_type' => (string) $rule['recipient_type'],
                'recipient_value' => (string) $rule['recipient_value'],
                'mode' => (string) $rule['mode'],
            ];

            $resolved = $this->resolve_rule($rule);
            $target_users = ((string) $rule['mode'] === 'exclude') ? $excluded_users : $included_users;
            $target_emails = ((string) $rule['mode'] === 'exclude') ? $excluded_emails : $included_emails;

            foreach ($resolved['users'] as $user_id) {
                $target_users[(int) $user_id] = (int) $user_id;
            }
            foreach ($resolved['emails'] as $email) {
                $target_emails[$email] = $email;
            }
            foreach ($resolved['groups'] as $group_id) {
                $included_groups[(int) $group_id] = (int) $group_id;
            }

            if ((string) $rule['mode'] === 'exclude') {
                $excluded_users = $target_users;
                $excluded_emails = $target_emails;
            } else {
                $included_users = $target_users;
                $included_emails = $target_emails;
            }
        }

        foreach ($excluded_users as $user_id) {
            if (isset($included_users[$user_id])) {
                unset($included_users[$user_id]);
                $result['excluded_count']++;
            }
        }

        foreach ($excluded_emails as $email) {
            if (isset($included_emails[$email])) {
                unset($included_emails[$email]);
                $result['excluded_count']++;
            }
        }

        if ($channel_code === 'email') {
            foreach ($included_users as $user_id) {
                $email = $this->user_email($user_id);
                if ($email !== '') {
                    $included_emails[strtolower($email)] = strtolower($email);
                }
            }
        }

        $result['users'] = array_values($included_users);
        $result['emails'] = array_values($included_emails);
        $result['groups'] = array_values($included_groups);

        return $result;
    }

    public function has_rules($event_code, $channel_code)
    {
        if (!\DBUtil::table_exists('core_communication_event_recipients')) {
            return false;
        }

        return \DB::select('id')
            ->from('core_communication_event_recipients')
            ->where('event_code', '=', trim((string) $event_code))
            ->where('channel_code', '=', trim((string) $channel_code))
            ->where('active', '=', 1)
            ->limit(1)
            ->execute()
            ->count() > 0;
    }

    protected function rules($event_code, $channel_code)
    {
        if (!\DBUtil::table_exists('core_communication_event_recipients')) {
            return [];
        }

        return \DB::select()
            ->from('core_communication_event_recipients')
            ->where('event_code', '=', $event_code)
            ->where('channel_code', '=', $channel_code)
            ->where('active', '=', 1)
            ->order_by('id', 'asc')
            ->execute()
            ->as_array();
    }

    protected function resolve_rule(array $rule)
    {
        $type = (string) $rule['recipient_type'];
        $value = trim((string) $rule['recipient_value']);
        $result = ['users' => [], 'emails' => [], 'groups' => []];

        if ($type === 'user') {
            $user_id = (int) $value;
            if ($user_id > 0 && $this->user_exists($user_id)) {
                $result['users'][] = $user_id;
            }
        } elseif ($type === 'group') {
            $group_id = (int) $value;
            if ($group_id > 0) {
                $result['groups'][] = $group_id;
                $result['users'] = array_merge($result['users'], $this->users_by_group($group_id));
            }
        } elseif ($type === 'email') {
            $email = strtolower($value);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $result['emails'][] = $email;
            }
        } elseif ($type === 'role') {
            $result['emails'] = array_merge($result['emails'], $this->emails_by_role($value));
        }

        return $result;
    }

    protected function user_exists($user_id)
    {
        if (!\DBUtil::table_exists('users')) {
            return false;
        }

        return \DB::select('id')
            ->from('users')
            ->where('id', '=', (int) $user_id)
            ->limit(1)
            ->execute()
            ->count() > 0;
    }

    protected function users_by_group($group_id)
    {
        if (!\DBUtil::table_exists('users')) {
            return [];
        }

        $rows = \DB::select('id')
            ->from('users')
            ->where('group_id', '=', (int) $group_id)
            ->execute()
            ->as_array();

        $users = [];
        foreach ($rows as $row) {
            $users[] = (int) $row['id'];
        }

        return $users;
    }

    protected function user_email($user_id)
    {
        if (!\DBUtil::table_exists('users')) {
            return '';
        }

        $row = \DB::select('email')
            ->from('users')
            ->where('id', '=', (int) $user_id)
            ->execute()
            ->current();

        $email = $row ? trim((string) $row['email']) : '';
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    protected function emails_by_role($role_code)
    {
        if (!\DBUtil::table_exists('core_email_roles')) {
            return [];
        }

        $role = \DB::select('to_emails')
            ->from('core_email_roles')
            ->where('code', '=', trim((string) $role_code))
            ->where('active', '=', 1)
            ->execute()
            ->current();

        if (!$role || trim((string) $role['to_emails']) === '') {
            return [];
        }

        $emails = [];
        foreach (preg_split('/[,;\n\r]+/', (string) $role['to_emails']) as $email) {
            $email = strtolower(trim($email));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[$email] = $email;
            }
        }

        return array_values($emails);
    }
}
