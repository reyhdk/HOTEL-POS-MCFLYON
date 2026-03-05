<template>
    <div class="d-flex flex-column gap-5">
        <!-- Kartu Statistik -->
        <div class="row g-5 g-xl-8">
            <div v-for="(stat, index) in statistics" :key="index" 
                 class="col-xl-4 col-md-4 animate-item" :style="`--delay: ${index * 0.1}s`">
                <div class="card bg-body hover-elevate-up border-0 h-100 theme-card shadow-sm">
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
                            class="form-control form-control-solid ps-12 w-250px h-42px" 
                            placeholder="Cari nomor/tipe kamar..." 
                        />
                    </div>

                    <!-- Sisi Kanan: Filter -->
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <!-- Filter Status -->
                        <div class="w-175px">
                            <el-select v-model="statusFilter" placeholder="Pilih Status" class="premium-filter-select w-100">
                                <template #prefix>
                                    <i class="ki-duotone ki-filter-search fs-3 text-gray-500">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                </template>
                                <el-option label="Semua Status" value="all" />
                                <el-option label="Kotor (Checkout)" value="dirty" />
                                <el-option label="Request Cleaning" value="request cleaning" />
                            </el-select>
                        </div>

                        <!-- Filter Tanggal -->
                        <div class="w-175px">
                            <el-date-picker
                                v-model="dateFilter"
                                type="date"
                                placeholder="Pilih Tanggal"
                                format="DD MMM YYYY"
                                value-format="YYYY-MM-DD"
                                :clearable="false"
                                @change="fetchCleaningTasks"
                                class="premium-filter-datepicker w-100"
                            >
                                <template #prefix>
                                    <i class="ki-duotone ki-calendar-8 fs-3 text-gray-500">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span>
                                    </i>
                                </template>
                            </el-date-picker>
                        </div>

                        <!-- Tombol Refresh -->
                        <button class="btn btn-icon btn-light-primary w-42px h-42px rounded flex-shrink-0 hover-scale" @click="handleRefresh" :disabled="isLoading" title="Refresh">
                            <i class="ki-duotone ki-arrows-circle fs-2" :class="{ 'spin-anim': isLoading }">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid Kamar Kotor -->
        <div class="position-relative min-h-400px">
            <!-- Loading State -->
            <div v-if="isLoading" class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-body bg-opacity-75 rounded-3" style="z-index: 10">
                <div class="d-flex flex-column align-items-center animate-pulse">
                    <span class="spinner-border text-primary mb-3 w-40px h-40px"></span>
                    <span class="text-gray-500 fw-bold">Memuat data kamar...</span>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredRooms.length === 0" class="d-flex flex-column align-items-center justify-content-center py-15 text-muted bg-white rounded shadow-sm border border-dashed border-gray-300">
                <div class="symbol symbol-100px mb-5 bg-light-success rounded-circle d-flex align-items-center justify-content-center">
                    <i class="ki-duotone ki-check-circle fs-4x text-success"><span class="path1"></span><span class="path2"></span></i>
                </div>
                <span class="fs-4 fw-bold text-gray-800">Tidak ada tugas pembersihan!</span>
                <span class="fs-6 text-gray-500 mt-1">Semua kamar sudah bersih atau tidak ada data yang cocok.</span>
            </div>

            <!-- Room List -->
            <div v-else>
                <TransitionGroup name="list-shuffle" tag="div" class="row g-6">
                    <div class="col-md-6 col-lg-4 col-xxl-3 room-item" v-for="room in filteredRooms" :key="room.id">
                        <div class="card h-100 border-0 shadow-sm theme-card hover-elevate-up group-card overflow-hidden">
                            <!-- Dekorasi Garis Atas Sesuai Status -->
                            <div class="position-absolute top-0 start-0 w-100 h-4px status-border" :class="getRoomStyle(room.status).bg"></div>

                            <div class="card-body p-5 d-flex flex-column">
                                <div class="mb-4 d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-gray-500 fs-9 fw-bold text-uppercase ls-1 mb-1">{{ room.type }}</div>
                                        <h3 class="fs-2 fw-bolder text-gray-900 m-0">No. {{ room.room_number }}</h3>
                                    </div>
                                    <div class="symbol symbol-45px bg-light rounded-3 d-flex align-items-center justify-content-center">
                                        <i class="ki-duotone ki-home-3 fs-2 text-gray-600">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </div>
                                </div>

                                <div class="d-flex flex-column gap-3 mb-5">
                                    <!-- Badge Status -->
                                    <div class="d-flex align-items-center p-3 rounded bg-light-subtle border border-dashed border-gray-300">
                                        <span class="bullet bullet-dot h-10px w-10px me-3 pulse-dot" :class="getRoomStyle(room.status).bg"></span>
                                        <div class="d-flex flex-column w-100">
                                            <span class="fs-7 fw-bolder" :class="getRoomStyle(room.status).text">
                                                {{ room.status === 'dirty' ? 'Kotor (Checkout)' : 'Request Tamu' }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Tanggal Update -->
                                    <div class="d-flex align-items-center text-gray-600 fs-8">
                                        <i class="ki-duotone ki-time fs-6 text-gray-500 me-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <span class="fw-bold me-1">Update:</span> {{ formatDate(room.updated_at) }}
                                    </div>
                                </div>

                                <!-- Action Button -->
                                <div class="mt-auto pt-4 border-top border-gray-200 border-opacity-50">
                                    <button 
                                        @click="markAsClean(room.id, room.room_number)" 
                                        class="btn btn-sm w-100 fw-bold hover-scale shadow-sm d-flex justify-content-center align-items-center gap-2"
                                        :class="getRoomStyle(room.status).btnClass"
                                    >
                                        <i class="ki-duotone ki-check fs-4">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        Tandai Selesai Bersih
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import ApiService from "@/core/services/ApiService";
import Swal from 'sweetalert2';

// Interface
interface Room {
    id: number;
    room_number: string;
    type: string;
    status: string;
    updated_at: string;
}

// State
const rooms = ref<Room[]>([]);
const isLoading = ref(true);

// Filters
const searchQuery = ref('');
const statusFilter = ref('all');

// Fungsi untuk mendapatkan tanggal hari ini dalam format YYYY-MM-DD
const getTodayDate = () => {
    const today = new Date();
    return today.toISOString().split('T')[0];
};
const dateFilter = ref(getTodayDate());

// Computed Properties untuk Kartu Statistik
const statistics = computed(() => {
    const total = rooms.value.length;
    const dirty = rooms.value.filter(r => r.status === 'dirty').length;
    const request = rooms.value.filter(r => r.status === 'request cleaning').length;

    return [
        { label: "Total Tugas", value: total, color: "primary", icon: "ki-clipboard", paths: [1, 2, 3] },
        { label: "Kotor (Checkout)", value: dirty, color: "danger", icon: "ki-entrance-left", paths: [1, 2] },
        { label: "Request Tamu", value: request, color: "warning", icon: "ki-message-text-2", paths: [1, 2, 3] }
    ];
});

// Utilities: Styling dinamis berdasarkan status
const getRoomStyle = (status: string) => {
    if (status === 'dirty') {
        return { bg: 'bg-danger', text: 'text-danger', btnClass: 'btn-light-danger' };
    }
    // request cleaning
    return { bg: 'bg-warning', text: 'text-warning', btnClass: 'btn-light-warning' };
};

// Fetch Data dari API menggunakan ApiService (Agar Auth dan CORS tertangani)
const fetchCleaningTasks = async () => {
    isLoading.value = true;
    try {
        // Menggunakan ApiService, path disesuaikan dengan BaseURL yang ada di axios config
        const { data } = await ApiService.get(`/rooms/cleaning-tasks?date=${dateFilter.value}`);
        rooms.value = data;
    } catch (error) {
        console.error('Gagal mengambil data kamar:', error);
        Swal.fire("Error", "Gagal memuat data pembersihan kamar.", "error");
    } finally {
        setTimeout(() => isLoading.value = false, 500); // Sedikit delay untuk animasi
    }
};

// Handle Refresh Button (Kembali ke Default / Hari Ini)
const handleRefresh = () => {
    // 1. Kembalikan tanggal ke hari ini
    dateFilter.value = getTodayDate();
    
    // 2. (Opsional) Reset kolom pencarian & status agar benar-benar fresh
    searchQuery.value = '';
    statusFilter.value = 'all';
    
    // 3. Panggil ulang data dari API
    fetchCleaningTasks();
};

// Tandai Kamar Bersih dengan SweetAlert2
const markAsClean = (roomId: number, roomNumber: string) => {
    Swal.fire({
        title: "Konfirmasi Pembersihan",
        text: `Apakah Anda yakin kamar ${roomNumber} sudah dibersihkan dan siap digunakan?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Ya, Sudah Bersih!",
        cancelButtonText: "Batal",
        customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-light" }
    }).then(async (res) => {
        if (res.isConfirmed) {
            try {
                await ApiService.post(`/rooms/${roomId}/mark-as-clean`, {});
                
                // Hapus item dari list dengan reaktif
                rooms.value = rooms.value.filter(room => room.id !== roomId);
                
                Swal.fire({
                    title: "Selesai!",
                    text: `Kamar ${roomNumber} telah ditandai bersih.`,
                    icon: "success",
                    timer: 2000,
                    showConfirmButton: false
                });
            } catch (error) {
                console.error('Gagal memperbarui status:', error);
                Swal.fire("Gagal", "Terjadi kesalahan saat menyimpan data.", "error");
            }
        }
    });
};

// Computed Properties untuk Frontend Filtering (Search & Status)
const filteredRooms = computed(() => {
    return rooms.value.filter(room => {
        // Filter Pencarian Text
        const searchMatch = 
            room.room_number.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
            room.type.toLowerCase().includes(searchQuery.value.toLowerCase());

        // Filter Status Dropdown
        const statusMatch = statusFilter.value === 'all' || room.status === statusFilter.value;

        return searchMatch && statusMatch;
    });
});

// Utilities: Format Tanggal
const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};

// Lifecycle Hooks
onMounted(() => {
    fetchCleaningTasks();
});
</script>

<style>
/* Global Element Plus Dark Mode Overrides for Tooltips/Dropdowns (Teleported) */
[data-bs-theme="dark"] .el-select-dropdown,
[data-bs-theme="dark"] .el-picker-panel {
    background: #1e1e2d !important; 
    border-color: #323248 !important;
}
[data-bs-theme="dark"] .el-select-dropdown__item { color: #cdcdde !important; }
[data-bs-theme="dark"] .el-select-dropdown__item.is-hovering,
[data-bs-theme="dark"] .el-select-dropdown__item.hover { 
    background-color: #2b2b40 !important; color: #009ef7 !important; 
}
[data-bs-theme="dark"] .el-popper[data-popper-placement^="bottom"] .el-popper__arrow::before {
    background: #1e1e2d !important; border-color: #323248 !important;
}
</style>

<style scoped>
.group-card { transition: all 0.3s ease; border-radius: 0.85rem; }
.group-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; }

.spin-anim { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

.pulse-dot { animation: pulse 2s infinite; }
@keyframes pulse { 0% { transform: scale(0.95); opacity: 0.7; } 70% { transform: scale(1.2); opacity: 1; } 100% { transform: scale(0.95); opacity: 0.7; } }

/* Unified Input Wrapper Styling untuk memastikan tinggi (height) sama rata (42px) */
.h-42px {
    height: 42px !important;
}

:deep(.premium-filter-datepicker.el-date-editor.el-input),
:deep(.premium-filter-select.el-select) {
    width: 100% !important;
    height: 42px !important;
}

:deep(.premium-filter-datepicker .el-input__wrapper),
:deep(.premium-filter-select .el-input__wrapper) {
    background-color: #f5f8fa !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 12px !important;
    height: 42px !important; /* Tinggi disamakan dengan form-control */
    min-height: 42px !important;
    border-radius: 0.475rem !important; /* Standard Metronic rounding */
    display: flex;
    align-items: center;
}

:deep(.premium-filter-datepicker .el-input__inner),
:deep(.premium-filter-select .el-input__inner) {
    font-weight: 600 !important;
    color: #5e6278 !important;
    font-size: 13px !important;
    height: 100% !important;
    display: flex;
    align-items: center;
}

:deep(.premium-filter-select .el-input__prefix-inner) {
    align-items: center;
}

/* Transisi List */
.list-shuffle-move, 
.list-shuffle-enter-active, 
.list-shuffle-leave-active {
    transition: all 0.5s cubic-bezier(0.55, 0, 0.1, 1);
}
.list-shuffle-enter-from, 
.list-shuffle-leave-to {
    opacity: 0;
    transform: scaleY(0.01) translate(30px, 0);
}
.list-shuffle-leave-active {
    position: absolute;
}

/* ========================
   DARK MODE OVERRIDES
   ======================== */
[data-bs-theme="dark"] .theme-card { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .bg-body { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-800 { color: #cdcdde !important; }
[data-bs-theme="dark"] .text-gray-600, 
[data-bs-theme="dark"] .text-gray-500 { color: #a1a5b7 !important; }
[data-bs-theme="dark"] .bg-white { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .bg-light-subtle { background-color: #151521 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .border-gray-200, 
[data-bs-theme="dark"] .border-gray-300 { border-color: #323248 !important; }
[data-bs-theme="dark"] .form-control-solid { background-color: #1b1b29 !important; color: #cdcdde !important; border-color: transparent !important; }

/* Unified Dark Mode Input Styling */
[data-bs-theme="dark"] :deep(.premium-filter-datepicker .el-input__wrapper),
[data-bs-theme="dark"] :deep(.premium-filter-select .el-input__wrapper) { 
    background-color: #1b1b29 !important; 
    box-shadow: none !important;
}
[data-bs-theme="dark"] :deep(.premium-filter-datepicker .el-input__inner),
[data-bs-theme="dark"] :deep(.premium-filter-select .el-input__inner) { 
    color: #cdcdde !important; 
}

[data-bs-theme="dark"] .btn-light-primary { background-color: rgba(0, 158, 247, 0.1); color: #009ef7; }
[data-bs-theme="dark"] .bg-light { background-color: #1b1b29 !important; }
</style>