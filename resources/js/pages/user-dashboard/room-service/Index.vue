<template>
  <div>
    <h1 class="mb-5">Permintaan Layanan Kamar</h1>

    <div v-if="isLoading" class="d-flex justify-content-center py-10">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else-if="!activeRoom" class="alert alert-warning">
      Fitur ini hanya tersedia untuk tamu yang sedang check-in.
    </div>

    <div v-else>
       <div class="mb-8 p-4 bg-light-primary rounded d-flex justify-content-between align-items-center">
          <div>
            <span class="fw-semibold text-muted">Permintaan dari:</span>
            <span class="fw-bold fs-5 d-block">{{ guestName }}</span>
          </div>
          <div>
            <span class="fw-semibold text-muted">Untuk Kamar:</span>
            <span class="badge badge-primary fs-5 d-block mt-1">{{ activeRoom.room_number }}</span>
          </div>
      </div>

      <div class="row g-5">
        <div
          v-for="service in availableServices"
          :key="service.name"
          class="col-md-6 col-lg-4"
        >
          <div
            class="card card-flush shadow-sm h-100"
            @click="openRequestModal(service)"
            role="button"
          >
            <div class="card-body text-center">
              <i :class="service.icon" class="text-primary fs-3x mb-5"></i>
              <h4 class="card-title">{{ service.name }}</h4>
              <p class="card-text text-muted">{{ service.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

// --- INTERFACES (Tipe Data) ---
interface Room {
  id: number;
  room_number: string;
}
interface Service {
  name: string;
  description: string;
  icon: string;
}

// --- STATE MANAGEMENT ---
const isLoading = ref(true);
const activeRoom = ref<Room | null>(null);
const guestName = ref<string>("");

// --- DAFTAR LAYANAN (Bisa juga diambil dari API jika dinamis) ---
const availableServices: Service[] = [
  {
    name: "Handuk Tambahan",
    description: "Minta handuk bersih tambahan.",
    icon: "ki-duotone ki-menu",
  },
  {
    name: "Air Mineral",
    description: "Minta botol air mineral tambahan.",
    icon: "ki-duotone ki-cup",
  },
  {
    name: "Pembersihan Kamar",
    description: "Jadwalkan pembersihan untuk kamar Anda.",
    icon: "ki-duotone ki-abstract-29",
  },
  {
    name: "Layanan Laundry",
    description: "Pengambilan pakaian untuk dicuci.",
    icon: "ki-duotone ki-bucket",
  },
  {
    name: "Peminjaman Setrika",
    description: "Pinjam setrika dan meja setrika.",
    icon: "ki-duotone ki-element-9",
  },
  {
    name: "Lainnya",
    description: "Tulis permintaan spesifik Anda.",
    icon: "ki-duotone ki-message-edit",
  },
];

// --- FUNGSI-FUNGSI API ---
const fetchGuestProfile = async () => {
  try {
    const response = await ApiService.get("/guest/profile");
    activeRoom.value = response.data.active_room;
    guestName.value = response.data.guest_details.name;
  } catch (error) {
    console.error("Gagal mengambil profil tamu, mungkin belum check-in.", error);
  } finally {
    isLoading.value = false;
  }
};

const submitRequest = async (payload: any) => {
  try {
    await ApiService.post("/guest/service-requests", payload);
    Swal.fire({
      text: "Permintaan Anda telah berhasil dikirim!",
      icon: "success",
      buttonsStyling: false,
      confirmButtonText: "Selesai",
      customClass: { confirmButton: "btn btn-primary" },
    });
  } catch (error: any) {
    const message = error.response?.data?.message || "Terjadi kesalahan.";
    Swal.fire({
      text: message,
      icon: "error",
      buttonsStyling: false,
      confirmButtonText: "Coba Lagi",
      customClass: { confirmButton: "btn btn-danger" },
    });
  }
};

const openRequestModal = async (service: Service) => {
  const { value: formValues } = await Swal.fire({
    title: `Permintaan: ${service.name}`,
    html: `
      <input type="number" id="swal-input-quantity" class="form-control" value="1" min="1" placeholder="Jumlah">
      <textarea id="swal-input-notes" class="form-control mt-3" placeholder="Catatan tambahan (opsional)"></textarea>
    `,
    focusConfirm: false,
    preConfirm: () => {
      const quantityEl = document.getElementById("swal-input-quantity") as HTMLInputElement;
      const notesEl = document.getElementById("swal-input-notes") as HTMLTextAreaElement;
      const quantity = parseInt(quantityEl.value);

      if (!quantity || quantity < 1) {
        Swal.showValidationMessage("Jumlah harus diisi dan minimal 1.");
        return false;
      }
      
      return {
        quantity: quantity,
        notes: notesEl.value,
      };
    },
    showCancelButton: true,
    confirmButtonText: 'Kirim Permintaan',
    cancelButtonText: 'Batal',
    customClass: {
      confirmButton: "btn btn-primary",
      cancelButton: "btn btn-light",
    }
  });

  if (formValues) {
    const payload = {
      service_name: service.name,
      quantity: formValues.quantity,
      notes: formValues.notes,
    };
    submitRequest(payload);
  }
};

// --- LIFECYCLE HOOK ---
onMounted(fetchGuestProfile);
</script>