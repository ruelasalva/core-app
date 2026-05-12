<div id="app-permissions">
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">1. Seleccionar Grupo</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column">
                        <li v-for="group in groups" :key="group.id" class="nav-item">
                            <a href="#" class="nav-link"
                               :class="{ 'active': selectedGroup && selectedGroup.id == group.id }"
                               @click.prevent="selectGroup(group)">
                                <i class="fas fa-user-tag mr-2"></i> {{ group.name }}
                                <span class="float-right badge bg-light text-dark">ID: {{ group.id }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-primary" v-if="selectedGroup">
                <div class="card-header">
                    <h3 class="card-title">2. Permisos para: <b>{{ selectedGroup.name }}</b></h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">Estado</th>
                                    <th>Area</th>
                                    <th>Permiso</th>
                                    <th>Descripcion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="perm in permissions" :key="perm.id" @click="togglePerm(perm.id)" style="cursor:pointer">
                                    <td class="text-center">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input"
                                                   :id="'p-' + perm.id"
                                                   :checked="isAssigned(perm.id)">
                                            <label class="custom-control-label" :for="'p-' + perm.id"></label>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-secondary">{{ perm.area }}</span></td>
                                    <td><code>{{ perm.permission }}</code></td>
                                    <td class="text-muted"><small>{{ perm.description }}</small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-success float-right" @click="savePermissions" :disabled="saving">
                        <i class="fas fa-save mr-1"></i> {{ saving ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>
            </div>
            <div v-else class="alert alert-info shadow-sm">
                <h5><i class="icon fas fa-info"></i> Paso 1</h5>
                Selecciona un grupo de la izquierda para gestionar sus permisos de acceso.
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-permissions',
        data: {
            groups: [],
            permissions: [],
            relations: [],
            selectedGroup: null,
            assignedPerms: [],
            saving: false,
            error: ''
        },
        mounted() {
            this.loadData();
        },
        methods: {
            loadData() {
                fetch('<?php echo Uri::create('admin/permissions/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            this.error = data.error;
                            alert(data.error);
                            return;
                        }

                        this.groups = data.groups || [];
                        this.permissions = data.permissions || [];
                        this.relations = data.relations || [];

                        if (this.selectedGroup) {
                            const current = this.groups.find(g => g.id == this.selectedGroup.id);
                            if (current) this.selectGroup(current);
                        }
                    })
                    .catch(() => {
                        this.error = 'No se pudieron cargar los permisos.';
                        alert(this.error);
                    });
            },
            selectGroup(group) {
                this.selectedGroup = group;
                this.assignedPerms = this.relations
                    .filter(r => r.group_id == group.id)
                    .map(r => r.perm_id);
            },
            isAssigned(permId) {
                return this.assignedPerms.includes(permId);
            },
            togglePerm(permId) {
                const index = this.assignedPerms.indexOf(permId);
                if (index > -1) {
                    this.assignedPerms.splice(index, 1);
                } else {
                    this.assignedPerms.push(permId);
                }
            },
            savePermissions() {
                this.saving = true;
                fetch('<?php echo Uri::create('admin/permissions/sync'); ?>', {
                    ...window.coreAppFetchOptions({
                        group_id: this.selectedGroup.id,
                        perms: this.assignedPerms
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.saving = false;
                    if (data.status == 'ok') {
                        alert("Permisos actualizados correctamente.");
                        this.loadData();
                    } else {
                        alert(data.error || "No se pudieron guardar los permisos.");
                    }
                })
                .catch(() => {
                    this.saving = false;
                    alert("No se pudieron guardar los permisos.");
                });
            }
        }
    });
};
</script>
