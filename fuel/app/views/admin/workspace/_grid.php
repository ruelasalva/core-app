<div class="workspace-grid row">
    <div v-for="instance in layout.widgets" :key="instance.widget_code" class="mb-3" :class="columnClass(instance)">
        <?php echo \View::forge('admin/workspace/_widget'); ?>
    </div>
</div>

<div v-if="notice" class="alert alert-info">{{ notice }}</div>
<div v-if="error" class="alert alert-warning">{{ error }}</div>

