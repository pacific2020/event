<script setup lang="ts">
import { useGraduandStore } from '@/store/graduand';

const auth = useGraduandStore();

</script>

<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-50 px-4">
    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden">
      
      <div class="bg-gradient-to-br from-blue-700 to-indigo-800 text-white text-center py-12 px-8">
        <h1 class="text-3xl md:text-4xl font-extrabold mb-4">Graduand Portal</h1>
        <p class="max-w-md mx-auto text-sm md:text-base leading-relaxed text-blue-100">
          Please enter your <b>Registration Number</b> and <b>National ID</b> to verify your eligibility and generate your official invitations.
        </p>
      </div>

      <div class="p-8 md:p-12">
        <FormKit
          type="form"
          :submit-label="auth.loading ? 'Verifying...' : 'Sign In'"
          @submit="auth.handleSubmit"
          :disabled="auth.loading"
          form-class="space-y-6"
          submit-class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-lg font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition-all duration-200"
        >
          <div class="space-y-1">
            <FormKit
              type="text"
              name="reg_no"
              label="Registration Number"
              placeholder="e.g., 22/001/..."
              validation="required|length:5,30"
              label-class="block text-sm font-semibold text-gray-700 mb-1"
              inner-class="relative"
              input-class="block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-xl text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all shadow-sm"
              messages-class="text-red-500 text-xs mt-1 font-medium"
            />
          </div>

          <div class="space-y-1">
            <FormKit
              type="text"
              name="scanned_number"
              label="National ID Number"
              placeholder="1 19XX 8 XXXX..."
              validation="required"
              validation-label="National ID"
              label-class="block text-sm font-semibold text-gray-700 mb-1"
              input-class="block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-xl text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all shadow-sm"
              messages-class="text-red-500 text-xs mt-1 font-medium"
            />
          </div>
        </FormKit>

        <div class="mt-8 text-center border-t border-gray-100 pt-6">
          <p class="text-sm text-gray-500">
            Having trouble? Contact the 
            <a href="#" class="text-blue-600 font-semibold hover:underline">Graduation Committee</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Optional: Fine-tuning FormKit default spacing if not using a global theme */
:deep(.formkit-outer) {
  margin-bottom: 0;
}
:deep(.formkit-label) {
  display: block;
}
</style>