<?php

/**
 * CONTROLADOR ADMINBASE
 *
 * Base comun para todos los controladores administrativos de Core-App.
 *
 * @package  app
 * @extends  Controller_Template
 */
class Controller_Adminbase extends Controller_Template
{
    public $template = 'admin/template';
    protected $user_id = 0;
    protected $user_group = 0;
    protected $is_super_admin = false;

    /**
     * BEFORE
     *
     * VALIDA SESION, OBTIENE DATOS DEL USUARIO Y PREPARA EL MENU ADMINISTRATIVO
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING
        parent::before();

        # VALIDAR SESION
        if (!\Auth::check()) {
            \Response::redirect('login');
        }

        # SE OBTIENE EL ID DEL USUARIO LOGUEADO
        $user_id_data = \Auth::get_user_id();
        $this->user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;
        $username = \Auth::get_screen_name();

        # SE OBTIENE EL GRUPO PRINCIPAL ORM AUTH
        $groups = \Auth::get_groups();
        if (!empty($groups)) {
            $group_data = $groups[0][1];
            $this->user_group = is_object($group_data) ? (int) $group_data->id : (int) $group_data;
        }

        $this->is_super_admin = ($this->user_group === 100);

        # SE ASIGNAN VARIABLES GLOBALES AL TEMPLATE
        $this->template->user_name = $username;
        $this->template->user_group = $this->user_group;

        # SE CONSTRUYE EL MENU SEGUN PERMISOS
        $this->template->menu = [
            'users'  => $this->is_super_admin || \Auth::has_access('user.access[view]'),
            'acl'    => $this->is_super_admin || \Auth::has_access('permissions.access[view]'),
            'config' => $this->is_super_admin || \Auth::has_access('config.access[view]'),
            'web'    => $this->is_super_admin || \Auth::has_access('web.access[view]'),
            'legal'  => $this->is_super_admin || \Auth::has_access('legal.access[view]'),
            'communications' => $this->is_super_admin || \Auth::has_access('communications.access[view]'),
            'integrations' => $this->is_super_admin || \Auth::has_access('integrations.access[view]'),
            'payments' => $this->is_super_admin || \Auth::has_access('payments.access[view]'),
            'accounting' => $this->is_super_admin || \Auth::has_access('accounting.access[view]'),
            'hr' => $this->is_super_admin || \Auth::has_access('hr.access[view]'),
            'purchases' => $this->is_super_admin || \Auth::has_access('purchases.access[view]'),
            'billing' => $this->is_super_admin || \Auth::has_access('billing.access[view]'),
            'sales' => $this->is_super_admin || \Auth::has_access('sales.access[view]'),
            'inventory' => $this->is_super_admin || \Auth::has_access('inventory.access[view]'),
            'crm' => $this->is_super_admin || \Auth::has_access('crm.access[view]'),
            'audit' => $this->is_super_admin || \Auth::has_access('audit.access[view]'),
            'sat' => $this->is_super_admin || \Auth::has_access('sat.access[view]'),
            'catalogs' => $this->is_super_admin || \Auth::has_access('catalogs.access[view]'),
            'commerce' => $this->is_super_admin || \Auth::has_access('commerce.access[view]'),
            'parties' => $this->is_super_admin || \Auth::has_access('parties.access[view]'),
            'portals' => $this->is_super_admin || \Auth::has_access('portals.access[view]'),
            'documents' => $this->is_super_admin || \Auth::has_access('documents.access[view]'),
            'helpdesk' => $this->is_super_admin || \Auth::has_access('helpdesk.access[view]'),
            'calendar' => $this->is_super_admin || \Auth::has_access('calendar.access[view]'),
            'frontend' => $this->is_super_admin || \Auth::has_access('frontend.access[view]'),
            'help' => $this->is_super_admin || \Auth::has_access('help.access[view]'),
        ];
    }

    /**
     * REQUIRE ACCESS
     *
     * VALIDA UN PERMISO ORM AUTH O LANZA ERROR DE ACCESO
     *
     * @access  protected
     * @return  Void
     */
    protected function require_access($permission)
    {
        if (!$this->is_super_admin && !\Auth::has_access($permission)) {
            throw new \HttpNoAccessException;
        }
    }

    /**
     * JSON RESPONSE
     *
     * GENERA RESPUESTAS JSON ESTANDAR PARA VUE/API ADMIN
     *
     * @access  protected
     * @return  Response
     */
    protected function json_response(array $data, $status = 200)
    {
        return \Response::forge(
            json_encode($data),
            $status,
            ['Content-Type' => 'application/json']
        );
    }

    protected function can_view_all_operational()
    {
        return $this->is_super_admin || in_array($this->user_group, [80, 90, 100], true);
    }

    protected function employee_department_id()
    {
        $row = \DB::select('department_id')
            ->from('core_employees')
            ->where('user_id', '=', (int) $this->user_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        return $row ? (int) $row['department_id'] : 0;
    }

    protected function apply_party_scope($query, $alias = 'p', $role = 'any')
    {
        if ($this->can_view_all_operational()) {
            return $query;
        }

        $department_id = $this->employee_department_id();
        $department_field = $alias.'.department_id';
        $sales_field = $alias.'.sales_user_id';
        $buyer_field = $alias.'.buyer_user_id';

        $query->where_open();
        if ($role === 'sales') {
            $query->where($sales_field, '=', (int) $this->user_id);
        } elseif ($role === 'purchases') {
            $query->where($buyer_field, '=', (int) $this->user_id);
        } else {
            $query->where($sales_field, '=', (int) $this->user_id)
                ->or_where($buyer_field, '=', (int) $this->user_id);
        }

        if ($department_id > 0) {
            $query->or_where($department_field, '=', $department_id);
        }
        $query->where_close();

        return $query;
    }
}
