<div id="app-users">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Listado de Usuarios</h3>
            <div class="card-tools">
                <button class="btn btn-primary btn-sm" v-on:click="openModal">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                </button>
            </div>
        </div>
        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando usuarios...</p>
            </div>

            <div v-show="!loading">
                <table id="table-users" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Grupo</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id">
                            <td>{{ user.id }}</td>
                            <td>{{ user.full_name || '-' }}</td>
                            <td>{{ user.username }}</td>
                            <td>{{ user.email }}</td>
                            <td>
                                <span class="badge badge-info">{{ user.group_id }}</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-xs btn-warning" title="Editar" @click="editUser(user)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-xs btn-info" @click="openSpecialPermissions(user)" title="Permisos Especiales">
                                    <i class="fas fa-key"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-user" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas" :class="isEditing ? 'fa-edit' : 'fa-user-plus'"></i> 
                        {{ isEditing ? 'Editar' : 'Nuevo' }} Usuario
                    </h5>
                    <button type="button" class="close text-white" @click="closeUserModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" class="form-control" v-model="form.full_name">
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" v-model="form.username" :disabled="isEditing">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" v-model="form.email">
                    </div>
                    <div class="form-group" v-if="!isEditing">
                        <label>Password</label>
                        <input type="password" class="form-control" v-model="form.password">
                    </div>
                    <div class="form-group">
                        <label>Grupo / Rol</label>
                        <select class="form-control" v-model="form.group_id">
                            <option value="" disabled>Seleccione un grupo...</option>
                            <option v-for="group in groups" :key="group.id" :value="group.id">
                                {{ group.name }} (Nivel: {{ group.id }})
                            </option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" @click="closeUserModal">Cerrar</button>
                    <button type="button" class="btn btn-primary" @click="saveUser">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-special-perms" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-key mr-2"></i> Permisos Especiales: {{ selectedUser.username }}</h5>
                    <button type="button" class="close" @click="closeSpecialModal"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <p class="text-muted small italic text-center">Nota: Estos permisos se suman a los que el usuario ya tiene por su grupo.</p>
                    <table class="table table-sm table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Área</th>
                                <th>Permiso</th>
                                <th class="text-center">Asignar Excepción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="perm in permissions" :key="perm.id">
                                <td><span class="badge badge-secondary">{{ perm.area }}</span></td>
                                <td><code>{{ perm.permission }}</code></td>
                                <td class="text-center">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" :id="'sp-'+perm.id" 
                                               :checked="isSpecialAssigned(perm.id)" @change="toggleSpecialPerm(perm.id)">
                                        <label class="custom-control-label" :for="'sp-'+perm.id"></label>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeSpecialModal">Cerrar</button>
                    <button type="button" class="btn btn-info" @click="saveSpecialPermissions">Guardar Excepciones</button>
                </div>
            </div>
        </div>
    </div>
</div> 
<script>
window.onload = function() {
    new Vue({
        el: '#app-users',
        data: {
            users: [],
            loading: true,
            groups: [],
            isEditing: false,
            // Variables para Permisos Especiales
            permissions: [],    
            selectedUser: {},   
            specialPerms: [],   
            // Formulario único para Nuevo/Editar
            form: { 
                id: null,
                username: '', 
                email: '', 
                group_id: 1, 
                password: '', 
                full_name: '' 
            }
        },
        mounted() {
            this.fetchUsers();
            this.fetchGroups();
            this.fetchCatalogPermissions();
        },
        methods: {
            // --- GESTIÓN DE USUARIOS ---
            fetchUsers() {
                this.loading = true;
                fetch('<?php echo Uri::create('admin/users/list'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.users = data;
                        this.loading = false;
                        this.initDataTable();
                    });
            },
            fetchGroups() {
                fetch('<?php echo Uri::create('admin/users/groups'); ?>')
                    .then(res => res.json())
                    .then(data => { this.groups = data; });
            },
            openModal() {
                this.isEditing = false;
                this.resetForm();
                this.showModal('modal-user');
            },
            editUser(user) {
                this.isEditing = true;
                this.form = {
                    id: user.id,
                    username: user.username || '',
                    email: user.email || '',
                    group_id: user.group_id || 1,
                    password: '',
                    full_name: user.full_name || ''
                };
                this.showModal('modal-user');
            },
            saveUser() {
                if (!this.form.username || !this.form.email) {
                    alert("Usuario y Email son obligatorios.");
                    return;
                }

                fetch('<?php echo Uri::create('admin/users/save'); ?>', {
                    ...window.coreAppFetchOptions(this.form)
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.error) {
                        this.closeUserModal();
                        this.fetchUsers();
                        this.resetForm();
                        alert("Cambios guardados correctamente.");
                    } else {
                        alert("Error: " + data.error);
                    }
                });
            },
            resetForm() {
                this.form = { id: null, username: '', email: '', group_id: 1, password: '', full_name: '' };
            },
            closeUserModal() {
                this.hideModal('modal-user');
            },

            // --- GESTIÓN DE PERMISOS ESPECIALES (EXCEPCIONES) ---
            fetchCatalogPermissions() {
                fetch('<?php echo Uri::create('admin/permissions/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.permissions = data.permissions;
                    });
            },
            openSpecialPermissions(user) {
                this.selectedUser = user;
                fetch('<?php echo Uri::create('admin/users/get_special_perms/'); ?>' + user.id)
                    .then(res => res.json())
                    .then(data => {
                        this.specialPerms = data.assigned; 
                        this.showModal('modal-special-perms');
                    });
            },
            isSpecialAssigned(permId) {
                return this.specialPerms.includes(permId);
            },
            toggleSpecialPerm(permId) {
                const index = this.specialPerms.indexOf(permId);
                if (index > -1) {
                    this.specialPerms.splice(index, 1);
                } else {
                    this.specialPerms.push(permId);
                }
            },
            saveSpecialPermissions() {
                fetch('<?php echo Uri::create('admin/users/save_special_perms'); ?>', {
                    ...window.coreAppFetchOptions({
                        user_id: this.selectedUser.id,
                        perms: this.specialPerms
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status == 'ok') {
                        this.closeSpecialModal();
                        alert("Excepciones aplicadas correctamente.");
                    }
                });
            },
            closeSpecialModal() {
                this.hideModal('modal-special-perms');
            },

            // --- UTILIDADES ---
            showModal(id) {
                const element = document.getElementById(id);
                if (!element) return;

                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(element).show();
                    return;
                }

                if (window.jQuery && $.fn.modal) {
                    $('#' + id).modal('show');
                }
            },
            hideModal(id) {
                const element = document.getElementById(id);
                if (!element) return;

                if (window.bootstrap && bootstrap.Modal) {
                    const instance = bootstrap.Modal.getInstance(element);
                    if (instance) instance.hide();
                } else if (window.jQuery && $.fn.modal) {
                    $('#' + id).modal('hide');
                }

                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            },
            initDataTable() {
                this.$nextTick(() => {
                    const tableId = '#table-users';
                    if ($.fn.DataTable.isDataTable(tableId)) {
                        $(tableId).DataTable().clear().destroy();
                    }
                    $(tableId).DataTable({
                        "responsive": true,
                        "autoWidth": false,
                        "language": { "url": "https://cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json" }
                    });
                });
            }
        }
    });
};
</script>
