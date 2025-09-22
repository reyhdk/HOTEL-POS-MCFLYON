<template>
  <Form
    v-if="isFormVisible"
    :selected="selectedId"
    @close="isFormVisible = false"
    @refresh="refreshTable"
  />

  <div class="card shadow-sm" v-else>
    <div class="card-header">
      <h3 class="card-title">Manajemen Role</h3>
      <div class="card-toolbar">
        <button class="btn btn-primary btn-sm" @click="openAddForm">
          <i class="ki-duotone ki-plus fs-2"></i>
          Tambah Role
        </button>
      </div>
    </div>
    <div class="card-body">
      <div v-if="loading" class="text-center">Memuat data...</div>
      <table v-else class="table table-row-dashed">
        <thead>
          <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
            <th>Nama Role</th>
            <th>Nama Tampilan</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="role in roles" :key="role.id">
            <td>{{ role.name }}</td>
            <td>{{ role.full_name }}</td>
            <td class="text-end">
              <button @click="openEditForm(role)" class="btn btn-sm btn-light-primary me-2">Edit</button>
              <button @click="deleteRole(role.id)" class="btn btn-sm btn-light-danger">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import Form from "./Form.vue"; // Pastikan path ini benar

interface Role {
  id: number;
  name: string;
  full_name: string;
}

const roles = ref<Role[]>([]);
const loading = ref(true);
const isFormVisible = ref(false);
const selectedId = ref<number | null>(null);

const fetchRoles = async () => {
  try {
    loading.value = true;
    const { data } = await ApiService.get("/master/roles");
    roles.value = data.data; // Sesuaikan dengan struktur paginasi Laravel
  } finally {
    loading.value = false;
  }
};

const refreshTable = () => {
    isFormVisible.value = false;
    fetchRoles();
};

const openAddForm = () => {
    selectedId.value = null;
    isFormVisible.value = true;
};

const openEditForm = (role: Role) => {
    selectedId.value = role.id;
    isFormVisible.value = true;
};

const deleteRole = (id: number) => {
    Swal.fire({
        text: "Apakah Anda yakin ingin menghapus role ini?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Hapus",
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await ApiService.delete(`/master/roles/${id}`);
                Swal.fire("Berhasil!", "Role telah dihapus.", "success");
                fetchRoles();
            } catch (error) {
                Swal.fire("Gagal", "Terjadi kesalahan saat menghapus.", "error");
            }
        }
    });
};

onMounted(fetchRoles);
</script>
