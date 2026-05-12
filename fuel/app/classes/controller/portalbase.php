<?php

/**
 * CONTROLADOR PORTALBASE
 *
 * Base comun para portales externos vinculados a terceros.
 *
 * @package  app
 * @extends  Controller_Template
 */
class Controller_Portalbase extends Controller_Template
{
    public $template = 'portal/template';
    protected $portal_code = '';
    protected $user_id = 0;
    protected $portal_link = null;
    protected $party = null;
    protected $branding = null;

    /**
     * BEFORE
     *
     * VALIDA SESION Y VINCULO USUARIO-TERCERO-PORTAL
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING
        parent::before();

        # VALIDAR SESION
        if (!\Auth::check()) {
            \Response::redirect($this->portal_code.'/login');
        }

        # SE OBTIENE USUARIO ACTUAL
        $user_id_data = \Auth::get_user_id();
        $this->user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;

        # SE VALIDA PORTAL CONFIGURADO
        if ($this->portal_code === '') {
            throw new \HttpNotFoundException;
        }

        # SE OBTIENE VINCULO ACTIVO
        $this->portal_link = Model_Core_Party_User_Link::query()
            ->where('user_id', $this->user_id)
            ->where('portal_code', $this->portal_code)
            ->where('active', 1)
            ->get_one();

        if (!$this->portal_link) {
            \Response::redirect($this->portal_code.'/login');
        }

        # SE OBTIENE TERCERO Y BRANDING
        $this->party = Model_Core_Party::find((int) $this->portal_link->party_id);
        $this->branding = Model_Core_Party_Branding::query()
            ->where('party_id', (int) $this->portal_link->party_id)
            ->where('portal_code', $this->portal_code)
            ->where('active', 1)
            ->get_one();

        # SE ASIGNAN VARIABLES GLOBALES AL TEMPLATE
        $this->template->portal_code = $this->portal_code;
        $this->template->portal_name = $this->portal_title();
        $this->template->party = $this->party;
        $this->template->branding = $this->branding;
        $this->template->user_name = \Auth::get_screen_name();
    }

    /**
     * PORTAL TITLE
     *
     * OBTIENE EL NOMBRE CONFIGURADO DEL PORTAL
     *
     * @access  protected
     * @return  String
     */
    protected function portal_title()
    {
        $profile = Model_Core_Portal_Profile::query()
            ->where('code', $this->portal_code)
            ->where('active', 1)
            ->get_one();

        return $profile ? (string) $profile->name : ucfirst($this->portal_code);
    }
}
