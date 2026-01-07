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
    const userRole = computed(() => user.value?.role?.name || "");

    const hasPermission = computed(() => {
        return (permissionName: string) =>
            permissions.value.includes(permissionName);
    });

    function setAuth(authData: any, token?: string) {
        console.log("🔐 setAuth called:", {
            hasToken: !!token,
            userData: authData.user,
        });

        // ✅ URUTAN PENTING: Simpan token DULU
        if (token) {
            console.log("💾 Saving token...");
            JwtService.saveToken(token);

            // Verifikasi token tersimpan
            const savedToken = JwtService.getToken();
            console.log("✅ Token saved:", !!savedToken);

            if (!savedToken) {
                console.error("❌ CRITICAL: Token not saved to localStorage!");
                return;
            }
        }

        // Baru kemudian set state
        isAuthenticated.value = true;
        user.value = authData.user;
        permissions.value = authData.permissions || [];
        errors.value = {};

        console.log("✅ Auth state updated:", {
            isAuthenticated: isAuthenticated.value,
            user: user.value?.name,
            role: user.value?.role?.name,
        });
    }

    function purgeAuth() {
        console.log("🧹 Purging auth...");

        isAuthenticated.value = false;
        user.value = null;
        permissions.value = [];
        errors.value = {};
        JwtService.destroyToken();

        console.log("✅ Auth purged");
    }

    function handleRedirect() {
        if (!user.value) {
            console.warn("⚠️ handleRedirect: No user data");
            return;
        }

        const role = user.value?.role?.name;
        const staffRoles = [
            "admin",
            "receptionist",
            "chef",
            "cleaning-service",
        ];

        console.log("🚀 Redirecting based on role:", role);

        if (role && staffRoles.includes(role)) {
            console.log("➡️ Redirecting to admin dashboard");
            router.push({ name: "admin-dashboard" });
        } else if (role === "user") {
            console.log("➡️ Redirecting to user dashboard");
            router.push({ name: "user-dashboard" });
        } else {
            console.warn("⚠️ Unknown role, redirecting to sign-in");
            router.push({ name: "sign-in" });
        }
    }

    async function login(credentials: any) {
        console.log("🔑 Login attempt...");

        try {
            const { data } = await ApiService.post("auth/login", credentials);

            console.log("✅ Login response received:", {
                hasData: !!data.data,
                hasToken: !!data.data.token,
                user: data.data.user?.name,
            });

            // ✅ Set auth dan token
            setAuth(data.data, data.data.token);

            // ⏱️ TUNGGU SEBENTAR untuk memastikan token tersimpan
            await new Promise((resolve) => setTimeout(resolve, 100));

            // ✅ Redirect setelah token tersimpan
            handleRedirect();

            return data;
        } catch (error: any) {
            console.error("❌ Login failed:", error.response?.data);
            purgeAuth();
            errors.value =
                error.response?.data?.message || "Email atau password salah.";
            throw error;
        }
    }

    async function register(credentials: any) {
        console.log("📝 Register attempt...");

        try {
            const { data } = await ApiService.post(
                "auth/register",
                credentials
            );

            console.log("✅ Register response received");

            // ✅ Set auth dan token
            setAuth(data.data, data.data.token);

            // ⏱️ TUNGGU SEBENTAR untuk memastikan token tersimpan
            await new Promise((resolve) => setTimeout(resolve, 100));

            // ✅ Redirect setelah token tersimpan
            handleRedirect();

            return data;
        } catch (error: any) {
            console.error("❌ Register failed:", error.response?.data);
            errors.value = error.response?.data?.errors || {
                message: ["Gagal mendaftar."],
            };
            throw error;
        }
    }

    async function logout() {
        console.log("🚪 Logout attempt...");

        try {
            if (JwtService.getToken()) {
                await ApiService.delete("auth/logout");
                console.log("✅ Logout API call successful");
            }
        } catch (error) {
            console.error("⚠️ Logout API call failed (non-critical):", error);
        } finally {
            purgeAuth();
            router.push({ name: "sign-in" });
            console.log("✅ Logout complete");
        }
    }

    async function verifyAuth() {
        const token = JwtService.getToken();

        console.log("🔍 Verifying auth:", {
            hasToken: !!token,
            isUserLoaded: isUserLoaded.value,
        });

        if (!token) {
            console.warn("⚠️ No token found, purging auth");
            purgeAuth();
            return;
        }

        try {
            console.log("📡 Fetching user data...");
            const { data } = await ApiService.get("auth/me");

            console.log("✅ User data fetched:", data.data.user?.name);

            // ⚠️ PENTING: Jangan kirim token lagi, karena sudah ada di localStorage
            setAuth(data.data);
        } catch (error: any) {
            console.error("❌ Verify auth failed:", {
                status: error.response?.status,
                message: error.response?.data?.message,
            });

            // ⚠️ HANYA purge jika benar-benar 401 (token invalid)
            if (error.response?.status === 401) {
                console.warn("🚫 Token invalid, purging auth");
                purgeAuth();
            }
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
        handleRedirect,
    };
});
