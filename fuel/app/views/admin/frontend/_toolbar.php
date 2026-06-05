            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">{{ currentDefinition.title || 'Frontend' }}</h3>
                <div class="d-flex align-items-center">
                    <a class="btn btn-outline-secondary btn-sm mr-2" href="<?php echo Uri::base(false); ?>" target="_blank"><i class="bi bi-eye"></i> Ver sitio</a>
                    <select class="form-control form-control-sm mr-2" v-model="currentSection">
                        <option v-for="key in sectionKeys" :key="key" :value="key">{{ definitions[key].title }}</option>
                    </select>
                    <button class="btn btn-primary btn-sm" @click="newItem"><i class="bi bi-plus-lg"></i> Nuevo</button>
                </div>
            </div>
