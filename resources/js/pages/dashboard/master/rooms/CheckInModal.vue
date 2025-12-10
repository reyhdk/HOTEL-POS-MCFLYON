<template>
  <div class="modal fade" id="kt_modal_check_in" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-500px">
      <div class="modal-content rounded-4 border-0 theme-modal shadow-lg">
        
        <div class="modal-header border-0 pb-0 justify-content-between align-items-center py-4 px-5">
            <div>
                <h2 class="fw-bold m-0 fs-3 text-gray-900">Check-in</h2>
                <div class="text-gray-500 fs-7 fw-semibold mt-1">
                    Kamar <span class="text-orange fw-bold">#{{ roomData?.room_number }}</span>
                </div>
            </div>
            <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
                <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>

        <div class="modal-body scroll-y px-5 pb-5 pt-2">
          <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef" label-position="top" class="modern-form">
            
            <div class="mb-4">
                <el-form-item prop="guest_id" class="mb-0">
                    <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase ls-1">Pilih Tamu</span></template>
                    <el-select v-model="formData.guest_id" filterable placeholder="Cari nama tamu..." class="w-100 metronic-input" :loading="loadingGuests">
                        <template #prefix><i class="ki-duotone ki-user fs-4 text-gray-500 me-2"><span class="path1"></span><span class="path2"></span></i></template>
                        <el-option v-for="guest in guests" :key="guest.id" :label="guest.name" :value="guest.id">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <span class="fw-bold text-gray-800">{{ guest.name }}</span>
                                <span class="fs-9 text-gray-400">{{ guest.phone_number }}</span>
                            </div>
                        </el-option>
                    </el-select>
                </el-form-item>
                <div class="d-flex justify-content-end mt-1">
                    <a href="#" class="text-orange fs-9 fw-bold hover-underline">Tamu Baru?</a>
                </div>
            </div>

            <div class="bg-light-subtle rounded-3 p-4 mb-5 border border-dashed border-gray-300">
                <div class="row g-3">
                    <div class="col-6">
                        <el-form-item prop="check_in_date" class="mb-0">
                            <template #label><span class="required fw-bold fs-9 text-gray-500 text-uppercase">Check-in</span></template>
                            <el-date-picker v-model="formData.check_in_date" type="date" class="w-100 metronic-date" placeholder="Tgl Masuk" format="DD/MM/YYYY" value-format="YYYY-MM-DD" :clearable="false"/>
                        </el-form-item>
                    </div>
                    <div class="col-6">
                        <el-form-item prop="check_out_date" class="mb-0">
                            <template #label><span class="required fw-bold fs-9 text-gray-500 text-uppercase">Check-out</span></template>
                            <el-date-picker v-model="formData.check_out_date" type="date" class="w-100 metronic-date" placeholder="Tgl Keluar" format="DD/MM/YYYY" value-format="YYYY-MM-DD" :clearable="false"/>
                        </el-form-item>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex flex-stack bg-light-primary rounded border border-dashed border-primary border-active p-4">
                    <div class="d-flex align-items-center me-2">
                        <i class="ki-duotone ki-eye-slash fs-2x me-4 text-primary">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                        </i>
                        <div class="flex-grow-1">
                            <span class="fw-bold text-gray-800 fs-6 d-block">Mode Incognito</span>
                            <span class="text-gray-500 fw-semibold fs-7">Sembunyikan identitas tamu di layar publik/POS</span>
                        </div>
                    </div>
                    <div class="form-check form-check-custom form-check-solid form-switch">
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="is_incognito" 
                            v-model="formData.is_incognito" 
                            :true-value="true" 
                            :false-value="false" 
                        />
                        <label class="form-check-label fw-bold text-gray-700" for="is_incognito"></label>
                    </div>
                </div>
            </div>

            <div class="mb-5">
                <label class="fw-bold fs-8 text-gray-600 text-uppercase ls-1 mb-3 d-block">Metode Pembayaran</label>
                <div class="row g-3">
                    <div class="col-6" v-for="method in paymentMethods" :key="method.value">
                        <div @click="formData.payment_method = method.value"
                             class="payment-card d-flex align-items-center p-3 rounded-3 border cursor-pointer transition-300 h-100 position-relative overflow-hidden"
                             :class="formData.payment_method === method.value ? 'border-orange bg-light-orange active' : 'border-gray-300 bg-white hover-border-gray-400'">
                             
                             <div class="symbol symbol-35px me-3">
                                 <div class="symbol-label rounded-circle" :class="formData.payment_method === method.value ? 'bg-orange text-white' : 'bg-light text-gray-500'">
                                     <i :class="method.icon" class="fs-4"></i>
                                 </div>
                             </div>
                             
                             <div class="d-flex flex-column">
                                 <span class="fw-bold fs-7" :class="formData.payment_method === method.value ? 'text-orange' : 'text-gray-700'">{{ method.label }}</span>
                                 <span class="fs-9 text-muted">{{ method.desc }}</span>
                             </div>

                             <i v-if="formData.payment_method === method.value" class="ki-duotone ki-check-circle fs-4 text-orange position-absolute top-0 end-0 m-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="totalCost > 0" class="d-flex align-items-center justify-content-between bg-light-success rounded-3 px-5 py-4 mb-6 border border-success border-dashed animate__animated animate__fadeIn">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-3">
                        <div class="symbol-label bg-white text-success shadow-sm rounded-circle">
                            <i class="ki-duotone ki-bill fs-2 text-success"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span></i>
                        </div>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="text-gray-600 fw-bold fs-8 text-uppercase">Total Biaya</span>
                        <span class="text-gray-800 fw-bolder fs-6">{{ durationInNights }} Malam x {{ formatCurrency(roomData?.price_per_night) }}</span>
                    </div>
                </div>
                <div class="text-end">
                    <span class="d-block text-success fw-bolder fs-2">{{ formatCurrency(totalCost) }}</span>
                </div>
            </div>

            <div class="d-flex gap-3 pt-2">
                <button type="button" class="btn btn-light w-100 fw-bold text-gray-600" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-orange w-100 fw-bold shadow-sm hover-elevate" type="submit" :disabled="loading">
                    <span v-if="!loading">
                        {{ formData.payment_method === 'cash' ? 'Konfirmasi' : 'Bayar Sekarang' }} 
                        <i class="ki-duotone ki-arrow-right fs-4 ms-2 text-white"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <span v-else class="indicator-progress">
                        Memproses... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>

          </el-form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import type { FormInstance, FormRules } from 'element-plus';

