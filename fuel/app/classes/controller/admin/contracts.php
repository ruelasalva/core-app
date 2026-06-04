<?php

/**
 * CONTROLADOR ADMIN_CONTRACTS
 *
 * Administra contratos internos y su flujo operativo inicial.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Contracts extends Controller_Adminbase
{
    protected $manager;

    /**
     * BEFORE
     *
     * VALIDA SESION ADMINISTRATIVA Y PERMISO DE LECTURA DE CONTRATOS.
     *
     * @return  Void
     */
    public function before()
    {
        parent::before();
        $this->require_access('contracts.access[view]');
        $this->manager = new \Service_Core_Contracts_Manager();
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL ADMINISTRATIVO DE CONTRATOS.
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        $this->template->title = 'Contratos';
        $this->template->content = \View::forge('admin/contracts/index');
    }

    /**
     * DATA
     *
     * ENTREGA CONTRATOS, CATALOGOS Y CONTADORES PARA VUE.
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            $this->assert_schema_ready();

            return $this->contract_response('Contratos cargados.', [
                'contracts' => $this->contracts(),
                'options' => $this->options(),
                'stats' => $this->stats(),
                'permissions' => $this->permissions(),
                'documents' => $this->contract_documents(),
                'relations' => $this->contract_relations(),
                'events' => $this->contract_events(),
                'available_documents' => $this->available_documents(),
                'relation_options' => $this->relation_options(),
                'document_structure' => $this->document_structure(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Contratos: error cargando datos: '.$e->getMessage());
            return $this->contract_response('No se pudo cargar contratos.', [], ['Error interno cargando contratos.'], 500);
        }
    }

    /**
     * SAVE
     *
     * CREA O ACTUALIZA UN CONTRATO.
     *
     * @access  public
     * @return  Response
     */
    public function post_save()
    {
        $val = (array) \Input::json();
        $id = (int) \Arr::get($val, 'id', 0);

        try {
            $permission = $id > 0 ? 'contracts.access[edit]' : 'contracts.access[create]';
            if (!$this->can_access_contract($permission)) {
                \Log::warning('Contratos: permiso insuficiente en guardar contrato permiso='.$permission.' user_id='.(int) $this->user_id);
                return $this->contract_response('No tienes permiso para guardar contratos.', [], ['Falta el permiso '.$permission.'.'], 403);
            }

            $this->assert_schema_ready();

            $title = trim((string) \Arr::get($val, 'title', ''));
            if ($title === '') {
                return $this->contract_response('El titulo es obligatorio.', [], ['El titulo es obligatorio.'], 422);
            }

            $contract_type = $this->codeify(\Arr::get($val, 'contract_type', 'service_agreement')) ?: 'service_agreement';
            if (!$this->valid_contract_type($contract_type)) {
                return $this->contract_response('Tipo de contrato no valido.', [], ['Selecciona un tipo de contrato valido.'], 422);
            }

            $party_id = (int) \Arr::get($val, 'party_id', 0);
            if ($party_id > 0 && !$this->record_exists('core_parties', $party_id)) {
                return $this->contract_response('Tercero no valido.', [], ['El tercero seleccionado no existe o esta inactivo.'], 422);
            }

            $responsible_user_id = (int) \Arr::get($val, 'responsible_user_id', 0);
            if ($responsible_user_id > 0 && !$this->record_exists('users', $responsible_user_id, false)) {
                return $this->contract_response('Responsable no valido.', [], ['El responsable seleccionado no existe.'], 422);
            }

            $start_date_result = $this->validated_date(\Arr::get($val, 'start_date', ''), 'Fecha inicial');
            if (!$start_date_result['valid']) {
                return $this->contract_response('Fecha inicial invalida.', [], [$start_date_result['error']], 422);
            }

            $end_date_result = $this->validated_date(\Arr::get($val, 'end_date', ''), 'Fecha final');
            if (!$end_date_result['valid']) {
                return $this->contract_response('Fecha final invalida.', [], [$end_date_result['error']], 422);
            }

            $start_date = $start_date_result['value'];
            $end_date = $end_date_result['value'];
            if ($start_date && $end_date && $start_date > $end_date) {
                return $this->contract_response('La fecha final no puede ser menor a la inicial.', [], ['Rango de fechas invalido.'], 422);
            }

            $data = [
                'company_id' => $this->current_company_id(),
                'contract_type' => $contract_type,
                'party_id' => $party_id,
                'portal_code' => $this->codeify(\Arr::get($val, 'portal_code', 'admin')) ?: 'admin',
                'title' => $title,
                'description' => $this->sanitize_rich_html(\Arr::get($val, 'description', '')),
                'start_date' => $start_date,
                'end_date' => $end_date,
                'renewal_type' => $this->renewal_type(\Arr::get($val, 'renewal_type', 'none')),
                'responsible_user_id' => $responsible_user_id,
                'contract_value' => max(0, (float) \Arr::get($val, 'contract_value', 0)),
                'currency_code' => $this->currency_code(\Arr::get($val, 'currency_code', 'MXN')),
                'renewal_value' => max(0, (float) \Arr::get($val, 'renewal_value', 0)),
                'renewal_currency_code' => $this->currency_code(\Arr::get($val, 'renewal_currency_code', \Arr::get($val, 'currency_code', 'MXN'))),
                'billing_type' => $this->billing_type(\Arr::get($val, 'billing_type', 'none')),
                'response_hours' => max(0, (float) \Arr::get($val, 'response_hours', 0)),
                'resolution_hours' => max(0, (float) \Arr::get($val, 'resolution_hours', 0)),
                'visibility' => $this->visibility(\Arr::get($val, 'visibility', 'internal')),
                'notes' => $this->sanitize_rich_html(\Arr::get($val, 'notes', '')),
                'updated_by' => $this->user_id,
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            if ($id > 0) {
                $contract = \Model_Core_Contract::find($id);
                if (!$contract) {
                    return $this->contract_response('Contrato no encontrado.', [], ['Contrato no encontrado.'], 404);
                }
                $protection_errors = $this->protected_status_errors($contract, $data);
                if (!empty($protection_errors)) {
                    return $this->contract_response('El contrato ya no permite modificar campos criticos.', [], $protection_errors, 422);
                }
                $old_status = (string) $contract->status;
                $contract->set($data);
                $event_type = 'updated';
                $message = 'Contrato actualizado.';
            } else {
                $old_status = '';
                $data['contract_number'] = $this->manager->next_contract_number();
                $data['status'] = 'draft';
                $data['approval_status'] = 'not_required';
                $data['approved_by'] = null;
                $data['approved_at'] = null;
                $data['signed_at'] = null;
                $data['created_by'] = $this->user_id;
                $contract = \Model_Core_Contract::forge($data);
                $event_type = 'created';
                $message = 'Contrato creado.';
            }

            $contract = $this->save_with_number_retry($contract, $id === 0);
            $this->manager->create_event((int) $contract->id, $event_type, $old_status, (string) $contract->status, $message, $contract->to_array(), $this->user_id);
            \Log::info('Contratos: '.$message.' id='.(int) $contract->id.' numero='.(string) $contract->contract_number);

            return $this->contract_response($message, [
                'contract' => $this->format_contract($contract),
                'contracts' => $this->contracts(),
                'stats' => $this->stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Contratos: error guardando contrato: '.$e->getMessage().' payload='.json_encode($val));
            return $this->contract_response('No se pudo guardar el contrato.', [], [$this->exception_message($e)], 400);
        }
    }

    /**
     * CHANGE STATUS
     *
     * CAMBIA EL ESTADO DE UN CONTRATO VALIDANDO EL FLUJO PERMITIDO.
     *
     * @access  public
     * @return  Response
     */
    public function post_change_status()
    {
        $this->require_access('contracts.access[status]');
        $val = (array) \Input::json();

        try {
            $this->assert_schema_ready();

            $id = (int) \Arr::get($val, 'id', 0);
            $status = $this->codeify(\Arr::get($val, 'status', ''));
            $contract = $id > 0 ? \Model_Core_Contract::find($id) : null;

            if (!$contract) {
                return $this->contract_response('Contrato no encontrado.', [], ['Contrato no encontrado.'], 404);
            }

            if (!$this->valid_status($status)) {
                return $this->contract_response('Estado no valido.', [], ['Estado no valido.'], 422);
            }

            $old_status = (string) $contract->status;
            if (!$this->manager->validate_status_transition($old_status, $status)) {
                \Log::warning('Contratos: transicion no permitida contrato='.(int) $contract->id.' '.$old_status.' -> '.$status);
                return $this->contract_response('Transicion de estado no permitida.', [], ['El flujo '.$old_status.' -> '.$status.' no esta permitido.'], 422);
            }

            $contract->status = $status;
            $contract->updated_by = $this->user_id;
            $contract->save();

            $message = 'Estado actualizado de '.$old_status.' a '.$status.'.';
            $this->manager->create_event((int) $contract->id, 'status_changed', $old_status, $status, $message, ['notes' => trim((string) \Arr::get($val, 'notes', ''))], $this->user_id);
            \Log::info('Contratos: '.$message.' id='.(int) $contract->id);

            return $this->contract_response('Estado actualizado.', [
                'contract' => $this->format_contract($contract),
                'contracts' => $this->contracts(),
                'stats' => $this->stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Contratos: error cambiando estado: '.$e->getMessage());
            return $this->contract_response('No se pudo cambiar el estado.', [], ['Error interno cambiando estado.'], 400);
        }
    }

    /**
     * UPLOAD DOCUMENT
     *
     * SUBE UN DOCUMENTO Y LO VINCULA A UN CONTRATO.
     *
     * @access  public
     * @return  Response
     */
    public function post_upload_document()
    {
        $this->require_access('contracts.access[upload_document]');

        try {
            $this->assert_schema_ready();

            $contract_id = (int) \Input::post('contract_id', 0);
            $contract = $this->contract_by_id($contract_id);
            if (!$contract) {
                return $this->contract_response('Contrato no encontrado.', [], ['Contrato no encontrado.'], 404);
            }

            $file = \Input::file('file');
            if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return $this->contract_response('Selecciona un archivo valido.', [], ['Selecciona un archivo valido.'], 422);
            }

            $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
            $allowed = ['pdf', 'xml', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];
            if (!in_array($extension, $allowed, true)) {
                return $this->contract_response('Tipo de archivo no permitido.', [], ['Tipo de archivo no permitido.'], 422);
            }

            if ((int) \Arr::get($file, 'size', 0) > 15728640) {
                return $this->contract_response('El archivo no puede superar 15 MB.', [], ['El archivo no puede superar 15 MB.'], 422);
            }

            $relation_type = $this->document_relation_type(\Input::post('relation_type', 'annex'));
            $document_type = $this->document_type_from_relation($relation_type);
            $relative_dir = 'assets/uploads/contracts/'.date('Y').'/'.date('m');
            $absolute_dir = DOCROOT.$relative_dir;
            if (!is_dir($absolute_dir)) {
                mkdir($absolute_dir, 0755, true);
            }

            $base_name = pathinfo((string) \Arr::get($file, 'name', 'documento'), PATHINFO_FILENAME);
            $filename = time().'_'.\Str::random('alnum', 12).'_'.$this->codeify($base_name).'.'.$extension;
            $target = $absolute_dir.DS.$filename;
            if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
                return $this->contract_response('No se pudo guardar el archivo.', [], ['No se pudo guardar el archivo.'], 400);
            }

            $path = str_replace('\\', '/', $relative_dir.'/'.$filename);
            $document = \Model_Core_Document::forge([
                'document_type' => $document_type,
                'title' => trim((string) \Input::post('title', '')) ?: $base_name,
                'description' => trim((string) \Input::post('description', '')),
                'file_path' => $path,
                'original_name' => (string) \Arr::get($file, 'name', ''),
                'mime_type' => (string) \Arr::get($file, 'type', ''),
                'file_extension' => $extension,
                'file_size' => (int) \Arr::get($file, 'size', 0),
                'checksum' => is_file($target) ? hash_file('sha256', $target) : '',
                'visibility' => $this->visibility(\Input::post('visibility', 'internal')),
                'is_evidence' => $relation_type === 'evidence' ? 1 : 0,
                'uploaded_by' => $this->user_id,
                'active' => 1,
            ]);
            $document->save();

            \Model_Core_Document_Link::forge([
                'document_id' => (int) $document->id,
                'entity_type' => 'contract',
                'entity_id' => $contract_id,
                'relation_type' => $relation_type,
                'notes' => trim((string) \Input::post('notes', '')),
                'created_by' => $this->user_id,
                'active' => 1,
            ])->save();

            $this->manager->create_event($contract_id, 'document_uploaded', (string) $contract->status, (string) $contract->status, 'Documento cargado: '.$document->title, ['document_id' => (int) $document->id], $this->user_id);
            \Log::info('Contratos: documento cargado contract_id='.$contract_id.' document_id='.(int) $document->id);

            return $this->detail_response('Documento cargado.', $contract_id);
        } catch (\Exception $e) {
            \Log::error('Contratos: error cargando documento: '.$e->getMessage());
            return $this->contract_response('No se pudo cargar el documento.', [], ['Error interno cargando documento.'], 400);
        }
    }

    /**
     * LINK DOCUMENT
     *
     * VINCULA UN DOCUMENTO EXISTENTE A UN CONTRATO.
     *
     * @access  public
     * @return  Response
     */
    public function post_link_document()
    {
        $this->require_access('contracts.access[upload_document]');
        $val = (array) \Input::json();

        try {
            $this->assert_schema_ready();

            $contract_id = (int) \Arr::get($val, 'contract_id', 0);
            $document_id = (int) \Arr::get($val, 'document_id', 0);
            $contract = $this->contract_by_id($contract_id);
            if (!$contract) {
                return $this->contract_response('Contrato no encontrado.', [], ['Contrato no encontrado.'], 404);
            }
            if (!$this->document_exists($document_id)) {
                return $this->contract_response('Documento no encontrado.', [], ['Documento no encontrado.'], 404);
            }
            if ($this->document_link_exists($contract_id, $document_id)) {
                return $this->contract_response('El documento ya esta vinculado.', [], ['El documento ya esta vinculado al contrato.'], 422);
            }

            \Model_Core_Document_Link::forge([
                'document_id' => $document_id,
                'entity_type' => 'contract',
                'entity_id' => $contract_id,
                'relation_type' => $this->document_relation_type(\Arr::get($val, 'relation_type', 'annex')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'created_by' => $this->user_id,
                'active' => 1,
            ])->save();

            $this->manager->create_event($contract_id, 'document_linked', (string) $contract->status, (string) $contract->status, 'Documento existente vinculado.', ['document_id' => $document_id], $this->user_id);
            \Log::info('Contratos: documento vinculado contract_id='.$contract_id.' document_id='.$document_id);

            return $this->detail_response('Documento vinculado.', $contract_id);
        } catch (\Exception $e) {
            \Log::error('Contratos: error vinculando documento: '.$e->getMessage());
            return $this->contract_response('No se pudo vincular el documento.', [], ['Error interno vinculando documento.'], 400);
        }
    }

    /**
     * DOWNLOAD DOCUMENT
     *
     * DESCARGA UN DOCUMENTO VINCULADO A CONTRATO.
     *
     * @access  public
     * @return  Response
     */
    public function action_download_document($document_id = 0)
    {
        try {
            $this->assert_schema_ready();
            $document = $this->contract_document_by_id((int) $document_id);
            if (!$document) {
                throw new \HttpNotFoundException;
            }

            return $this->download_document_file($document);
        } catch (\HttpNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Contratos: error descargando documento: '.$e->getMessage());
            throw new \HttpNotFoundException;
        }
    }

    /**
     * REMOVE DOCUMENT LINK
     *
     * DESACTIVA EL VINCULO SIN BORRAR EL DOCUMENTO.
     *
     * @access  public
     * @return  Response
     */
    public function post_remove_document_link()
    {
        $this->require_access('contracts.access[upload_document]');
        $val = (array) \Input::json();

        try {
            $this->assert_schema_ready();
            $link_id = (int) \Arr::get($val, 'link_id', 0);
            $link = \Model_Core_Document_Link::find($link_id);
            if (!$link || (string) $link->entity_type !== 'contract' || (int) $link->active !== 1) {
                return $this->contract_response('Vinculo documental no encontrado.', [], ['Vinculo documental no encontrado.'], 404);
            }

            $contract = $this->contract_by_id((int) $link->entity_id);
            if (!$contract) {
                return $this->contract_response('Contrato no encontrado.', [], ['Contrato no encontrado.'], 404);
            }

            $link->active = 0;
            $link->save();

            $this->manager->create_event((int) $contract->id, 'document_unlinked', (string) $contract->status, (string) $contract->status, 'Vinculo documental removido.', ['document_id' => (int) $link->document_id], $this->user_id);
            \Log::info('Contratos: vinculo documental removido link_id='.$link_id.' contract_id='.(int) $contract->id);

            return $this->detail_response('Vinculo documental removido.', (int) $contract->id);
        } catch (\Exception $e) {
            \Log::error('Contratos: error removiendo vinculo documental: '.$e->getMessage());
            return $this->contract_response('No se pudo remover el vinculo documental.', [], ['Error interno removiendo vinculo documental.'], 400);
        }
    }

    /**
     * SAVE RELATION
     *
     * CREA UNA RELACION ENTRE CONTRATO Y ENTIDAD ERP.
     *
     * @access  public
     * @return  Response
     */
    public function post_save_relation()
    {
        $this->require_access('contracts.access[link]');
        $val = (array) \Input::json();

        try {
            $this->assert_schema_ready();

            $contract_id = (int) \Arr::get($val, 'contract_id', 0);
            $contract = $this->contract_by_id($contract_id);
            if (!$contract) {
                return $this->contract_response('Contrato no encontrado.', [], ['Contrato no encontrado.'], 404);
            }

            $entity_type = $this->relation_entity_type(\Arr::get($val, 'related_entity_type', ''));
            $entity_id = (int) \Arr::get($val, 'related_entity_id', 0);
            $module = $this->relation_module($entity_type);
            if ($entity_type === '' || $entity_id < 1 || !$this->relation_target_exists($entity_type, $entity_id)) {
                return $this->contract_response('Entidad relacionada no valida.', [], ['La entidad relacionada no existe o no esta disponible.'], 422);
            }
            if ($this->contract_relation_exists($contract_id, $entity_type, $entity_id)) {
                return $this->contract_response('La relacion ya existe.', [], ['La relacion ya existe para este contrato.'], 422);
            }

            $relation = \Model_Core_Contract_Relation::forge([
                'contract_id' => $contract_id,
                'related_module' => $module,
                'related_entity_type' => $entity_type,
                'related_entity_id' => $entity_id,
                'relation_type' => $this->codeify(\Arr::get($val, 'relation_type', 'reference')) ?: 'reference',
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'created_by' => $this->user_id,
                'active' => 1,
            ]);
            $relation->save();

            $this->manager->create_event($contract_id, 'relation_created', (string) $contract->status, (string) $contract->status, 'Relacion creada con '.$entity_type.' #'.$entity_id.'.', $relation->to_array(), $this->user_id);
            \Log::info('Contratos: relacion creada contract_id='.$contract_id.' entity='.$entity_type.'#'.$entity_id);

            return $this->detail_response('Relacion creada.', $contract_id);
        } catch (\Exception $e) {
            \Log::error('Contratos: error creando relacion: '.$e->getMessage());
            return $this->contract_response('No se pudo crear la relacion.', [], ['Error interno creando relacion.'], 400);
        }
    }

    /**
     * REMOVE RELATION
     *
     * DESACTIVA UNA RELACION DEL CONTRATO.
     *
     * @access  public
     * @return  Response
     */
    public function post_remove_relation()
    {
        $this->require_access('contracts.access[link]');
        $val = (array) \Input::json();

        try {
            $this->assert_schema_ready();
            $relation_id = (int) \Arr::get($val, 'relation_id', 0);
            $relation = \Model_Core_Contract_Relation::find($relation_id);
            if (!$relation || (int) $relation->active !== 1) {
                return $this->contract_response('Relacion no encontrada.', [], ['Relacion no encontrada.'], 404);
            }

            $contract = $this->contract_by_id((int) $relation->contract_id);
            if (!$contract) {
                return $this->contract_response('Contrato no encontrado.', [], ['Contrato no encontrado.'], 404);
            }

            $relation->active = 0;
            $relation->save();

            $this->manager->create_event((int) $contract->id, 'relation_removed', (string) $contract->status, (string) $contract->status, 'Relacion removida.', $relation->to_array(), $this->user_id);
            \Log::info('Contratos: relacion removida relation_id='.$relation_id.' contract_id='.(int) $contract->id);

            return $this->detail_response('Relacion removida.', (int) $contract->id);
        } catch (\Exception $e) {
            \Log::error('Contratos: error removiendo relacion: '.$e->getMessage());
            return $this->contract_response('No se pudo remover la relacion.', [], ['Error interno removiendo relacion.'], 400);
        }
    }

    protected function contracts()
    {
        $rows = \Model_Core_Contract::query()
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->limit(300)
            ->get();

        $contracts = [];
        foreach ($rows as $row) {
            $contracts[] = $this->format_contract($row);
        }

        return $contracts;
    }

    protected function contract_documents($contract_id = 0)
    {
        if (!\DBUtil::table_exists('core_documents') || !\DBUtil::table_exists('core_document_links')) {
            return [];
        }

        $query = \DB::select(
                ['l.id', 'link_id'],
                ['l.entity_id', 'contract_id'],
                ['l.relation_type', 'relation_type'],
                ['l.notes', 'link_notes'],
                ['d.id', 'document_id'],
                ['d.document_type', 'document_type'],
                ['d.title', 'title'],
                ['d.original_name', 'original_name'],
                ['d.file_extension', 'file_extension'],
                ['d.file_size', 'file_size'],
                ['d.visibility', 'visibility'],
                ['d.is_evidence', 'is_evidence'],
                ['d.created_at', 'created_at']
            )
            ->from(['core_document_links', 'l'])
            ->join(['core_documents', 'd'], 'inner')->on('d.id', '=', 'l.document_id')
            ->where('l.entity_type', '=', 'contract')
            ->where('l.active', '=', 1)
            ->where('d.active', '=', 1);

        if ((int) $contract_id > 0) {
            $query->where('l.entity_id', '=', (int) $contract_id);
        }

        $rows = $query->order_by('l.id', 'desc')->limit(500)->execute();
        $documents = [];
        foreach ($rows as $row) {
            $documents[] = [
                'link_id' => (int) $row['link_id'],
                'contract_id' => (int) $row['contract_id'],
                'document_id' => (int) $row['document_id'],
                'document_type' => (string) $row['document_type'],
                'relation_type' => (string) $row['relation_type'],
                'relation_label' => $this->document_relation_label((string) $row['relation_type']),
                'title' => (string) $row['title'],
                'original_name' => (string) $row['original_name'],
                'file_extension' => (string) $row['file_extension'],
                'file_size' => (int) $row['file_size'],
                'visibility' => (string) $row['visibility'],
                'is_evidence' => (int) $row['is_evidence'],
                'link_notes' => (string) $row['link_notes'],
                'created_at' => !empty($row['created_at']) ? date('d/m/Y H:i', (int) $row['created_at']) : '',
                'download_url' => \Uri::create('admin/contracts/download_document/'.(int) $row['document_id']),
            ];
        }

        return $documents;
    }

    protected function contract_relations($contract_id = 0)
    {
        $query = \Model_Core_Contract_Relation::query()
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->limit(500);

        if ((int) $contract_id > 0) {
            $query->where('contract_id', '=', (int) $contract_id);
        }

        $relations = [];
        foreach ($query->get() as $relation) {
            $entity_type = (string) $relation->related_entity_type;
            $relations[] = [
                'id' => (int) $relation->id,
                'contract_id' => (int) $relation->contract_id,
                'related_module' => (string) $relation->related_module,
                'related_entity_type' => $entity_type,
                'related_entity_label' => $this->relation_entity_label($entity_type),
                'related_entity_id' => (int) $relation->related_entity_id,
                'relation_type' => (string) $relation->relation_type,
                'notes' => (string) $relation->notes,
                'created_at' => $relation->created_at ? date('d/m/Y H:i', (int) $relation->created_at) : '',
            ];
        }

        return $relations;
    }

    protected function contract_events($contract_id = 0)
    {
        $query = \Model_Core_Contract_Event::query()
            ->order_by('id', 'desc')
            ->limit(500);

        if ((int) $contract_id > 0) {
            $query->where('contract_id', '=', (int) $contract_id);
        }

        $events = [];
        foreach ($query->get() as $event) {
            $events[] = [
                'id' => (int) $event->id,
                'contract_id' => (int) $event->contract_id,
                'event_type' => (string) $event->event_type,
                'old_status' => (string) $event->old_status,
                'new_status' => (string) $event->new_status,
                'message' => (string) $event->message,
                'created_by' => (int) $event->created_by,
                'created_at' => $event->created_at ? date('d/m/Y H:i', (int) $event->created_at) : '',
            ];
        }

        return $events;
    }

    protected function available_documents()
    {
        if (!\DBUtil::table_exists('core_documents')) {
            return [];
        }

        $rows = \Model_Core_Document::query()
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->limit(300)
            ->get();

        $documents = [];
        foreach ($rows as $document) {
            $documents[] = [
                'value' => (string) $document->id,
                'label' => '#'.(int) $document->id.' - '.((string) $document->title ?: (string) $document->original_name),
            ];
        }

        return $documents;
    }

    protected function detail_response($message, $contract_id)
    {
        return $this->contract_response($message, [
            'contracts' => $this->contracts(),
            'stats' => $this->stats(),
            'documents' => $this->contract_documents(),
            'relations' => $this->contract_relations(),
            'events' => $this->contract_events(),
            'available_documents' => $this->available_documents(),
            'selected' => [
                'contract_id' => (int) $contract_id,
                'documents' => $this->contract_documents((int) $contract_id),
                'relations' => $this->contract_relations((int) $contract_id),
                'events' => $this->contract_events((int) $contract_id),
            ],
        ]);
    }

    protected function format_contract(\Model_Core_Contract $contract)
    {
        $party = $contract->party ?: null;
        $expiration = $this->manager->calculate_expiration_status($contract);
        $expiration_days = $this->manager->calculate_expiration_days($contract);

        return [
            'id' => (int) $contract->id,
            'company_id' => (int) $contract->company_id,
            'contract_number' => (string) $contract->contract_number,
            'contract_type' => (string) $contract->contract_type,
            'contract_type_label' => $this->contract_type_label((string) $contract->contract_type),
            'party_id' => (int) $contract->party_id,
            'party_name' => $party ? (string) $party->name : '',
            'portal_code' => (string) $contract->portal_code,
            'title' => (string) $contract->title,
            'description' => (string) $contract->description,
            'start_date' => (string) $contract->start_date,
            'end_date' => (string) $contract->end_date,
            'renewal_type' => (string) $contract->renewal_type,
            'status' => (string) $contract->status,
            'status_label' => $this->status_label((string) $contract->status),
            'expiration_status' => $expiration,
            'expiration_label' => $this->expiration_label($expiration),
            'expiration_days' => $expiration_days,
            'expiration_days_label' => $this->expiration_days_label($expiration_days, $expiration),
            'responsible_user_id' => (int) $contract->responsible_user_id,
            'contract_value' => (float) $contract->contract_value,
            'currency_code' => (string) $contract->currency_code,
            'renewal_value' => (float) $contract->renewal_value,
            'renewal_currency_code' => (string) $contract->renewal_currency_code,
            'billing_type' => (string) $contract->billing_type,
            'response_hours' => (float) $contract->response_hours,
            'resolution_hours' => (float) $contract->resolution_hours,
            'visibility' => (string) $contract->visibility,
            'notes' => (string) $contract->notes,
            'active' => (int) $contract->active,
        ];
    }

    protected function stats()
    {
        $contracts = $this->contracts();
        $stats = [
            'active' => 0,
            'expiring_90' => 0,
            'expiring_60' => 0,
            'expiring_30' => 0,
            'expired' => 0,
            'no_end_date' => 0,
        ];

        foreach ($contracts as $contract) {
            if ($contract['expiration_status'] === 'active') {
                $stats['active']++;
            }
            if ($contract['expiration_status'] === 'expiring_90') {
                $stats['expiring_90']++;
            }
            if ($contract['expiration_status'] === 'expiring_60') {
                $stats['expiring_60']++;
            }
            if ($contract['expiration_status'] === 'expiring_30') {
                $stats['expiring_30']++;
            }
            if ($contract['expiration_status'] === 'expired') {
                $stats['expired']++;
            }
            if ($contract['expiration_status'] === 'no_end_date') {
                $stats['no_end_date']++;
            }
        }

        return $stats;
    }

    protected function options()
    {
        return [
            'contract_types' => $this->contract_type_options(),
            'contract_type_catalog_empty' => $this->contract_type_catalog_empty(),
            'parties' => $this->select_options('core_parties', 'id', 'name'),
            'users' => $this->user_options(),
            'currencies' => $this->currency_options(),
            'portal_codes' => [
                ['value' => 'admin', 'label' => 'Administracion'],
                ['value' => 'clientes', 'label' => 'Clientes'],
                ['value' => 'proveedores', 'label' => 'Proveedores'],
                ['value' => 'revendedores', 'label' => 'Revendedores'],
                ['value' => 'socios', 'label' => 'Socios'],
            ],
            'renewal_types' => [
                ['value' => 'none', 'label' => 'Sin renovacion'],
                ['value' => 'manual', 'label' => 'Manual'],
                ['value' => 'automatic', 'label' => 'Automatica'],
                ['value' => 'approval_required', 'label' => 'Requiere aprobacion'],
            ],
            'billing_types' => [
                ['value' => 'none', 'label' => 'Sin facturacion'],
                ['value' => 'one_time', 'label' => 'Unica'],
                ['value' => 'monthly', 'label' => 'Mensual'],
                ['value' => 'quarterly', 'label' => 'Trimestral'],
                ['value' => 'annual', 'label' => 'Anual'],
            ],
            'statuses' => $this->status_options(),
            'expiration_filters' => $this->expiration_filter_options(),
            'visibilities' => [
                ['value' => 'internal', 'label' => 'Interno'],
                ['value' => 'portal', 'label' => 'Visible en portal'],
                ['value' => 'private', 'label' => 'Privado / sensible'],
            ],
        ];
    }

    protected function relation_options()
    {
        return [
            'entity_types' => [
                ['value' => 'helpdesk_ticket', 'label' => 'Ticket helpdesk'],
                ['value' => 'sales_quote', 'label' => 'Cotizacion de venta'],
                ['value' => 'sales_order', 'label' => 'Pedido de venta'],
                ['value' => 'billing_invoice', 'label' => 'Factura'],
                ['value' => 'purchase_order', 'label' => 'Orden de compra'],
                ['value' => 'employee', 'label' => 'Empleado'],
                ['value' => 'crm_opportunity', 'label' => 'Oportunidad CRM'],
            ],
            'relation_types' => [
                ['value' => 'reference', 'label' => 'Referencia'],
                ['value' => 'origin', 'label' => 'Origen'],
                ['value' => 'supports', 'label' => 'Soporta'],
                ['value' => 'billed_by', 'label' => 'Facturado por'],
                ['value' => 'covered_by', 'label' => 'Cubierto por contrato'],
            ],
        ];
    }

    protected function permissions()
    {
        return [
            'create' => $this->is_super_admin || \Auth::has_access('contracts.access[create]'),
            'edit' => $this->is_super_admin || \Auth::has_access('contracts.access[edit]'),
            'status' => $this->is_super_admin || \Auth::has_access('contracts.access[status]'),
            'upload_document' => $this->is_super_admin || \Auth::has_access('contracts.access[upload_document]'),
            'link' => $this->is_super_admin || \Auth::has_access('contracts.access[link]'),
        ];
    }

    protected function can_access_contract($permission)
    {
        return $this->is_super_admin || \Auth::has_access($permission);
    }

    protected function contract_type_options()
    {
        $options = [];
        if (\DBUtil::table_exists('core_contract_types')) {
            $rows = \DB::select('code', 'name')
                ->from('core_contract_types')
                ->where('active', '=', 1)
                ->order_by('name', 'asc')
                ->execute();

            foreach ($rows as $row) {
                $options[] = ['value' => (string) $row['code'], 'label' => (string) $row['name']];
            }
        }

        if (!empty($options)) {
            return $options;
        }

        return [
            ['value' => 'customer', 'label' => 'Cliente'],
            ['value' => 'supplier', 'label' => 'Proveedor'],
            ['value' => 'employee', 'label' => 'Empleado'],
            ['value' => 'partner_reseller', 'label' => 'Socio / Revendedor'],
            ['value' => 'service_agreement', 'label' => 'Acuerdo de servicio'],
            ['value' => 'maintenance_contract', 'label' => 'Contrato de mantenimiento'],
            ['value' => 'rental_contract', 'label' => 'Contrato de renta'],
            ['value' => 'supplier_agreement', 'label' => 'Contrato proveedor'],
            ['value' => 'distribution_agreement', 'label' => 'Acuerdo de distribucion'],
            ['value' => 'employment_agreement', 'label' => 'Contrato laboral'],
            ['value' => 'confidentiality_agreement', 'label' => 'Acuerdo de confidencialidad'],
        ];
    }

    protected function contract_type_catalog_empty()
    {
        if (!\DBUtil::table_exists('core_contract_types')) {
            return true;
        }

        return (int) \DB::select()->from('core_contract_types')->where('active', '=', 1)->execute()->count() === 0;
    }

    protected function valid_contract_type($type)
    {
        $type = $this->codeify($type);
        if ($type === '') {
            return false;
        }

        if (\DBUtil::table_exists('core_contract_types')) {
            $count = (int) \DB::select()->from('core_contract_types')->where('active', '=', 1)->execute()->count();
            if ($count > 0) {
                return (bool) \DB::select('id')
                    ->from('core_contract_types')
                    ->where('code', '=', $type)
                    ->where('active', '=', 1)
                    ->limit(1)
                    ->execute()
                    ->current();
            }
        }

        $fallback = array_map(function ($option) {
            return $option['value'];
        }, $this->contract_type_options());

        return in_array($type, $fallback, true);
    }

    protected function select_options($table, $value_field, $label_field)
    {
        if (!\DBUtil::table_exists($table)) {
            return [];
        }

        $query = \DB::select($value_field, $label_field)->from($table);
        if (\DBUtil::field_exists($table, ['active'])) {
            $query->where('active', '=', 1);
        }
        $rows = $query->order_by($label_field, 'asc')->execute();

        $options = [];
        foreach ($rows as $row) {
            $options[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }

        return $options;
    }

    protected function user_options()
    {
        if (!\DBUtil::table_exists('users')) {
            return [];
        }

        $rows = \DB::select('id', 'username')->from('users')->order_by('username', 'asc')->execute();
        $options = [];
        foreach ($rows as $row) {
            $options[] = ['value' => (string) $row['id'], 'label' => (string) $row['username']];
        }
        return $options;
    }

    protected function record_exists($table, $id, $require_active = true)
    {
        if ($id < 1 || !\DBUtil::table_exists($table)) {
            return false;
        }

        $query = \DB::select('id')->from($table)->where('id', '=', (int) $id);
        if ($require_active && \DBUtil::field_exists($table, ['active'])) {
            $query->where('active', '=', 1);
        }

        return (bool) $query->limit(1)->execute()->current();
    }

    protected function currency_options()
    {
        $options = $this->select_options('core_catalog_currencies', 'code', 'name');
        return !empty($options) ? $options : [['value' => 'MXN', 'label' => 'MXN']];
    }

    protected function status_options()
    {
        $statuses = ['draft', 'pending_signature', 'active', 'renewal_pending', 'expired', 'terminated', 'cancelled', 'archived'];
        $options = [];
        foreach ($statuses as $status) {
            $options[] = ['value' => $status, 'label' => $this->status_label($status)];
        }
        return $options;
    }

    protected function expiration_filter_options()
    {
        return [
            ['value' => 'all', 'label' => 'Todos'],
            ['value' => 'no_end_date', 'label' => 'Sin vencimiento'],
            ['value' => 'active', 'label' => 'Vigentes'],
            ['value' => 'expiring_90', 'label' => 'Por vencer 90'],
            ['value' => 'expiring_60', 'label' => 'Por vencer 60'],
            ['value' => 'expiring_30', 'label' => 'Por vencer 30'],
            ['value' => 'expired', 'label' => 'Vencidos'],
        ];
    }

    protected function document_structure()
    {
        return [
            'entity_type' => 'contract',
            'document_types' => ['contract_pdf', 'contract_annex', 'contract_evidence', 'contract_signed'],
            'relation_types' => [
                ['value' => 'main_contract', 'label' => 'Contrato principal'],
                ['value' => 'annex', 'label' => 'Anexo'],
                ['value' => 'evidence', 'label' => 'Evidencia'],
                ['value' => 'signed_document', 'label' => 'Documento firmado'],
            ],
        ];
    }

    protected function current_company_id()
    {
        if (!\DBUtil::table_exists('core_companies')) {
            return 0;
        }

        $company = \Model_Core_Company::get_current();
        return $company && isset($company->id) ? (int) $company->id : 0;
    }

    protected function contract_by_id($contract_id)
    {
        $contract_id = (int) $contract_id;
        if ($contract_id < 1) {
            return null;
        }

        $contract = \Model_Core_Contract::find($contract_id);
        return $contract && (int) $contract->active === 1 ? $contract : null;
    }

    protected function document_exists($document_id)
    {
        $document_id = (int) $document_id;
        if ($document_id < 1 || !\DBUtil::table_exists('core_documents')) {
            return false;
        }

        return (bool) \DB::select('id')
            ->from('core_documents')
            ->where('id', '=', $document_id)
            ->where('active', '=', 1)
            ->limit(1)
            ->execute()
            ->current();
    }

    protected function document_link_exists($contract_id, $document_id)
    {
        return (bool) \DB::select('id')
            ->from('core_document_links')
            ->where('entity_type', '=', 'contract')
            ->where('entity_id', '=', (int) $contract_id)
            ->where('document_id', '=', (int) $document_id)
            ->where('active', '=', 1)
            ->limit(1)
            ->execute()
            ->current();
    }

    protected function contract_relation_exists($contract_id, $entity_type, $entity_id)
    {
        return (bool) \DB::select('id')
            ->from('core_contract_relations')
            ->where('contract_id', '=', (int) $contract_id)
            ->where('related_entity_type', '=', (string) $entity_type)
            ->where('related_entity_id', '=', (int) $entity_id)
            ->where('active', '=', 1)
            ->limit(1)
            ->execute()
            ->current();
    }

    protected function relation_target_exists($entity_type, $entity_id)
    {
        $map = $this->relation_table_map();
        if (!isset($map[$entity_type]) || !\DBUtil::table_exists($map[$entity_type])) {
            return false;
        }

        return $this->record_exists($map[$entity_type], (int) $entity_id);
    }

    protected function relation_table_map()
    {
        return [
            'helpdesk_ticket' => 'core_helpdesk_tickets',
            'sales_quote' => 'core_sales_quotes',
            'sales_order' => 'core_sales_orders',
            'billing_invoice' => 'core_billing_invoices',
            'purchase_order' => 'core_purchase_orders',
            'employee' => 'core_employees',
            'crm_opportunity' => 'core_crm_opportunities',
        ];
    }

    protected function relation_module($entity_type)
    {
        $modules = [
            'helpdesk_ticket' => 'helpdesk',
            'sales_quote' => 'sales',
            'sales_order' => 'sales',
            'billing_invoice' => 'billing',
            'purchase_order' => 'purchases',
            'employee' => 'hr',
            'crm_opportunity' => 'crm',
        ];

        return isset($modules[$entity_type]) ? $modules[$entity_type] : '';
    }

    protected function relation_entity_type($value)
    {
        $value = $this->codeify($value);
        return array_key_exists($value, $this->relation_table_map()) ? $value : '';
    }

    protected function relation_entity_label($entity_type)
    {
        $labels = [
            'helpdesk_ticket' => 'Ticket helpdesk',
            'sales_quote' => 'Cotizacion de venta',
            'sales_order' => 'Pedido de venta',
            'billing_invoice' => 'Factura',
            'purchase_order' => 'Orden de compra',
            'employee' => 'Empleado',
            'crm_opportunity' => 'Oportunidad CRM',
        ];

        return isset($labels[$entity_type]) ? $labels[$entity_type] : $entity_type;
    }

    protected function document_relation_type($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['main_contract', 'annex', 'evidence', 'signed_document'], true) ? $value : 'annex';
    }

    protected function document_type_from_relation($relation_type)
    {
        $map = [
            'main_contract' => 'contract_pdf',
            'annex' => 'contract_annex',
            'evidence' => 'contract_evidence',
            'signed_document' => 'contract_signed',
        ];

        return isset($map[$relation_type]) ? $map[$relation_type] : 'contract_annex';
    }

    protected function document_relation_label($relation_type)
    {
        $labels = [
            'main_contract' => 'Contrato principal',
            'annex' => 'Anexo',
            'evidence' => 'Evidencia',
            'signed_document' => 'Documento firmado',
        ];

        return isset($labels[$relation_type]) ? $labels[$relation_type] : $relation_type;
    }

    protected function contract_document_by_id($document_id)
    {
        if ((int) $document_id < 1) {
            return null;
        }

        return \DB::select(['d.id', 'id'], ['d.file_path', 'file_path'], ['d.original_name', 'original_name'], ['d.mime_type', 'mime_type'])
            ->from(['core_document_links', 'l'])
            ->join(['core_documents', 'd'], 'inner')->on('d.id', '=', 'l.document_id')
            ->join(['core_contracts', 'c'], 'inner')->on('c.id', '=', 'l.entity_id')
            ->where('d.id', '=', (int) $document_id)
            ->where('l.entity_type', '=', 'contract')
            ->where('l.active', '=', 1)
            ->where('d.active', '=', 1)
            ->where('c.active', '=', 1)
            ->limit(1)
            ->execute()
            ->current();
    }

    protected function download_document_file(array $document)
    {
        $relative = str_replace('\\', '/', ltrim((string) \Arr::get($document, 'file_path', ''), '/'));
        if ($relative === '' || strpos($relative, '..') !== false || preg_match('/^[a-z]+:/i', $relative)) {
            throw new \RuntimeException('Ruta de documento invalida.');
        }

        $absolute = DOCROOT.$relative;
        if (!is_file($absolute)) {
            throw new \RuntimeException('Archivo no encontrado.');
        }

        $filename = (string) \Arr::get($document, 'original_name', '');
        if ($filename === '') {
            $filename = basename($absolute);
        }

        $mime = (string) \Arr::get($document, 'mime_type', '');
        if ($mime === '') {
            $mime = 'application/octet-stream';
        }

        return \Response::forge(file_get_contents($absolute), 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.str_replace('"', '', $filename).'"',
            'Content-Length' => (string) filesize($absolute),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    protected function assert_schema_ready()
    {
        foreach (['core_contract_types', 'core_contracts', 'core_contract_relations', 'core_contract_events', 'core_documents', 'core_document_links'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar la migracion de contratos: '.$table);
            }
        }
    }

    protected function contract_response($message, array $data = [], array $errors = [], $status = 200)
    {
        return $this->json_response([
            'success' => empty($errors),
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], $status);
    }

    protected function exception_message(\Exception $e)
    {
        $message = trim((string) $e->getMessage());
        if ($message === '') {
            return 'Error interno sin detalle disponible.';
        }

        if (stripos($message, 'core_contract_types') !== false) {
            return 'Falta ejecutar o validar el catalogo de tipos de contrato.';
        }
        if (stripos($message, 'contracts.access') !== false) {
            return 'Falta asignar permisos del modulo de contratos.';
        }
        if (stripos($message, 'duplicate') !== false || stripos($message, '1062') !== false) {
            return 'Ya existe un contrato con el mismo folio. Intenta guardar nuevamente.';
        }

        return 'Detalle tecnico: '.$message;
    }

    protected function save_with_number_retry(\Model_Core_Contract $contract, $is_new)
    {
        $attempts = $is_new ? 3 : 1;
        $last_exception = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                if ($is_new && $attempt > 1) {
                    $contract->contract_number = $this->manager->next_contract_number();
                }
                $contract->save();
                return $contract;
            } catch (\Exception $e) {
                $last_exception = $e;
                if (!$is_new || !$this->manager->is_duplicate_number_error($e) || $attempt === $attempts) {
                    throw $e;
                }
                \Log::warning('Contratos: colision de folio '.$contract->contract_number.'. Reintentando intento '.$attempt.'.');
            }
        }

        throw $last_exception;
    }

    protected function protected_status_errors(\Model_Core_Contract $contract, array $new_data)
    {
        $status = (string) $contract->status;
        if (!in_array($status, ['active', 'terminated', 'cancelled', 'archived'], true)) {
            return [];
        }

        $critical_fields = [
            'contract_type' => 'tipo de contrato',
            'party_id' => 'tercero',
            'portal_code' => 'portal',
            'start_date' => 'fecha inicial',
            'end_date' => 'fecha final',
            'renewal_type' => 'tipo de renovacion',
            'contract_value' => 'valor',
            'currency_code' => 'moneda',
            'renewal_value' => 'valor de renovacion',
            'renewal_currency_code' => 'moneda de renovacion',
            'billing_type' => 'tipo de facturacion',
            'response_hours' => 'horas de respuesta',
            'resolution_hours' => 'horas de resolucion',
        ];

        $errors = [];
        foreach ($critical_fields as $field => $label) {
            if ($this->normalized_compare_value($contract->{$field}) !== $this->normalized_compare_value(\Arr::get($new_data, $field))) {
                $errors[] = 'No se puede modificar '.$label.' cuando el contrato esta '.$this->status_label($status).'.';
            }
        }

        return $errors;
    }

    protected function normalized_compare_value($value)
    {
        if (is_float($value) || is_int($value) || is_numeric($value)) {
            return number_format((float) $value, 4, '.', '');
        }

        return trim((string) $value);
    }

    protected function validated_date($value, $label)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return ['valid' => true, 'value' => null, 'error' => ''];
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return ['valid' => false, 'value' => null, 'error' => $label.' debe tener formato AAAA-MM-DD.'];
        }

        $parts = explode('-', $value);
        if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return ['valid' => false, 'value' => null, 'error' => $label.' no es una fecha valida.'];
        }

        return ['valid' => true, 'value' => $value, 'error' => ''];
    }

    protected function sanitize_rich_html($html)
    {
        $html = preg_replace('#<(script|iframe|object|embed|style)[^>]*>.*?</\1>#is', '', (string) $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html);
        $html = preg_replace('/javascript\s*:/is', '', $html);

        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4><h5><blockquote><code><pre><a><hr><table><thead><tbody><tr><th><td>');
    }

    protected function date_or_null($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $parts = explode('-', $value);
        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]) ? $value : null;
    }

    protected function codeify($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }

    protected function currency_code($value)
    {
        $value = strtoupper(trim((string) $value));
        return preg_match('/^[A-Z]{3}$/', $value) ? $value : 'MXN';
    }

    protected function renewal_type($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['none', 'manual', 'automatic', 'approval_required'], true) ? $value : 'none';
    }

    protected function billing_type($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['none', 'one_time', 'monthly', 'quarterly', 'annual'], true) ? $value : 'none';
    }

    protected function visibility($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['internal', 'portal', 'private'], true) ? $value : 'internal';
    }

    protected function valid_status($value)
    {
        return in_array($value, ['draft', 'pending_signature', 'active', 'renewal_pending', 'expired', 'terminated', 'cancelled', 'archived'], true);
    }

    protected function status_label($status)
    {
        $labels = [
            'draft' => 'Borrador',
            'pending_signature' => 'Pendiente de firma',
            'active' => 'Activo',
            'renewal_pending' => 'Renovación pendiente',
            'expired' => 'Vencido',
            'terminated' => 'Terminado',
            'cancelled' => 'Cancelado',
            'archived' => 'Archivado',
        ];

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    protected function contract_type_label($type)
    {
        foreach ($this->contract_type_options() as $option) {
            if ($option['value'] === $type) {
                return $option['label'];
            }
        }

        return $type;
    }

    protected function expiration_label($status)
    {
        $labels = [
            'no_end_date' => 'Sin vencimiento',
            'inactive' => 'Inactivo',
            'expired' => 'Vencido',
            'expiring_30' => 'Vence en 30 dias',
            'expiring_60' => 'Vence en 60 dias',
            'expiring_90' => 'Vence en 90 dias',
            'active' => 'Vigente',
        ];

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    protected function expiration_days_label($days, $status)
    {
        if ($status === 'no_end_date') {
            return 'Sin fecha final';
        }
        if ($status === 'inactive') {
            return 'Contrato inactivo';
        }
        if ($days === null) {
            return '';
        }
        if ((int) $days < 0) {
            return 'Vencido hace '.abs((int) $days).' dias';
        }
        if ((int) $days === 0) {
            return 'Vence hoy';
        }

        return 'Faltan '.(int) $days.' dias';
    }
}
