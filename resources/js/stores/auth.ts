import { ref, computed } from "vue";
import { defineStore } from "pinia";
import ApiService from "@/core/services/ApiService";
import JwtService from "@/core/services/JwtService";
import router from "@/router";

export interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
    phone: string;
    photo_url?: string;
    all_permissions: string[];
    role?: {
        id: number;
        name: string;
        full_name: string;
    };
}

export const useAuthStore = defineStore("auth", () => {
    const errors = ref<any>({});
    const user = ref<User | null>(null);
    const isAuthenticated = ref(!!JwtService.getToken());
    const permissions = ref<string[]>([]);
    const loading = ref(false); // ✅ Tambahkan status loading

    const isUserLoaded = computed(() => !!user.value);
    
    // ✅ User role hanya dikembalikan jika tidak sedang loading
    const userRole = computed(() => {
        if (loading.value) return "";
        return user.value?.role?.name || "";
    });

    const hasPermission = computed(() => {
        return (permissionName: string) =>
            permissions.value.includes(permissionName);
    });

    function setAuth(authData: any, token?: string) {
        if (token) {
            JwtService.saveToken(token);
        }

        isAuthenticated.value = true;
        user.value = authData.user;
        permissions.value = authData.permissions || [];
        errors.value = {};
        loading.value = false;
    }

    function purgeAuth() {
        isAuthenticated.value = false;
        user.value = null;
        permissions.value = [];
        errors.value = {};
        loading.value = false;
        JwtService.destroyToken();
    }

    function handleRedirect() {
        const role = user.value?.role?.name;

        if (!role) {
            router.push({ name: "sign-in" });
            return;
        }

        if (role === "user") {
            router.push({ name: "user-dashboard" });
        } else {
            router.push({ name: "admin-dashboard" });
        }
    }

    async function login(credentials: any) {
        loading.value = true;
        try {
            const { data } = await ApiService.post("auth/login", credentials);
            setAuth(data.data, data.data.token);
            await new Promise((resolve) => setTimeout(resolve, 50));
            handleRedirect();
            return data;
        } catch (error: any) {
            purgeAuth();
            errors.value = error.response?.data?.message || "Email atau password salah.";
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function register(credentials: any) {
        loading.value = true;
        try {
            const { data } = await ApiService.post("auth/register", credentials);
            setAuth(data.data, data.data.token);
            await new Promise((resolve) => setTimeout(resolve, 50));
            handleRedirect();
            return data;
        } catch (error: any) {
            errors.value = error.response?.data?.errors || { message: ["Gagal mendaftar."] };
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            if (JwtService.getToken()) {
                await ApiService.delete("auth/logout");
            }
        } finally {
            purgeAuth();
            router.push({ name: "sign-in" });
        }
    }

    async function verifyAuth() {
        const token = JwtService.getToken();
        
        if (!token) {
            purgeAuth();
            return;
        }

        loading.value = true;
        try {
            const { data } = await ApiService.get("auth/me");
            setAuth(data.data);
        } catch (error: any) {
            // Jika token kadaluarsa atau tidak valid (401), hapus sesi
            if (error.response?.status === 401) {
                purgeAuth();
            }
        } finally {
            loading.value = false;
        }
    }

    return {
        errors,
        user,
        loading,
        isAuthenticated,
        isUserLoaded,
        userRole,
        permissions,
        hasPermission,
        login,
        logout,
        register,
        verifyAuth,
        handleRedirect,
    };
});