// ===== INTERFACES & TYPES =====
interface Guest {
    id: number;
    name: string;
    phone_number: string;
}

interface PaymentMethod {
    label: string;
    value: string;
    icon: string;
    desc: string;
}

interface FormData {
    room_id: number | null;
    guest_id: number | null;
    check_in_date: string;
    check_out_date: string;
    payment_method: string;
    is_incognito: boolean;
}

// Declare Midtrans Snap
declare const snap: any;

// ===== PROPS & EMITS =====
const props = defineProps<{ 
    roomData: any 
}>();

const emit = defineEmits<{
    'checkin-success': [];
    'close-modal': [];
}>();

// ===== REFS =====
const formRef = ref<FormInstance>();
const modalRef = ref<HTMLElement | null>(null);
const loading = ref(false);
const loadingGuests = ref(false);
const guests = ref<Guest[]>([]);

const paymentMethods = ref<PaymentMethod[]>([
    { label: 'Tunai', value: 'cash', icon: 'ki-duotone ki-wallet', desc: 'Bayar di Receptionist' },
    { label: 'QRIS', value: 'midtrans', icon: 'ki-duotone ki-scan-barcode', desc: 'Auto Payment' }
]);

// ===== FORM DATA =====
const getInitialFormData = (): FormData => ({
    room_id: null,
    guest_id: null,
    check_in_date: '',
    check_out_date: '',
    payment_method: 'cash',
    is_incognito: false,
});

