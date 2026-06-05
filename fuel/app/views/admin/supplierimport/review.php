<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<?php echo View::forge('admin/supplierimport/review/_styles'); ?>

<div id="app-supplier-review" v-cloak>
    <?php echo View::forge('admin/supplierimport/review/_header_actions', ['title' => $title]); ?>

    <?php echo View::forge('admin/supplierimport/review/_alerts'); ?>
    <?php echo View::forge('admin/supplierimport/review/_apply_result'); ?>
    <?php echo View::forge('admin/supplierimport/review/_image_result'); ?>
    <?php echo View::forge('admin/supplierimport/review/_filters'); ?>

    <?php echo View::forge('admin/supplierimport/review/_staging_table'); ?>

    <?php echo View::forge('admin/supplierimport/review/_detail_modal'); ?>
</div>

<?php echo View::forge('admin/supplierimport/review/_scripts', ['initial_data' => $initial_data, 'json_flags' => $json_flags]); ?>
