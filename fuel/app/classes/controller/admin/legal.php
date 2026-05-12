<?php

/**
 * CONTROLADOR ADMIN_LEGAL
 *
 * Administra documentos legales, consentimientos y preferencias de cookies.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Legal extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA LEGAL
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('legal.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA LA PANTALLA PRINCIPAL DEL MODULO LEGAL
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Legal';
        $this->template->content = View::forge('admin/legal/index');
    }

    /**
     * DATA
     *
     * ENTREGA DOCUMENTOS Y ESTADISTICAS DE COOKIES EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE REGRESA LA INFORMACION PARA VUE
            return $this->json_response([
                'documents' => $this->get_documents(),
                'cookie_preferences' => $this->get_cookie_preferences(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando legal: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar el modulo legal.'], 500);
        }
    }

    /**
     * SAVE DOCUMENT
     *
     * CREA O ACTUALIZA UN DOCUMENTO LEGAL
     *
     * @access  public
     * @return  Response
     */
    public function post_save_document()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('legal.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE INICIALIZAN VARIABLES PRINCIPALES
            $title = trim((string) \Arr::get($val, 'title', ''));
            $shortcode = trim((string) \Arr::get($val, 'shortcode', ''));

            # VALIDACIONES MINIMAS
            if ($title === '' || $shortcode === '') {
                return $this->json_response(['error' => 'Titulo y shortcode son obligatorios.'], 422);
            }

            # SE PREPARAN LOS DATOS DEL MODELO
            $data = [
                'category' => trim((string) \Arr::get($val, 'category', 'general')),
                'document_type' => trim((string) \Arr::get($val, 'document_type', 'otros')),
                'shortcode' => $this->slugify($shortcode),
                'title' => $title,
                'content' => trim((string) \Arr::get($val, 'content', '')),
                'version' => trim((string) \Arr::get($val, 'version', '1.0')),
                'required' => (int) (bool) \Arr::get($val, 'required', false),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
                'allow_download' => (int) (bool) \Arr::get($val, 'allow_download', false),
            ];

            # SE BUSCA EL REGISTRO EXISTENTE O SE CREA UNO NUEVO
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $document = Model_Core_Legal_Document::find($id);
                if (!$document) {
                    return $this->json_response(['error' => 'Documento no encontrado.'], 404);
                }
                $document->set($data);
            } else {
                $document = Model_Core_Legal_Document::forge($data);
            }

            # SE GUARDA EL DOCUMENTO
            $document->save();

            # SE REGRESA LA LISTA ACTUALIZADA
            return $this->json_response(['status' => 'ok', 'documents' => $this->get_documents()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando documento legal: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el documento.'], 400);
        }
    }

    /**
     * GET DOCUMENTS
     *
     * FORMATEA DOCUMENTOS LEGALES PARA LA VISTA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_documents()
    {
        # SE INICIALIZA EL ARREGLO DE RESPUESTA
        $items = [];

        # SE RECORREN LOS DOCUMENTOS
        foreach (Model_Core_Legal_Document::list_for_admin() as $document) {
            $items[] = [
                'id' => (int) $document->id,
                'category' => (string) $document->category,
                'document_type' => (string) $document->document_type,
                'shortcode' => (string) $document->shortcode,
                'title' => (string) $document->title,
                'content' => (string) $document->content,
                'version' => (string) $document->version,
                'required' => (int) $document->required,
                'active' => (int) $document->active,
                'allow_download' => (int) $document->allow_download,
            ];
        }

        return $items;
    }

    /**
     * GET COOKIE PREFERENCES
     *
     * OBTIENE LAS ULTIMAS PREFERENCIAS DE COOKIES
     *
     * @access  protected
     * @return  Array
     */
    protected function get_cookie_preferences()
    {
        # SE CONSULTAN LOS ULTIMOS REGISTROS
        $rows = Model_Core_Web_Cookie_Preference::query()
            ->order_by('updated_at', 'desc')
            ->limit(50)
            ->get();

        # SE FORMATEA LA RESPUESTA
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row->id,
                'user_id' => $row->user_id ? (int) $row->user_id : null,
                'token' => (string) $row->token,
                'analytics' => (int) $row->analytics,
                'marketing' => (int) $row->marketing,
                'personalization' => (int) $row->personalization,
                'ip_address' => (string) $row->ip_address,
                'updated_at' => $row->updated_at ? date('d/m/Y H:i', $row->updated_at) : '',
            ];
        }

        return $items;
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES LEGALES BASICOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        # SE REGRESAN CONTADORES AGREGADOS
        return [
            'documents' => (int) \DB::count_records('core_legal_documents'),
            'consents' => (int) \DB::count_records('core_user_consents'),
            'cookie_preferences' => (int) \DB::count_records('core_web_cookie_preferences'),
            'analytics' => (int) \DB::select()->from('core_web_cookie_preferences')->where('analytics', '=', 1)->execute()->count(),
            'marketing' => (int) \DB::select()->from('core_web_cookie_preferences')->where('marketing', '=', 1)->execute()->count(),
        ];
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LAS TABLAS LEGALES EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        # SE VERIFICA CADA TABLA REQUERIDA
        foreach (['core_legal_documents', 'core_user_consents', 'core_web_cookie_preferences'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones legales.');
            }
        }
    }

    /**
     * SLUGIFY
     *
     * NORMALIZA SHORTCODES LEGALES
     *
     * @access  protected
     * @return  String
     */
    protected function slugify($value)
    {
        # SE NORMALIZA EL VALOR RECIBIDO
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}
