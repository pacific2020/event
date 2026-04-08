<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import type { EventForm } from '@/types/index'
import EventCard from './EventCard.vue'
import axiosInstance from '@/axios'

const BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'

// ✅ Fetch events
const { data: publicEvents, isLoading, isError } = useQuery<EventForm[]>({
  queryKey: ['publicEvents'],
  queryFn: async () => {
    const response = await axiosInstance.get('/eventsPublic')
    return response.data.data.map((event: EventForm) => ({
      ...event,
      image: event.image ? `${BASE_URL}/storage/${event.image}` : null
    }))
  },
  staleTime: 1000 * 60 * 5, // 5 minutes
})
</script>

<template>
  <section class="px-4 py-10">
    <h2 class="text-2xl font-bold mb-6">Upcoming Events</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
      <template v-if="isLoading">Loading events...</template>
      <template v-else-if="isError">Failed to load events</template>
      <template v-else>
        <EventCard
          v-for="event in publicEvents"
          :key="event.id"
          :category="event.category"
          :title="event.event_name"
          :starting_date="event.starting_date"
          :ending_date="event.ending_date"
          :generated_at="event.generated_at"
          :attendees="event.expected_invitation"
          :image="event.image"
        />
      </template>
    </div>
  </section>
</template>