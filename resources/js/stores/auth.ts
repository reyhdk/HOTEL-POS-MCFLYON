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
  all_permissions: string[]; // [DIBENARKAN] Disesuaikan dengan accessor di backend
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

  const isUserLoaded = computed(() => !!user.value);
  const userRole = computed(() => user.value?.role?.name || '');

  function setAuth(authUser: User, token?: string) {
    isAuthenticated.value = true;
    user.value = authUser;
    errors.value = {};
    if (token) {
      JwtService.saveToken(token);
    }
  }

  function purgeAuth() {
    isAuthenticated.value = false;
    user.value = null;
    errors.value = {};
    JwtService.destroyToken();
  }

  function handleRedirect(loggedInUser: User) {
    const staffRoles = ['admin', 'receptionist', 'chef', 'cleaning-service'];
    const userRoleName = loggedInUser?.role?.name;

    if (userRoleName && staffRoles.includes(userRoleName)) {
      // Jika rolenya adalah salah satu dari staf, arahkan ke dasbor admin
      router.push({ name: "admin-dashboard" });
    } else {
      // Jika bukan, berarti itu tamu (user)
      router.push({ name: "user-dashboard" });
    }
  }

  async function login(credentials: any) {
    try {
      const { data } = await ApiService.post("auth/login", credentials);
      const loggedInUser = data.data.user;
      const token = data.data.token;
      setAuth(loggedInUser, token);
      handleRedirect(loggedInUser);
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
      setAuth(data.data.user);
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
    login,
    logout,
    register,
    verifyAuth,
  };
});