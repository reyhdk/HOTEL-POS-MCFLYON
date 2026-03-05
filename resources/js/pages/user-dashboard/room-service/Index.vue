<template>
  <div>
    <h1 class="mb-5">Layanan Kamar</h1>
    <p class="fs-6 text-muted mb-8">
      Butuh sesuatu? Pilih layanan di bawah ini dan kami akan segera membantu Anda.
    </p>

    <!-- Loading State -->
    <div v-if="isLoading" class="d-flex justify-content-center py-10">
      <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <!-- Belum Check In State -->
    <div v-else-if="!activeRoom" class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6">
      <i class="ki-duotone ki-information fs-2tx text-warning me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
      <div class="d-flex flex-stack flex-grow-1">
        <div class="fw-semibold">
          <h4 class="text-gray-900 fw-bold">Fitur Belum Tersedia</h4>
          <div class="fs-6 text-gray-700">Layanan kamar hanya dapat diakses setelah Anda melakukan check-in.</div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
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
        
        <!-- Grid Daftar Layanan dari Database -->
        <div v-for="service in availableServices" :key="service.id" class="col-md-6 col-xl-4">
          <div @click="openRequestModal(service)" role="button"
            class="card card-stretch border border-2 border-gray-300 border-hover-primary shadow-sm overflow-hidden h-100">
            <div class="card-body d-flex align-items-center p-5">
              
              <!-- Gambar atau Icon -->
              <div class="symbol symbol-60px me-5 rounded-3 bg-light-primary d-flex justify-content-center align-items-center flex-shrink-0">
                <img v-if="service.photo_url" :src="service.photo_url" class="w-100 h-100 object-fit-cover rounded-3" :alt="service.name" />
                <i v-else class="ki-duotone ki-box fs-2x text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
              </div>

              <!-- Informasi Layanan -->
              <span class="d-flex flex-column flex-grow-1">
                <span class="fs-5 fw-bold text-gray-800 mb-1">{{ service.name }}</span>
                <span class="fs-8 text-muted mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                  {{ service.description || 'Pesan layanan ini ke kamar Anda.' }}
                </span>
                <div>
                  <span class="badge badge-light-primary fs-9 fw-bold px-2 py-1">{{ service.category }}</span>
                </div>
              </span>

            </div>
          </div>
        </div>

        <!-- FITUR BARU: Card Permintaan Khusus -->
        <div class="col-md-6 col-xl-4">
          <div @click="openCustomRequestModal()" role="button"
            class="card card-stretch bg-light-info border border-2 border-info border-dashed border-hover-info shadow-sm overflow-hidden h-100" style="transition: all 0.3s ease;">
            <div class="card-body d-flex align-items-center p-5">
              <div class="symbol symbol-60px me-5 rounded-3 bg-info d-flex justify-content-center align-items-center flex-shrink-0">
                <i class="ki-duotone ki-message-text-2 fs-1 text-white"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
              </div>
              <span class="d-flex flex-column flex-grow-1">
                <span class="fs-5 fw-bold text-info mb-1">Permintaan Khusus</span>
                <span class="fs-8 text-muted mb-2">Tuliskan kebutuhan lain secara manual di sini.</span>
                <div>
                  <span class="badge badge-info fs-9 fw-bold px-2 py-1">Custom Request</span>
                </div>
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

interface Room { id: number; room_number: string; }

interface ServiceItem {
  id: number;
  name: string;
  category: string;
  description: string;
  max_quantity: number;
  photo_url: string | null;
  is_active: boolean;
}

const isLoading = ref(true);
const activeRoom = ref<Room | null>(null);
const guestName = ref<string>("");
const availableServices = ref<ServiceItem[]>([]); 

