<template>
    <div class="d-flex flex-column gap-5">
        <!-- Kartu Statistik -->
        <div class="row g-5 mb-5">
            <div class="col-md-4">
                <div class="card bg-white hover-elevate-up shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-3">
                            <div class="symbol-label bg-light-warning text-warning">
                                <i class="ki-duotone ki-time fs-2x"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-gray-800 fs-5">Menunggu Verifikasi</span>
                            <span class="text-muted fw-semibold fs-7">{{ stats.pending }} Tamu Baru</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-white hover-elevate-up shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-3">
                            <div class="symbol-label bg-light-success text-success">
                                <i class="ki-duotone ki-shield-tick fs-2x"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-gray-800 fs-5">Terverifikasi</span>
                            <span class="text-muted fw-semibold fs-7">Data Valid & Aman</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-gradient-orange hover-elevate-up shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center position-relative overflow-hidden">
                        <div class="position-absolute top-0 end-0 opacity-10 pe-3 pt-3">
                            <i class="ki-duotone ki-user fs-4x text-white"><span class="path1"></span><span class="path2"></span></i>
                        </div>
                        <div class="d-flex flex-column z-index-1">
                            <span class="fw-bold text-white fs-5">Total Database</span>
                            <span class="text-white opacity-75 fw-semibold fs-7">{{ total }} Tamu Terdaftar</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar: Pencarian & Filter -->
        <div class="card shadow-sm mb-5">
            <div class="card-body py-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Search -->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 text-gray-500">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input
                        type="text"
                        v-model="search"
                        @input="handleSearch"
                        class="form-control form-control-solid w-250px ps-12"
                        placeholder="Cari Nama / Email..."
                    />
                </div>

                <!-- Filter & Refresh Group -->
                <div class="d-flex align-items-center gap-3">
                    <div class="nav-group d-inline-flex bg-light rounded-pill p-1">
                        <button
                            v-for="tab in ['all', 'pending', 'verified']"
                            :key="tab"
                            @click="setFilter(tab)"
                            class="btn btn-sm px-6 rounded-pill fw-bold transition-300 text-capitalize"
                            :class="filterStatus === tab ? 'btn-white shadow-sm text-primary' : 'text-gray-600 hover-text-primary'"
                        >
                            {{ tab === "all" ? "Semua" : tab }}
                            <span v-if="tab === 'pending' && stats.pending > 0" class="badge badge-circle badge-danger w-6px h-6px ms-2 align-middle"></span>
                        </button>
                    </div>

                    <!-- Tombol Refresh -->
                    <button 
                        @click="fetchData" 
                        class="btn btn-sm btn-icon btn-light-primary rounded-circle hover-elevate-up"
                        :disabled="loading"
                        title="Perbarui Data"
                    >
                        <i class="ki-duotone ki-arrows-circle fs-2" :class="{'spin-animation': loading}">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Skeleton Loading -->
        <div v-if="loading && list.length === 0" class="row g-6">
            <div v-for="n in 6" :key="n" class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="d-flex align-items-center mb-4">
                            <div class="skeleton-circle w-50px h-50px me-3"></div>
                            <div class="flex-grow-1">
                                <div class="skeleton-line w-75 mb-2"></div>
                                <div class="skeleton-line w-50"></div>
                            </div>
                        </div>
                        <div class="skeleton-rect w-100 h-150px rounded mb-4"></div>
                        <div class="d-flex gap-2">
                            <div class="skeleton-rect w-50 h-40px rounded"></div>
                            <div class="skeleton-rect w-50 h-40px rounded"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- State Kosong -->
        <div v-else-if="!loading && list.length === 0" class="text-center py-15 card bg-transparent border-dashed border-gray-300">
            <div class="mb-4">
                <i class="ki-duotone ki-folder-added fs-5x text-gray-300"><span class="path1"></span><span class="path2"></span></i>
            </div>
            <h3 class="fw-bold text-gray-800">Data Kosong</h3>
            <p class="text-muted">Tidak ada tamu yang sesuai dengan filter.</p>
        </div>

        <!-- Grid Daftar Tamu -->
        <transition-group name="staggered" tag="div" class="row g-6" v-else>
            <div
                v-for="(item, index) in list"
                :key="item.id"
                class="col-md-6 col-lg-4 staggered-item"
                :style="{ '--delay': index * 0.05 + 's' }"
            >
                <div class="card h-100 border-0 shadow-sm card-hover-rise overflow-hidden position-relative" :class="{'opacity-50': loading}">
                    <!-- Indikator Status di Samping -->
                    <div
                        class="position-absolute top-0 start-0 bottom-0 w-4px"
                        :class="needsVerification(item) ? 'bg-warning' : item.is_verified ? 'bg-success' : 'bg-secondary'"
                    ></div>

                    <div class="card-body p-0 d-flex flex-column">
                        <div class="p-5 d-flex align-items-center">
                            <div class="symbol symbol-50px me-3">
                                <span class="symbol-label fw-bold fs-3" :class="item.is_verified ? 'bg-light-success text-success' : 'bg-light-warning text-warning'">
                                    {{ item.name.charAt(0).toUpperCase() }}
                                </span>
                                <div v-if="item.is_verified" class="symbol-badge bg-success start-100 top-100 border-4 h-15px w-15px ms-n2 mt-n2"></div>
                            </div>
                            <div class="d-flex flex-column overflow-hidden">
                                <span class="text-gray-800 fw-bold text-truncate fs-5">{{ item.name }}</span>
                                <span class="text-gray-400 fs-7 text-truncate">{{ item.email || '-' }}</span>
                            </div>
                            <div class="ms-auto">
                                <span class="badge badge-light fw-bold">#{{ item.id }}</span>
                            </div>
                        </div>

                        <!-- Preview KTP -->
                        <div class="px-5 pb-2 position-relative">
                            <div class="rounded-3 overflow-hidden bg-light position-relative" style="height: 180px">
                                <img
                                    v-if="item.ktp_image"
                                    :src="`/storage/${item.ktp_image}`"
                                    class="w-100 h-100 object-fit-cover cursor-pointer"
                                    @click="showKtpModal(item)"
                                />
                                <div v-else class="w-100 h-100 d-flex align-items-center justify-content-center">
                                    <i class="ki-duotone ki-picture fs-3x text-gray-300"></i>
                                    <span class="fs-8 text-gray-400">Belum Upload KTP</span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Booking Terakhir -->
                        <div class="px-5 py-3">
                            <div v-if="item.bookings && item.bookings.length > 0" class="d-flex align-items-center justify-content-between bg-light-primary rounded p-3 border border-primary border-opacity-10">
                                <div class="d-flex flex-column">
                                    <span class="text-gray-500 fs-8 fw-bold text-uppercase">Check-In</span>
                                    <span class="text-gray-800 fw-bold fs-7">{{ formatDate(item.bookings[0].check_in_date) }}</span>
                                </div>
                                <div class="vr opacity-25"></div>
                                <div class="d-flex flex-column text-end">
                                    <span class="text-gray-500 fs-8 fw-bold text-uppercase">Status</span>
                                    <span :class="getPaymentTextClass(item.bookings[0].status)" class="fw-bold fs-7 text-uppercase">
                                        {{ item.bookings[0].status }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-center py-2 bg-light rounded text-muted fs-8 fst-italic">Belum ada data booking aktif</div>
                        </div>

                        <!-- Footer Aksi -->
                        <div class="mt-auto border-top p-4 bg-light bg-opacity-50">
                            <div v-if="needsVerification(item)" class="d-flex gap-2">
                                <button @click="rejectKtpAction(item)" class="btn btn-light-danger btn-sm flex-grow-1 fw-bold">
                                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                                    Tolak
                                </button>
                                <button @click="verifyKtpAction(item)" class="btn btn-warning btn-sm flex-grow-1 fw-bold shadow-sm">
                                    <i class="ki-duotone ki-check fs-2"><span class="path1"></span><span class="path2"></span></i>
                                    Verifikasi
                                </button>
                            </div>
                            <div v-else class="d-flex justify-content-center">
                                <span v-if="item.is_verified" class="text-success fw-bold fs-7 d-flex align-items-center">
                                    <i class="ki-duotone ki-check-circle fs-5 me-1 text-success"><span class="path1"></span><span class="path2"></span></i>
                                    Identitas Terverifikasi
                                </span>
                                <span v-else class="text-gray-400 fw-bold fs-7">Data Belum Lengkap</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition-group>

        <!-- KTP Modal Viewer -->
        <el-dialog v-model="ktpModalVisible" width="650px" align-center destroy-on-close :show-close="false" class="bg-transparent shadow-none">
            <div class="modal-content-wrapper bg-body rounded-4 overflow-hidden shadow-lg position-relative animate__animated animate__zoomIn animate__faster">
                <div class="d-flex justify-content-between align-items-center px-6 py-4 border-bottom bg-white">
                    <h3 class="fw-bold m-0 text-gray-800">Review KTP Tamu</h3>
                    <div class="btn btn-icon btn-sm btn-light-danger rounded-circle" @click="ktpModalVisible = false">
                        <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="d-flex flex-column">
                    <div class="bg-dark d-flex align-items-center justify-content-center position-relative p-5 overflow-hidden pattern-bg" style="height: 400px">
                        <img
                            v-if="selectedGuest"
                            :src="`/storage/${selectedGuest.ktp_image || (selectedGuest.bookings && selectedGuest.bookings.length > 0 ? selectedGuest.bookings[0].ktp_image : '')}`"
                            class="img-fluid rounded shadow-lg transition-transform"
                            :style="{ transform: `scale(${imageScale}) rotate(${imageRotation}deg)` }"
                            style="max-height: 100%; object-fit: contain"
                        />

                        <!-- Alat Kontrol Viewer -->
                        <div class="position-absolute bottom-0 mb-4 bg-white bg-opacity-10 backdrop-blur rounded-pill p-2 d-flex gap-2 border border-white border-opacity-10 shadow-lg">
                            <button class="btn btn-icon btn-sm btn-custom-glass" @click="zoomOut"><i class="ki-duotone ki-minus text-white fs-3"></i></button>
                            <button class="btn btn-icon btn-sm btn-custom-glass" @click="zoomIn"><i class="ki-duotone ki-plus text-white fs-3"></i></button>
                            <div class="vr bg-white opacity-25 mx-1"></div>
                            <button class="btn btn-icon btn-sm btn-custom-glass" @click="rotateRight"><i class="ki-duotone ki-refresh text-white fs-3"><span class="path1"></span><span class="path2"></span></i></button>
                        </div>
                    </div>

                    <div class="px-6 py-5 bg-white" v-if="selectedGuest">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px me-3">
                                    <div class="symbol-label bg-light-primary text-primary fw-bold">{{ selectedGuest.name.charAt(0) }}</div>
                                </div>
                                <div>
                                    <span class="text-gray-800 fw-bold fs-6 d-block">{{ selectedGuest.name }}</span>
                                    <span class="text-gray-400 fs-8">ID Tamu: #{{ selectedGuest.id }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <button class="btn btn-light-danger flex-grow-1 fw-bold py-3" @click="ktpModalVisible = false; rejectKtpAction(selectedGuest)">
                                <i class="ki-duotone ki-cross-circle fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                                Tolak KTP
                            </button>
                            <button class="btn btn-warning flex-grow-1 fw-bold py-3 shadow-sm" @click="ktpModalVisible = false; verifyKtpAction(selectedGuest)">
                                <i class="ki-duotone ki-check-circle fs-2 me-2 text-white"><span class="path1"></span><span class="path2"></span></i>
                                Konfirmasi Valid
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from "vue";
import ApiService from "@/core/services/ApiService";
import { ElMessage } from "element-plus";
import Swal from "sweetalert2";
import debounce from "lodash/debounce";

// --- STATE ---
const list = ref<any[]>([]);
const loading = ref(false);
const search = ref("");
const filterStatus = ref("pending");
const total = ref(0);
const stats = reactive({ pending: 0 });

// State Viewer KTP
const ktpModalVisible = ref(false);
const selectedGuest = ref<any>(null);
const imageRotation = ref(0);
const imageScale = ref(1);

// --- HELPERS ---
const formatDate = (date: string) => {
    if (!date) return "-";
    return new Intl.DateTimeFormat("id-ID", { day: "numeric", month: "short" }).format(new Date(date));
};

const getPaymentTextClass = (status: string) => {
    switch (status) {
        case "paid":
        case "settlement":
        case "confirmed": return "text-success";
        case "pending": return "text-warning";
        case "cancelled":
        case "rejected": return "text-danger";
        default: return "text-gray-600";
    }
};

const needsVerification = (item: any): boolean => {
    if (item.is_verified) return false;
    
    // Butuh verifikasi jika ada booking yang melampirkan KTP
    if (item.bookings && item.bookings.length > 0) {
        const latestBooking = item.bookings[0];
        // Jika ada KTP di booking, maka butuh verifikasi
        if (latestBooking.ktp_image) return true;
        
        // Atau jika status pembayaran sudah lunas (harus diverifikasi identitasnya)
        const s = latestBooking.status;
        if (s === "paid" || s === "settlement") return true;
    }
    
    return !!item.ktp_image;
};

// --- PENGAMBILAN DATA ---
const fetchData = async () => {
    loading.value = true;
    try {
        const params: any = { search: search.value };
        if (filterStatus.value !== "all") params.verification_status = filterStatus.value;

        const response = await ApiService.query("guests", params);
        list.value = response.data.data;
        total.value = response.data.total;
        
        // Update statistik khusus pending jika filter sedang di pending
        if (filterStatus.value === 'pending') stats.pending = response.data.total;
    } catch (error) {
        ElMessage.error("Gagal memuat data tamu.");
    } finally {
        loading.value = false;
    }
};

const handleSearch = debounce(() => fetchData(), 500);
const setFilter = (status: string) => {
    filterStatus.value = status;
    fetchData();
};

// --- LOGIKA VIEWER KTP ---
const showKtpModal = (guest: any) => {
    selectedGuest.value = guest;
    imageRotation.value = 0;
    imageScale.value = 1;
    ktpModalVisible.value = true;
};

const zoomIn = () => { if (imageScale.value < 3) imageScale.value += 0.2; };
const zoomOut = () => { if (imageScale.value > 0.5) imageScale.value -= 0.2; };
const rotateRight = () => { imageRotation.value += 90; };

// --- AKSI UTAMA ---

// Verifikasi KTP
const verifyKtpAction = async (guest: any) => {
    const result = await Swal.fire({
        title: "Konfirmasi Verifikasi",
        text: `Tandai identitas ${guest.name} sebagai valid?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Ya, Verifikasi",
        cancelButtonText: "Batal",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-warning fw-bold",
            cancelButton: "btn btn-light fw-bold",
            popup: "rounded-4",
        },
    });

    if (result.isConfirmed) {
        loading.value = true;
        try {
            // Kita gunakan API verify-ktp yang akan mensinkronisasi foto dari booking ke guest
            await ApiService.post(`guests/${guest.id}/verify-ktp`, {});
            Swal.fire({ text: "KTP Berhasil Diverifikasi.", icon: "success", buttonsStyling: false, confirmButtonText: "Ok", customClass: { confirmButton: "btn btn-primary fw-bold" } });
            fetchData();
        } catch (error: any) {
            ElMessage.error(error.response?.data?.message || "Gagal verifikasi.");
        } finally {
            loading.value = false;
        }
    }
};

// Tolak KTP
const rejectKtpAction = async (guest: any) => {
    const result = await Swal.fire({
        title: "Tolak KTP?",
        html: `
            <p class="text-gray-600 mb-4">Foto KTP akan dihapus. Tamu tetap boleh menginap, namun wajib memberikan KTP valid saat Check-In di resepsionis.</p>
            <textarea id="rejection-reason" class="form-control" rows="3" placeholder="Tuliskan alasan penolakan (wajib diisi)..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;"></textarea>
        `,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Tolak KTP",
        cancelButtonText: "Batal",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-danger fw-bold",
            cancelButton: "btn btn-light fw-bold",
        },
        preConfirm: () => {
            const reason = (document.getElementById('rejection-reason') as HTMLTextAreaElement)?.value;
            if (!reason || reason.trim() === '') {
                Swal.showValidationMessage('Alasan penolakan wajib diisi!');
                return false;
            }
            return reason;
        }
    });

    if (result.isConfirmed && result.value) {
        loading.value = true;
        try {
            await ApiService.post(`guests/${guest.id}/reject-ktp`, { 
                reason: result.value 
            });
            Swal.fire({ 
                title: "KTP Ditolak", 
                text: "Status tamu kembali menjadi 'Unverified'. Alasan penolakan telah dikirim ke tamu.", 
                icon: "info", 
                buttonsStyling: false, 
                confirmButtonText: "Ok", 
                customClass: { confirmButton: "btn btn-primary fw-bold" } 
            });
            fetchData();
        } catch (error: any) {
            ElMessage.error(error.response?.data?.message || "Gagal menolak KTP.");
        } finally {
            loading.value = false;
        }
    }
};

onMounted(() => fetchData());
</script>

<style scoped lang="scss">
/* --- Animasi --- */
.staggered-enter-active, .staggered-leave-active { transition: all 0.4s ease; }
.staggered-enter-from, .staggered-leave-to { opacity: 0; transform: translateY(20px); }
.staggered-item { transition-delay: var(--delay); }

/* --- Efek Hover Card --- */
.card-hover-rise {
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
}
.card-hover-rise:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08) !important;
    z-index: 2;
}

/* --- KTP Preview --- */
.ktp-wrapper { background: #f8f9fa; }
.hover-scale-img:hover { transform: scale(1.03); }
.hover-opacity-100:hover { opacity: 1 !important; }

/* --- Skeleton --- */
.skeleton-circle { background: #eee; border-radius: 50%; animation: pulse 1.5s infinite; }
.skeleton-line { height: 10px; background: #eee; border-radius: 4px; animation: pulse 1.5s infinite; }
.skeleton-rect { background: #eee; animation: pulse 1.5s infinite; }
@keyframes pulse { 0% { opacity: 0.6; } 50% { opacity: 1; } 100% { opacity: 0.6; } }

/* --- Utilitas --- */
.bg-gradient-orange { background: linear-gradient(135deg, #f68b1e 0%, #ffc700 100%); }
.pattern-bg {
    background-color: #1e1e2d;
    background-image: radial-gradient(#ffffff 1px, transparent 1px);
    background-size: 20px 20px;
}
.backdrop-blur { backdrop-filter: blur(8px); }
.btn-custom-glass { background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.2); color: white; }
.btn-custom-glass:hover { background: rgba(255, 255, 255, 0.3); transform: scale(1.1); }

/* Spin Animation untuk Tombol Refresh */
.spin-animation {
    animation: rotate 1s linear infinite;
    display: inline-block;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>