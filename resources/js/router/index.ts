import { createRouter, createWebHistory, type RouteRecordRaw } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import JwtService from "@/core/services/JwtService";
import NProgress from "nprogress";
import "nprogress/nprogress.css";

// Deklarasi tipe Anda, menggunakan 'role'
declare module "vue-router" {
  interface RouteMeta {
    pageTitle?: string;
    breadcrumb?: string[];
    middleware?: "auth" | "guest";
    role?: "admin" | "user"; // Sesuai dengan guard baru Anda
  }
}

const routes: Array<RouteRecordRaw> = [
  // =======================================================
  // ▼▼▼ GRUP RUTE USER (TIDAK BERUBAH) ▼▼▼
  // =======================================================
  {
    path: "/user",
    component: () => import("@/layouts/default-layout/DefaultLayout.vue"),
    meta: { middleware: "auth", role: "user" },
    children: [
      {
        path: "dashboard",
        name: "user-dashboard",
        component: () => import("@/pages/dashboard/Index.vue"),
        meta: { pageTitle: "Dasbor" },
      },
      {
        path: "booking",
        name: "user-booking",
        component: () => import("@/pages/user-dashboard/bookings/Index.vue"),
        meta: {
          pageTitle: "Booking Online",
          breadcrumb: ["Dashboard", "Booking"],
        },
      },
      {
        path: 'booking-history',
        name: 'booking-history',
        component: () => import("@/pages/user-dashboard/booking-history/Index.vue"),
        meta: {
            middleware: "auth",
        }
      },
        {
        path: "/user/food-order",
        name: "user-food-order",

        // --- UBAH BARIS DI BAWAH INI ---
        component: () => import("@/pages/user-dashboard/food-order/index.vue"),
        // --- SESUAIKAN DENGAN PATH DI ATAS ---

        meta: {
            pageTitle: "Pesan Makanan",
            breadcrumbs: ["Dashboard", "Pesan Makanan"],
            middleware: "auth",
        },
        },

    ],
  },

  // =======================================================
  // ▼▼▼ GRUP RUTE ADMIN (SUDAH DISESUAIKAN) ▼▼▼
  // =======================================================
  {
    path: "/admin",
    component: () => import("@/layouts/default-layout/DefaultLayout.vue"),
    meta: { middleware: "auth", role: "admin" }, // Menggunakan 'role'
    children: [
      {
        path: "dashboard",
        name: "admin-dashboard",
        component: () => import("@/pages/dashboard/Index.vue"),
        meta: { pageTitle: "Dasbor Admin" },
      },
      {
        path: "pos",
        name: "admin-pos",
        component: () => import("@/pages/dashboard/pos/Index.vue"),
        meta: { pageTitle: "Point of Sale" },
      },
      {
        path: "master/rooms",
        name: "admin-master-rooms",
        component: () => import("@/pages/dashboard/master/rooms/Index.vue"),
        meta: { pageTitle: "Kamar" },
      },
      {
        path: "master/facilities",
        name: "admin-master-facilities",
        component: () => import("@/pages/dashboard/master/facilities/Index.vue"),
        meta: { pageTitle: "Fasilitas" },
      },
      {
        path: "payment",
        name: "admin-payment",
        component: () => import("@/pages/dashboard/payment/Index.vue"),
        meta: { pageTitle: "Daftar Tagihan" },
      },
      {
        path: "folio",
        name: "admin-folio",
        component: () => import("@/pages/dashboard/folio/Index.vue"),
        meta: { pageTitle: "Folio Kamar" },
      },
      {
        path: "history",
        name: "admin-history",
        component: () => import("@/pages/dashboard/history/Index.vue"),
        meta: { pageTitle: "Riwayat Transaksi" },
      },
      {
        path: "master/guests",
        name: "admin-master-guests",
        component: () => import("@/pages/dashboard/master/guests/Index.vue"),
        meta: { pageTitle: "Tamu" },
      },
      {
        path: "/admin/online-orders",
        name: "admin-online-orders",
        component: () => import("@/pages/dashboard/online-orders/Index.vue"),
        meta: { middleware: "auth", pageTitle: "Pesanan Online" },
      },
      {
        path: "master/menus",
        name: "admin-master-menus",
        component: () => import("@/pages/dashboard/master/menus/Index.vue"),
        meta: { pageTitle: "Menu" },
      },
      {
        path: "master/users",
        name: "admin-master-users",
        component: () => import("@/pages/dashboard/master/users/Index.vue"),
        meta: { pageTitle: "Users" },
      },
      {
        path: "master/roles",
        name: "admin-master-roles",
        component: () => import("@/pages/dashboard/master/users/roles/Index.vue"),
        meta: { pageTitle: "Roles" },
      },
      {
        path: "setting",
        name: "admin-setting",
        component: () => import("@/pages/dashboard/setting/Index.vue"),
        meta: { pageTitle: "Website Setting" },
      },
    ],
  },

  // =======================================================
  // ▼▼▼ GRUP RUTE GUEST & 404 (TIDAK BERUBAH) ▼▼▼
  // =======================================================
  {
    path: "/",
    component: () => import("@/layouts/AuthLayout.vue"),
    meta: { middleware: "guest" },
    children: [
      {
        path: "",
        name: "sign-in",
        component: () => import("@/pages/auth/sign-in/Index.vue"),
        meta: { pageTitle: "Sign In" },
      },
      {
        path: "sign-up",
        name: "sign-up",
        component: () => import("@/pages/auth/sign-up/Index.vue"),
        meta: { pageTitle: "Sign Up" },
      },
    ],
  },
  {
    path: "/:pathMatch(.*)*", // Koreksi kecil: (.*)* lebih umum untuk catch-all
    name: '404',
    component: () => import("@/pages/errors/Error404.vue"),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0, left: 0, behavior: "smooth" };
  },
});

