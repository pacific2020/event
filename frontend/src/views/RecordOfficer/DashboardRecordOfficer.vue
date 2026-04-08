<script setup lang="ts">
import { onMounted } from 'vue';
import { useAuthStore } from '@/store/auth';


const auth = useAuthStore();
onMounted(() => {
  auth.getUser();
})
</script>

<template>
  <div class="flex">


    <!-- Main content -->
    <div class="flex-1 p-4">
       <div v-if="auth.loading">
        Loading...
    </div>
      You're welcome back {{ auth.user?.fullname }}
    </div>
  </div>
</template>