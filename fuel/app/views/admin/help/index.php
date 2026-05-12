<div id="app-help">
    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ activeCount }}</h3>
                    <p>Manuales activos</p>
                </div>
                <div class="icon"><i class="bi bi-journal-text"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ categories.length }}</h3>
                    <p>Categorias</p>
                </div>
                <div class="icon"><i class="bi bi-folder2-open"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ articles.length }}</h3>
                    <p>Total manuales</p>
                </div>
                <div class="icon"><i class="bi bi-collection"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ selectedArticle ? selectedArticle.category : '-' }}</h3>
                    <p>Categoria actual</p>
                </div>
                <div class="icon"><i class="bi bi-bookmark-check"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Base de conocimiento</h3>
                <?php if ($can_edit): ?>
                <button class="btn btn-primary btn-sm" @click="newArticle">
                    <i class="bi bi-plus-lg"></i> Nuevo manual
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body">
            <div v-if="error" class="alert alert-danger">
                {{ error }}
            </div>

            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando manuales...</p>
            </div>

            <div v-show="!loading" class="row">
                <div class="col-lg-4">
                    <div class="form-group">
                        <label>Buscar manual</label>
                        <input class="form-control" v-model="search" placeholder="Pagina, frontend, catalogos...">
                    </div>

                    <div class="form-group">
                        <label>Categoria</label>
                        <select class="form-control" v-model="categoryFilter">
                            <option value="">Todas</option>
                            <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                        </select>
                    </div>

                    <div class="list-group help-list">
                        <button v-for="article in filteredArticles" :key="article.id" type="button" class="list-group-item list-group-item-action text-left" :class="{ active: selectedArticle && selectedArticle.id === article.id }" @click="selectArticle(article)">
                            <strong>{{ article.title }}</strong>
                            <span class="d-block small">{{ article.category }}</span>
                            <span v-if="article.active != 1" class="badge badge-secondary mt-1">Inactivo</span>
                        </button>
                    </div>

                    <div v-if="filteredArticles.length === 0" class="alert alert-light border mt-3">
                        No hay manuales con ese filtro.
                    </div>
                </div>

                <div class="col-lg-8">
                    <div v-if="selectedArticle" class="card shadow-none border">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h3 class="card-title mb-1">{{ selectedArticle.title }}</h3>
                                    <span class="badge badge-light">{{ selectedArticle.category }}</span>
                                    <span v-if="selectedArticle.active != 1" class="badge badge-secondary">Inactivo</span>
                                </div>
                                <?php if ($can_edit): ?>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-warning" @click="editArticle(selectedArticle)">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="btn btn-outline-secondary" @click="disableArticle(selectedArticle)" :disabled="selectedArticle.active != 1">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <p v-if="selectedArticle.summary" class="lead">{{ selectedArticle.summary }}</p>
                            <div class="help-content" v-html="selectedArticle.content"></div>
                        </div>
                    </div>

                    <div v-if="!selectedArticle" class="card shadow-none border">
                        <div class="card-body text-muted">
                            Selecciona un manual de la lista para ver los pasos.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($can_edit): ?>
    <div class="modal fade" id="modal-help" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ form.id ? 'Editar manual' : 'Nuevo manual' }}</h5>
                    <button type="button" class="close text-white" @click="hideModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="error" class="alert alert-danger">{{ error }}</div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Titulo</label>
                                <input class="form-control" v-model="form.title">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Codigo</label>
                                <input class="form-control" v-model="form.code" placeholder="Se genera automaticamente">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Categoria</label>
                                <input class="form-control" v-model="form.category" list="help-categories">
                                <datalist id="help-categories">
                                    <option v-for="category in categories" :key="category" :value="category"></option>
                                </datalist>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Orden</label>
                                <input type="number" class="form-control" v-model.number="form.sort_order">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Estado</label>
                                <select class="form-control" v-model.number="form.active">
                                    <option :value="1">Activo</option>
                                    <option :value="0">Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Resumen</label>
                                <input class="form-control" v-model="form.summary">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Contenido del manual</label>
                                <textarea id="help-editor" class="form-control" rows="12" v-model="form.content"></textarea>
                                <small class="text-muted">Aqui van los pasos de uso, criterios, advertencias y ejemplos.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal">Cancelar</button>
                    <button class="btn btn-primary" @click="saveArticle" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm"></span>
                        Guardar manual
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .help-list {
        max-height: 540px;
        overflow-y: auto;
    }
    .help-content h3,
    .help-content h4,
    .help-content h5 {
        margin-top: 1.35rem;
        font-weight: 700;
    }
    .help-content ol,
    .help-content ul {
        padding-left: 1.25rem;
    }
    .help-content li {
        margin-bottom: .45rem;
    }
    .help-content code,
    .help-content pre {
        background: #f1f3f5;
        border-radius: 4px;
        padding: 2px 5px;
    }
    .help-content table {
        width: 100%;
        margin-bottom: 1rem;
        border-collapse: collapse;
    }
    .help-content th,
    .help-content td {
        border: 1px solid #dee2e6;
        padding: .5rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
new Vue({
    el: '#app-help',
    data: {
        loading: true,
        saving: false,
        error: '',
        search: '',
        categoryFilter: '',
        articles: [],
        selectedArticle: null,
        form: {},
        editor: null,
        canEdit: <?php echo $can_edit ? 'true' : 'false'; ?>
    },
    computed: {
        activeCount: function() {
            return this.articles.filter(function(article) { return article.active == 1; }).length;
        },
        categories: function() {
            var values = {};
            this.articles.forEach(function(article) {
                if (article.category) values[article.category] = true;
            });
            return Object.keys(values).sort();
        },
        filteredArticles: function() {
            var term = this.search.toLowerCase();
            var category = this.categoryFilter;
            return this.articles.filter(function(article) {
                var haystack = [article.title, article.category, article.summary, article.code].join(' ').toLowerCase();
                return (!category || article.category === category) && (!term || haystack.indexOf(term) !== -1);
            });
        }
    },
    mounted: function() {
        this.load();
    },
    methods: {
        load: function() {
            this.loading = true;
            fetch('<?php echo Uri::create('admin/help/data'); ?>')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    this.loading = false;
                    if (data.error) {
                        this.error = data.error;
                        return;
                    }
                    this.articles = data.articles || [];
                    this.selectedArticle = this.findActiveArticle() || this.articles[0] || null;
                }.bind(this))
                .catch(function() {
                    this.loading = false;
                    this.error = 'No se pudo cargar la base de conocimiento.';
                }.bind(this));
        },
        findActiveArticle: function() {
            for (var i = 0; i < this.articles.length; i++) {
                if (this.articles[i].active == 1) {
                    return this.articles[i];
                }
            }
            return null;
        },
        selectArticle: function(article) {
            this.selectedArticle = article;
        },
        newArticle: function() {
            this.form = {
                id: 0,
                code: '',
                title: '',
                category: this.categoryFilter || 'General',
                summary: '',
                content: '',
                sort_order: 0,
                active: 1
            };
            this.showModal();
        },
        editArticle: function(article) {
            this.form = JSON.parse(JSON.stringify(article));
            this.showModal();
        },
        showModal: function() {
            this.error = '';
            $('#modal-help').modal('show');
            this.$nextTick(() => this.initEditor());
        },
        hideModal: function() {
            $('#modal-help').modal('hide');
        },
        initEditor: function() {
            if (!this.canEdit || typeof ClassicEditor === 'undefined') {
                return;
            }
            if (this.editor) {
                this.editor.destroy();
                this.editor = null;
            }
            ClassicEditor
                .create(document.querySelector('#help-editor'), { language: 'es' })
                .then(function(editor) {
                    this.editor = editor;
                    this.editor.setData(this.form.content || '');
                }.bind(this));
        },
        saveArticle: function() {
            if (this.editor) {
                this.form.content = this.editor.getData();
            }
            this.saving = true;
            this.error = '';
            fetch('<?php echo Uri::create('admin/help/save'); ?>', window.coreAppFetchOptions(this.form))
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    this.saving = false;
                    if (data.error) {
                        this.error = data.error;
                        return;
                    }
                    this.articles = data.articles || [];
                    this.selectedArticle = this.findArticleById(this.form.id) || this.articles[0] || null;
                    this.hideModal();
                }.bind(this))
                .catch(function() {
                    this.saving = false;
                    this.error = 'No se pudo guardar el manual.';
                }.bind(this));
        },
        findArticleById: function(id) {
            for (var i = 0; i < this.articles.length; i++) {
                if (this.articles[i].id == id) {
                    return this.articles[i];
                }
            }
            return null;
        },
        disableArticle: function(article) {
            if (!confirm('Quieres desactivar este manual?')) {
                return;
            }
            fetch('<?php echo Uri::create('admin/help/delete'); ?>', window.coreAppFetchOptions({ id: article.id }))
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.error) {
                        this.error = data.error;
                        return;
                    }
                    this.articles = data.articles || [];
                    this.selectedArticle = this.findArticleById(article.id) || this.articles[0] || null;
                }.bind(this));
        }
    }
});
});
</script>
