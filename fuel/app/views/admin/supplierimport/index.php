<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<?php echo View::forge('admin/supplierimport/index/_styles'); ?>

<div id="app-supplier-import" v-cloak>
    <?php echo View::forge('admin/supplierimport/index/_header', ['title' => $title]); ?>

    <?php echo View::forge('admin/supplierimport/index/_alerts'); ?>

    <?php echo View::forge('admin/supplierimport/index/_upload_response'); ?>

    <?php echo View::forge('admin/supplierimport/index/_upload_result'); ?>

    <?php echo View::forge('admin/supplierimport/index/_summary'); ?>

    <?php echo View::forge('admin/supplierimport/index/_runs_table'); ?>

    <?php echo View::forge('admin/supplierimport/index/_staging_table'); ?>

    <?php echo View::forge('admin/supplierimport/index/_help'); ?>

    <?php echo View::forge('admin/supplierimport/index/_upload_modal'); ?>
</div>

<?php echo View::forge('admin/supplierimport/index/_scripts', ['initial_data' => $initial_data, 'json_flags' => $json_flags]); ?>