const formData = ref<FormData>(getInitialFormData());

// ===== VALIDATION RULES =====
const rules = ref<FormRules>({
    guest_id: [{ required: true, message: "Pilih tamu", trigger: "change" }],
    check_in_date: [{ required: true, message: "Wajib", trigger: "change" }],
    check_out_date: [{ required: true, message: "Wajib", trigger: "change" }],
});

// ===== COMPUTED PROPERTIES =====
const durationInNights = computed(() => {
    if (!formData.value.check_in_date || !formData.value.check_out_date) return 0;
    const diffTime = new Date(formData.value.check_out_date).getTime() - new Date(formData.value.check_in_date).getTime();
    const nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return nights > 0 ? nights : 0;
});

const totalCost = computed(() => {
    if (!props.roomData || durationInNights.value <= 0) return 0;
    return props.roomData.price_per_night * durationInNights.value;
});

// ===== HELPER FUNCTIONS =====
const formatCurrency = (value: number) => {
    if (!value || isNaN(value)) return "Rp 0";
    return new Intl.NumberFormat("id-ID", { 
        style: "currency", 
        currency: "IDR", 
        minimumFractionDigits: 0 
    }).format(value);
};

// ===== API CALLS =====
const getGuests = async () => {
    try {
        loadingGuests.value = true;
        const { data } = await ApiService.get("/guests");
        guests.value = data;
    } catch (error) {
        console.error("Gagal mengambil data tamu:", error);
    } finally {
        loadingGuests.value = false;
    }
};

// ===== WATCHERS =====
watch(() => props.roomData, (newVal) => {
    if (newVal) {
        const today = new Date();
        const tomorrow = new Date();
        tomorrow.setDate(today.getDate() + 1);

        // ✅ FIX: Gunakan spread operator untuk memastikan semua property ter-set
        formData.value = {
            ...getInitialFormData(), // Start dengan default values
            room_id: newVal.id,
            check_in_date: today.toISOString().split('T')[0],
            check_out_date: tomorrow.toISOString().split('T')[0],
        };
    }
});

// ===== SUBMIT HANDLER =====
const submit = () => {
    if (!formRef.value) return;
    
    formRef.value.validate(async (valid: boolean) => {
        if (!valid) return;
        
        if (durationInNights.value <= 0) {
            Swal.fire({ 
                text: "Durasi minimal 1 malam.", 
                icon: "warning", 
                confirmButtonText: "Ok" 
            });
            return;
        }
        
        loading.value = true;
        let response: any = null;
        
        try {
            response = await ApiService.post('/check-in', formData.value);

            const handleSuccess = () => {
                Swal.fire({ 
                    text: "Check-in Berhasil!", 
                    icon: "success", 
                    timer: 1500, 
                    showConfirmButton: false 
                });
                emit('checkin-success');
                if (modalRef.value) {
                    Modal.getInstance(modalRef.value)?.hide();
                }
            };

            // Handle Midtrans Payment
            if (response.data.snap_token) {
                loading.value = false; // Stop loading to show Snap popup
                
                snap.pay(response.data.snap_token, {
                    onSuccess: handleSuccess,
                    onPending: () => {
                        Swal.fire("Pending", "Menunggu pembayaran selesai.", "info");
                        emit('checkin-success');
                        if (modalRef.value) {
                            Modal.getInstance(modalRef.value)?.hide();
                        }
                    },
                    onClose: () => {
                        Swal.fire("Dibatalkan", "Pembayaran belum diselesaikan.", "warning");
                    }
                });
            } else {
                // Cash payment - direct success
                handleSuccess();
            }
            
        } catch (error: any) {
            const errorMsg = error.response?.data?.message || "Gagal memproses check-in.";
            Swal.fire({ 
                text: errorMsg, 
                icon: "error" 
            });
        } finally {
            // Only set loading to false if not showing Midtrans
            if (!response?.data?.snap_token) {
                loading.value = false;
            }
        }
    });
};

