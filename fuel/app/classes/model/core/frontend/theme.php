<?php

class Model_Core_Frontend_Theme extends \Orm\Model
{
    protected static $_table_name = 'core_frontend_themes';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'code', 'name', 'layout_key', 'color_primary', 'color_secondary', 'color_accent',
        'color_background', 'color_surface', 'color_text', 'color_muted', 'font_family',
        'heading_font_family', 'logo_path', 'favicon_path', 'header_style', 'footer_style',
        'custom_css', 'is_active', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];

    /**
     * GET ACTIVE
     *
     * OBTIENE EL TEMA ACTIVO DEL FRONTEND
     *
     * @access  public
     * @return  Model_Core_Frontend_Theme|null
     */
    public static function get_active()
    {
        # SE BUSCA EL TEMA MARCADO COMO ACTIVO
        return static::query()
            ->where('is_active', 1)
            ->where('active', 1)
            ->order_by('id', 'asc')
            ->get_one();
    }
}
