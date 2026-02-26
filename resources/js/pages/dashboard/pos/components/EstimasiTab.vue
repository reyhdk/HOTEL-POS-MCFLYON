<template>
  <div class="d-flex flex-column h-100 bg-surface">
    
    <!-- HEADER -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 mb-6 pt-2">
        <div>
            <h3 class="fw-bolder text-dark mb-1 ls-tight">Status Dapur</h3>
            <div class="d-flex align-items-center gap-2 text-gray-500 fs-7 fw-semibold">
                <span><i class="bi bi-receipt me-1"></i> {{ orders.length }} Pesanan Aktif</span>
                <span class="bullet bullet-dot bg-gray-300 mx-1"></span>
                <span><i class="bi bi-fire text-orange me-1"></i> Total Beban: {{ totalWorkloadMinutes }} Menit</span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Kontrol Koki (Sinkronisasi Database) -->
            <div class="d-flex align-items-center bg-white rounded-3 shadow-sm p-2 border border-gray-100" title="Koki yang bertugas (Sinkron via Database)">
                <div class="px-2 d-flex align-items-center gap-2 border-end border-gray-100 pe-3">
                    <i class="bi bi-person-workspace text-orange fs-4"></i>
                    <div class="d-flex flex-column lh-1">
                       <span class="fs-9 fw-bold text-gray-400 text-uppercase">Koki Aktif</span>
                       <span class="fs-6 fw-bolder text-gray-800">{{ chefCount }} Orang</span>
                    </div>
                </div>
                <!-- Indikator Kesibukan -->
                <div class="px-3 d-flex flex-column lh-1 text-center" :class="canStartCooking ? 'text-success' : 'text-danger'" :title="canStartCooking ? 'Ada koki tersedia' : 'Semua koki sedang memasak'">
                    <span class="fs-9 fw-bold text-uppercase">{{ activeProcessingCount }}/{{ chefCount }}</span>
                    <span class="fs-9 fw-bold">Sibuk</span>
                </div>
            </div>

            <!-- Estimasi Tunggu Global -->
            <div class="bg-orange bg-gradient-orange rounded-3 py-2 px-3 shadow-orange d-flex align-items-center gap-3 text-white" title="Rata-rata waktu tunggu berdasarkan koki aktif">
                <div class="d-flex flex-column align-items-end lh-1">
                    <span class="fs-8 text-white text-opacity-75 fw-bold text-uppercase tracking-wider">Estimasi Tunggu</span>
                    <span class="fs-9 text-white text-opacity-75">Rata-rata per pesanan</span>
                </div>
                <div class="vr opacity-25"></div>
                <span class="fs-2 fw-bolder">{{ estimatedWaitTime }}<span class="fs-6 ms-1 fw-normal">Mnt</span></span>
            </div>

            <!-- Tombol Refresh -->
            <button @click="manualRefresh" class="btn btn-light-primary btn-icon w-45px h-45px rounded-3 shadow-sm hover-elevate-up" :disabled="loading" title="Refresh Data">
                <i class="bi bi-arrow-clockwise fs-2" :class="{ 'spin': loading }"></i>
            </button>
        </div>
    </div>

    <!-- KONTEN -->
    <div class="flex-grow-1 custom-scroll overflow-auto pb-5">
        
        <!-- Loading State -->
        <div v-if="loading && orders.length === 0" class="h-100 d-flex flex-column align-items-center justify-content-center animate-fade-in">
            <div class="spinner-border text-orange mb-3" role="status"></div>
            <p class="text-gray-500 fw-semibold">Sinkronisasi data dapur...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="orders.length === 0" class="h-100 d-flex flex-column align-items-center justify-content-center text-center animate-fade-in">
            <div class="symbol symbol-100px mb-4 bg-light-orange rounded-circle p-5">
                <i class="bi bi-cup-hot fs-5x text-orange"></i>
            </div>
            <h4 class="fw-bold text-gray-800 mb-1">Dapur Bersih!</h4>
            <p class="text-gray-400 fs-7 mw-300px">Tidak ada pesanan aktif saat ini.</p>
        </div>

        <!-- Order Grid -->
        <div v-else class="row g-4">
            <div class="col-md-6 col-lg-4 col-xxl-3" v-for="order in orders" :key="order.id">
                
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative transition-300 hover-translate-up" 
                     :class="getCardClass(order)">
                    
                    <!-- Indikator Status Samping -->
                    <div class="position-absolute top-0 start-0 h-100 w-4px z-index-1" 
                         :class="getStatusColor(order)"></div>

                    <!-- Header Kartu -->
                    <div class="card-header border-0 pt-5 pb-2 px-5 min-h-auto d-flex justify-content-between align-items-start">
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge fw-bolder px-2 py-1 fs-9 rounded-2 text-uppercase"
                                    :class="getStatusBadgeClass(order)">
                                    {{ getStatusLabel(order.status) }}
                                </span>
                                <span class="fs-8 fw-bold text-gray-400">#{{ order.order_code || order.id }}</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span v-if="order.room" class="fs-5 fw-bolder text-gray-800 d-flex align-items-center">
                                    <i class="bi bi-door-open-fill text-gray-300 fs-6 me-2"></i>Kamar {{ order.room.room_number }}
                                </span>
                                <span v-else-if="order.table" class="fs-5 fw-bolder text-gray-800 d-flex align-items-center">
                                    <i class="bi bi-layout-sidebar-inset text-gray-300 fs-6 me-2"></i>{{ order.table.name }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Logika Timer -->
                        <div v-if="order.status === 'processing'" 
                             class="rounded-pill px-3 py-1 d-flex align-items-center gap-1 fs-7 fw-bolder shadow-sm animate-pulse"
                             :class="getCookingTimer(order).isLate ? 'bg-danger text-white' : 'bg-orange text-white'">
                            <i v-if="getCookingTimer(order).isLate" class="bi bi-exclamation-triangle-fill text-white"></i>
                            <i v-else class="bi bi-stopwatch-fill text-white"></i> 
                            {{ getCookingTimer(order).text }}
                        </div>

                        <div v-else-if="order.status === 'ready_for_delivery'" 
                             class="bg-success text-white rounded-pill px-3 py-1 d-flex align-items-center gap-1 fs-7 fw-bolder shadow-sm">
                             <i class="bi bi-bicycle text-white"></i> {{ getDeliveryCountdown(order) }}
                        </div>

                        <div v-else class="bg-light rounded-pill px-3 py-1 d-flex align-items-center gap-2 text-gray-700 fs-8 fw-bold border border-gray-200">
                            <template v-if="order.wait_time_before_cooking > 0">
                                <span class="text-danger">
                                    <i class="bi bi-hourglass-split"></i> Antre {{ order.wait_time_before_cooking }}m
                                </span>
                                <span class="vr bg-gray-300"></span>
                                <span>Estimasi Selesai: {{ order.calculated_estimation }}m</span>
                            </template>
                            <template v-else>
                                <span class="text-success"><i class="bi bi-play-circle-fill"></i> Siap</span>
                                <span class="vr bg-gray-300"></span>
                                <span>Masak: {{ order.estimated_workload }}m</span>
                            </template>
                        </div>
                    </div>

                    <div class="separator separator-dashed border-gray-200 mx-5 my-2"></div>

                    <!-- Daftar Item -->
                    <div class="card-body px-5 py-2 d-flex flex-column flex-grow-1">
                        <div class="custom-scroll pe-2 flex-grow-1" style="max-height: 180px; overflow-y: auto;">
                            <div v-for="item in order.items" :key="item.id" class="d-flex align-items-start mb-3 last-mb-0">
                                <div class="symbol symbol-30px me-3">
                                    <div class="symbol-label fw-bolder fs-8 rounded-3" :class="order.status === 'ready_for_delivery' ? 'bg-light-success text-success' : 'bg-light-orange text-orange'">
                                        {{ item.quantity }}x
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="text-gray-800 fw-bold fs-7 d-block lh-sm mb-1">{{ item.menu?.name }}</span>
                                    <span v-if="order.status !== 'ready_for_delivery'" class="d-flex align-items-center gap-1 badge badge-light-secondary px-2 py-1 fs-10 fw-bold text-gray-500 mt-1 w-auto d-inline-flex">
                                        {{ item.menu?.cooking_estimation_time || 10 }}m x {{ item.quantity }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="card-footer border-0 pt-0 pb-4 px-5">
                        <button v-if="order.status === 'paid' || order.status === 'pending'" 
                                @click="updateOrderStatus(order, 'processing')" 
                                :disabled="!canStartCooking || order._isUpdating"
                                :class="canStartCooking ? 'btn-outline-orange btn-active-light-orange' : 'btn-light text-gray-400 border-gray-300'"
                                class="btn btn-outline btn-outline-dashed w-100 fw-bold fs-7 d-flex align-items-center justify-content-center gap-2 transition-all">
                            <span v-if="order._isUpdating" class="spinner-border spinner-border-sm text-orange"></span>
                            <template v-else>
                                <i class="bi bi-fire fs-5"></i> 
                                {{ canStartCooking ? 'Mulai Masak' : 'Koki Sibuk (Mengantre)' }}
                            </template>
                        </button>

                        <button v-if="order.status === 'processing'" 
                                @click="updateOrderStatus(order, 'ready_for_delivery')" 
                                :disabled="order._isUpdating"
                                class="btn btn-orange w-100 fw-bold fs-7 d-flex align-items-center justify-content-center gap-2 shadow-orange">
                            <span v-if="order._isUpdating" class="spinner-border spinner-border-sm text-white"></span>
                            <template v-else><i class="bi bi-check-lg fs-4"></i> Selesai Masak</template>
                        </button>

                        <button v-if="order.status === 'ready_for_delivery'" 
                                @click="updateOrderStatus(order, 'completed')" 
                                :disabled="order._isUpdating"
                                class="btn btn-success w-100 fw-bold fs-7 d-flex align-items-center justify-content-center gap-2 shadow-success">
                            <span v-if="order._isUpdating" class="spinner-border spinner-border-sm text-white"></span>
                            <template v-else><i class="bi bi-check2-circle fs-4"></i> Selesai Antar</template>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

const orders = ref<any[]>([]);
const totalWorkloadMinutes = ref(0);
const loading = ref(false);
const chefCount = ref(1); 
const currentTime = ref(new Date()); 
const DELIVERY_TIME_SECONDS = 60; 

let refreshInterval: any = null;
let timerInterval: any = null;

// --- Sinkronisasi Koki via Database ---
const fetchActiveChefStatus = async () => {
    try {
        const { data } = await ApiService.get("/chefs/statistics");
        chefCount.value = data.active_chefs || 1;
    } catch (e) {
        console.error("Gagal sinkron status koki dari DB", e);
    }
};

const activeProcessingCount = computed(() => {
    return orders.value.filter(o => o.status === 'processing').length;
});

const canStartCooking = computed(() => {
    return activeProcessingCount.value < chefCount.value;
});

const estimatedWaitTime = computed(() => {
    if (chefCount.value <= 0) return totalWorkloadMinutes.value; 
    return Math.ceil(totalWorkloadMinutes.value / chefCount.value);
});

const startTimer = () => {
    timerInterval = setInterval(() => {
        currentTime.value = new Date();
        checkDeliveryAutoCompletion(); 
        calculateQueue(); 
    }, 1000);
};

const calculateQueue = () => {
    let activeChefsCount = chefCount.value > 0 ? chefCount.value : 1;
    let chefWorkloads = Array(activeChefsCount).fill(0);

    let processingOrders = orders.value.filter(o => o.status === 'processing');
    let waitingOrders = orders.value.filter(o => ['pending', 'paid'].includes(o.status));

    processingOrders.forEach((order, index) => {
        const chefIndex = index % activeChefsCount; 
        const startTime = new Date(order.updated_at).getTime();
        const now = currentTime.value.getTime();
        const estimatedDurationMs = (order.estimated_workload || 10) * 60 * 1000;
        
        const remainingMs = Math.max(0, (startTime + estimatedDurationMs) - now);
        const remainingMins = Math.ceil(remainingMs / 60000);

        chefWorkloads[chefIndex] += remainingMins;
        order.calculated_estimation = remainingMins;
    });

    let sortedWaitingOrders = [...waitingOrders].sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());

    sortedWaitingOrders.forEach(order => {
        let chosenChefIndex = 0;
        let minWorkload = chefWorkloads[0];

        for (let i = 1; i < chefWorkloads.length; i++) {
            if (chefWorkloads[i] < minWorkload) {
                minWorkload = chefWorkloads[i];
                chosenChefIndex = i;
            }
        }

        let orderCookingTime = order.estimated_workload || 10;
        order.wait_time_before_cooking = minWorkload; 
        order.calculated_estimation = minWorkload + orderCookingTime;
        chefWorkloads[chosenChefIndex] += orderCookingTime;
    });
};

const getCookingTimer = (order: any) => {
    const startTime = new Date(order.updated_at).getTime();
    const now = currentTime.value.getTime();
    const estimatedDurationMs = (order.estimated_workload || 10) * 60 * 1000;
    const targetEndTime = startTime + estimatedDurationMs;
    const diff = targetEndTime - now;

    if (diff >= 0) {
        const mins = Math.floor(diff / 60000);
        const secs = Math.floor((diff % 60000) / 1000);
        return { text: `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`, isLate: false };
    } else {
        const overdue = Math.abs(diff);
        const mins = Math.floor(overdue / 60000);
        const secs = Math.floor((overdue % 60000) / 1000);
        return { text: `+${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`, isLate: true };
    }
};

const getDeliveryCountdown = (order: any) => {
    const start = new Date(order.updated_at).getTime(); 
    const now = currentTime.value.getTime();
    const elapsedSeconds = Math.floor((now - start) / 1000);
    const remaining = DELIVERY_TIME_SECONDS - elapsedSeconds;
    if (remaining <= 0) return "00:00"; 
    const mins = Math.floor(remaining / 60);
    const secs = remaining % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

const checkDeliveryAutoCompletion = () => {
    orders.value.forEach(order => {
        if (order.status === 'ready_for_delivery') {
            const start = new Date(order.updated_at).getTime();
            const now = currentTime.value.getTime();
            const elapsedSeconds = (now - start) / 1000;
            if (elapsedSeconds >= DELIVERY_TIME_SECONDS && !order._isCompleting) {
                order._isCompleting = true; 
                updateOrderStatus(order, 'completed', true);
            }
        }
    });
};

const manualRefresh = async () => {
    loading.value = true;
    await fetchActiveChefStatus();
    await fetchQueueStatus(true); 
};

const fetchQueueStatus = async (force = false) => {
    if (orders.value.some(o => o._isUpdating) && !force) return;
    try {
        if(orders.value.length === 0) loading.value = true;
        const { data } = await ApiService.get("/kitchen/queue");
        orders.value = data.orders.map((newOrder: any) => {
            const existing = orders.value.find(o => o.id === newOrder.id);
            if (existing) {
                newOrder._isUpdating = existing._isUpdating;
                newOrder._isCompleting = existing._isCompleting;
            }
            return newOrder;
        });
        totalWorkloadMinutes.value = data.total_workload_minutes || 0;
        calculateQueue(); 
    } catch (e) {
        console.error("Gagal load antrian dapur", e);
    } finally {
        setTimeout(() => loading.value = false, 500);
    }
};

const updateOrderStatus = async (order: any, status: string, isAuto = false) => {
    if (status === 'processing' && !isAuto) {
        if (!canStartCooking.value) {
            Swal.fire({
                title: 'Semua Koki Sedang Sibuk!',
                text: `Hanya ada ${chefCount.value} koki aktif yang sedang memasak. Harap tunggu pesanan selesai.`,
                icon: 'warning',
                confirmButtonText: 'Mengerti',
                customClass: { confirmButton: 'btn btn-orange' }
            });
            return; 
        }
    }

    try {
        order._isUpdating = true;
        await ApiService.patch(`/kitchen/orders/${order.id}/status`, { status });
        order._isUpdating = false;

        if (status === 'completed') {
            orders.value = orders.value.filter(o => o.id !== order.id);
            if (!isAuto) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Pesanan Selesai!', showConfirmButton: false, timer: 1500 });
        } else {
            order.status = status; 
            order.updated_at = new Date().toISOString(); 
        }
        calculateQueue(); 
        fetchQueueStatus(true);
    } catch (e) {
        order._isUpdating = false;
        Swal.fire('Error', 'Gagal update status pesanan', 'error');
    }
};

const getStatusLabel = (s: string) => {
    if(s === 'processing') return 'Dimasak';
    if(s === 'ready_for_delivery') return 'Antar';
    if(s === 'paid') return 'Siap';
    if(s === 'pending') return 'Antre';
    return s;
};

const getStatusBadgeClass = (order: any) => {
    const s = order.status;
    if(s === 'processing') return getCookingTimer(order).isLate ? 'badge-light-danger text-danger' : 'badge-light-orange text-orange';
    if(s === 'ready_for_delivery') return 'badge-light-success text-success';
    return 'badge-light text-gray-500';
};

const getStatusColor = (order: any) => {
    const s = order.status;
    if(s === 'processing') return getCookingTimer(order).isLate ? 'bg-danger' : 'bg-orange';
    if(s === 'ready_for_delivery') return 'bg-success';
    return 'bg-gray-300';
};

const getCardClass = (order: any) => {
    const s = order.status;
    if(s === 'processing') return getCookingTimer(order).isLate ? 'late-card' : 'processing-card';
    if(s === 'ready_for_delivery') return 'delivery-card';
    return '';
}

onMounted(() => {
    fetchActiveChefStatus();
    fetchQueueStatus();
    startTimer();
    refreshInterval = setInterval(() => {
        fetchQueueStatus(false);
        fetchActiveChefStatus(); 
    }, 10000); 
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
    if (timerInterval) clearInterval(timerInterval);
});
</script>

<style scoped>
.bg-surface { background-color: #f9f9f9; }
.text-orange { color: #FF6B00 !important; }
.bg-orange { background-color: #FF6B00 !important; }
.bg-light-orange { background-color: #FFF4E6 !important; }
.badge-light-orange { background-color: #FFF4E6; color: #FF6B00; }
.btn-orange { background: #FF6B00; color: white; border: none; }
.btn-outline-orange { color: #FF6B00; border-color: #FF6B00; }
.btn-outline-orange:hover { background-color: #FFF4E6; }

.bg-success { background-color: #17c653 !important; }
.text-success { color: #17c653 !important; }
.badge-light-success { background-color: #e8fff3; color: #17c653; }
.shadow-success { box-shadow: 0 4px 15px rgba(23, 198, 83, 0.25); }

.bg-danger { background-color: #f1416c !important; }
.text-danger { color: #f1416c !important; }
.badge-light-danger { background-color: #fff5f8; color: #f1416c; }

.bg-gradient-orange { background: linear-gradient(135deg, #FF6B00 0%, #FF8800 100%); }
.shadow-orange { box-shadow: 0 4px 15px rgba(255, 107, 0, 0.25); }

.processing-card { border: 1px solid #FF6B00 !important; }
.delivery-card { border: 1px solid #17c653 !important; }
.late-card { border: 1px solid #f1416c !important; animation: shake 0.82s both; }

.animate-fade-in { animation: fadeIn 0.5s ease forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-pulse { animation: pulse 2s infinite; }
@keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }
@keyframes shake { 10%, 90% { transform: translate3d(-1px, 0, 0); } 20%, 80% { transform: translate3d(2px, 0, 0); } 30%, 50%, 70% { transform: translate3d(-4px, 0, 0); } 40%, 60% { transform: translate3d(4px, 0, 0); } }

.custom-scroll::-webkit-scrollbar { width: 5px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #E1E3EA; border-radius: 10px; }
.ls-tight { letter-spacing: -0.025em; }
.last-mb-0:last-child { margin-bottom: 0 !important; }
.hover-translate-up:hover { transform: translateY(-5px); }

/* DUKUNGAN DARK MODE */
[data-bs-theme="dark"] .bg-surface { background-color: #1e1e2d; }
[data-bs-theme="dark"] .text-gray-500 { color: #cdcdde !important; }
[data-bs-theme="dark"] .text-gray-400 { color: #9a9cae !important; }
[data-bs-theme="dark"] .text-gray-600 { color: #9a9cae !important; }
[data-bs-theme="dark"] .text-gray-300 { color: #5a5c6f !important; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-800 { color: #e4e6ef !important; }
[data-bs-theme="dark"] .bg-light { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: rgba(255, 107, 0, 0.15) !important; }
[data-bs-theme="dark"] .bg-white { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .card { background-color: #1e1e2d !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .badge-light-orange { background-color: rgba(255, 107, 0, 0.15); color: #ff8833; }
[data-bs-theme="dark"] .badge-light-success { background-color: rgba(23, 198, 83, 0.15); color: #17c653; }
[data-bs-theme="dark"] .badge-light-danger { background-color: rgba(241, 65, 108, 0.15); color: #f1416c; }
[data-bs-theme="dark"] .badge-light-primary { background-color: rgba(62, 151, 255, 0.15); color: #3E97FF; }
[data-bs-theme="dark"] .border-gray-300 { border-color: #323248 !important; }
[data-bs-theme="dark"] .border-dashed { border-color: #323248 !important; }
</style>