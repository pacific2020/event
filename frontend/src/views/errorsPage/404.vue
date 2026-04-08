<script setup lang="ts">

</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">

        <div class="bg-white shadow-lg rounded-2xl p-10 text-center max-w-md w-full">

            <h1 class="text-7xl font-bold text-blue-600 mb-4">
                404
            </h1>

            <h2 class="text-2xl font-semibold text-gray-800 mb-2">
                Page Not Found
            </h2>

            <p class="text-gray-500 mb-6">
                The page you are looking for does not exist or has been moved.
            </p>

            <router-link
                to="/"
                class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition"
            >
                Go Back Home
            </router-link>

        </div>

    </div>
</template>