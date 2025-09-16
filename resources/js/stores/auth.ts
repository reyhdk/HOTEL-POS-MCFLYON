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
  photo?: string;
  permission: Array<string>;
  role?: {
    name: string;
    full_name: string;
  };
}

export const useAuthStore = defineStore("auth", () => {
  const errors = ref<any>({});
  const user = ref<User | null>(null);
  const isAuthenticated = ref(!!JwtService.getToken());

  const isUserLoaded = computed(() => !!user.value);
  const userRole = computed(() => user.value?.role?.name || 'user');

  function setAuth(authUser: User, token?: string) {
    isAuthenticated.value = true;
    user.value = authUser;
    errors.value = {};
    if (token) {
      JwtService.saveToken(token);
      // ApiService.setHeader(); // <-- HAPUS BARIS INI
    }
  }

  function purgeAuth() {
    isAuthenticated.value = false;
    user.value = null;
    errors.value = {};
    JwtService.destroyToken();
    // ApiService.removeHeader(); // <-- HAPUS BARIS INI
  }

  function handleRedirect(loggedInUser: User) {
    if (loggedInUser?.role?.name === 'admin') {
      router.push({ name: "admin-dashboard" });
    } else {
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
    // 1. Kirim data kredensial ke endpoint 'auth/register'
    const { data } = await ApiService.post("auth/register", credentials);

    // 2. Ambil data user dan token dari respons server
    const newUser = data.data.user;
    const token = data.data.token;

    // 3. Simpan data user dan token, set status menjadi "terautentikasi"
    setAuth(newUser, token);

    // 4. Arahkan user ke dashboard yang sesuai setelah berhasil mendaftar
    handleRedirect(newUser);
  } catch (error: any) {
    // 5. Jika terjadi error (misal: email sudah terdaftar)
    // Simpan pesan error untuk ditampilkan di halaman
    errors.value = error.response?.data?.errors || { message: ["Gagal mendaftar."] };
    throw error; // Lemparkan error agar komponen bisa menangani
  }
}

  async function logout() {
    try {
      await ApiService.delete("auth/logout");
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

    // ApiService.setHeader(); // <-- HAPUS BARIS INI
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
