<template>
  <div class="card shadow-sm">
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
            placeholder="Cari Nomor Kamar"
          />
        </div>
      </div>
      <div class="card-toolbar">
        <button type="button" class="btn btn-primary" @click="openAddRoomModal">
          <i class="ki-duotone ki-plus fs-2"></i> Tambah Kamar
        </button>
      </div>
    </div>
    <div class="card-body pt-0">
      <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
          <thead>
            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
              <th class="min-w-100px">Gambar</th>
              <th>No. Kamar</th>
              <th>Tipe</th>
              <th>Fasilitas</th>
              <th>Status</th>
              <th>Harga / Malam</th>
              <th>Periode Tersedia</th>
              <th class="text-end min-w-200px">Aksi</th>
            </tr>
          </thead>
          <tbody class="fw-semibold text-gray-600">
            <tr v-if="loading"><td colspan="8" class="text-center py-10">Memuat data...</td></tr>
            <tr v-else-if="filteredRooms.length === 0">
              <td colspan="8" class="text-center py-10">
                {{ searchQuery ? 'Kamar tidak ditemukan.' : 'Tidak ada data kamar.' }}
              </td>
            </tr>
            <tr v-for="room in filteredRooms" :key="room.id">
              <td>
                <div class="symbol symbol-50px">
                  <img :src="room.image_url || '/media/svg/files/blank-image.svg'" alt="Gambar Kamar" class="rounded"/>
                </div>
              </td>
              <td>{{ room.room_number }}</td>
              <td>{{ room.type }}</td>
              <td>
                <div v-if="room.facilities && room.facilities.length > 0" class="d-flex align-items-center gap-2">
                  <div v-for="facility in room.facilities" :key="facility.id"
                       class="symbol symbol-25px" v-tooltip :title="facility.name">
                    <img v-if="facility.icon_url" :src="facility.icon_url" :alt="facility.name" />
                    <span v-else class="symbol-label bg-light-primary text-primary">{{ facility.name.charAt(0) }}</span>
                  </div>
                </div>
                <span v-else>-</span>
              </td>
              <td><span :class="getStatusBadge(room.status)" class="text-capitalize">{{ getStatusLabel(room.status) }}</span></td>
              <td>{{ formatCurrency(room.price_per_night) }}</td>
              <td>
                <span
                  :class="getAvailabilityPeriod(room) === 'Selamanya' ? 'badge badge-light-success' : 'badge badge-light-info'"
                >
                  {{ getAvailabilityPeriod(room) }}
                </span>
              </td>
              <td class="text-end">
                <div class="d-flex justify-content-end flex-shrink-0">
                  <button v-if="room.status === 'available'" @click="openCheckInModal(room)" class="btn btn-sm btn-light-success me-2">
                    Check-in
                  </button>
                  <button v-if="room.status === 'occupied'" @click="processCheckout(room)" class="btn btn-sm btn-light-warning me-2">
                    Check-out
                  </button>
                  <button v-if="room.status === 'occupied'" @click="requestCleaning(room)" class="btn btn-icon btn-light-primary btn-sm me-2" title="Minta Dibersihkan">
                    <i class="ki-duotone ki-brush fs-3"></i>
                  </button>
                  <button v-if="room.status === 'needs cleaning' || room.status === 'request cleaning'" @click="markAsClean(room)" class="btn btn-sm btn-light-info me-2">
                    Tandai Bersih
                  </button>
                  <a href="#" @click.prevent="openEditRoomModal(room)" class="btn btn-icon btn-light-primary btn-sm me-2" title="Edit">
                    <i class="ki-duotone ki-notepad-edit fs-3"></i>
                  </a>
                  <a href="#" @click.prevent="deleteRoom(room.id)" class="btn btn-icon btn-light-danger btn-sm" title="Hapus">
                    <i class="ki-duotone ki-trash-square fs-3"></i>
                  </a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <RoomModal :room-data="selectedRoom" @room-updated="refreshData" @close-modal="selectedRoom = null" />
  <CheckInModal :room-data="selectedRoom" @checkin-success="refreshData" @close-modal="selectedRoom = null" />
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import RoomModal from "./RoomModal.vue";
import CheckInModal from "./CheckInModal.vue";

// Interface untuk tipe data
interface Facility {
  id: number;
  name: string;
  icon: string | null;
  icon_url: string | null;
}
interface Room {
  id: number;
  room_number: string;
  type: string;
  status: string;
  price_per_night: number;
  description: string | null;
  image: string | null;
  image_url: string | null;
  tersedia_mulai: string | null;
  tersedia_sampai: string | null;
  facilities: Facility[];
}

// --- FUNGSI BANTUAN ---
const formatDateSimple = (dateString: string | null): string => {
    if (!dateString) return '';
    const options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};
