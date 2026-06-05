    <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
    </div>

    <div v-if="warnings.length > 0" class="alert alert-warning">
        <div v-for="warning in warnings" :key="warning">{{ warning }}</div>
    </div>
