<template>
  <div class="card">
    <div class="card-header border-0 pt-6">
      <div class="card-title">
        <div class="d-flex align-items-center position-relative my-1">
          <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
            <span class="path1"></span><span class="path2"></span>
          </i>
          <input
            type="text"
            v-model="searchQuery"
            class="form-control form-control-solid w-250px ps-13"
            placeholder="Cari Menu"
          />
        </div>
      </div>
      <div class="card-toolbar">
        <button v-if="userHasPermission('create menus')" type="button" class="btn btn-primary" @click="openAddModal">
          <i class="ki-duotone ki-plus fs-2"></i> Tambah Menu
        </button>
      </div>
    </div>
    <div class="card-body pt-0">
      <table class="table align-middle table-row-dashed fs-6 gy-5">
        <thead>
          <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
            <th class="min-w-100px">Gambar</th>
            <th>Nama Menu</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Stok</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody class="fw-semibold text-gray-600">
          <tr v-if="loading"><td colspan="6" class="text-center">Memuat data...</td></tr>
          <tr v-else-if="filteredMenus.length === 0">
            <td colspan="6" class="text-center">
              {{ searchQuery ? 'Menu tidak ditemukan.' : 'Tidak ada data menu.' }}
            </td>
          </tr>
          <tr v-for="menu in filteredMenus" :key="menu.id">
            <td>
              <div class="symbol symbol-50px">
                <img :src="menu.image_url || '/media/svg/files/blank-image.svg'" alt="Gambar Menu" class="object-fit-cover" />
              </div>
            </td>
            <td>{{ menu.name }}</td>
            <td><span class="badge badge-light-success">{{ menu.category }}</span></td>
            <td>{{ formatCurrency(menu.price) }}</td>
            <td>{{ menu.stock }}</td>
            <td class="text-end">
              <a href="#" v-if="userHasPermission('edit menus')" @click.prevent="openEditModal(menu)" class="btn btn-icon btn-light-primary btn-sm me-2" title="Edit">
                <i class="ki-duotone ki-pencil fs-3"></i>
              </a>
              <a href="#" v-if="userHasPermission('delete menus')" @click.prevent="deleteMenu(menu.id)" class="btn btn-icon btn-light-danger btn-sm" title="Hapus">
                <i class="ki-duotone ki-trash fs-3"></i>
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <MenuModal :menu-data="selectedMenu" @menu-updated="refreshData" />
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth"; // <-- 1. Import auth store
import axios from "@/libs/axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import MenuModal from "./MenuModal.vue";

interface Menu {
  id: number;
  name: string;
  category: string;
  price: number;
  stock: number;
  image_url: string | null;
}

const authStore = useAuthStore(); // <-- 2. Inisialisasi store
const menus = ref<Menu[]>([]);
const loading = ref(true);
const selectedMenu = ref<Menu | null>(null);
const searchQuery = ref("");

// [BARU] Fungsi bantuan untuk mengecek izin user
const userHasPermission = (permission: string): boolean => {
  return authStore.user?.all_permissions?.includes(permission) ?? false;
};

const filteredMenus = computed(() => {
  if (!searchQuery.value) {
    return menus.value;
  }
  return menus.value.filter((menu) =>
    menu.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const getMenus = async () => {
  try {
    loading.value = true;
    const response = await axios.get("/menus");
    menus.value = response.data;
  } catch (error) {
    console.error("Gagal mengambil data menu:", error);
    Swal.fire("Error", "Gagal memuat data menu.", "error");
  } finally {
    loading.value = false;
  }
};

const refreshData = () => {
  getMenus();
};

const formatCurrency = (value: number) => {
  if (!value || isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(value);
};

const deleteMenu = (id: number) => {
  Swal.fire({
    text: "Apakah Anda yakin ingin menghapus menu ini?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Ya, hapus!",
    cancelButtonText: "Tidak, batal",
    customClass: {
      confirmButton: "btn fw-bold btn-danger",
      cancelButton: "btn fw-bold btn-active-light-primary",
    },
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await axios.delete(`/menus/${id}`);
        Swal.fire("Berhasil!", "Menu berhasil dihapus.", "success");
        refreshData();
      } catch (error) {
        Swal.fire("Gagal", "Menu tidak dapat dihapus.", "error");
        console.error("Gagal menghapus menu:", error);
      }
    }
  });
};

const openModal = () => {
  const modalEl = document.getElementById("kt_modal_menu");
  if (modalEl) {
    const modal = Modal.getOrCreateInstance(modalEl);
    modal.show();
  }
};

const openAddModal = () => {
  selectedMenu.value = null;
  openModal();
};

const openEditModal = (menu: Menu) => {
  selectedMenu.value = { ...menu };
  openModal();
};

onMounted(() => {
  getMenus();
});
</script>
