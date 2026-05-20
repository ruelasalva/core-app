<?php

class Model_Core_Crm_Cut_Calculation extends \Orm\Model
{
    protected static $_table_name = 'core_crm_cut_calculations';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'party_id', 'user_id', 'material', 'sheet_width', 'sheet_height',
        'piece_width', 'piece_height', 'kerf', 'pieces_x', 'pieces_y', 'total_pieces',
        'waste_percent', 'notes', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
