import { createRouter, createWebHistory, type RouteRecordRaw } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import JwtService from "@/core/services/JwtService";
import NProgress from "nprogress";
import "nprogress/nprogress.css";

declare module "vue-router" {
  interface RouteMeta {
    pageTitle?: string;
    breadcrumb?: string[];
    middleware?: "auth" | "guest";
    role?: string | string[];
  }
}

const routes: Array<RouteRecordRaw> = [
  // =======================================================
  // ▼▼▼ GRUP RUTE USER (TAMU) ▼▼▼
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
        meta: { pageTitle: "Booking Online", breadcrumb: ["Dashboard", "Booking"] },
      },
      {
        path: 'booking-history',
        name: 'booking-history',
        component: () => import("@/pages/user-dashboard/booking-history/Index.vue"),
        meta: { pageTitle: "Riwayat Booking" }
      },
      {
        path: "food-order",
        name: "user-food-order",
        component: () => import("@/pages/user-dashboard/food-order/Index.vue"),
        meta: { pageTitle: "Pesan Makanan", breadcrumbs: ["Dashboard", "Pesan Makanan"] },
      },
      {
      path: "room-service",
      name: "user-room-service",
      component: () => import("@/pages/user-dashboard/room-service/Index.vue"),
      meta: { pageTitle: "Layanan Kamar" },
    },
      {
        path: "payment/:orderId",
        name: "user-payment",
        component: () => import("@/pages/user-dashboard/payment-page/Index.vue"),
        meta: { pageTitle: "Pembayaran", breadcrumbs: ["Dashboard", "Pesan Makanan", "Pembayaran"] },
      }
    ],
  },

  // =======================================================
  // ▼▼▼ GRUP RUTE ADMIN & STAF LAINNYA ▼▼▼
  // =======================================================
  {
    path: "/admin",
    component: () => import("@/layouts/default-layout/DefaultLayout.vue"),
    meta: { middleware: "auth", role: ['admin', 'receptionist', 'chef', 'cleaning-service'] },
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
        path: "online-orders",
        name: "admin-online-orders",
        component: () => import("@/pages/dashboard/online-orders/Index.vue"),
        meta: { pageTitle: "Pesanan Online" },
      },
      {
      path: "service-requests",
      name: "admin-service-requests",
      component: () => import("@/pages/dashboard/service-requests/Index.vue"),
      meta: { pageTitle: "Permintaan Layanan" },
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
  // ▼▼▼ GRUP RUTE AUTENTIKASI (DIPERBAIKI) ▼▼▼
  // =======================================================
  {
    path: "/auth", // 1. Path induk diubah menjadi /auth
    component: () => import("@/layouts/AuthLayout.vue"),
    meta: { middleware: "guest" },
    children: [
      {
        path: "sign-in", // 2. Path anak sekarang menjadi 'sign-in'
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
    // 3. Rute root (/) sekarang akan otomatis mengarahkan ke halaman login
    path: "/",
    redirect: "/auth/sign-in",
  },
  {
    path: "/:pathMatch(.*)*",
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

// [DIBENARKAN] Logika Navigation Guard yang lebih aman
router.beforeEach(async (to, from, next) => {
  NProgress.start();
  document.title = `${to.meta.pageTitle || 'Welcome'} - ${import.meta.env.VITE_APP_NAME}`;

  const authStore = useAuthStore();
  const token = JwtService.getToken();

  if (token && !authStore.isUserLoaded) {
    await authStore.verifyAuth();
  }

  const isAuthenticated = authStore.isAuthenticated;
  const userRole = authStore.userRole;
  const staffRoles = ['admin', 'receptionist', 'chef', 'cleaning-service'];

  // 1. Logika untuk halaman tamu (login, register)
  if (to.meta.middleware === "guest") {
    if (isAuthenticated) {
      if (staffRoles.includes(userRole)) {
        return next({ name: 'admin-dashboard' });
      }
      return next({ name: 'user-dashboard' });
    }
    return next();
  }
  
  // 2. Logika untuk halaman yang butuh login
  if (to.meta.middleware === "auth") {
    if (!isAuthenticated) {
      return next({ name: 'sign-in' });
    }

    // Cek otorisasi berdasarkan role
    if (to.meta.role) {
      const requiredRoles = Array.isArray(to.meta.role) ? to.meta.role : [to.meta.role];
      if (!requiredRoles.includes(userRole)) {
        return next({ name: '404' });
      }
    }
  }

  // 3. Jika semua kondisi lolos, izinkan akses
  return next();
});
    
router.afterEach(() => {
  NProgress.done();
});

export default router;