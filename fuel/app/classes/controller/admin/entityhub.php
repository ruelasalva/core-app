<?php

class Controller_Admin_Entityhub extends Controller_Adminbase
{
    protected $json_guard_response = null;

    public function before()
    {
        if (!\Auth::check()) {
            $this->json_guard_response = $this->standard_json(false, 'Sesion requerida.', array(), array('auth_required'), 401);
            return;
        }

        parent::before();

        if (!$this->is_super_admin && !\Auth::has_access('entityhub.access[view]')) {
            $this->json_guard_response = $this->standard_json(false, 'No tienes permiso para consultar el Hub de Entidades.', array(), array('permission_denied'), 403);
        }
    }

    public function action_index()
    {
        if ($this->json_guard_response) {
            return $this->json_guard_response;
        }

        $this->template->title = 'Hub de Entidades';
        $this->template->content = \View::forge('admin/entityhub/index');
    }

    public function action_entity()
    {
        if ($this->json_guard_response) {
            return $this->json_guard_response;
        }

        $entity_type = trim((string) \Input::get('entity_type', ''));
        $entity_id = (int) \Input::get('entity_id', 0);

        if ($entity_type === '' || $entity_id <= 0) {
            return $this->standard_json(false, 'Faltan parametros de entidad.', array(), array('entity_type y entity_id son obligatorios.'), 400);
        }

        try {
            $resolver = new Service_Core_EntityHub_ProfileResolver();
            $profile = $resolver->profile($entity_type, $entity_id, $this->user_id);

            if (empty($profile['found'])) {
                return $this->standard_json(false, 'Entidad no encontrada.', array(), array('entity_not_found'), 404);
            }
            if (empty($profile['visible'])) {
                return $this->standard_json(false, 'No tienes permiso para ver esta entidad.', array(), array('permission_denied'), 403);
            }

            return $this->standard_json(true, 'Entidad consultada correctamente.', array('profile' => $profile), array(), 200);
        } catch (\Exception $e) {
            \Log::error('EntityHub entity error: '.$e->getMessage());
            return $this->standard_json(false, 'No fue posible consultar la entidad.', array(), array('internal_error'), 500);
        }
    }

    public function action_relationships()
    {
        if ($this->json_guard_response) {
            return $this->json_guard_response;
        }

        $entity_type = trim((string) \Input::get('entity_type', ''));
        $entity_id = (int) \Input::get('entity_id', 0);
        $limit = (int) \Input::get('limit', 100);

        if ($entity_type === '' || $entity_id <= 0) {
            return $this->standard_json(false, 'Faltan parametros de entidad.', array(), array('entity_type y entity_id son obligatorios.'), 400);
        }

        try {
            $resolver = new Service_Core_EntityHub_RelationshipResolver();
            $result = $resolver->relationships($entity_type, $entity_id, $this->user_id, $limit);

            if (empty($result['found'])) {
                return $this->standard_json(false, 'Entidad no encontrada.', array(), array('entity_not_found'), 404);
            }
            if (empty($result['visible'])) {
                return $this->standard_json(false, 'No tienes permiso para ver relaciones de esta entidad.', array(), array('permission_denied'), 403);
            }

            return $this->standard_json(true, 'Relaciones consultadas correctamente.', $result, array(), 200);
        } catch (\Exception $e) {
            \Log::error('EntityHub relationships error: '.$e->getMessage());
            return $this->standard_json(false, 'No fue posible consultar relaciones.', array(), array('internal_error'), 500);
        }
    }

    public function action_relationship_engine()
    {
        if ($this->json_guard_response) {
            return $this->json_guard_response;
        }

        $entity_type = trim((string) \Input::get('entity_type', ''));
        $entity_id = (int) \Input::get('entity_id', 0);
        $limit = (int) \Input::get('limit', 100);
        $categories_param = trim((string) \Input::get('categories', ''));
        $categories = array();

        if ($categories_param !== '') {
            $categories = array_filter(array_map('trim', explode(',', $categories_param)));
        }

        if ($entity_type === '' || $entity_id <= 0) {
            return $this->standard_json(false, 'Faltan parametros de entidad.', array(), array('entity_type y entity_id son obligatorios.'), 400);
        }

        try {
            $engine = new Service_Core_EntityHub_RelationshipEngine();
            $result = $engine->aggregate($entity_type, $entity_id, $categories, $this->user_id, $limit);

            if (empty($result['found'])) {
                return $this->standard_json(false, 'Entidad no encontrada.', array(), array('entity_not_found'), 404);
            }
            if (empty($result['visible'])) {
                return $this->standard_json(false, 'No tienes permiso para ver relaciones de esta entidad.', array(), array('permission_denied'), 403);
            }

            return $this->standard_json(true, 'Motor de relaciones consultado correctamente.', array(
                'entity' => $result['entity'],
                'relationships' => $result['relationships'],
                'counts' => $result['counts'],
                'hidden_count' => $result['hidden_count'],
            ), array(), 200);
        } catch (\Exception $e) {
            \Log::error('EntityHub relationship engine error: '.$e->getMessage());
            return $this->standard_json(false, 'No fue posible consultar el motor de relaciones.', array(), array('internal_error'), 500);
        }
    }

    public function action_timeline()
    {
        if ($this->json_guard_response) {
            return $this->json_guard_response;
        }

        $entity_type = trim((string) \Input::get('entity_type', ''));
        $entity_id = (int) \Input::get('entity_id', 0);
        $limit = (int) \Input::get('limit', 100);
        $categories_param = trim((string) \Input::get('categories', ''));
        $date_from = trim((string) \Input::get('date_from', ''));
        $date_to = trim((string) \Input::get('date_to', ''));
        $categories = array();

        if ($categories_param !== '') {
            $categories = array_filter(array_map('trim', explode(',', $categories_param)));
        }

        if ($entity_type === '' || $entity_id <= 0) {
            return $this->standard_json(false, 'Faltan parametros de entidad.', array(), array('entity_type y entity_id son obligatorios.'), 400);
        }

        try {
            $reader = new Service_Core_EntityHub_TimelineReader();
            $result = $reader->timeline($entity_type, $entity_id, $categories, $this->user_id, $limit, $date_from, $date_to);

            if (empty($result['found'])) {
                return $this->standard_json(false, 'Entidad no encontrada.', array(), array('entity_not_found'), 404);
            }
            if (empty($result['visible'])) {
                return $this->standard_json(false, 'No tienes permiso para ver la linea de tiempo de esta entidad.', array(), array('permission_denied'), 403);
            }

            return $this->standard_json(true, 'Linea de tiempo consultada correctamente.', array(
                'entity' => $result['entity'],
                'timeline' => $result['timeline'],
                'counts' => $result['counts'],
                'hidden_count' => $result['hidden_count'],
            ), array(), 200);
        } catch (\Exception $e) {
            \Log::error('EntityHub timeline error: '.$e->getMessage());
            return $this->standard_json(false, 'No fue posible consultar la linea de tiempo.', array(), array('internal_error'), 500);
        }
    }

    protected function standard_json($success, $message, array $data = array(), array $errors = array(), $status = 200)
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
