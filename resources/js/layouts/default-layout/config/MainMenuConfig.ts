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
      heading: "Layanan Kamar",
      route: "/user/room-service",
      keenthemesIcon: "profile-circle",
      roles: ["user"],
      },
      {
      heading: "Tagihan & Checkout",
      route: "/user/checkout",
      keenthemesIcon: "exit-right",
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
        sectionTitle: "Layanan Hotel",
        route: "/hotel-services", // Rute induk tidak harus ada
        name: "manage service_requests", // Gunakan permission yang sesuai
        keenthemesIcon: "abstract-29",
        sub: [
          {
            heading: "Permintaan Layanan",
            route: "/admin/service-requests",
            name: "manage service_requests", // Permission yang sama untuk sub-menu
          },

        ],
      },
      {
      heading: "Riwayat Permintaan",
      route: "/user/request-history",
      keenthemesIcon: "time",
      roles: ["user"],
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
      route: "/admin/master/rooms", // BENAR
      name: "view rooms",
      roles: ["admin"],
    },
    {
      heading: "Fasilitas",
      route: "/admin/master/facilities", // BENAR
      name: "view facilities",
      roles: ["admin"],
    },
    {
      heading: "Tamu",
      route: "/admin/master/guests", // BENAR
      name: "view guests",
      roles: ["admin"],
    },
    {
      heading: "Menu",
      route: "/admin/master/menus", // BENAR
      name: "view menus",
      roles: ["admin"],
    },
    {
      heading: "Users",
      route: "/admin/master/users", // BENAR
      name: "view users",
      roles: ["admin"],
    },
    {
      heading: "Roles",
      route: "/admin/master/roles", // BENAR
      name: "view roles",
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
