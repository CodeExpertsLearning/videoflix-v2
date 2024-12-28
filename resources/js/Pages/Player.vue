<script setup>
import PlayerList from "@/Components/VideoFlix/Players/PlayerList.vue";
import PlayerFull from "@/Components/VideoFlix/Players/PlayerFull.vue";

defineProps({ content: {}, videos: {} });
</script>

<template>
    <div class="w-full">
        <PlayerList v-if="content.type === 2" :videos="videos" />

        <PlayerFull v-if="content.type === 1" />
    </div>
</template>

<style scoped></style>
