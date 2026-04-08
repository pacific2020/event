<script setup lang="ts">

import { onMounted, ref, computed } from 'vue'
import { useAuthStore } from '@/store/auth'
import axiosInstance from '@/axios'
import { useToast } from 'vue-toastification'
import { EventCategories } from '@/types/index'
// ✅ Import the interface
import type { EventForm } from '@/types/index'

const auth = useAuthStore()
const toast = useToast()
const loading = ref(false)

const selectedEventCategoryValue = ref<string | null>(null)

const selectedEventCategoryLabel = computed(() => {
  const pos = EventCategories.find(p => p.value === selectedEventCategoryValue.value)
  return pos ? pos.label : 'Graduation'
})



onMounted(() => {
  auth.getUser()
})

const handleSubmit = async (formData: any) => {
  loading.value = true

  try {
    const payload = new FormData()

    // ✅ Handle formatting directly in the append call
    // We split the string only if it exists, otherwise we append an empty string
    payload.append('event_name', formData.eventName ?? '')
    
    payload.append('starting_date', formData.startingDate?.split('T')[0] ?? '')
    payload.append('ending_date', formData.EndingDate?.split('T')[0] ?? '')
    payload.append('generated_at', formData.activeDateToAllow?.split('T')[0] ?? '')
    
    payload.append('expected_invitation', String(formData.expectedInvitation ?? 0))
    payload.append('category', selectedEventCategoryLabel.value ?? 'Graduation')

    // Handle Image
    if (formData.image?.length) {
      const file = formData.image[0].file
      if (file instanceof Blob) {
        payload.append('image', file)
      }
    }

    const response = await axiosInstance.post('/createEvent', payload)
    toast.success(response.data.message)

  } catch (e: any) {
    // ... error handling remains same
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen bg-gray-100">


    <div class="flex-1 flex items-center justify-center p-6">
      <div class="w-full max-w-lg bg-white shadow-lg rounded-xl p-6">
        
        <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">
          Create Event
        </h2>

        <FormKit
          type="form"
          :disabled="loading"
          @submit="handleSubmit"
        >

          <!-- Category -->
          <FormKit
            type="select"
            name="category"
            :v-model="selectedEventCategoryValue"
            label="Select Category"
            :options="EventCategories"
            placeholder="Choose a category"
            validation="required"
          />

          <!-- Event Name -->
          <FormKit
            type="text"
            name="eventName"
            label="Event Name"
            validation="required"
          />

          <!-- Dates -->
          <FormKit
            type="datetime-local"
            name="startingDate"
            label="Starting Date"
            validation="required"
          />

          <FormKit
            type="datetime-local"
            name="EndingDate"
            label="Ending Date"
            validation="required"
          />

          <FormKit
            type="number"
            name="expectedInvitation"
            label="Expected Invitations"
            validation="required"
          />

          <FormKit
            type="datetime-local"
            name="activeDateToAllow"
            label="Generated At"
            validation="required"
          />

          <!-- Image -->
          <FormKit
            type="file"
            name="image"
            label="Cover Image"
            accept=".jpg,.png,.jpeg"
            validation="required"
          />

          <!-- ✅ Submit Button -->
       

        </FormKit>
      </div>
    </div>
  </div>
</template>