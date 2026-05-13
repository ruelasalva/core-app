<?php

/**
 * CONTROLADOR ACCOUNT
 *
 * Maneja registro, acceso y cuenta basica de clientes del frontend publico.
 *
 * @package  app
 * @extends  Controller_Template
 */
class Controller_Account extends Controller_Template
{
    /**
     * Plantilla publica del sitio.
     *
     * @var string
     */
    public $template = 'frontend/template';

    /**
     * BEFORE
     *
     * PREPARA DATOS COMUNES DEL FRONTEND PARA LAS VISTAS DE CUENTA
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING
        parent::before();

        # SE ASIGNAN VARIABLES GLOBALES DEL FRONTEND
        $this->prepare_template('Mi cuenta', 'Acceso de clientes.');
    }

    /**
     * LOGIN
     *
     * AUTENTICA CLIENTES DEL FRONTEND PUBLICO
     *
     * @access  public
     * @return  Void|View
     */
    public function action_login()
    {
        # SI YA HAY SESION DE CLIENTE, SE REDIRECCIONA A MI CUENTA
        if (\Auth::check() && $this->customer_link()) {
            \Response::redirect('mi-cuenta');
        }

        # SE PREPARAN DATOS
        $data = ['error' => \Session::get_flash('error'), 'success' => \Session::get_flash('success')];

        # SE PROCESA FORMULARIO
        if (\Input::method() === 'POST') {
            $email = trim((string) \Input::post('email', ''));
            $password = (string) \Input::post('password', '');

            if (\Auth::login($email, $password) && $this->customer_link()) {
                # SE MIGRAN CONSENTIMIENTOS ANONIMOS A USUARIO
                $user_id = $this->current_user_id();
                if (class_exists('Helper_Core_Legal')) {
                    \Helper_Core_Legal::migrate_anonymous_to_user($user_id);
                }

                \Log::info('LOGIN CLIENTE WEB: '.$email.' desde '.\Input::ip());
                \Response::redirect('mi-cuenta');
            }

            \Auth::logout();
            \Log::warning('FALLO LOGIN CLIENTE WEB: '.$email);
            $data['error'] = 'Correo o contrasena incorrectos.';
        }

        # SE CARGA VISTA
        $this->template->title = 'Acceso clientes';
        $this->template->content = \View::forge('account/login', $data);
    }

    /**
     * REGISTER
     *
     * CREA CLIENTE CON MENOR PRIVILEGIO Y ACCESO AL PORTAL CLIENTES
     *
     * @access  public
     * @return  Void|View
     */
    public function action_register()
    {
        # SI YA HAY SESION DE CLIENTE, SE REDIRECCIONA A MI CUENTA
        if (\Auth::check() && $this->customer_link()) {
            \Response::redirect('mi-cuenta');
        }

        # SE PREPARAN DATOS
        $data = ['error' => null];

        # SE PROCESA FORMULARIO
        if (\Input::method() === 'POST') {
            try {
                # SE VALIDA CAPTCHA SOLO SI ESTA CONFIGURADO EN WEB
                $this->verify_public_captcha();

                # SE VALIDA Y CREA CLIENTE
                $payload = $this->registration_payload();
                $user_id = $this->create_customer_user($payload);
                $party = $this->create_customer_party($payload);
                $this->create_customer_link($user_id, (int) $party->id);

                # SE AUDITA EL REGISTRO PUBLICO
                if (class_exists('Helper_Core_Audit')) {
                    Helper_Core_Audit::log([
                        'module' => 'account',
                        'action' => 'register_customer',
                        'business_event' => 'account.register_customer',
                        'entity_type' => 'party',
                        'entity_id' => (int) $party->id,
                        'table_name' => 'core_parties',
                        'summary' => 'Registro cliente web '.$party->name,
                        'new_values' => $party->to_array(),
                    ]);
                }

                \Session::set_flash('success', 'Tu cuenta fue creada. Ingresa para ver precios y continuar.');
                \Response::redirect('acceso');
            } catch (\InvalidArgumentException $e) {
                $data['error'] = $e->getMessage();
            } catch (\Exception $e) {
                \Log::error('Error registrando cliente web: '.$e->getMessage());
                $data['error'] = 'No se pudo crear la cuenta. Intenta nuevamente.';
            }
        }

        # SE CARGA VISTA
        $this->template->title = 'Registro clientes';
        $this->template->content = \View::forge('account/register', array_merge($data, [
            'captcha_html' => class_exists('Helper_Core_Web') ? Helper_Core_Web::render_captcha() : '',
        ]), false);
        $this->template->set('frontend_extra_scripts', class_exists('Helper_Core_Web') ? Helper_Core_Web::captcha_script() : '', false);
    }

