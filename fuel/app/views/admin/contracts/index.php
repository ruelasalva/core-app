<div id="app-contracts">
    <?php echo View::forge('admin/contracts/_styles'); ?>
    <?php echo View::forge('admin/contracts/_summary'); ?>

    <div class="row">
        <?php echo View::forge('admin/contracts/_list'); ?>
        <?php echo View::forge('admin/contracts/_detail'); ?>
    </div>

    <?php echo View::forge('admin/contracts/_modals'); ?>
</div>

<?php echo View::forge('admin/contracts/_scripts'); ?>
