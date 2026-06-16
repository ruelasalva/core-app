<div v-if="commandPaletteOpen" class="workspace-command-backdrop" @click.self="closeCommandPalette">
    <div class="workspace-command-panel" role="dialog" aria-modal="true" aria-label="Command Palette">
        <div class="workspace-command-search">
            <i class="bi bi-search"></i>
            <input
                ref="commandPaletteInput"
                type="search"
                class="form-control"
                v-model="commandPaletteQuery"
                placeholder="Buscar acciones, menús o widgets"
                @input="onCommandPaletteInput"
                @keydown.down.prevent="moveCommandSelection(1)"
                @keydown.up.prevent="moveCommandSelection(-1)"
                @keydown.enter.prevent="openSelectedCommandResult"
                @keydown.esc.prevent="closeCommandPalette">
            <button type="button" class="btn btn-sm btn-link text-muted" @click="closeCommandPalette">Cerrar</button>
        </div>

        <div class="workspace-command-body">
            <div v-if="commandPaletteLoading" class="workspace-command-empty">
                Buscando...
            </div>

            <div v-else-if="commandPaletteError" class="workspace-command-empty text-danger">
                {{ commandPaletteError }}
            </div>

            <div v-else-if="commandPaletteResults.length === 0" class="workspace-command-empty">
                No se encontraron resultados.
            </div>

            <template v-else>
                <button
                    v-for="(result, index) in commandPaletteResults"
                    :key="result.type + '-' + result.code"
                    type="button"
                    class="workspace-command-result"
                    :class="{ active: index === commandPaletteSelectedIndex }"
                    @mouseenter="commandPaletteSelectedIndex = index"
                    @click="openCommandResult(result)">
                    <span class="workspace-command-icon"><i :class="result.icon || 'bi bi-arrow-right-circle'"></i></span>
                    <span class="workspace-command-text">
                        <strong>{{ result.title }}</strong>
                        <small>{{ result.description }}</small>
                    </span>
                    <span class="workspace-command-badge">{{ commandTypeLabel(result.type) }}</span>
                </button>
            </template>
        </div>
    </div>
</div>