    /**
     * INDEX
     *
     * MUESTRA RESUMEN BASICO DE LA CUENTA DEL CLIENTE
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE VALIDA SESION DE CLIENTE
        $link = $this->require_customer();
        $party = Model_Core_Party::find((int) $link->party_id);

        # SE CARGA VISTA
        $this->template->title = 'Mi cuenta';
        $this->template->content = \View::forge('account/index', [
            'party' => $party,
            'link' => $link,
            'price_list' => $this->price_list_name($party ? (int) $party->price_list_id : 0),
            'quotes' => $this->customer_quotes($party ? (int) $party->id : 0),
        ]);
    }

    /**
     * LOGOUT
     *
     * CIERRA SESION DEL CLIENTE WEB
     *
     * @access  public
     * @return  Void
     */
    public function action_logout()
    {
        # SE CIERRA SESION Y REGRESA AL FRONTEND
        \Auth::logout();
        \Response::redirect(\Uri::base(false));
    }

    /**
     * REGISTRATION PAYLOAD
     *
     * VALIDA DATOS DE REGISTRO PUBLICO
     *
     * @access  protected
     * @return  Array
     */
    protected function registration_payload()
    {
        # SE NORMALIZAN ENTRADAS
        $name = trim((string) \Input::post('name', ''));
        $email = strtolower(trim((string) \Input::post('email', '')));
        $phone = trim((string) \Input::post('phone', ''));
        $password = (string) \Input::post('password', '');
        $confirm = (string) \Input::post('password_confirm', '');

        # VALIDACIONES BASICAS
        if ($name === '' || $email === '' || $password === '') {
            throw new \InvalidArgumentException('Nombre, correo y contrasena son obligatorios.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Captura un correo valido.');
        }

        if (strlen($password) < 10) {
            throw new \InvalidArgumentException('La contrasena debe tener al menos 10 caracteres.');
        }

        if ($password !== $confirm) {
            throw new \InvalidArgumentException('La confirmacion de contrasena no coincide.');
        }

        if ($this->email_exists($email)) {
            throw new \InvalidArgumentException('Ya existe una cuenta con ese correo.');
        }

        return [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
        ];
    }

    /**
     * VERIFY PUBLIC CAPTCHA
     *
     * VALIDA RECAPTCHA CUANDO EL MODULO WEB LO TIENE CONFIGURADO.
     *
     * @access  protected
     * @return  Void
     */
    protected function verify_public_captcha()
    {
        # SI NO EXISTE HELPER O CAPTCHA NO ESTA ACTIVO, NO SE BLOQUEA
        if (!class_exists('Helper_Core_Web') || !Helper_Core_Web::captcha_enabled()) {
            return;
        }

        # SE VALIDA RESPUESTA DEL FORMULARIO
        $token = (string) \Input::post('g-recaptcha-response', '');
        if (!Helper_Core_Web::verify_captcha($token)) {
            throw new \InvalidArgumentException('No se pudo validar el captcha. Intenta nuevamente.');
        }
    }

    /**
     * CREATE CUSTOMER USER
     *
     * CREA USUARIO ORM AUTH CON GRUPO DE PORTAL EXTERNO
     *
     * @access  protected
     * @return  Int
     */
    protected function create_customer_user(array $payload)
    {
        # GRUPO 15 = PORTAL EXTERNO
        return (int) \Auth::create_user(
            $payload['email'],
            $payload['password'],
            $payload['email'],
            15,
            ['full_name' => $payload['name']]
        );
    }

    /**
     * CREATE CUSTOMER PARTY
     *
     * CREA EL TERCERO CLIENTE CON LISTA DE PRECIO DEFAULT
     *
     * @access  protected
     * @return  Model_Core_Party
     */
    protected function create_customer_party(array $payload)
    {
        # SE CREA TERCERO CLIENTE
        $party = Model_Core_Party::forge([
            'party_type' => 'customer',
            'code' => $this->unique_party_code($payload['email']),
            'name' => $payload['name'],
            'legal_name' => '',
            'rfc' => '',
            'email' => $payload['email'],
            'phone' => $payload['phone'],
            'price_list_id' => $this->default_price_list_id(),
            'payment_term_id' => 0,
            'sat_cfdi_use_code' => '',
            'sat_tax_regime_code' => '',
            'fiscal_operation_type_id' => 0,
            'shipping_method_id' => 0,
            'credit_limit' => 0,
            'credit_days' => 0,
            'notes' => 'Cliente registrado desde frontend.',
            'active' => 1,
        ]);
        $party->save();

        return $party;
    }

    /**
     * CREATE CUSTOMER LINK
     *
     * VINCULA USUARIO, CLIENTE Y PORTAL CLIENTES
     *
     * @access  protected
     * @return  Void
     */
    protected function create_customer_link($user_id, $party_id)
    {
        # SE CREA ACCESO MINIMO AL PORTAL CLIENTES
        Model_Core_Party_User_Link::forge([
            'user_id' => (int) $user_id,
            'party_id' => (int) $party_id,
            'portal_code' => 'clientes',
            'role_code' => 'customer',
            'scope_json' => '{"scope":"own"}',
            'can_manage_users' => 0,
            'active' => 1,
        ])->save();
    }