const getAvailabilityPeriod = (room: Room): string => {
    const start = formatDateSimple(room.tersedia_mulai);
    const end = formatDateSimple(room.tersedia_sampai);
    if (start && end) return `${start} - ${end}`;
    if (start) return `Mulai ${start}`;
    if (end) return `Hingga ${end}`;
    return 'Selamanya';
};
const formatCurrency = (value: number) => {
  if (isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};
const getStatusBadge = (status: string) => {
  const statusMap = {
    available: 'badge badge-light-success',
    occupied: 'badge badge-light-danger',
    maintenance: 'badge badge-light-warning',
    'needs cleaning': 'badge badge-light-info',
    'request cleaning': 'badge badge-light-primary',
  };
  return statusMap[status] || 'badge badge-light';
};
const getStatusLabel = (status: string) => {
    const labelMap = {
      'needs cleaning': 'Perlu Dibersihkan',
      'request cleaning': 'Minta Dibersihkan',
    };
    const defaultLabel = status.charAt(0).toUpperCase() + status.slice(1);
    return labelMap[status] || defaultLabel;
};

// --- STATE (VARIABEL REAKTIF) ---
const rooms = ref<Room[]>([]);
const loading = ref(true);
const selectedRoom = ref<Room | null>(null);
const searchQuery = ref("");

const filteredRooms = computed(() => {
  if (!searchQuery.value) {
    return rooms.value;
  }
  return rooms.value.filter((room) =>
    room.room_number.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

// --- FUNGSI-FUNGSI UTAMA ---
const getRooms = async () => {
  try {
    loading.value = true;
    const { data } = await ApiService.get("/rooms");
    rooms.value = data;
  } catch (error) {
    rooms.value = [];
    Swal.fire("Error", "Gagal memuat data kamar.", "error");
  } finally {
    loading.value = false;
  }
};

const refreshData = () => getRooms();

const openAddRoomModal = () => {
  selectedRoom.value = null;
  const modalEl = document.getElementById("kt_modal_room");
  if (modalEl) new Modal(modalEl).show();
};

const openEditRoomModal = (room: Room) => {
  selectedRoom.value = { ...room };
  const modalEl = document.getElementById("kt_modal_room");
  if (modalEl) new Modal(modalEl).show();
};

const openCheckInModal = (room: Room) => {
  selectedRoom.value = { ...room };
  const modalEl = document.getElementById("kt_modal_check_in");
  if (modalEl) new Modal(modalEl).show();
};

const deleteRoom = (id: number) => {
  Swal.fire({
    text: "Apakah Anda yakin ingin menghapus kamar ini?",
    icon: "warning",
    showCancelButton: true,
    buttonsStyling: false,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    customClass: { confirmButton: "btn fw-bold btn-danger", cancelButton: "btn fw-bold btn-active-light-primary" },
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await ApiService.delete(`/rooms/${id}`);
        Swal.fire("Berhasil!", "Data kamar telah dihapus.", "success");
        refreshData();
      } catch (error: any) {
        const message = error.response?.data?.message || 'Gagal menghapus data kamar.';
        Swal.fire("Error!", message, "error");
      }
    }
  });
};

const processCheckout = async (room: Room) => {
  Swal.fire({
    text: `Apakah Anda yakin ingin melakukan check-out untuk kamar ${room.room_number}?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Ya, Check-out",
    cancelButtonText: "Batal",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await ApiService.post(`/check-out/${room.id}`, {});
        Swal.fire("Berhasil!", `Kamar ${room.room_number} telah berhasil check-out.`, "success");
        refreshData();
      } catch (error: any) {
        const message = error.response?.data?.message || "Gagal melakukan check-out.";
        Swal.fire("Error!", message, "error");
      }
    }
  });
};

const requestCleaning = async (room: Room) => {
  Swal.fire({
    text: `Ubah status kamar ${room.room_number} menjadi "Minta Dibersihkan"?`,
    icon: "info",
    showCancelButton: true,
    confirmButtonText: "Ya, Ubah Status",
    cancelButtonText: "Batal",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await ApiService.post(`/rooms/${room.id}/request-cleaning`, {});
        Swal.fire("Berhasil!", `Status kamar ${room.room_number} telah diubah.`, "success");
        refreshData();
      } catch (error: any) {
        const message = error.response?.data?.message || "Gagal mengubah status.";
        Swal.fire("Error!", message, "error");
      }
    }
  });
};

const markAsClean = async (room: Room) => {
  Swal.fire({
    text: `Apakah Anda yakin ingin menandai kamar ${room.room_number} sudah bersih?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Ya, Sudah Bersih",
    cancelButtonText: "Batal",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await ApiService.post(`/rooms/${room.id}/mark-as-clean`, {});
        Swal.fire("Berhasil!", `Kamar ${room.room_number} telah ditandai bersih.`, "success");
        refreshData();
      } catch (error: any) {
        const message = error.response?.data?.message || "Gagal menandai kamar bersih.";
        Swal.fire("Error!", message, "error");
      }
    }
  });
};

// --- LIFECYCLE HOOK ---
onMounted(() => {
  getRooms();
});
</script>
