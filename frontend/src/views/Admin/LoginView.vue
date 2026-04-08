<script setup lang="ts">

import { useAuthStore } from '@/store/auth';

const auth = useAuthStore();

</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-6">

      <h2 class="text-2xl font-bold text-center mb-6">Login</h2>

      <FormKit
      type="form"
      :actions="false"
      @submit="auth.login"
      >

      <FormKit
        type="email"
        name="email"
        label="Email"
        v-model="auth.form.email"
        validation="required|email"
        placeholder="Enter your email"
      />
      <span v-if="auth.errors.email.length"
class="text-red-500 text-sm">
{{ auth.errors.email[0] }}
</span>

      <FormKit
        type="password"
        name="password"
        label="Password"
        v-model="auth.form.password"
        validation="required"
        placeholder="Enter your password"
      />
      <span v-if="auth.errors.password.length"
class="text-red-500 text-sm">
{{ auth.errors.password[0] }}
</span>

   <!-- Button -->

<FormKit
type="submit"
label="Sign In"
input-class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition"
/>

      </FormKit>

    </div>
  </div>
</template>