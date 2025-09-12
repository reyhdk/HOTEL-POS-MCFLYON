<template>
  <div class="card">
    <div class="card-header border-0 pt-6">
      <div class="card-title">
        </div>
      <div class="card-toolbar">
        <button type="button" class="btn btn-primary" @click="openAddModal">
          <i class="ki-duotone ki-plus fs-2"></i> Tambah Fasilitas
        </button>
      </div>
    </div>
    <div class="card-body pt-0">
      <table class="table align-middle table-row-dashed fs-6 gy-5">
        <thead>
          <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
            <th>Nama Fasilitas</th>
            <th>Ikon</th>
            <th>Deskripsi</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody class="fw-semibold text-gray-600">
          <tr v-if="loading"><td colspan="4" class="text-center">Memuat data...</td></tr>
          <tr v-else-if="facilities.length === 0"><td colspan="4" class="text-center">Tidak ada data fasilitas.</td></tr>
          <tr v-for="facility in facilities" :key="facility.id">
            <td>{{ facility.name }}</td>
            
            <td>
              <div v-if="facility.icon_url" class="symbol symbol-30px">
                <img :src="facility.icon_url" :alt="facility.name" />
              </div>
              <span v-else>-</span>
            </td>
            <td>{{ facility.description || '-' }}</td>
            <td class="text-end">
              <a href="#" @click.prevent="openEditModal(facility)" class="btn btn-icon btn-light-primary btn-sm me-2" title="Edit">
                <i class="ki-duotone ki-notepad-edit fs-3"></i>
              </a>
              <a href="#" @click.prevent="deleteFacility(facility.id)" class="btn btn-icon btn-light-danger btn-sm" title="Hapus">
                <i class="ki-duotone ki-trash-square fs-3"></i>
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <FacilityModal :facility-data="selectedFacility" @facility-updated="refreshData" />
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "@/libs/axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import FacilityModal from "./FacilityModal.vue";

// [START] INTERFACE YANG DIUBAH
interface Facility {
  id: number;
  name: string;
  icon: string | null;
  description: string | null;
  icon_url: string | null; // Tambahkan properti ini
}
// [END] INTERFACE YANG DIUBAH

const facilities = ref<Facility[]>([]);
const loading = ref(true);
const selectedFacility = ref<Facility | null>(null);

const getFacilities = async () => {
  try {
    loading.value = true;
    const response = await axios.get("/facilities");
    
    if (Array.isArray(response.data)) {
      facilities.value = response.data;
    } else {
      facilities.value = [];
    }

  } catch (error) {
    console.error("Gagal mengambil data fasilitas:", error);
    facilities.value = [];
  } finally {
    loading.value = false;
  }
};

const refreshData = () => {
  getFacilities();
};

const openAddModal = () => {
  selectedFacility.value = null;
  const modalEl = document.getElementById("kt_modal_facility");
  if (modalEl) new Modal(modalEl).show();
};

const openEditModal = (facility: Facility) => {
  selectedFacility.value = { ...facility }; // Gunakan salinan untuk menghindari reaktivitas tak terduga
  const modalEl = document.getElementById("kt_modal_facility");
  if (modalEl) new Modal(modalEl).show();
};

const deleteFacility = (id: number) => {
  Swal.fire({
    text: "Apakah Anda yakin ingin menghapus fasilitas ini?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    customClass: {
      confirmButton: "btn fw-bold btn-danger",
      cancelButton: "btn fw-bold btn-active-light-primary",
    },
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await axios.delete(`/facilities/${id}`);
        Swal.fire("Berhasil!", "Data fasilitas telah dihapus.", "success");
        refreshData();
      } catch (error) {
        Swal.fire("Error!", "Gagal menghapus data.", "error");
      }
    }
  });
};

onMounted(() => {
  getFacilities();
});
</script>