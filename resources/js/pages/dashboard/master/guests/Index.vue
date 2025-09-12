<template>
  <div class="card">
    <div class="card-header border-0 pt-6">
      <div class="card-title">
        <h2>Daftar Tamu</h2>
      </div>
      <div class="card-toolbar">
        <button type="button" class="btn btn-primary" @click="openAddModal">
          <i class="ki-duotone ki-plus fs-2"></i> Tambah Tamu
        </button>
      </div>
    </div>
    <div class="card-body pt-0">
      <table class="table align-middle table-row-dashed fs-6 gy-5">
        <thead>
          <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
            <th>Nama Tamu</th>
            <th>Email</th>
            <th>No. Telepon</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody class="fw-semibold text-gray-600">
          <tr v-if="loading"><td colspan="4" class="text-center">Memuat data tamu...</td></tr>
          <tr v-else-if="guests.length === 0"><td colspan="4" class="text-center">Belum ada data tamu.</td></tr>
          <tr v-for="guest in guests" :key="guest.id">
            <td>{{ guest.name }}</td>
            <td>{{ guest.email || '-' }}</td>
            <td>{{ guest.phone_number || '-' }}</td>
            <td class="text-end">
              <a href="#" @click="openEditModal(guest)" class="btn btn-icon btn-light-primary btn-sm me-2">
                <i class="ki-duotone ki-pencil fs-3"></i>
              </a>
              <a href="#" @click="deleteGuest(guest.id)" class="btn btn-icon btn-light-danger btn-sm">
                <i class="ki-duotone ki-trash fs-3"></i>
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <GuestModal :guest-data="selectedGuest" @guest-updated="refreshData" />
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import GuestModal from "./GuestModal.vue";


interface Guest {
  id: number;
  name: string;
  email: string | null;
  phone_number: string | null;
  address: string | null;
}

const guests = ref<Guest[]>([]);
const loading = ref(true);
const selectedGuest = ref<Guest | null>(null);

const getGuests = async () => {
  try {
    loading.value = true;
    const response = await axios.get("/guests");
    guests.value = response.data;
  } catch (error) {
    console.error("Gagal mengambil data tamu:", error);
  } finally {
    loading.value = false;
  }
};

const refreshData = () => getGuests();

const deleteGuest = (id: number) => {
  Swal.fire({
    text: "Anda yakin ingin menghapus tamu ini?",
    icon: "warning",
    showCancelButton: true,
    buttonsStyling: false,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    customClass: { confirmButton: "btn fw-bold btn-danger", cancelButton: "btn fw-bold btn-active-light-primary" },
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await axios.delete(`/guests/${id}`);
        Swal.fire("Berhasil!", "Data tamu telah dihapus.", "success");
        refreshData();
      } catch (error) {
        Swal.fire("Error!", "Gagal menghapus data tamu.", "error");
      }
    }
  });
};

const openAddModal = () => {
  selectedGuest.value = null;
  const modalEl = document.getElementById("kt_modal_guest");
  if (modalEl) new Modal(modalEl).show();
};

const openEditModal = (guest: Guest) => {
  selectedGuest.value = guest;
  const modalEl = document.getElementById("kt_modal_guest");
  if (modalEl) new Modal(modalEl).show();
};

onMounted(() => {
  getGuests();
});
</script>