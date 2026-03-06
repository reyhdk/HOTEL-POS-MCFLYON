<template>
    <div class="d-flex flex-column gap-5 laundry-admin-wrapper">
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
                            placeholder="Cari No. Order / Kamar..." 
                        />
                    </div>

                    <!-- Sisi Kanan: Filter -->
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        
                        <!-- Filter Tanggal -->
                        <div class="w-200px">
                            <el-date-picker
                                v-model="dateFilter"
                                type="date"
                                placeholder="Pilih Tanggal"
                                format="DD MMM YYYY"
                                class="premium-filter-select w-100"
                                :clearable="false"
                                @change="triggerFilterLoading"
                            />
                        </div>

                        <!-- Filter Status -->
                        <div class="w-200px">
                            <el-select 
                                v-model="statusFilter" 
                                placeholder="Pilih Status" 
                                class="premium-filter-select w-100"
                                @change="triggerFilterLoading"
                            >
                                <template #prefix>
                                    <i class="ki-duotone ki-filter-search fs-3 text-gray-500">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                </template>
                                <el-option label="Semua Status" value="all" />
                                <el-option label="Menunggu Jemput (Requested)" value="requested" />
                                <el-option label="Sudah Dijemput (Picked Up)" value="picked_up" />
                                <el-option label="Sedang Dicuci (Processing)" value="processing" />
                                <el-option label="Selesai (Delivered)" value="delivered" />
                            </el-select>
                        </div>

                        <!-- Tombol Refresh -->
                        <button class="btn btn-icon btn-light-primary w-42px h-42px rounded flex-shrink-0 hover-scale" @click="handleRefresh" :disabled="isLoading" title="Refresh Data">
                            <i class="ki-duotone ki-arrows-circle fs-2" :class="{ 'spin-anim': isLoading }">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid Pesanan Laundry -->
        <div class="position-relative min-h-400px">
            <!-- Loading State -->
            <div v-if="isLoading" class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-body bg-opacity-75 rounded-3" style="z-index: 10">
                <div class="d-flex flex-column align-items-center animate-pulse">
                    <span class="spinner-border text-primary mb-3 w-40px h-40px"></span>
                    <span class="text-gray-500 fw-bold">Memuat data laundry...</span>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredOrders.length === 0" class="d-flex flex-column align-items-center justify-content-center py-15 text-muted bg-white rounded shadow-sm border border-dashed border-gray-300">
                <div class="symbol symbol-100px mb-5 bg-light-success rounded-circle d-flex align-items-center justify-content-center">
                    <i class="ki-duotone ki-check-circle fs-4x text-success"><span class="path1"></span><span class="path2"></span></i>
                </div>
                <span class="fs-4 fw-bold text-gray-800">Tidak ada pesanan laundry!</span>
                <span class="fs-6 text-gray-500 mt-1">Belum ada pesanan pada tanggal atau status yang dipilih.</span>
            </div>

            <!-- Order List -->
            <div v-else>
                <TransitionGroup name="list-shuffle" tag="div" class="row g-6">
                    <div class="col-md-6 col-lg-4 col-xxl-3 room-item" v-for="order in filteredOrders" :key="order.id">
                        <div class="card h-100 border-0 shadow-sm theme-card hover-elevate-up group-card overflow-hidden">
                            <!-- Dekorasi Garis Atas Sesuai Status -->
                            <div class="position-absolute top-0 start-0 w-100 h-4px status-border" :class="getOrderStyle(order.status).bg"></div>

                            <div class="card-body p-5 d-flex flex-column">
                                <div class="mb-4 d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-gray-500 fs-9 fw-bold text-uppercase ls-1 mb-1">{{ order.order_number }}</div>
                                        <h3 class="fs-2 fw-bolder text-gray-900 m-0">Kamar {{ order.room_number }}</h3>
                                    </div>
                                    <div class="symbol symbol-45px bg-light rounded-3 d-flex align-items-center justify-content-center">
                                        <i class="ki-duotone ki-basket fs-2" :class="getOrderStyle(order.status).text">
                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                                        </i>
                                    </div>
                                </div>

                                <div class="d-flex flex-column gap-3 mb-5">
                                    <!-- Badge Status -->
                                    <div class="d-flex align-items-center p-3 rounded bg-light-subtle border border-dashed border-gray-300">
                                        <span class="bullet bullet-dot h-10px w-10px me-3 pulse-dot" :class="getOrderStyle(order.status).bg"></span>
                                        <div class="d-flex flex-column w-100">
                                            <span class="fs-7 fw-bolder" :class="getOrderStyle(order.status).text">
                                                {{ getOrderStyle(order.status).label }}
                                            </span>
                                            <span v-if="order.total_price > 0" class="fs-8 fw-bold text-gray-500 mt-1">
                                                Tagihan: {{ formatCurrency(order.total_price) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Waktu Request -->
                                    <div class="d-flex align-items-center text-gray-600 fs-8">
                                        <i class="ki-duotone ki-time fs-6 text-gray-500 me-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <span class="fw-bold me-1">Req:</span> {{ formatDate(order.created_at) }}
                                    </div>
                                </div>

                                <!-- Action Buttons (Berdasarkan Status) -->
                                <div class="mt-auto pt-4 border-top border-gray-200 border-opacity-50">
                                    
                                    <button v-if="order.status === 'requested'" 
                                        @click="openPickupModal(order)" 
                                        class="btn btn-sm w-100 fw-bold hover-scale shadow-sm d-flex justify-content-center align-items-center gap-2 btn-light-warning">
                                        <i class="ki-duotone ki-handcart fs-4"><span class="path1"></span><span class="path2"></span></i>
                                        Jemput & Input Berat
                                    </button>

                                    <button v-else-if="order.status === 'picked_up'" 
                                        @click="processOrder(order.id)" 
                                        class="btn btn-sm w-100 fw-bold hover-scale shadow-sm d-flex justify-content-center align-items-center gap-2 btn-light-primary">
                                        <i class="ki-duotone ki-color-swatch fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span><span class="path11"></span><span class="path12"></span><span class="path13"></span><span class="path14"></span><span class="path15"></span><span class="path16"></span><span class="path17"></span><span class="path18"></span><span class="path19"></span><span class="path20"></span><span class="path21"></span></i>
                                        Mulai Cuci (Potong Stok)
                                    </button>

                                    <button v-else-if="order.status === 'processing'" 
                                        @click="deliverOrder(order.id)" 
                                        class="btn btn-sm w-100 fw-bold hover-scale shadow-sm d-flex justify-content-center align-items-center gap-2 btn-light-success">
                                        <i class="ki-duotone ki-check-circle fs-4"><span class="path1"></span><span class="path2"></span></i>
                                        Selesai & Antar
                                    </button>

                                    <button v-else 
                                        class="btn btn-sm w-100 fw-bold d-flex justify-content-center align-items-center gap-2 btn-secondary disabled">
                                        <i class="ki-duotone ki-check-all fs-4"><span class="path1"></span><span class="path2"></span></i>
                                        Pesanan Selesai
                                    </button>

                                </div>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>
            </div>
        </div>

        <!-- MODAL PICKUP (Input Cucian) -->
        <div class="modal fade" id="kt_modal_pickup" ref="pickupModalRef" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-700px">
                <div class="modal-content rounded-3 shadow-lg border-0 theme-modal">
                    <div class="modal-header border-0 pb-0 justify-content-between align-items-center py-4 px-5">
                        <div class="d-flex flex-column">
                            <h2 class="fw-bold m-0 fs-3 text-gray-900">Input Cucian Tamu</h2>
                            <span class="text-muted fs-7 mt-1">Order: {{ selectedOrder?.order_number }} - Kamar: {{ selectedOrder?.room_number }}</span>
                        </div>
                        <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                        </div>
                    </div>

                    <div class="modal-body px-5 pb-5 pt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-gray-800 m-0">Detail Layanan</h5>
                            <button type="button" class="btn btn-sm btn-light-primary fw-bold py-1 px-3" @click="addItemRow">
                                <i class="ki-duotone ki-plus fs-4"></i> Tambah Item
                            </button>
                        </div>

                        <div class="table-responsive border border-gray-200 rounded p-1 mb-4">
                            <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-3 m-0">
                                <thead class="fw-bold fs-8 text-gray-500 text-uppercase bg-light">
                                    <tr>
                                        <th class="ps-3 min-w-200px">Layanan</th>
                                        <th class="w-100px text-center">Harga</th>
                                        <th class="w-100px text-center">Qty/Berat</th>
                                        <th class="w-100px text-end">Subtotal</th>
                                        <th class="w-40px text-center"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="pickupItems.length === 0">
                                        <td colspan="5" class="text-center py-4 text-muted fs-7">Belum ada item ditambahkan.</td>
                                    </tr>
                                    <tr v-for="(item, index) in pickupItems" :key="index">
                                        <td class="ps-2">
                                            <el-select v-model="item.service_id" placeholder="Pilih Layanan" class="w-100 premium-filter-select" @change="(val) => onServiceSelect(val, index)">
                                                <el-option v-for="svc in availableServices" :key="svc.id" :label="svc.name" :value="svc.id">
                                                    <span class="float-start">{{ svc.name }}</span>
                                                    <span class="float-end text-muted fs-9">/ {{ svc.unit }}</span>
                                                </el-option>
                                            </el-select>
                                        </td>
                                        <td class="text-center fs-7 text-gray-600">{{ formatCurrency(item.price) }}</td>
                                        <td>
                                            <input type="number" v-model="item.qty" class="form-control form-control-sm form-control-solid text-center" min="0" step="0.1" @input="calculateSubtotal(index)" />
                                        </td>
                                        <td class="text-end fs-7 fw-bold text-gray-800">{{ formatCurrency(item.subtotal) }}</td>
                                        <td class="text-center">
                                            <button @click="removeItemRow(index)" class="btn btn-icon btn-sm btn-light-danger w-25px h-25px">
                                                <i class="ki-duotone ki-trash fs-6"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end fw-bolder fs-6">Total Tagihan:</th>
                                        <th class="text-end fw-bolder text-primary fs-5">{{ formatCurrency(totalPickupPrice) }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end mt-5">
                            <button type="button" class="btn btn-sm btn-light me-3 fw-bold" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-sm btn-primary fw-bold" @click="submitPickup" :disabled="isSubmitting">
                                <span v-if="!isSubmitting">Simpan & Jemput Cucian</span>
                                <span v-else class="spinner-border spinner-border-sm"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from "@/libs/axios";
import Swal from 'sweetalert2';
import { Modal } from "bootstrap";

// --- Interfaces ---
interface LaundryOrder {
    id: number;
    order_number: string;
    room_number: string;
    status: string;
    total_price: number;
    created_at: string;
}

interface LaundryService {
    id: number;
    name: string;
    unit: string;
    price: number;
}

interface PickupItem {
    service_id: number | null;
    price: number;
    qty: number;
    subtotal: number;
}

// --- State ---
const orders = ref<LaundryOrder[]>([]);
const availableServices = ref<LaundryService[]>([]);
const isLoading = ref(true);
const isSubmitting = ref(false);

// Filter
const searchQuery = ref('');
const statusFilter = ref('all');
const dateFilter = ref<Date | null>(new Date()); // Set default ke hari ini

// Modal State
const selectedOrder = ref<LaundryOrder | null>(null);
const pickupItems = ref<PickupItem[]>([]);
const pickupModalRef = ref<null | HTMLElement>(null);

// --- Computed Statistics ---
const statistics = computed(() => {
    const total = filteredOrders.value.length;
    const requested = filteredOrders.value.filter(o => o.status === 'requested').length;
    const processing = filteredOrders.value.filter(o => o.status === 'processing' || o.status === 'picked_up').length;

    return [
        { label: "Total Request (Sesuai Filter)", value: total, color: "primary", icon: "ki-clipboard", paths: [1, 2, 3] },
        { label: "Menunggu Jemput", value: requested, color: "warning", icon: "ki-time", paths: [1, 2] },
        { label: "Sedang Diproses", value: processing, color: "info", icon: "ki-color-swatch", paths: Array.from({length: 21}, (_, i) => i + 1) }
    ];
});

const filteredOrders = computed(() => {
    return orders.value.filter(order => {
        const searchMatch = 
            order.order_number.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
            order.room_number.toLowerCase().includes(searchQuery.value.toLowerCase());
        const statusMatch = statusFilter.value === 'all' || order.status === statusFilter.value;
        
        // Logika Filter Tanggal
        let dateMatch = true;
        if (dateFilter.value) {
            const orderDate = new Date(order.created_at);
            const filterDate = dateFilter.value;
            
            // Bandingkan Tahun, Bulan, dan Tanggal saja
            dateMatch = 
                orderDate.getFullYear() === filterDate.getFullYear() &&
                orderDate.getMonth() === filterDate.getMonth() &&
                orderDate.getDate() === filterDate.getDate();
        }

        return searchMatch && statusMatch && dateMatch;
    });
});

// --- Utilities ---
const getOrderStyle = (status: string) => {
    const map: Record<string, {bg: string, text: string, label: string}> = {
        'requested': { bg: 'bg-warning', text: 'text-warning', label: 'Menunggu Jemput' },
        'picked_up': { bg: 'bg-info', text: 'text-info', label: 'Sudah Dijemput' },
        'processing': { bg: 'bg-primary', text: 'text-primary', label: 'Sedang Dicuci' },
        'delivered': { bg: 'bg-success', text: 'text-success', label: 'Selesai & Diantar' }
    };
    return map[status] || { bg: 'bg-secondary', text: 'text-gray-600', label: status };
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value || 0);
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(date);
};

// --- API Calls & Triggers ---
const fetchOrders = async () => {
    isLoading.value = true;
    try {
        const { data } = await axios.get('/admin/laundry/orders');
        orders.value = data.data;
    } catch (error) {
        console.error('Gagal mengambil pesanan:', error);
    } finally {
        setTimeout(() => isLoading.value = false, 300);
    }
};

const fetchServices = async () => {
    try {
        const { data } = await axios.get('/admin/laundry/services');
        availableServices.value = data.data;
    } catch (error) {
        console.error('Gagal mengambil layanan:', error);
    }
};

const triggerFilterLoading = () => {
    isLoading.value = true;
    // Berikan jeda loading selama 400ms saat mengganti filter tanggal/status
    setTimeout(() => {
        isLoading.value = false;
    }, 400);
};

const handleRefresh = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    dateFilter.value = new Date(); // Reset ke hari ini saat di-refresh
    fetchOrders();
};

// --- Pickup Logic (Jemput & Input Cucian) ---
const openPickupModal = (order: LaundryOrder) => {
    selectedOrder.value = order;
    pickupItems.value = [{ service_id: null, price: 0, qty: 1, subtotal: 0 }]; // Reset dengan 1 baris kosong
    const modalEl = document.getElementById('kt_modal_pickup');
    if (modalEl) new Modal(modalEl).show();
};

const addItemRow = () => pickupItems.value.push({ service_id: null, price: 0, qty: 1, subtotal: 0 });
const removeItemRow = (index: number) => pickupItems.value.splice(index, 1);

const onServiceSelect = (serviceId: number, index: number) => {
    const svc = availableServices.value.find(s => s.id === serviceId);
    if (svc) {
        pickupItems.value[index].price = svc.price;
        calculateSubtotal(index);
    }
};

const calculateSubtotal = (index: number) => {
    const item = pickupItems.value[index];
    item.subtotal = item.price * (item.qty || 0);
};

const totalPickupPrice = computed(() => {
    return pickupItems.value.reduce((acc, item) => acc + item.subtotal, 0);
});

const submitPickup = async () => {
    if (!selectedOrder.value) return;

    // Filter items yang valid
    const validItems = pickupItems.value.filter(i => i.service_id && i.qty > 0);
    if (validItems.length === 0) {
        Swal.fire({ text: "Masukkan minimal 1 layanan cucian", icon: "warning", customClass: { confirmButton: 'btn btn-warning' }});
        return;
    }

    isSubmitting.value = true;
    try {
        await axios.post(`/admin/laundry/orders/${selectedOrder.value.id}/pickup`, {
            items: validItems
        });
        
        Swal.fire({ title: "Berhasil!", text: "Cucian berhasil dijemput dan tagihan diupdate.", icon: "success", timer: 2000, showConfirmButton: false });
        
        const modalEl = document.getElementById('kt_modal_pickup');
        if (modalEl) Modal.getInstance(modalEl)?.hide();
        
        fetchOrders();
    } catch (error: any) {
        Swal.fire({ title: "Gagal", text: error.response?.data?.message || "Terjadi kesalahan", icon: "error", customClass: { confirmButton: 'btn btn-danger' } });
    } finally {
        isSubmitting.value = false;
    }
};

// --- Process Logic (Mulai Cuci & Potong Stok) ---
const processOrder = (orderId: number) => {
    Swal.fire({
        title: "Mulai Proses Cuci?",
        text: "Tindakan ini otomatis akan memotong stok bahan gudang (deterjen, pewangi) sesuai takaran.",
        icon: "info",
        showCancelButton: true,
        confirmButtonText: "Ya, Mulai Cuci",
        cancelButtonText: "Batal",
        customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-light" }
    }).then(async (res) => {
        if (res.isConfirmed) {
            try {
                await axios.post(`/admin/laundry/orders/${orderId}/process`);
                Swal.fire({ title: "Diproses!", text: "Stok gudang telah dipotong.", icon: "success", timer: 2000, showConfirmButton: false });
                fetchOrders();
            } catch (error: any) {
                Swal.fire("Gagal", error.response?.data?.message || "Terjadi kesalahan", "error");
            }
        }
    });
};

// --- Deliver Logic (Selesai & Antar) ---
const deliverOrder = (orderId: number) => {
    Swal.fire({
        title: "Selesai Dicuci?",
        text: "Tandai pesanan ini selesai dan siap diantar kembali ke kamar tamu.",
        icon: "success",
        showCancelButton: true,
        confirmButtonText: "Ya, Selesai & Antar",
        cancelButtonText: "Batal",
        customClass: { confirmButton: "btn btn-success", cancelButton: "btn btn-light" }
    }).then(async (res) => {
        if (res.isConfirmed) {
            try {
                await axios.post(`/admin/laundry/orders/${orderId}/deliver`);
                Swal.fire({ title: "Selesai!", text: "Status diperbarui.", icon: "success", timer: 2000, showConfirmButton: false });
                fetchOrders();
            } catch (error: any) {
                Swal.fire("Gagal", "Terjadi kesalahan", "error");
            }
        }
    });
};

onMounted(() => {
    fetchOrders();
    fetchServices();
});
</script>

<style scoped>
.group-card { transition: all 0.3s ease; border-radius: 0.85rem; }
.group-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; }

