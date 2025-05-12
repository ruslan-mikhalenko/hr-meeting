<script setup>
import { reactive } from 'vue';

defineProps({
    subscribers: Array,
});

const state = reactive({
    subscribers,
});
</script>

<template>
    <div>
        <h1>Новые подписчики</h1>
        <ul>
            <li v-for="subscriber in state.subscribers" :key="subscriber.id">
                {{ subscriber.username || 'Unnamed subscriber' }}
            </li>
        </ul>
    </div>
</template>
