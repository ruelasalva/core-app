<?php

/**
 * CONTROLADOR ADMIN_HELP
 *
 * Administra y muestra manuales, guias y conocimiento operativo del sistema.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Help extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE AYUDA
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('help.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL CENTRO DE AYUDA Y CONOCIMIENTO
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Ayuda';
        $this->template->content = View::forge('admin/help/index', [
            'can_edit' => $this->is_super_admin || \Auth::has_access('help.access[edit]'),
        ]);
    }

    /**
     * DATA
     *
     * ENTREGA LOS MANUALES DE LA BASE DE CONOCIMIENTO EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE CONSULTAN TODOS LOS ARTICULOS PARA ADMINISTRACION
            $articles = Model_Core_Knowledge_Article::query()
                ->order_by('category', 'asc')
                ->order_by('sort_order', 'asc')
                ->order_by('title', 'asc')
                ->get();

            # SE REGRESA INFORMACION NORMALIZADA PARA VUE
            return $this->json_response([
                'articles' => array_values(array_map([$this, 'format_article'], $articles)),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando ayuda: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar la ayuda.'], 500);
        }
    }

    /**
     * SAVE
     *
     * CREA O ACTUALIZA UN MANUAL DE LA BASE DE CONOCIMIENTO
     *
     * @access  public
     * @return  Response
     */
    public function post_save()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('help.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE PREPARAN VARIABLES PRINCIPALES
            $title = trim((string) \Arr::get($val, 'title', ''));
            $category = trim((string) \Arr::get($val, 'category', 'General'));
            $content = trim((string) \Arr::get($val, 'content', ''));

            # VALIDACIONES MINIMAS
            if ($title === '' || $category === '' || $content === '') {
                return $this->json_response(['error' => 'Titulo, categoria y contenido son obligatorios.'], 422);
            }

            # SE PREPARA DATA DEL MODELO
            $data = [
                'code' => $this->unique_code(\Arr::get($val, 'code', ''), $title, (int) \Arr::get($val, 'id', 0)),
                'title' => $title,
                'category' => $category,
                'summary' => trim((string) \Arr::get($val, 'summary', '')),
                'content' => $this->sanitize_rich_html($content),
                'sort_order' => (int) \Arr::get($val, 'sort_order', 0),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            # SE BUSCA EL REGISTRO EXISTENTE O SE CREA UNO NUEVO
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $article = Model_Core_Knowledge_Article::find($id);
                if (!$article) {
                    return $this->json_response(['error' => 'Manual no encontrado.'], 404);
                }
                $article->set($data);
            } else {
                $article = Model_Core_Knowledge_Article::forge($data);
            }

            # SE GUARDA EL MANUAL
            $article->save();

            # SE REGRESA ESTADO ACTUALIZADO
            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error guardando ayuda: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el manual.'], 400);
        }
    }

    /**
     * DELETE
     *
     * DESACTIVA UN MANUAL DE LA BASE DE CONOCIMIENTO
     *
     * @access  public
     * @return  Response
     */
    public function post_delete()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('help.access[edit]');

        try {
            # SE OBTIENE EL ID DEL MANUAL
            $id = (int) \Arr::get((array) \Input::json(), 'id', 0);
            $article = Model_Core_Knowledge_Article::find($id);
            if (!$article) {
                return $this->json_response(['error' => 'Manual no encontrado.'], 404);
            }

            # SE DESACTIVA PARA CONSERVAR HISTORIAL
            $article->active = 0;
            $article->save();

            # SE REGRESA ESTADO ACTUALIZADO
            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error desactivando ayuda: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo desactivar el manual.'], 400);
        }
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LA MIGRACION DE AYUDA ESTE EJECUTADA
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        if (!\DBUtil::table_exists('core_knowledge_articles')) {
            throw new \RuntimeException('Falta ejecutar migraciones de ayuda.');
        }
    }

    /**
     * FORMAT ARTICLE
     *
     * NORMALIZA UN MANUAL PARA EL FRONTEND ADMIN
     *
     * @access  protected
     * @return  Array
     */
    protected function format_article($article)
    {
        return [
            'id' => (int) $article->id,
            'code' => (string) $article->code,
            'title' => (string) $article->title,
            'category' => (string) $article->category,
            'summary' => (string) $article->summary,
            'content' => (string) $article->content,
            'sort_order' => (int) $article->sort_order,
            'active' => (int) $article->active,
        ];
    }

    /**
     * UNIQUE CODE
     *
     * GENERA UN CODIGO UNICO PARA IDENTIFICAR EL MANUAL
     *
     * @access  protected
     * @return  String
     */
    protected function unique_code($code, $title, $current_id = 0)
    {
        # SE NORMALIZA EL CODIGO BASE
        $base = $this->slugify($code ?: $title);
        if ($base === '') {
            $base = 'manual';
        }

        # SE VALIDA SI YA EXISTE EN OTRO REGISTRO
        $candidate = $base;
        $counter = 2;
        while (true) {
            $query = \DB::select('id')
                ->from('core_knowledge_articles')
                ->where('code', '=', $candidate);

            if ($current_id > 0) {
                $query->where('id', '!=', $current_id);
            }

            if (!$query->execute()->current()) {
                return $candidate;
            }

            $candidate = $base.'-'.$counter;
            $counter++;
        }
    }

    /**
     * SANITIZE RICH HTML
     *
     * PERMITE FORMATO SEGURO PARA MANUALES SIN ACEPTAR SCRIPTS
     *
     * @access  protected
     * @return  String
     */
    protected function sanitize_rich_html($html)
    {
        # SE ELIMINAN BLOQUES PELIGROSOS
        $html = preg_replace('#<(script|iframe|object|embed|style)[^>]*>.*?</\1>#is', '', (string) $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html);
        $html = preg_replace('/javascript\s*:/is', '', $html);

        # SE PERMITE SOLO HTML NECESARIO PARA MANUALES
        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4><h5><blockquote><code><pre><a><hr><table><thead><tbody><tr><th><td>');
    }

    /**
     * SLUGIFY
     *
     * GENERA IDENTIFICADORES LEGIBLES Y SEGUROS
     *
     * @access  protected
     * @return  String
     */
    protected function slugify($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }
}
