<div class="card card-outline card-secondary workspace-edit-toolbar" v-if="canEditWorkspace">
    <div class="card-body d-flex flex-wrap align-items-center">
        <button type="button" class="btn btn-sm" :class="editMode ? 'btn-outline-secondary' : 'btn-primary'" @click="toggleEditMode">
            <i class="bi" :class="editMode ? 'bi-x-lg' : 'bi-pencil-square'"></i>
            {{ editMode ? 'Salir de edición' : 'Editar Workspace' }}
        </button>
        <button v-if="editMode" type="button" class="btn btn-success btn-sm ml-2" :disabled="!layoutDirty || layoutSaving" @click="saveLayout">
            <i class="bi bi-save"></i> Guardar layout
        </button>
        <button v-if="editMode" type="button" class="btn btn-outline-primary btn-sm ml-2" :disabled="layoutSaving" @click="openWidgetPalette">
            <i class="bi bi-plus-circle"></i> Agregar widget
        </button>
        <button v-if="editMode" type="button" class="btn btn-outline-danger btn-sm ml-2" :disabled="layoutSaving" @click="openResetLayoutModal">
            <i class="bi bi-arrow-counterclockwise"></i> Restablecer
        </button>
        <span v-if="editMode && layoutDirty" class="badge badge-warning ml-2">Cambios sin guardar</span>
        <span v-if="editMode && !layoutDirty" class="text-muted small ml-2">Puedes ordenar, cambiar tamaño u ocultar widgets.</span>
    </div>
</div>

<div class="workspace-grid row">
    <div v-for="instance in visibleLayoutWidgets()" :key="instance.widget_code" class="mb-3" :class="columnClass(instance)">
        <?php echo \View::forge('admin/workspace/_widget'); ?>
    </div>
</div>

<div v-if="notice" class="alert alert-info">{{ notice }}</div>
<div v-if="error" class="alert alert-warning">{{ error }}</div>

<div v-if="widgetPaletteOpen" class="workspace-modal-backdrop" @click.self="closeWidgetPalette">
    <div class="workspace-modal-panel workspace-palette-panel">
        <div class="workspace-modal-header">
            <h5>Agregar widget</h5>
            <button type="button" class="close" @click="closeWidgetPalette">&times;</button>
        </div>
        <div class="workspace-modal-body">
            <div class="form-row">
                <div class="form-group col-md-7">
                    <label class="small text-muted mb-1">Buscar</label>
                    <input type="search" class="form-control form-control-sm" v-model="widgetPaletteQuery" placeholder="Buscar widget">
                </div>
                <div class="form-group col-md-5">
                    <label class="small text-muted mb-1">Categoría</label>
                    <select class="form-control form-control-sm" v-model="widgetPaletteCategory">
                        <option value="">Todas</option>
                        <option v-for="category in widgetPaletteCategories()" :key="category" :value="category">{{ category }}</option>
                    </select>
                </div>
            </div>

            <div v-if="widgetPaletteLoading" class="workspace-command-empty">Cargando widgets...</div>
            <div v-else-if="filteredWidgetPalette().length === 0" class="workspace-command-empty">No hay widgets disponibles con esos filtros.</div>
            <div v-else class="workspace-palette-list">
                <div v-for="widget in filteredWidgetPalette()" :key="widget.code" class="workspace-palette-item">
                    <div class="workspace-palette-icon"><i :class="widget.icon || 'bi bi-grid'"></i></div>
                    <div class="workspace-palette-text">
                        <strong>{{ widget.title }}</strong>
                        <small>{{ widget.description }}</small>
                        <span class="badge badge-light">{{ widget.category }}</span>
                        <span class="badge badge-secondary">{{ widget.type }}</span>
                    </div>
                    <button v-if="widget.state === 'visible'" type="button" class="btn btn-sm btn-outline-secondary" disabled>Ya visible</button>
                    <button v-else-if="widget.state === 'hidden'" type="button" class="btn btn-sm btn-outline-primary" :disabled="widgetPaletteSaving === widget.code" @click="addWidgetFromPalette(widget)">
                        Mostrar de nuevo
                    </button>
                    <button v-else type="button" class="btn btn-sm btn-primary" :disabled="widgetPaletteSaving === widget.code" @click="addWidgetFromPalette(widget)">
                        Agregar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div v-if="resetLayoutModalOpen" class="workspace-modal-backdrop" @click.self="closeResetLayoutModal">
    <div class="workspace-modal-panel">
        <div class="workspace-modal-header">
            <h5>Restablecer Workspace</h5>
            <button type="button" class="close" @click="closeResetLayoutModal">&times;</button>
        </div>
        <div class="workspace-modal-body">
            <p class="mb-0">Se quitará tu layout personal y volverás al layout genérico o asignado por rol.</p>
        </div>
        <div class="workspace-modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="closeResetLayoutModal">Cancelar</button>
            <button type="button" class="btn btn-danger btn-sm" :disabled="layoutSaving" @click="resetLayout">
                <i class="bi bi-arrow-counterclockwise"></i> Restablecer
            </button>
        </div>
    </div>
</div>
