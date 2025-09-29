<template>
  <div>
    <h1 class="mb-5">Layanan Kamar</h1>
    <p class="fs-6 text-muted mb-8">
      Butuh sesuatu? Pilih layanan di bawah ini dan kami akan segera membantu Anda.
    </p>

    <div v-if="isLoading" class="d-flex justify-content-center py-10">
      <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else-if="!activeRoom" class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6">
      <i class="ki-duotone ki-information fs-2tx text-warning me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
      <div class="d-flex flex-stack flex-grow-1">
        <div class="fw-semibold">
          <h4 class="text-gray-900 fw-bold">Fitur Belum Tersedia</h4>
          <div class="fs-6 text-gray-700">Layanan kamar hanya dapat diakses setelah Anda melakukan check-in.</div>
        </div>
      </div>
    </div>

    <div v-else>
      <div class="card card-flush shadow-sm mb-10">
        <div class="card-body">
          <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div class="d-flex align-items-center mb-3 mb-md-0">
              <i class="ki-duotone ki-profile-circle fs-2x text-primary me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
              <div>
                <div class="fs-7 text-muted">Permintaan dari</div>
                <div class="fs-5 fw-bold">{{ guestName }}</div>
              </div>
            </div>
            <div class="d-flex align-items-center">
               <i class="ki-duotone ki-key fs-2x text-primary me-4"><span class="path1"></span><span class="path2"></span></i>
              <div>
                <div class="fs-7 text-muted">Nomor Kamar</div>
                <div class="fs-5 fw-bold">{{ activeRoom.room_number }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-6 g-xl-9">
        <div v-for="service in availableServices" :key="service.name" class="col-md-6 col-xl-4">
          <div @click="openRequestModal(service)" role="button"
            class="card card-stretch border border-2 border-gray-300 border-hover-primary shadow-sm">
            <div class="card-body d-flex align-items-center">
              <span class="d-flex flex-shrink-0 me-6">
                <i :class="service.icon" class="text-primary fs-3x"></i>
              </span>
              <span class="d-flex flex-column">
                <span class="fs-5 fw-bold text-gray-800 mb-1">{{ service.name }}</span>
                <span class="fs-7 text-muted">{{ service.description }}</span>
              </span>
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
  request_type: 'quantity' | 'time'; // Tipe permintaan
}

// --- STATE MANAGEMENT ---
const isLoading = ref(true);
const activeRoom = ref<Room | null>(null);
const guestName = ref<string>("");

// --- DAFTAR LAYANAN (Diperbarui dengan request_type) ---
const availableServices: Service[] = [
  {
    name: "Handuk Tambahan",
    description: "Minta handuk bersih tambahan.",
    icon: "ki-duotone ki-menu",
    request_type: 'quantity',
  },
  {
    name: "Air Mineral",
    description: "Minta botol air mineral tambahan.",
    icon: "ki-duotone ki-cup",
    request_type: 'quantity',
  },
  {
    name: "Pembersihan Kamar",
    description: "Jadwalkan pembersihan untuk kamar Anda.",
    icon: "ki-duotone ki-abstract-29",
    request_type: 'time', // <-- Khusus untuk pembersihan kamar
  },
  {
    name: "Layanan Laundry",
    description: "Pengambilan pakaian untuk dicuci.",
    icon: "ki-duotone ki-bucket",
    request_type: 'quantity',
  },
  {
    name: "Peminjaman Setrika",
    description: "Pinjam setrika dan meja setrika.",
    icon: "ki-duotone ki-element-9",
    request_type: 'quantity',
  },
  {
    name: "Lainnya",
    description: "Tulis permintaan spesifik Anda.",
    icon: "ki-duotone ki-message-edit",
    request_type: 'quantity',
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
  let modalConfig: any = {
    title: `Permintaan: ${service.name}`,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Kirim Permintaan',
    cancelButtonText: 'Batal',
    customClass: {
      confirmButton: "btn btn-primary",
      cancelButton: "btn btn-light",
    }
  };

  // Logika kondisional untuk tampilan popup
  if (service.request_type === 'time') {
    modalConfig.html = `
      <label for="swal-input-time" class="form-label">Pukul berapa kamar ingin dibersihkan?</label>
      <input type="time" id="swal-input-time" class="form-control mb-3">
      <textarea id="swal-input-notes" class="form-control" placeholder="Catatan tambahan"></textarea>
    `;
    modalConfig.preConfirm = () => {
      const timeEl = document.getElementById("swal-input-time") as HTMLInputElement;
      const notesEl = document.getElementById("swal-input-notes") as HTMLTextAreaElement;
      if (!timeEl.value) {
        Swal.showValidationMessage("Harap tentukan waktu pembersihan.");
        return false;
      }
      return { time: timeEl.value, notes: notesEl.value };
    };
  } else {
    modalConfig.html = `
      <label for="swal-input-quantity" class="form-label">Jumlah</label>
      <input type="number" id="swal-input-quantity" class="form-control" value="1" min="1">
      <textarea id="swal-input-notes" class="form-control mt-3" placeholder="Catatan tambahan "></textarea>
    `;
    modalConfig.preConfirm = () => {
      const quantityEl = document.getElementById("swal-input-quantity") as HTMLInputElement;
      const notesEl = document.getElementById("swal-input-notes") as HTMLTextAreaElement;
      const quantity = parseInt(quantityEl.value);
      if (!quantity || quantity < 1) {
        Swal.showValidationMessage("Jumlah harus diisi dan minimal 1.");
        return false;
      }
      return { quantity: quantity, notes: notesEl.value };
    };
  }

  const { value: formValues } = await Swal.fire(modalConfig);

  if (formValues) {
    const payload = {
      service_name: service.name,
      quantity: formValues.quantity || 1, // Default 1 jika tidak ada
      notes: formValues.notes,
      cleaning_time: formValues.time || null, // Tambahkan waktu pembersihan
    };
    submitRequest(payload);
  }
};

// --- LIFECYCLE HOOK ---
onMounted(fetchGuestProfile);
</script>
