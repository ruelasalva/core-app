            <table v-if="!loading && currentSection !== 'sections'" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th v-for="field in tableFields" :key="field.name">{{ field.label }}</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in currentItems" :key="item.id">
                        <td v-for="field in tableFields" :key="field.name">
                            <template v-if="currentSection === 'pages' && field.name === 'published'">
                                <span class="badge" :class="item.published == 1 ? 'badge-success' : 'badge-secondary'">
                                    {{ item.published == 1 ? 'Publicada' : 'Borrador' }}
                                </span>
                            </template>
                            <template v-else-if="currentSection === 'pages' && field.name === 'is_home'">
                                <span v-if="item.is_home == 1" class="badge badge-info">Página de inicio</span>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-else-if="currentSection === 'pages' && field.name === 'template_key'">
                                <span class="badge badge-light">{{ templateLabel(item.template_key) }}</span>
                            </template>
                            <template v-else>{{ displayValue(item, field) }}</template>
                        </td>
                        <td>
                            <span class="badge" :class="isActive(item) ? 'badge-success' : 'badge-secondary'">
                                {{ isActive(item) ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a v-if="currentSection === 'pages' && canPreviewPage(item)"
                               class="btn btn-xs btn-info mr-1"
                               :href="previewUrl(item)"
                               target="_blank"
                               rel="noopener"
                               :title="previewTitle(item)">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button v-else-if="currentSection === 'pages'"
                                    class="btn btn-xs btn-secondary mr-1"
                                    disabled
                                    title="Vista previa de borradores pendiente de implementar.">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                            <button class="btn btn-xs btn-warning" @click="editItem(item)"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <tr v-if="currentItems.length === 0">
                        <td :colspan="tableFields.length + 2" class="text-center text-muted">Sin registros</td>
                    </tr>
                </tbody>
            </table>
