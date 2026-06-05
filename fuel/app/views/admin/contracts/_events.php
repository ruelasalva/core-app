                        <div v-show="tab === 'events'">
                            <table class="table table-sm table-bordered">
                                <thead><tr><th>Fecha</th><th>Evento</th><th>Estado</th><th>Mensaje</th></tr></thead>
                                <tbody>
                                    <tr v-for="event in selectedEvents" :key="event.id">
                                        <td>{{ event.created_at }}</td>
                                        <td>{{ event.event_type }}</td>
                                        <td>{{ event.old_status || '-' }} &rarr; {{ event.new_status || '-' }}</td>
                                        <td>{{ event.message }}</td>
                                    </tr>
                                    <tr v-if="selectedEvents.length === 0"><td colspan="4" class="text-muted text-center">Sin eventos.</td></tr>
                                </tbody>
                            </table>
                        </div>
