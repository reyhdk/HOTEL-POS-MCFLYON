<template>
    <div class="d-flex flex-column gap-5">
        <!-- Kartu Statistik -->
        <div class="row g-5 g-xl-8">
            <div v-for="(stat, index) in statistics" :key="index" 
                 class="col-xl-3 col-6 animate-item" :style="`--delay: ${index * 0.1}s`">
                <div class="card bg-body hover-elevate-up border-0 h-100 theme-card shadow-sm"
                     :class="stat.cardClass">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-3">
                            <div :class="`symbol-label bg-light-${stat.color} text-${stat.color} rounded-4`">
                                <i :class="`ki-duotone ${stat.icon} fs-2x text-${stat.color}`">
                                    <span v-for="p in stat.paths" :key="p" :class="`path${p}`"></span>
                                </i>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ stat.value }}</span>
                            <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">{{ stat.label }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar & Filter -->
        <div class="card border-0 shadow-sm theme-card">
            <div class="card-body py-4">
                <div class="d-flex flex-stack flex-wrap gap-4">
                    <!-- Sisi Kiri: Pencarian -->
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-duotone ki-magnifier fs-2 position-absolute ms-4 text-gray-500">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            class="form-control form-control-solid ps-12 w-250px" 
                            placeholder="Cari nomor kamar..." 
                        />
                    </div>

                    <!-- Sisi Kanan: Filter (Dikembalikan seperti semula) -->
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <!-- Filter Status -->
                        <div class="w-180px">
                            <el-select v-model="filterStatus" placeholder="Pilih Status" class="premium-filter-select">
                                <template #prefix>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ki-duotone ki-filter-search fs-3 text-gray-500">
                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                        </i>
                                        <span class="fs-9 fw-bold text-gray-600 text-uppercase ls-1">Status</span>
                                    </div>
                                </template>
                                <el-option label="Semua Status" value="all" />
                                <el-option label="Tersedia" value="available" />
                                <el-option label="Terisi" value="occupied" />
                                <el-option label="Perawatan" value="dirty" />
                            </el-select>
                        </div>

                        <!-- Filter Tipe (Dikembalikan) -->
                        <div class="w-180px">
                            <el-select v-model="filterType" placeholder="Pilih Tipe" class="premium-filter-select">
                                <template #prefix>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ki-duotone ki-category fs-3 text-gray-500">
                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                                        </i>
                                        <span class="fs-9 fw-bold text-gray-600 text-uppercase ls-1">Tipe</span>
                                    </div>
                                </template>
                                <el-option label="Semua Tipe" value="all" />
                                <el-option v-for="type in roomTypes" :key="type" :label="type" :value="type" />
                            </el-select>
                        </div>

                        <!-- Tombol Refresh -->
                        <button class="btn btn-icon btn-light-primary w-40px h-40px rounded hover-scale" @click="refreshData" :disabled="loading" title="Refresh">
                            <i class="ki-duotone ki-arrows-circle fs-2" :class="{ 'spin-anim': loading }">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </button>

                        <!-- Tombol Tambah Kamar -->
                        <button class="btn btn-orange fw-bold hover-scale px-5 shadow-sm d-flex align-items-center gap-2" @click="openAddRoomModal">
                            <i class="ki-duotone ki-plus fs-2 text-white">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i> 
                            Tambah Kamar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid Kamar -->
        <div class="position-relative min-h-400px">
            <div v-if="loading" class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-body bg-opacity-75 rounded-3" style="z-index: 10">
                <div class="d-flex flex-column align-items-center animate-pulse">
                    <span class="spinner-border text-orange mb-3 w-40px h-40px"></span>
                    <span class="text-gray-500 fw-bold">Sinkronisasi data...</span>
                </div>
            </div>

            <div v-else-if="filteredRooms.length === 0" class="d-flex flex-column align-items-center justify-content-center py-15 text-muted bg-white rounded shadow-sm border border-dashed border-gray-300">
                <div class="symbol symbol-100px mb-5 bg-light-orange rounded-circle d-flex align-items-center justify-content-center">
                    <i class="ki-duotone ki-file-sheet fs-4x text-orange"><span class="path1"></span><span class="path2"></span></i>
                </div>
                <span class="fs-4 fw-bold text-gray-800">Tidak ada kamar yang ditemukan.</span>
            </div>

            <div v-else>
                <TransitionGroup name="list-shuffle" tag="div" class="row g-6">
                    <div class="col-md-6 col-lg-4 col-xxl-3 room-item" v-for="room in filteredRooms" :key="room.id">
                        <div class="card h-100 border-0 shadow-sm theme-card hover-elevate-up group-card overflow-hidden">
                            <div class="position-relative h-200px bg-secondary overflow-hidden room-image-container">
                                <img :src="getRoomImage(room)" class="w-100 h-100 object-fit-cover room-img" />
                                <div class="position-absolute top-0 start-0 w-100 h-4px status-border" :class="getRoomStyle(room.status).bg"></div>
                                <div class="position-absolute bottom-0 end-0 m-3 z-index-2">
                                    <div class="backdrop-blur px-3 py-1 rounded-pill shadow-sm">
                                        <span class="fw-bolder fs-7 text-white">Rp {{ formatPrice(room.price_per_night) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-5 d-flex flex-column">
                                <div class="mb-3">
                                    <div class="text-gray-500 fs-9 fw-bold text-uppercase ls-1 mb-1">{{ room.type }}</div>
                                    <h3 class="fs-2 fw-bolder text-gray-900 m-0">No. {{ room.room_number }}</h3>
                                </div>

                                <div class="d-flex align-items-center p-3 rounded mb-4 bg-light-subtle border border-dashed border-gray-300">
                                    <span class="bullet bullet-dot h-10px w-10px me-3 pulse-dot" :class="getRoomStyle(room.status).bg"></span>
                                    <div class="d-flex flex-column w-100">
                                        <span class="fs-7 fw-bold" :class="getRoomStyle(room.status).text">{{ getRoomStatusLabel(room.status) }}</span>
                                        <div v-if="room.status === 'occupied'" class="mt-1">
                                            <div v-if="isIncognito(room)" class="badge badge-light-danger fw-bold fs-9 mb-1">INCOGNITO</div>
                                            <div class="fs-8 fw-bolder text-gray-800 text-truncate">{{ getGuestName(room) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-auto pt-4 border-top border-gray-200 border-opacity-50 d-flex gap-2">
                                    <div class="flex-grow-1">
                                        <button v-if="room.status === 'available'" @click="openCheckInModal(room)" class="btn btn-sm btn-success w-100 fw-bold hover-scale shadow-sm">Check-in</button>
                                        <button v-else-if="room.status === 'occupied'" @click="handleCheckout(room)" class="btn btn-sm btn-danger w-100 fw-bold hover-scale shadow-sm">Check-out</button>
                                        <button v-else @click="handleMarkAsClean(room)" class="btn btn-sm btn-info w-100 fw-bold text-white hover-scale shadow-sm">Selesai</button>
                                    </div>
                                    
                                    <button class="btn btn-sm btn-icon btn-light-warning w-35px rounded hover-scale shadow-xs" @click="openScheduleModal(room)" title="Jadwal Kamar">
                                        <i class="ki-duotone ki-calendar-tick fs-3 text-warning">
                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span>
                                        </i>
                                    </button>

                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-icon btn-light w-35px rounded dropdown-toggle-custom hide-arrow shadow-xs" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ki-duotone ki-dots-square fs-3">
                                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                                            </i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2 theme-dropdown">
                                            <li>
                                                <a href="#" class="dropdown-item rounded px-3 py-2 fw-semibold d-flex align-items-center" @click.prevent="openEditRoomModal(room)">
                                                    <i class="ki-duotone ki-pencil fs-6 me-2 text-primary">
                                                        <span class="path1"></span><span class="path2"></span>
                                                    </i>
                                                    Edit Detail Kamar
                                                </a>
                                            </li>
                                            <li v-if="room.status === 'occupied'">
                                                <a href="#" class="dropdown-item rounded px-3 py-2 fw-semibold d-flex align-items-center" @click.prevent="handleRequestCleaning(room)">
                                                    <i class="ki-duotone ki-brush fs-6 me-2 text-warning">
                                                        <span class="path1"></span><span class="path2"></span>
                                                    </i>
                                                    Request Cleaning
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider opacity-50"></li>
                                            <li>
                                                <a href="#" class="dropdown-item rounded px-3 py-2 fw-semibold text-danger d-flex align-items-center" @click.prevent="handleDeleteRoom(room.id)">
                                                    <i class="ki-duotone ki-trash fs-6 me-2 text-danger">
                                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                                                    </i>
                                                    Hapus Kamar
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>
            </div>
        </div>

        <RoomModal :room-data="selectedRoom" @room-updated="refreshData" @close-modal="selectedRoom = null" />
        <CheckInModal ref="checkInModalRef" :room-data="selectedRoom" @success="refreshData" />
        <RoomScheduleModal ref="scheduleModalRef" />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import { ElMessage } from "element-plus";
import RoomModal from "./RoomModal.vue";
import CheckInModal from "./CheckInModal.vue";
import RoomScheduleModal from "./RoomScheduleModal.vue";

const rooms = ref<any[]>([]);
const loading = ref(true);
const selectedRoom = ref<any>(null);
const searchQuery = ref("");
const filterStatus = ref("all");
const filterType = ref("all");

const checkInModalRef = ref<any>(null);
const scheduleModalRef = ref<any>(null);

const statistics = computed(() => {
    const total = rooms.value.length;
    const available = rooms.value.filter(r => r.status === 'available').length;
    const occupied = rooms.value.filter(r => r.status === 'occupied').length;
    const maintenance = rooms.value.filter(r => ['dirty', 'needs cleaning', 'request cleaning'].includes(r.status)).length;

    return [
        { label: "Total Kamar", value: total, color: "orange", icon: "ki-home", paths: [1, 2], cardClass: "card-stat-orange" },
        { label: "Tersedia", value: available, color: "success", icon: "ki-check-circle", paths: [1, 2] },
        { label: "Terisi", value: occupied, color: "danger", icon: "ki-user", paths: [1, 2] },
        { label: "Pembersihan", value: maintenance, color: "warning", icon: "ki-brush", paths: [1, 2] }
    ];
});

const roomTypes = computed(() => {
    const types = rooms.value.map(r => r.type).filter(t => t);
    return [...new Set(types)];
});

const filteredRooms = computed(() => {
    return rooms.value.filter(room => {
        const matchesSearch = room.room_number.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesType = filterType.value === 'all' || room.type === filterType.value;
        const matchesStatus = filterStatus.value === 'all' || 
            (filterStatus.value === 'dirty' ? ['dirty', 'needs cleaning', 'request cleaning'].includes(room.status) : room.status === filterStatus.value);
        
        return matchesSearch && matchesType && matchesStatus;
    });
});

const refreshData = async () => {
    loading.value = true;
    try {
        const { data } = await ApiService.get("/rooms");
        rooms.value = data;
    } catch (e) {
        console.error(e);
    } finally {
        setTimeout(() => loading.value = false, 600);
    }
};

const formatPrice = (v: number) => new Intl.NumberFormat("id-ID").format(v);
const getRoomImage = (r: any) => r.image ? (r.image.startsWith("http") ? r.image : `/storage/${r.image}`) : "/media/svg/files/blank-image.svg";

const getRoomStatusLabel = (s: string) => {
    if (['dirty', 'needs cleaning', 'request cleaning'].includes(s)) return 'Perlu Dibersihkan';
    return s === 'occupied' ? 'Terisi' : (s === 'available' ? 'Tersedia' : s);
};

const getRoomStyle = (s: string) => {
    if (s === 'occupied') return { bg: 'bg-danger', text: 'text-danger' };
    if (['dirty', 'needs cleaning', 'request cleaning'].includes(s)) return { bg: 'bg-warning', text: 'text-warning' };
    return { bg: 'bg-success', text: 'text-success' };
};

const getGuestName = (r: any) => r.check_ins?.[0]?.guest?.name || "Tamu Aktif";
const isIncognito = (r: any) => r.check_ins?.[0]?.is_incognito;

const openAddRoomModal = () => {
    selectedRoom.value = null;
    const m = document.getElementById('kt_modal_room');
    if (m) new Modal(m).show();
};
const openEditRoomModal = (r: any) => {
    selectedRoom.value = { ...r };
    const m = document.getElementById('kt_modal_room');
    if (m) new Modal(m).show();
};
const openCheckInModal = (r: any) => checkInModalRef.value?.openModal(r);
const openScheduleModal = (r: any) => scheduleModalRef.value?.openModal(r);

const handleCheckout = (room: any) => {
    Swal.fire({
        title: "Konfirmasi Check-Out?",
        text: `Tamu di kamar ${room.room_number} akan di-checkout.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Check-Out",
        cancelButtonText: "Batal",
        customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-light" }
    }).then(async (res) => {
        if (res.isConfirmed) {
            try {
                await ApiService.post(`/rooms/${room.id}/checkout`, {});
                await refreshData();
                Swal.fire({
                    title: "Berhasil",
                    text: "Tamu telah di-checkout dan kamar kini berstatus kotor.",
                    icon: "success",
                    timer: 2000,
                    showConfirmButton: false
                });
            } catch (e) {
                Swal.fire("Gagal", "Terjadi kesalahan saat proses checkout.", "error");
            }
        }
    });
};

const handleMarkAsClean = async (room: any) => {
    try {
        await ApiService.post(`/rooms/${room.id}/mark-as-clean`, {});
        await refreshData();
        Swal.fire({
            title: "Selesai!",
            text: `Kamar ${room.room_number} kini sudah bersih dan siap digunakan.`,
            icon: "success",
            timer: 1500,
            showConfirmButton: false
        });
    } catch (e) {
        ElMessage.error("Gagal sinkronisasi.");
    }
};

const handleRequestCleaning = async (room: any) => {
    try {
        await ApiService.post(`/rooms/${room.id}/request-cleaning`, {});
        await refreshData();
        Swal.fire("Selesai", "Permintaan pembersihan dikirim.", "success");
    } catch (e) {
        Swal.fire("Error", "Gagal mengirim permintaan.", "error");
    }
};

const handleDeleteRoom = (id: number) => {
    Swal.fire({
        title: "Hapus Kamar?",
        text: "Data akan terhapus permanen!",
        icon: "error",
        showCancelButton: true,
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal"
    }).then(async (res) => {
        if (res.isConfirmed) {
            try {
                await ApiService.delete(`/rooms/${id}`);
                await refreshData();
                Swal.fire("Terhapus", "Data telah dihapus.", "success");
            } catch (e) {
                Swal.fire("Error", "Terjadi kesalahan server.", "error");
            }
        }
    });
};

onMounted(refreshData);
</script>

<style scoped>
.text-orange { color: #f68b1e !important; }
.bg-light-orange { background-color: rgba(246, 139, 30, 0.1) !important; }
.btn-orange { background-color: #f68b1e; color: white; border: none; }
.btn-orange:hover:not(:disabled) { background-color: #d97814; color: white; }

.group-card { transition: all 0.3s ease; border-radius: 0.85rem; }
.group-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; }

.room-img { transition: transform 0.6s cubic-bezier(0.25, 0.8, 0.25, 1); }
.group-card:hover .room-img { transform: scale(1.08); }

.backdrop-blur { backdrop-filter: blur(8px); background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); }
.hide-arrow::after { display: none !important; }

.spin-anim { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

.pulse-dot { animation: pulse 2s infinite; }
@keyframes pulse { 0% { transform: scale(0.95); opacity: 0.7; } 70% { transform: scale(1.2); opacity: 1; } 100% { transform: scale(0.95); opacity: 0.7; } }

.theme-dropdown .dropdown-item:hover { background-color: #f9f9f9; color: #f68b1e; }

:deep(.premium-filter-select .el-input__wrapper) {
    background-color: #f5f8fa !important;
    border: none !important;
    box-shadow: none !important;
    padding: 8px 12px;
}

:deep(.premium-filter-select .el-input__prefix-inner) {
    align-items: center;
}
</style>