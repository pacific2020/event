import { defineStore } from "pinia"
import { ref, reactive, watch } from "vue"
import type { GraduationList } from "@/types"
import router from "@/router"
import axiosInstance from "@/axios"
import { useToast } from 'vue-toastification'
import { useQuery } from '@tanstack/vue-query'

const toast = useToast();

export const useGraduandStore = defineStore("graduand", () => {
    // --- State ---
    const savedData = localStorage.getItem('graduand_data');
    const user = ref<GraduationList | null>(savedData ? JSON.parse(savedData) : null)
    const gownData = ref<any>(null) 
    const isLoggedIn = ref(!!savedData)
    const loading = ref(false)

    // --- Actions ---

    const setUser = (userData: GraduationList) => {
        user.value = userData
        isLoggedIn.value = true
        localStorage.setItem('graduand_data', JSON.stringify(userData));
    }

    const getUser = async () => {
        if (!user.value) loading.value = true
        try {
            const { data } = await axiosInstance.get('/graduandUser')
            const graduandData = data.graduand || data; 
            setUser(graduandData)
        } catch (error) {
            cleanState()
            router.push('/apply')
        } finally {
            loading.value = false
        }
    }

   const { data: polledGown, isFetching: isGownLoading } = useQuery({
    queryKey: ['gownStatus', () => user.value?.reg_no],
    queryFn: async () => {
        if (!user.value?.reg_no) return null;
        try {
            const response = await axiosInstance.get(`/view-gown/${user.value.reg_no}`);
            
            // ✅ IF API returns 404 or empty data, return null
            if (!response.data || (Array.isArray(response.data) && response.data.length === 0)) {
                return null;
            }

            return Array.isArray(response.data) ? response.data[0] : response.data;
        } catch (error: any) {
            // ✅ If the record is deleted and the API throws a 404, return null
            if (error.response?.status === 404) return null;
            throw error;
        }
    },
    enabled: () => !!user.value?.reg_no && isLoggedIn.value,
    refetchInterval: 2000, 
});

// ✅ CRITICAL: Ensure we sync 'null' values too!
watch(polledGown, (newValue) => {
    // We remove the "if (newValue)" check so that null updates the state
    gownData.value = newValue || null;
}, { immediate: true });

    const cleanState = () => {
        user.value = null;
        gownData.value = null;
        isLoggedIn.value = false;
        localStorage.removeItem('token');
        localStorage.removeItem('graduand_data');
    }

    const logout = async () => {
        try {
            await axiosInstance.post('/GraduandLogout')
        } catch (error) {
            console.error("Logout Error:", error)
        } finally {
            cleanState()
            router.push('/apply')
        }
    }

    const handleSubmit = async (formData: any) => {
        loading.value = true;
        try {
            const { data } = await axiosInstance.post('/graduand/login', formData);

            if (data.status === 'success') {
                toast.success('Welcome!');
                localStorage.setItem('token', data.token);
                axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
                setUser(data.graduand);
                router.push('/Graduand/dashboard');
            }
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Login failed');
        } finally {
            loading.value = false;
        }
    };

    // --- Reactive Forms ---
    const form = reactive({
        reg_no: '',
        scanned_number: ''
    })

    const errors = reactive({
        reg_no: [] as string[],
        scanned_number: [] as string[]
    })

    return {
        user,
        gownData,
        isGownLoading, // Useful for showing a small spinner in UI
        isLoggedIn,
        loading,
        setUser,
        logout,
        getUser,
        handleSubmit,
        form,
        errors,
        cleanState
    }
})