NProgress.configure({ showSpinner: false });

router.beforeEach(async (to, from, next) => {
  NProgress.start();
  document.title = `${to.meta.pageTitle || 'Welcome'} - ${import.meta.env.VITE_APP_NAME}`;

  const authStore = useAuthStore();

  // 1. Selalu coba pulihkan sesi jika token ada tapi data user belum dimuat
  // Ini adalah kunci untuk menangani refresh halaman
  if (JwtService.getToken() && !authStore.isUserLoaded) {
    await authStore.verifyAuth();
  }

  const isAuthenticated = authStore.isAuthenticated;
  const userRole = authStore.userRole; // Gunakan getter dari store

  const requiresAuth = to.meta.middleware === 'auth';
  const requiresGuest = to.meta.middleware === 'guest';
  const requiredRole = to.meta.role; // Pastikan Anda menggunakan 'role' di meta rute Anda

  // 2. Logika untuk rute yang butuh login ('auth')
  if (requiresAuth) {
    if (!isAuthenticated) {
      // Jika butuh login tapi pengguna belum login, arahkan ke sign-in
      return next({ name: 'sign-in' });
    }

    if (requiredRole && requiredRole !== userRole) {
      // Jika sudah login tapi role tidak cocok, arahkan ke dashboard mereka masing-masing
      if (userRole === 'admin') return next({ name: 'admin-dashboard' });
      return next({ name: 'user-dashboard' });
    }
  }

  // 3. Logika untuk rute tamu ('guest')
  if (requiresGuest && isAuthenticated) {
    // Jika ini halaman tamu (seperti login) tapi pengguna sudah login,
    // arahkan mereka ke dashboard yang sesuai
    if (userRole === 'admin') return next({ name: 'admin-dashboard' });
    return next({ name: 'user-dashboard' });
  }

  // 4. Jika semua kondisi di atas tidak terpenuhi, izinkan akses
  next();
});

router.afterEach(() => {
  NProgress.done();
});

export default router;
