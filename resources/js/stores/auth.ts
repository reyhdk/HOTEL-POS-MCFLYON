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

  const isUserLoaded = computed(() => !!user.value);
  const userRole = computed(() => user.value?.role?.name || '');

  const hasPermission = computed(() => {
    return (permissionName: string) => permissions.value.includes(permissionName);
  });

  function setAuth(authData: any, token?: string) {
    isAuthenticated.value = true;
    // PASTIKAN OBJEK USER DIAMBIL DARI authData.user
    user.value = authData.user;
    permissions.value = authData.permissions || [];
    errors.value = {};
    if (token) {
      JwtService.saveToken(token);
    }
  }

  function purgeAuth() {
    isAuthenticated.value = false;
    user.value = null;
    permissions.value = [];
    errors.value = {};
    JwtService.destroyToken();
  }

  // ▼▼▼ [DIBENARKAN] FUNGSI REDIRECT YANG "PINTAR" ▼▼▼
  function handleRedirect() {
    if (!user.value) return;

    // Variabel 'role' bisa berupa string atau undefined
    const role = user.value?.role?.name;
    const staffRoles = ['admin', 'receptionist', 'chef', 'cleaning-service'];

    // [FIX] Tambahkan 'role &&' untuk memastikan 'role' tidak undefined
    // sebelum memanggil .includes()
    if (role && staffRoles.includes(role)) {
      router.push({ name: 'admin-dashboard' });
    } else if (role === 'user') {
      router.push({ name: 'user-dashboard' });
    } else {
      // Jika role tidak ada atau tidak dikenali, arahkan ke halaman login
      router.push({ name: 'sign-in' });
    }
  }

  async function login(credentials: any) {
    try {
      const { data } = await ApiService.post("auth/login", credentials);
      setAuth(data.data, data.data.token);
      handleRedirect(); // <-- [DIBENARKAN] Panggil tanpa parameter
    } catch (error: any) {
      purgeAuth();
      errors.value = error.response?.data?.message || "Email atau password salah.";
      throw error;
    }
  }

  async function register(credentials: any) {
    try {
      const { data } = await ApiService.post("auth/register", credentials);
      // [DIBENARKAN] Kirim seluruh objek data.data ke setAuth
      setAuth(data.data, data.data.token);
      handleRedirect(); // <-- [DIBENARKAN] Panggil tanpa parameter
    } catch (error: any) {
      errors.value = error.response?.data?.errors || { message: ["Gagal mendaftar."] };
      throw error;
    }
  }

  async function logout() {
    try {
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
    permissions,
    hasPermission,
    login,
    logout,
    register,
    verifyAuth,
  };
});