.spin-anim { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

.pulse-dot { animation: pulse 2s infinite; }
@keyframes pulse { 0% { transform: scale(0.95); opacity: 0.7; } 70% { transform: scale(1.2); opacity: 1; } 100% { transform: scale(0.95); opacity: 0.7; } }

/* Unified Input Wrapper Styling untuk memastikan tinggi (height) sama rata (42px) */
.h-42px { height: 42px !important; }

:deep(.premium-filter-select.el-select),
:deep(.premium-filter-select.el-date-editor) { width: 100% !important; height: 42px !important; }

:deep(.premium-filter-select .el-input__wrapper) {
    background-color: #f5f8fa !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 12px !important;
    height: 42px !important; 
    min-height: 42px !important;
    border-radius: 0.475rem !important; 
    display: flex;
    align-items: center;
}
:deep(.premium-filter-select .el-input__inner) {
    font-weight: 600 !important; color: #5e6278 !important; font-size: 13px !important;
}

/* Transisi List */
.list-shuffle-move, 
.list-shuffle-enter-active, 
.list-shuffle-leave-active { transition: all 0.5s cubic-bezier(0.55, 0, 0.1, 1); }
.list-shuffle-enter-from, 
.list-shuffle-leave-to { opacity: 0; transform: scaleY(0.01) translate(30px, 0); }
.list-shuffle-leave-active { position: absolute; }
</style>

<!-- STYLE GLOBAL UNTUK DARK MODE -->
<style>
/* ========================
   DARK MODE OVERRIDES
   ======================== */
[data-bs-theme="dark"] .laundry-admin-wrapper .theme-card { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .laundry-admin-wrapper .bg-body { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .laundry-admin-wrapper .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .laundry-admin-wrapper .text-gray-800 { color: #cdcdde !important; }
[data-bs-theme="dark"] .laundry-admin-wrapper .text-gray-600, 
[data-bs-theme="dark"] .laundry-admin-wrapper .text-gray-500 { color: #a1a5b7 !important; }
[data-bs-theme="dark"] .laundry-admin-wrapper .bg-white { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .laundry-admin-wrapper .bg-light-subtle { background-color: #151521 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .laundry-admin-wrapper .border-gray-200, 
[data-bs-theme="dark"] .laundry-admin-wrapper .border-gray-300 { border-color: #323248 !important; }
[data-bs-theme="dark"] .laundry-admin-wrapper .form-control-solid { background-color: #1b1b29 !important; color: #cdcdde !important; border-color: transparent !important; }

/* Unified Dark Mode Input Styling */
[data-bs-theme="dark"] :deep(.premium-filter-select .el-input__wrapper) { 
    background-color: #1b1b29 !important; box-shadow: none !important;
}
[data-bs-theme="dark"] :deep(.premium-filter-select .el-input__inner) { color: #cdcdde !important; }
[data-bs-theme="dark"] .laundry-admin-wrapper .btn-light-primary { background-color: rgba(0, 158, 247, 0.1); color: #009ef7; }
[data-bs-theme="dark"] .laundry-admin-wrapper .bg-light { background-color: #1b1b29 !important; }

/* Dark Mode for Modal */
[data-bs-theme="dark"] .theme-modal { background-color: #1e1e2d !important; color: #ffffff; }
[data-bs-theme="dark"] .theme-modal .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .theme-modal .text-gray-800 { color: #e1e1e8 !important; }
[data-bs-theme="dark"] .theme-modal .table-row-dashed tr { border-bottom-color: #323248 !important; }
[data-bs-theme="dark"] .theme-modal .bg-light { background-color: #151521 !important; }
</style>