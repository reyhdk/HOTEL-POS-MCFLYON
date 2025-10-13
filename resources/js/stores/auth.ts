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
  all_permissions: string[]; // <-- TAMBAHKAN BARIS INI
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
  // ▼▼▼ TAMBAHKAN STATE UNTUK PERMISSIONS ▼▼▼
  const permissions = ref<string[]>([]);

  const isUserLoaded = computed(() => !!user.value);
  const userRole = computed(() => user.value?.role?.name || '');

  // ▼▼▼ TAMBAHKAN COMPUTED PROPERTY UNTUK MENGECEK PERMISSION ▼▼▼
  const hasPermission = computed(() => {
    return (permissionName: string) => permissions.value.includes(permissionName);
  });

  function setAuth(authData: any, token?: string) {
    isAuthenticated.value = true;
    user.value = authData.user;
    // ▼▼▼ SIMPAN PERMISSIONS KE DALAM STATE ▼▼▼
    permissions.value = authData.permissions || [];
    errors.value = {};
    if (token) {
      JwtService.saveToken(token);
    }
  }

  function purgeAuth() {
    isAuthenticated.value = false;
    user.value = null;
    // ▼▼▼ KOSONGKAN JUGA PERMISSIONS SAAT LOGOUT ▼▼▼
    permissions.value = [];
    errors.value = {};
    JwtService.destroyToken();
  }

  function handleRedirect(loggedInUser: User) {
    // ... (Fungsi ini tidak perlu diubah)
  }

  async function login(credentials: any) {
    try {
      const { data } = await ApiService.post("auth/login", credentials);
      // 'data.data' sekarang berisi { user, permissions, token }
      setAuth(data.data, data.data.token);
      handleRedirect(data.data.user);
    } catch (error: any) {
      purgeAuth();
      errors.value = error.response?.data?.message || "Email atau password salah.";
      throw error;
    }
  }

  async function register(credentials: any) {
    try {
      const { data } = await ApiService.post("auth/register", credentials);
      const newUser = data.data.user;
      const token = data.data.token;
      setAuth(newUser, token);
      handleRedirect(newUser);
    } catch (error: any) {
      errors.value = error.response?.data?.errors || { message: ["Gagal mendaftar."] };
      throw error;
    }
  }

  async function logout() {
    try {
      // Hanya panggil API logout jika ada token
      if(JwtService.getToken()){
        await ApiService.delete("auth/logout");
      }
    } catch (error) {
      console.error("Logout gagal:", error);
    } finally {
      purgeAuth();
      router.push({ name: "sign-in" });
    }
  }

    async function verifyAuth() {
    if (!JwtService.getToken()) {
      purgeAuth();
      return;
    }

     try {
      const { data } = await ApiService.get("auth/me");
      setAuth(data.data);
    } catch (error) {
      purgeAuth();
    }
  }

  return {
    errors,
    user,
    isAuthenticated,
    isUserLoaded,
    userRole,
    permissions,// Expose permissions
    hasPermission,
    login,
    logout,
    register,
    verifyAuth,
  };
}); // End of defineStore
