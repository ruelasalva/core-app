                        <div v-show="tab === 'relations'">
                            <div v-if="permissions.link" class="border rounded p-2 mb-3">
                                <h6>Relacionar entidad</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label>Entidad</label>
                                        <select class="form-control form-control-sm" v-model="relationForm.related_entity_type">
                                            <option v-for="option in relationOptions.entity_types" :value="option.value">{{ option.label }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>ID</label>
                                        <input type="number" min="1" class="form-control form-control-sm" v-model.number="relationForm.related_entity_id">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Relacion</label>
                                        <select class="form-control form-control-sm" v-model="relationForm.relation_type">
                                            <option v-for="option in relationOptions.relation_types" :value="option.value">{{ option.label }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button class="btn btn-primary btn-sm btn-block" @click="saveRelation">Agregar</button>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <input class="form-control form-control-sm" placeholder="Notas" v-model="relationForm.notes">
                                    </div>
                                </div>
                            </div>

                            <table class="table table-sm table-bordered">
                                <thead><tr><th>Modulo</th><th>Entidad</th><th>Relacion</th><th>Notas</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <tr v-for="relation in selectedRelations" :key="relation.id">
                                        <td>{{ relation.related_module }}</td>
                                        <td>{{ relation.related_entity_label }} #{{ relation.related_entity_id }}</td>
                                        <td>{{ relation.relation_type }}</td>
                                        <td>{{ relation.notes || '-' }}</td>
                                        <td><button v-if="permissions.link" class="btn btn-outline-danger btn-xs" @click="removeRelation(relation)">Quitar</button></td>
                                    </tr>
                                    <tr v-if="selectedRelations.length === 0"><td colspan="5" class="text-muted text-center">Sin relaciones.</td></tr>
                                </tbody>
                            </table>
                        </div>