// ===== LIFECYCLE =====
onMounted(() => {
    getGuests();
});
</script>

<style scoped>
/* ========================
   THEME COLORS
   ======================== */
.text-orange { color: #F68B1E !important; }
.bg-light-orange { background-color: #FFF8F1 !important; }
.border-orange { border-color: #F68B1E !important; }
.btn-orange { background-color: #F68B1E; color: white; border: none; }
.btn-orange:hover { background-color: #d97814; }

/* ========================
   PAYMENT CARD UI
   ======================== */
.payment-card { transition: all 0.2s ease; }
.payment-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.payment-card.active { box-shadow: 0 4px 12px rgba(246, 139, 30, 0.15); }

/* ========================
   INPUT STYLING (MATCHING OTHERS)
   ======================== */
.required:after { content: "*"; color: #f1416c; margin-left: 3px; }

:deep(.metronic-input .el-input__wrapper), 
:deep(.metronic-date .el-input__wrapper) {
    background-color: #F9F9F9;
    box-shadow: none !important; 
    border: 1px solid transparent; 
    border-radius: 0.6rem; 
    padding: 8px 12px;
    transition: all 0.2s;
    height: 42px;
}

:deep(.metronic-input .el-input__wrapper.is-focus),
:deep(.metronic-date .el-input__wrapper.is-focus) {
    background-color: #ffffff;
    border-color: #F68B1E !important;
    box-shadow: 0 0 0 3px rgba(246, 139, 30, 0.1) !important;
}

.hover-underline:hover { text-decoration: underline !important; }
.hover-elevate:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

/* ========================
   DARK MODE
   ======================== */
[data-bs-theme="dark"] .theme-modal { background-color: #1e1e2d; color: #ffffff; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-800 { color: #e1e3ea !important; }
[data-bs-theme="dark"] .text-gray-700,
[data-bs-theme="dark"] .text-gray-600 { color: #CDCDDE !important; }
[data-bs-theme="dark"] .bg-light-subtle { background-color: #1b1b29 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .bg-light-success { background-color: rgba(23, 198, 83, 0.15) !important; border-color: rgba(23, 198, 83, 0.3) !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: rgba(246, 139, 30, 0.15) !important; border-color: #F68B1E !important; }
[data-bs-theme="dark"] .border-gray-300 { border-color: #323248 !important; }

[data-bs-theme="dark"] .payment-card { 
    background-color: #1b1b29; border-color: #323248; 
}
[data-bs-theme="dark"] .payment-card.active { 
    background-color: rgba(246, 139, 30, 0.1) !important; 
    border-color: #F68B1E !important; 
}
[data-bs-theme="dark"] .payment-card:not(.active):hover {
    border-color: #565674;
}

[data-bs-theme="dark"] :deep(.metronic-input .el-input__wrapper),
[data-bs-theme="dark"] :deep(.metronic-date .el-input__wrapper) {
    background-color: #1b1b29 !important; 
    color: #ffffff;
    border-color: #323248 !important;
}
[data-bs-theme="dark"] :deep(.metronic-input .el-input__wrapper.is-focus),
[data-bs-theme="dark"] :deep(.metronic-date .el-input__wrapper.is-focus) {
    border-color: #F68B1E !important;
    background-color: #151521 !important;
}
[data-bs-theme="dark"] :deep(.el-input__inner) { color: #ffffff; }

[data-bs-theme="dark"] :deep(.el-select-dropdown__item.hover) { background-color: #2b2b40 !important; color: #F68B1E; }
[data-bs-theme="dark"] :deep(.el-select-dropdown__item) { color: #CDCDDE; }
[data-bs-theme="dark"] :deep(.el-select-dropdown) { background-color: #1e1e2d; border-color: #323248; }
</style>    