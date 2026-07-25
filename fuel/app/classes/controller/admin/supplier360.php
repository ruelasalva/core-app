<?php

class Controller_Admin_Supplier360 extends Controller_Adminbase
{
    public function before()
    {
        if ($this->request && in_array($this->request->action, array('data', 'search'), true)) {
            $this->auto_render = false;
            if (\Auth::check()) {
                $this->setup_authenticated_context();
            }
            return;
        }

        parent::before();

        if (!$this->can_access_supplier360()) {
            throw new \HttpNoAccessException;
        }
    }

    public function action_index()
    {
        $this->template->title = 'Vista 360 de Proveedor';
        $this->template->content = \View::forge('admin/supplier360/index', array(
            'can_purchases' => $this->is_super_admin || \Auth::has_access('purchases.access[view]'),
            'can_communications' => $this->is_super_admin || \Auth::has_access('communications.access[view]'),
            'can_documents' => $this->is_super_admin || \Auth::has_access('documents.access[view]'),
        ));
    }

    public function action_data()
    {
        if ($response = $this->json_guard()) {
            return $response;
        }

        $party_id = (int) \Input::get('party_id', 0);
        if ($party_id <= 0) {
            return $this->json(false, 'Captura un proveedor valido.', array(), array('party_id_required'), 400);
        }

        try {
            $service = new Service_Core_EntityHub_Supplier360();
            $data = $service->data($party_id, $this->user_id);

            if (empty($data['found'])) {
                return $this->json(false, 'Proveedor no encontrado.', array(), array('supplier_not_found'), 404);
            }
            if (empty($data['visible'])) {
                return $this->json(false, 'No tienes permiso para ver este proveedor.', array(), array('permission_denied'), 403);
            }

            return $this->json(true, 'Vista 360 de Proveedor consultada correctamente.', $data);
        } catch (\Exception $e) {
            \Log::error('Supplier360 data error: '.$e->getMessage());
            return $this->json(false, 'No fue posible cargar Vista 360 de Proveedor.', array(), array('internal_error'), 500);
        }
    }

    public function action_search()
    {
        if ($response = $this->json_guard()) {
            return $response;
        }

        if (!\DBUtil::table_exists('core_parties')) {
            return $this->json(false, 'Falta la tabla de proveedores.', array(), array('core_parties_missing'), 500);
        }

        $q = trim((string) \Input::get('q', ''));
        $limit = max(1, min(20, (int) \Input::get('limit', 15)));

        try {
            $query = \DB::select('p.id', 'p.code', 'p.name', 'p.legal_name', 'p.email', 'p.phone')
                ->from(array('core_parties', 'p'))
                ->where('p.party_type', '=', 'supplier')
                ->where('p.active', '=', 1)
                ->order_by('p.name', 'asc')
                ->limit($limit);

            if ($q !== '') {
                $like = '%'.$q.'%';
                $query->where_open()
                    ->where('p.name', 'like', $like)
                    ->or_where('p.legal_name', 'like', $like)
                    ->or_where('p.code', 'like', $like)
                    ->or_where('p.email', 'like', $like)
                    ->where_close();
            }

            $scope = new Service_Core_EntityHub_SecurityScope(new Service_Core_EntityHub_EntityResolver());
            $items = array();
            foreach ($query->execute() as $row) {
                $entity = (new Service_Core_EntityHub_EntityResolver())->resolve('supplier', (int) $row['id']);
                if (!$entity || !$scope->can_view('supplier', (int) $row['id'], $entity, (int) $this->user_id)) {
                    continue;
                }
                $items[] = array(
                    'id' => (int) $row['id'],
                    'code' => (string) $row['code'],
                    'name' => (string) $row['name'],
                    'legal_name' => (string) $row['legal_name'],
                    'email' => (string) $row['email'],
                    'phone' => (string) $row['phone'],
                    'label' => trim(((string) $row['code'] !== '' ? (string) $row['code'].' - ' : '').(string) $row['name']),
                );
            }

            return $this->json(true, 'Proveedores consultados correctamente.', array('suppliers' => $items));
        } catch (\Exception $e) {
            \Log::error('Supplier360 search error: '.$e->getMessage());
            return $this->json(false, 'No fue posible buscar proveedores.', array(), array('internal_error'), 500);
        }
    }

    protected function can_access_supplier360()
    {
        if ($this->is_super_admin) {
            return true;
        }

        foreach (array('purchases.access[view]', 'suppliers.access[view]', 'business.access[view]', 'parties.access[view]') as $permission) {
            if (\Auth::has_access($permission)) {
                return true;
            }
        }

        return false;
    }

    protected function setup_authenticated_context()
    {
        $user_id_data = \Auth::get_user_id();
        $this->user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;

        $groups = \Auth::get_groups();
        if (!empty($groups)) {
            $group_data = $groups[0][1];
            $this->user_group = is_object($group_data) ? (int) $group_data->id : (int) $group_data;
        }

        $this->is_super_admin = ($this->user_group === 100);
    }

    protected function json_guard()
    {
        if (!\Auth::check()) {
            return $this->json(false, 'Sesion requerida.', array(), array('auth_required'), 401);
        }

        if ($this->user_id <= 0) {
            $this->setup_authenticated_context();
        }

        if ((new \Service_Core_Auth_PasswordPolicy())->must_change($this->user_id)) {
            return $this->json(false, 'Debes cambiar tu contraseña antes de continuar.', array(), array('password_change_required'), 403);
        }

        if (!$this->can_access_supplier360()) {
            return $this->json(false, 'No tienes permiso para consultar Vista 360 de Proveedor.', array(), array('permission_denied'), 403);
        }

        return null;
    }

    protected function json($success, $message, array $data = array(), array $errors = array(), $status = 200)
    {
        return \Response::forge(
            json_encode(array(
                'success' => (bool) $success,
                'message' => (string) $message,
                'data' => $data,
                'errors' => $errors,
            )),
            $status,
            array('Content-Type' => 'application/json')
        );
    }
}
