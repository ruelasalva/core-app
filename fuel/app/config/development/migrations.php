<?php
return array (
  'version' => array(  
    'app' => array(    
      'default' => array(      
        0 => '001_create_core_configuration_tables',
        1 => '002_add_company_configuration_fields',
        2 => '003_create_core_web_tables',
        3 => '004_create_core_legal_tables',
        4 => '005_create_core_communication_tables',
        5 => '006_create_core_sat_tables',
        6 => '007_create_core_catalog_tables',
        7 => '008_create_core_commerce_catalog_tables',
        8 => '009_create_core_commerce_price_tables',
        9 => '010_create_core_sat_catalog_tables',
        10 => '011_create_core_frontend_tables',
        11 => '012_create_core_frontend_theme_tables',
        12 => '013_create_core_knowledge_tables',
        13 => '014_create_core_operational_catalog_tables',
        14 => '015_create_core_party_tables',
        15 => '016_create_core_portal_access_tables',
        16 => '017_create_core_document_tables',
        17 => '018_create_core_helpdesk_tables',
      ),
    ),
    'module' => array(    
    ),
    'package' => array(    
      'auth' => array(      
        0 => '001_auth_create_usertables',
        1 => '002_auth_create_grouptables',
        2 => '003_auth_create_roletables',
        3 => '004_auth_create_permissiontables',
        4 => '005_auth_create_authdefaults',
        5 => '006_auth_add_authactions',
        6 => '007_auth_add_permissionsfilter',
        7 => '008_auth_create_providers',
        8 => '009_auth_create_oauth2tables',
        9 => '010_auth_fix_jointables',
        10 => '011_auth_group_optional',
        11 => '012_auth_update_userindex',
      ),
    ),
  ),
  'folder' => 'migrations/',
  'table' => 'migration',
  'flush_cache' => false,
  'flag' => NULL,
);
