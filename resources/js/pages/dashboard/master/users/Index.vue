<template>
  <Form
    v-if="isFormVisible"
    :selected="selectedUserUuid"
    @close="isFormVisible = false"
    @refresh="refreshTable"
  />

  <div class="card shadow-sm" v-else>
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
            placeholder="Cari User"
          />
        </div>
      </div>
      <div class="card-toolbar">
        <button type="button" class="btn btn-primary" @click="openAddForm">
          <i class="ki-duotone ki-plus fs-2"></i> Tambah User
        </button>
      </div>
    </div>
    <div class="card-body pt-0">
      <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
          <thead>
            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
              <th>User</th>
              <th>Role</th>
              <th>Telepon</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody class="fw-semibold text-gray-600">
            <tr v-if="loading"><td colspan="4" class="text-center py-10">Memuat data...</td></tr>
            <tr v-else-if="filteredUsers.length === 0">
              <td colspan="4" class="text-center py-10">
                {{ searchQuery ? 'User tidak ditemukan.' : 'Tidak ada data user.' }}
              </td>
            </tr>
            <tr v-for="user in filteredUsers" :key="user.uuid">
              <td class="d-flex align-items-center">
                <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                    <div class="symbol-label">
                        <img :src="user.photo_url || '/media/svg/avatars/blank.svg'" :alt="user.name" class="w-100" />
                    </div>
                </div>
                <div class="d-flex flex-column">
                    <span class="text-gray-800 mb-1">{{ user.name }}</span>
                    <span>{{ user.email }}</span>
                </div>
              </td>
              <td>
                <span class="badge badge-light-primary">{{ user.roles[0]?.full_name || 'N/A' }}</span>
              </td>
              <td>{{ user.phone || '-' }}</td>
              <td class="text-end">
                <button @click="openEditForm(user)" class="btn btn-icon btn-light-primary btn-sm me-2" title="Edit">
                  <i class="ki-duotone ki-pencil fs-3"></i>
                </button>
                <button @click="deleteUser(user.uuid)" class="btn btn-icon btn-light-danger btn-sm" title="Hapus">
                  <i class="ki-duotone ki-trash fs-3"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import Form from "./Form.vue"; // Pastikan path ke Form.vue sudah benar

// Definisikan tipe data secara lokal
interface Role {
  id: number;
  name: string;
  full_name: string;
}
interface User {
  id: number;
  uuid: string;
  name: string;
  email: string;
  phone: string | null;
  photo_url: string | null;
  roles: Role[];
}

// State Management
const users = ref<User[]>([]);
const loading = ref(true);
const isFormVisible = ref(false);
const selectedUserUuid = ref<string | null>(null);
const searchQuery = ref("");

// Fungsi untuk mengambil data user dari API
const fetchUsers = async () => {
  try {
    loading.value = true;
    const { data } = await ApiService.get("/master/users");
    users.value = data.data || data; 
  } catch (error) {
    Swal.fire({
        text: "Gagal memuat data user.", 
        icon: "error",
        buttonsStyling: false,
        confirmButtonText: "Ok",
        customClass: { confirmButton: "btn btn-primary" },
    });
  } finally {
    loading.value = false;
  }
};

// Computed property untuk filtering berdasarkan pencarian
const filteredUsers = computed(() => {
  if (!searchQuery.value) {
    return users.value;
  }
  return users.value.filter(user =>
    user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    user.email.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

// Fungsi untuk me-refresh tabel (dipanggil dari Form.vue)
const refreshTable = () => {
  isFormVisible.value = false;
  fetchUsers();
};

// Fungsi untuk membuka form dalam mode 'Tambah'
const openAddForm = () => {
  selectedUserUuid.value = null;
  isFormVisible.value = true;
};

// Fungsi untuk membuka form dalam mode 'Edit'
const openEditForm = (user: User) => {
  selectedUserUuid.value = user.uuid;
  isFormVisible.value = true;
};

// Fungsi untuk menghapus user
const deleteUser = (uuid: string) => {
  Swal.fire({
    text: "Apakah Anda yakin ingin menghapus user ini?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus!",
    cancelButtonText: "Batal",
    customClass: {
        confirmButton: "btn btn-danger",
        cancelButton: "btn btn-light"
    }
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await ApiService.delete(`/master/users/${uuid}`);
        Swal.fire("Berhasil!", "User telah dihapus.", "success");
        fetchUsers(); // Refresh tabel setelah hapus
      } catch (error: any) {
        Swal.fire("Gagal", error.response?.data?.message || "Terjadi kesalahan.", "error");
      }
    }
  });
};

onMounted(fetchUsers);
</script>