const fetchInitialData = async () => {
  isLoading.value = true;
  try {
    const profileRes = await ApiService.get("/guest/profile");
    activeRoom.value = profileRes.data.active_room;
    guestName.value = profileRes.data.guest_details.name;

    const servicesRes = await ApiService.get("/guest/service-items?is_active=true");
    availableServices.value = servicesRes.data.data || servicesRes.data; 
  } catch (error) {
    console.error("Gagal mengambil data inisial.", error);
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

const openRequestModal = async (service: ServiceItem) => {
  let modalConfig: any = {
    title: `Permintaan: ${service.name}`,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Kirim Permintaan',
    cancelButtonText: 'Batal',
    customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-light" }
  };

  const isTimeRequest = service.name.toLowerCase().includes('pembersihan') || service.category.toLowerCase().includes('housekeeping');

  if (isTimeRequest) {
    modalConfig.html = `
      <label for="swal-input-time" class="form-label">Pukul berapa kamar ingin dibersihkan?</label>
      <input type="time" id="swal-input-time" class="form-control mb-3">
      <textarea id="swal-input-notes" class="form-control" placeholder="Catatan tambahan (Opsional)"></textarea>
    `;
    modalConfig.preConfirm = () => {
      const timeEl = document.getElementById("swal-input-time") as HTMLInputElement;
      const notesEl = document.getElementById("swal-input-notes") as HTMLTextAreaElement;
      if (!timeEl.value) { Swal.showValidationMessage("Harap tentukan waktu pembersihan."); return false; }
      return { time: timeEl.value, notes: notesEl.value };
    };
  } else {
    modalConfig.html = `
      <label for="swal-input-quantity" class="form-label">Jumlah (Maksimal: ${service.max_quantity})</label>
      <input type="number" id="swal-input-quantity" class="form-control" value="1" min="1" max="${service.max_quantity}">
      <textarea id="swal-input-notes" class="form-control mt-3" placeholder="Catatan tambahan (Opsional)"></textarea>
    `;
    modalConfig.preConfirm = () => {
      const quantityEl = document.getElementById("swal-input-quantity") as HTMLInputElement;
      const notesEl = document.getElementById("swal-input-notes") as HTMLTextAreaElement;
      const quantity = parseInt(quantityEl.value);
      
      if (!quantity || quantity < 1) { Swal.showValidationMessage("Jumlah harus diisi dan minimal 1."); return false; }
      if (quantity > service.max_quantity) { Swal.showValidationMessage(`Jumlah melebihi batas maksimal (${service.max_quantity}).`); return false; }
      return { quantity: quantity, notes: notesEl.value };
    };
  }

  const { value: formValues } = await Swal.fire(modalConfig);

  if (formValues) {
    const payload = {
      service_name: service.name,
      quantity: formValues.quantity || 1, 
      notes: formValues.notes,
      cleaning_time: formValues.time || null, 
    };
    submitRequest(payload);
  }
};

// FITUR BARU: Modal Permintaan Khusus
const openCustomRequestModal = async () => {
  const { value: formValues } = await Swal.fire({
    title: 'Permintaan Khusus',
    html: `
      <div class="text-start mb-2">
        <label for="swal-input-name" class="form-label fw-bold">Apa yang Anda butuhkan?</label>
        <input type="text" id="swal-input-name" class="form-control mb-4" placeholder="Contoh: Setrika, Tambahan Guling, dll">
      </div>
      <div class="text-start mb-2">
        <label for="swal-input-qty" class="form-label fw-bold">Jumlah</label>
        <input type="number" id="swal-input-qty" class="form-control mb-4" value="1" min="1">
      </div>
      <div class="text-start">
        <label for="swal-input-notes" class="form-label fw-bold">Keterangan (Opsional)</label>
        <textarea id="swal-input-notes" class="form-control" placeholder="Tuliskan keterangan detail di sini..."></textarea>
      </div>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Kirim Permintaan',
    cancelButtonText: 'Batal',
    customClass: { confirmButton: "btn btn-info", cancelButton: "btn btn-light" },
    preConfirm: () => {
      const nameEl = document.getElementById("swal-input-name") as HTMLInputElement;
      const qtyEl = document.getElementById("swal-input-qty") as HTMLInputElement;
      const notesEl = document.getElementById("swal-input-notes") as HTMLTextAreaElement;

      if (!nameEl.value.trim()) {
        Swal.showValidationMessage("Nama barang atau permintaan wajib diisi.");
        return false;
      }

      const quantity = parseInt(qtyEl.value);
      if (!quantity || quantity < 1) {
        Swal.showValidationMessage("Jumlah minimal adalah 1.");
        return false;
      }

      return {
        name: nameEl.value.trim(),
        quantity: quantity,
        notes: notesEl.value.trim()
      };
    }
  });

  if (formValues) {
    const payload = {
      service_name: formValues.name, // Dikirim sebagai custom string
      quantity: formValues.quantity,
      notes: formValues.notes,
      cleaning_time: null
    };
    submitRequest(payload);
  }
};

onMounted(fetchInitialData);
</script>