            <div v-if="!loading && currentSection === 'sections'">
                <div v-for="group in sectionGroups" :key="'page-sections-' + group.page.id" class="card card-outline card-secondary mb-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">
                                {{ group.page.title }}
                                <span v-if="group.page.is_home == 1" class="badge badge-info ml-2">Página de inicio</span>
                            </h3>
                            <span class="badge badge-light">{{ group.sections.length }} secciones</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 90px;">Orden</th>
                                    <th>Sección</th>
                                    <th>Tipo</th>
                                    <th>Bloque</th>
                                    <th>Estado</th>
                                    <th class="text-center" style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(section, index) in group.sections" :key="'section-row-' + section.id">
                                    <td>{{ section.sort_order }}</td>
                                    <td>
                                        <strong>{{ section.title || section.section_key || 'Sin título' }}</strong>
                                        <div class="text-muted small">{{ section.section_key || '-' }}</div>
                                        <div v-if="blockWarning(section)" class="text-warning small">{{ blockWarning(section) }}</div>
                                    </td>
                                    <td><span class="badge badge-light">{{ sectionTypeLabel(section.section_type) }}</span></td>
                                    <td>{{ section.section_type === 'block' ? blockLabel(section.target_id, section.section_key) : '-' }}</td>
                                    <td>
                                        <span class="badge" :class="isActive(section) ? 'badge-success' : 'badge-secondary'">
                                            {{ isActive(section) ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-xs btn-outline-secondary mr-1" :disabled="index === 0" title="Mover arriba" @click="moveSection(section, 'up')">
                                            <i class="bi bi-arrow-up"></i>
                                        </button>
                                        <button class="btn btn-xs btn-outline-secondary mr-1" :disabled="index === group.sections.length - 1" title="Mover abajo" @click="moveSection(section, 'down')">
                                            <i class="bi bi-arrow-down"></i>
                                        </button>
                                        <button class="btn btn-xs btn-warning" @click="editItem(section)"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <tr v-if="group.sections.length === 0">
                                    <td colspan="6" class="text-center text-muted">Sin secciones para esta página</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="sectionGroups.length === 0" class="text-center text-muted p-4">Sin páginas para agrupar secciones</div>
            </div>
