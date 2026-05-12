<?php

/**
 * CONTROLADOR ADMIN_DOCUMENTS
 *
 * Administra documentos y evidencias transversales del ERP.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Documents extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE DOCUMENTOS
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('documents.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL DE DOCUMENTOS Y EVIDENCIAS
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Documentos';
        $this->template->content = View::forge('admin/documents/index');
    }

    /**
     * DATA
     *
     * ENTREGA DOCUMENTOS, VINCULOS Y OPCIONES EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE REGRESA INFORMACION PARA VUE
            return $this->json_response([
                'documents' => $this->get_documents(),
                'links' => $this->get_links(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando documentos: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar documentos.'], 500);
        }
    }

    /**
     * UPLOAD
     *
     * SUBE UN DOCUMENTO Y CREA SU VINCULO OPCIONAL
     *
     * @access  public
     * @return  Response
     */
    public function post_upload()
    {
        # VALIDAR PERMISO PARA CREAR
        $this->require_access('documents.access[create]');

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE OBTIENE EL ARCHIVO
            $file = \Input::file('file');
            if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return $this->json_response(['error' => 'Selecciona un archivo valido.'], 422);
            }

            # SE VALIDAN DATOS BASICOS DEL ARCHIVO
            $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
            $allowed = ['pdf', 'xml', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];
            if (!in_array($extension, $allowed)) {
                return $this->json_response(['error' => 'Tipo de archivo no permitido.'], 422);
            }

            if ((int) \Arr::get($file, 'size', 0) > 15728640) {
                return $this->json_response(['error' => 'El archivo no puede superar 15 MB.'], 422);
            }

            # SE PREPARA DESTINO PUBLICO CONTROLADO
            $document_type = $this->codeify(\Input::post('document_type', 'general'));
            $relative_dir = 'assets/uploads/documents/'.$document_type.'/'.date('Y').'/'.date('m');
            $absolute_dir = DOCROOT.$relative_dir;
            if (!is_dir($absolute_dir)) {
                mkdir($absolute_dir, 0755, true);
            }

            # SE GENERA NOMBRE SEGURO
            $base_name = pathinfo((string) \Arr::get($file, 'name', 'documento'), PATHINFO_FILENAME);
            $filename = time().'_'.\Str::random('alnum', 12).'_'.$this->codeify($base_name).'.'.$extension;
            $target = $absolute_dir.DS.$filename;

            if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
                return $this->json_response(['error' => 'No se pudo guardar el archivo.'], 400);
            }

            # SE CREA EL DOCUMENTO
            $path = str_replace('\\', '/', $relative_dir.'/'.$filename);
            $document = Model_Core_Document::forge([
                'document_type' => $document_type,
                'title' => trim((string) \Input::post('title', '')) ?: $base_name,
                'description' => trim((string) \Input::post('description', '')),
                'file_path' => $path,
                'original_name' => (string) \Arr::get($file, 'name', ''),
                'mime_type' => (string) \Arr::get($file, 'type', ''),
                'file_extension' => $extension,
                'file_size' => (int) \Arr::get($file, 'size', 0),
                'checksum' => is_file($target) ? hash_file('sha256', $target) : '',
                'visibility' => $this->codeify(\Input::post('visibility', 'internal')),
                'is_evidence' => (int) (bool) \Input::post('is_evidence', false),
                'uploaded_by' => $this->user_id,
                'active' => 1,
            ]);
            $document->save();

            # SE CREA VINCULO OPCIONAL
            $entity_type = $this->codeify(\Input::post('entity_type', ''));
            $entity_id = (int) \Input::post('entity_id', 0);
            if ($entity_type !== '' && $entity_id > 0) {
                Model_Core_Document_Link::forge([
                    'document_id' => (int) $document->id,
                    'entity_type' => $entity_type,
                    'entity_id' => $entity_id,
                    'relation_type' => $this->codeify(\Input::post('relation_type', 'attachment')),
                    'notes' => trim((string) \Input::post('link_notes', '')),
                    'created_by' => $this->user_id,
                    'active' => 1,
                ])->save();
            }

            # SE REGRESA ESTADO ACTUALIZADO
            return $this->json_response([
                'status' => 'ok',
                'documents' => $this->get_documents(),
                'links' => $this->get_links(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error subiendo documento: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo subir el documento.'], 400);
        }
    }

    /**
     * LINK
     *
     * CREA UN VINCULO ENTRE DOCUMENTO Y ENTIDAD DEL ERP
     *
     * @access  public
     * @return  Response
     */
    public function post_link()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('documents.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDAN DATOS MINIMOS
            $document_id = (int) \Arr::get($val, 'document_id', 0);
            $entity_type = $this->codeify(\Arr::get($val, 'entity_type', ''));
            $entity_id = (int) \Arr::get($val, 'entity_id', 0);

            if ($document_id < 1 || $entity_type === '' || $entity_id < 1) {
                return $this->json_response(['error' => 'Documento, entidad y registro son obligatorios.'], 422);
            }

            # SE CREA VINCULO
            Model_Core_Document_Link::forge([
                'document_id' => $document_id,
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'relation_type' => $this->codeify(\Arr::get($val, 'relation_type', 'attachment')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'created_by' => $this->user_id,
                'active' => 1,
            ])->save();

            return $this->json_response(['status' => 'ok', 'links' => $this->get_links(), 'stats' => $this->get_stats()]);
        } catch (\Exception $e) {
            \Log::error('Error vinculando documento: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo vincular el documento.'], 400);
        }
    }

    /**
     * GET DOCUMENTS
     *
     * FORMATEA DOCUMENTOS PARA EL PANEL ADMIN
     *
     * @access  protected
     * @return  Array
     */
    protected function get_documents()
    {
        $rows = Model_Core_Document::query()
            ->order_by('id', 'desc')
            ->limit(200)
            ->get();

        $documents = [];
        foreach ($rows as $row) {
            $documents[] = [
                'id' => (int) $row->id,
                'document_type' => (string) $row->document_type,
                'title' => (string) $row->title,
                'description' => (string) $row->description,
                'file_path' => (string) $row->file_path,
                'original_name' => (string) $row->original_name,
                'mime_type' => (string) $row->mime_type,
                'file_extension' => (string) $row->file_extension,
                'file_size' => (int) $row->file_size,
                'visibility' => (string) $row->visibility,
                'is_evidence' => (int) $row->is_evidence,
                'active' => (int) $row->active,
                'created_at' => $row->created_at ? date('d/m/Y H:i', $row->created_at) : '',
            ];
        }

        return $documents;
    }

    /**
     * GET LINKS
     *
     * FORMATEA VINCULOS DOCUMENTALES
     *
     * @access  protected
     * @return  Array
     */
    protected function get_links()
    {
        $rows = Model_Core_Document_Link::query()
            ->order_by('id', 'desc')
            ->limit(200)
            ->get();

        $links = [];
        foreach ($rows as $row) {
            $links[] = $row->to_array();
        }

        return $links;
    }

    /**
     * GET OPTIONS
     *
     * OBTIENE OPCIONES BASE PARA VINCULAR DOCUMENTOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_options()
    {
        return [
            'parties' => $this->select_options('core_parties', 'id', 'name'),
            'entity_types' => [
                ['value' => 'party', 'label' => 'Tercero'],
                ['value' => 'ticket', 'label' => 'Ticket'],
                ['value' => 'invoice', 'label' => 'Factura'],
                ['value' => 'quote', 'label' => 'Cotizacion'],
                ['value' => 'order', 'label' => 'Orden'],
            ],
        ];
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES BASE
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        return [
            'documents' => (int) \DB::count_records('core_documents'),
            'evidence' => (int) \DB::select()->from('core_documents')->where('is_evidence', 1)->execute()->count(),
            'links' => (int) \DB::count_records('core_document_links'),
        ];
    }

    /**
     * SELECT OPTIONS
     *
     * FORMATEA OPCIONES PARA SELECTS
     *
     * @access  protected
     * @return  Array
     */
    protected function select_options($table, $value_field, $label_field)
    {
        $rows = \DB::select($value_field, $label_field)
            ->from($table)
            ->where('active', '=', 1)
            ->order_by($label_field, 'asc')
            ->execute();

        $options = [];
        foreach ($rows as $row) {
            $options[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }

        return $options;
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LAS TABLAS DOCUMENTALES EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        foreach (['core_documents', 'core_document_links'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de documentos.');
            }
        }
    }

    /**
     * CODEIFY
     *
     * NORMALIZA CODIGOS INTERNOS
     *
     * @access  protected
     * @return  String
     */
    protected function codeify($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}
