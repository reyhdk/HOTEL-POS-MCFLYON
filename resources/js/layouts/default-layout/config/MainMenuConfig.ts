import type { MenuItem } from "@/layouts/default-layout/config/types";

const MainMenuConfig: MenuItem[] = [
  // =======================================================
  // == MENU UNTUK USER (TAMU) ==
  // =======================================================
  {
    pages: [
      {
        heading: "Dashboard",
        route: "/user/dashboard",
        keenthemesIcon: "element-11",
        roles: ["user"],
      },
      {
        heading: "Restaurant",
        route: "/user/food-order",
        keenthemesIcon: "coffee",
        roles: ["user"],
      },
      {
        heading: "Booking Online",
        route: "/user/booking",
        keenthemesIcon: "calendar-add",
        roles: ["user"],
      },
      {
        heading: "Riwayat Booking Saya",
        route: "/user/booking-history",
        keenthemesIcon: "book-open",
        roles: ["user"],
      },
    ],
  },

  // =======================================================
  // == MENU KHUSUS UNTUK ADMIN ==
  // =======================================================
  {
    pages: [
      {
        heading: "Dashboard Admin",
        route: "/admin/dashboard",
        name: "view dashboard", // [DIBENARKAN] Ditambahkan 'name'
        keenthemesIcon: "element-11",
        roles: ["admin"],
      },
    ],
  },
  {
    heading: "Aplikasi",
    route: "/apps",
    roles: ["admin"],
    pages: [
      {
        sectionTitle: "Point of Sale",
        route: "/pos",
        name: "pos", // [DIBENARKAN] Ditambahkan 'name' induk
        keenthemesIcon: "basket",
        roles: ["admin"],
        sub: [
          {
            heading: "Buat Pesanan",
            route: "/admin/pos",
            name: "create pos_orders", // [DIBENARKAN] Ditambahkan 'name'
            roles: ["admin"],
          },
          {
            heading: "Pesanan Online",
            route: "/admin/online-orders",
            name: "view online_orders", // [DIBENARKAN] Ditambahkan 'name'
            roles: ["admin"],
          },
          {
            heading: "Daftar Tagihan",
            route: "/admin/payment",
            name: "manage payments", // [DIBENARKAN] Ditambahkan 'name'
            roles: ["admin"],
          },
          {
            heading: "Folio Kamar",
            route: "/admin/folio",
            name: "view folios", // [DIBENARKAN] Ditambahkan 'name'
            roles: ["admin"],
          },
        ],
      },
      {
        sectionTitle: "Laporan",
        route: "/reports",
        name: "reports", // [DIBENARKAN] Ditambahkan 'name' induk
        keenthemesIcon: "chart-simple",
        roles: ["admin"],
        sub: [
          {
            heading: "Riwayat Transaksi",
            route: "/admin/history",
            name: "view transaction_history", // [DIBENARKAN] Ditambahkan 'name'
            roles: ["admin"],
          },
        ],
      },
      {
        sectionTitle: "Master",
        route: "/admin",
        name: "master", // [DIBENARKAN] Ditambahkan 'name' induk
        keenthemesIcon: "cube-3",
        roles: ["admin"],
        sub: [
          {
            heading: "Kamar",
            route: "/admin/admin/rooms",
            name: "view rooms", // [DIBENARKAN] Ditambahkan 'name'
            roles: ["admin"],
          },
          {
            heading: "Fasilitas",
            route: "/admin/admin/facilities",
            name: "view facilities", // [DIBENARKAN] Ditambahkan 'name'
            roles: ["admin"],
          },
          {
            heading: "Tamu",
            route: "/admin/admin/guests",
            name: "view guests", // [DIBENARKAN] Ditambahkan 'name'
            roles: ["admin"],
          },
          {
            heading: "Menu",
            route: "/admin/admin/menus",
            name: "view menus", // [DIBENARKAN] Ditambahkan 'name'
            roles: ["admin"],
          },
          {
            heading: "Users",
            route: "/admin/admin/users",
            name: "view users", // [DIBENARKAN] Ditambahkan 'name'
            roles: ["admin"],
          },
          {
            heading: "Roles",
            route: "/admin/admin/roles",
            name: "view roles", // [DIBENARKAN] Ditambahkan 'name'
            roles: ["admin"],
          },
        ],
      },
      {
        sectionTitle: "Pengaturan",
        route: "/pengaturan",
        name: "settings", // [DIBENARKAN] Ditambahkan 'name' induk
        keenthemesIcon: "setting-2",
        roles: ["admin"],
        sub: [
          {
            heading: "Website Setting",
            route: "/admin/setting",
            name: "edit settings", // [DIBENARKAN] Ditambahkan 'name'
            roles: ["admin"],
          },
        ],
      },
    ],
  },
];

export default MainMenuConfig;
