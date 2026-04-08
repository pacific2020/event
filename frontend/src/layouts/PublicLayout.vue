<script setup lang="ts">
import NavBar from '@/components/NavBar/nav.vue'
import PublicFooter from '@/components/Footer/PublicFooter.vue';

</script>

<template>
<div>
    <div>
        <NavBar />
    </div>
    <main>
        <router-view />
    </main>
    <PublicFooter />
</div>
</template>