    /**
     * CUSTOMER LINK
     *
     * OBTIENE VINCULO ACTIVO DE CLIENTE PARA LA SESION ACTUAL
     *
     * @access  protected
     * @return  Model_Core_Party_User_Link|null
     */
    protected function customer_link()
    {
        # SE VALIDA SESION Y LINK CLIENTES
        $user_id = $this->current_user_id();
        if ($user_id < 1) {
            return null;
        }

        return Model_Core_Party_User_Link::query()
            ->where('user_id', $user_id)
            ->where('portal_code', 'clientes')
            ->where('active', 1)
            ->get_one();
    }

    /**
     * REQUIRE CUSTOMER
     *
     * VALIDA SESION DE CLIENTE WEB
     *
     * @access  protected
     * @return  Model_Core_Party_User_Link
     */
    protected function require_customer()
    {
        # SE REDIRECCIONA A ACCESO SI NO HAY CLIENTE
        if (!\Auth::check()) {
            \Response::redirect('acceso');
        }

        $link = $this->customer_link();
        if (!$link) {
            \Auth::logout();
            \Response::redirect('acceso');
        }

        return $link;
    }

    /**
     * PREPARE TEMPLATE
     *
     * PREPARA DATOS COMUNES DE LA PLANTILLA PUBLICA
     *
     * @access  protected
     * @return  Void
     */
    protected function prepare_template($title, $description = '')
    {
        # SE RESUELVE TEMA Y EMPRESA PARA BRANDING/SEO
        $theme = $this->get_active_theme();
        $company = Model_Core_Company::get_current();

        # SE REUTILIZAN LOS DATOS PUBLICOS MINIMOS
        $this->template->title = $title;
        $this->template->seo_description = $description ?: (($theme && !empty($theme->default_seo_description)) ? $theme->default_seo_description : '');
        $this->template->site_name = ($theme && !empty($theme->site_name)) ? $theme->site_name : ($company ? $company->name : 'Core-App');
        $this->template->canonical_url = \Uri::current();
        $this->template->menu_items = $this->get_menu_items('header');
        $this->template->footer_columns = $this->get_footer_columns();
        $this->template->theme = $theme;
        $this->template->frontend_user = [
            'logged_in' => \Auth::check() && (bool) $this->customer_link(),
            'name' => \Auth::check() ? \Auth::get_screen_name() : '',
        ];
        $this->template->cart_count = class_exists('Helper_Core_Cart') ? Helper_Core_Cart::count() : 0;
        $this->template->set('cookie_banner', class_exists('Helper_Core_Legal') ? Helper_Core_Legal::render_cookie_banner() : '', false);
    }

    protected function get_menu_items($location = 'header')
    {
        $menu = Model_Core_Frontend_Menu::query()
            ->where('location', $location)
            ->where('active', 1)
            ->order_by('id', 'asc')
            ->get_one();

        if (!$menu) {
            return [];
        }

        return Model_Core_Frontend_Menu_Item::query()
            ->where('menu_id', $menu->id)
            ->where('parent_id', 0)
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
    }

    protected function get_footer_columns()
    {
        return Model_Core_Frontend_Footer_Column::query()
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
    }

    protected function get_active_theme()
    {
        if (!\DBUtil::table_exists('core_frontend_themes')) {
            return null;
        }

        return Model_Core_Frontend_Theme::get_active();
    }

    protected function current_user_id()
    {
        $user_id_data = \Auth::get_user_id();
        return isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;
    }

    protected function email_exists($email)
    {
        return (bool) \DB::select('id')->from('users')->where('email', '=', $email)->execute()->current();
    }

    protected function unique_party_code($email)
    {
        $base = $this->codeify(strtok($email, '@') ?: 'cliente');
        $code = $base;
        $i = 1;

        while (\DB::select('id')->from('core_parties')->where('code', '=', $code)->execute()->current()) {
            $code = $base.'_'.$i;
            $i++;
        }

        return $code;
    }

    protected function default_price_list_id()
    {
        $row = \DB::select('id')
            ->from('core_commerce_price_lists')
            ->where('active', '=', 1)
            ->where('is_default', '=', 1)
            ->order_by('priority', 'desc')
            ->execute()
            ->current();

        return $row ? (int) $row['id'] : 0;
    }

    protected function price_list_name($price_list_id)
    {
        if ((int) $price_list_id < 1) {
            return 'Precio base';
        }

        $row = \DB::select('name')->from('core_commerce_price_lists')->where('id', '=', (int) $price_list_id)->execute()->current();
        return $row ? (string) $row['name'] : 'Precio base';
    }

    protected function customer_quotes($party_id)
    {
        if ((int) $party_id < 1 || !\DBUtil::table_exists('core_sales_quotes')) {
            return [];
        }

        return \DB::select('folio', 'status', 'currency_code', 'total', 'created_at')
            ->from('core_sales_quotes')
            ->where('party_id', '=', (int) $party_id)
            ->order_by('id', 'desc')
            ->limit(20)
            ->execute()
            ->as_array();
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
}
