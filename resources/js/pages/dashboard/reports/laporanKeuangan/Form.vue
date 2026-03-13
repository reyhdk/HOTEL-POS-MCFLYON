<template>
  <div class="modal fade" id="kt_modal_cashflow" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-600px">
      <div class="modal-content rounded shadow-lg border-0">
        
        <!-- Modal Header -->
        <div class="modal-header border-0 pb-0 pt-5 px-7">
          <h2 class="fw-bolder m-0 text-gray-900">
            <i class="ki-duotone ki-wallet fs-2 text-orange me-2">
              <span class="path1"></span>
              <span class="path2"></span>
              <span class="path3"></span>
              <span class="path4"></span>
            </i>
            Catat Transaksi Manual
          </h2>
          <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
            <i class="ki-duotone ki-cross fs-1">
              <span class="path1"></span>
              <span class="path2"></span>
            </i>
          </div>
        </div>

        <!-- Modal Body -->
        <div class="modal-body scroll-y px-7 py-5">
          <el-form 
            @submit.prevent="submit" 
            :model="formData" 
            :rules="rules" 
            ref="formRef" 
            label-position="top"
            size="default"
          >
            
            <div class="row g-3 mb-4">
              <!-- Tanggal Transaksi -->
              <div class="col-md-6">
                <el-form-item prop="transaction_date" label="Tanggal Transaksi" class="mb-0">
                  <el-date-picker
                    v-model="formData.transaction_date"
                    type="datetime"
                    placeholder="Pilih tanggal & waktu"
                    format="DD MMM YYYY HH:mm"
                    value-format="YYYY-MM-DD HH:mm:ss"
                    class="w-100 metronic-datepicker-orange"
                  />
                </el-form-item>
              </div>

              <!-- Tipe Transaksi -->
              <div class="col-md-6">
                <el-form-item prop="type" label="Tipe Arus Kas" class="mb-0">
                  <el-select 
                    v-model="formData.type" 
                    placeholder="Pilih tipe" 
                    class="w-100 metronic-select-orange"
                  >
                    <el-option label="Pemasukan (Income)" value="income">
                      <span class="text-success fw-bold"><i class="ki-duotone ki-arrow-down text-success me-1"><span class="path1"></span><span class="path2"></span></i> Pemasukan</span>
                    </el-option>
                    <el-option label="Pengeluaran (Expense)" value="expense">
                      <span class="text-danger fw-bold"><i class="ki-duotone ki-arrow-up text-danger me-1"><span class="path1"></span><span class="path2"></span></i> Pengeluaran</span>
                    </el-option>
                  </el-select>
                </el-form-item>
              </div>
            </div>

            <!-- Kategori -->
            <el-form-item prop="category" label="Kategori" class="mb-4">
              <el-select 
                v-model="formData.category" 
                placeholder="Pilih kategori transaksi" 
                class="w-100 metronic-select-orange"
              >
                <el-option label="Booking Kamar" value="booking" />
                <el-option label="Restoran & Cafe" value="resto" />
                <el-option label="Laundry" value="laundry" />
                <el-option label="Pembelian Stok Gudang" value="warehouse" />
                <el-option label="Operasional & Lain-lain" value="other" />
              </el-select>
            </el-form-item>

            <!-- Deskripsi -->
            <el-form-item prop="description" label="Deskripsi / Keterangan" class="mb-4">
              <el-input 
                v-model="formData.description" 
                type="textarea"
                :rows="2"
                placeholder="Contoh: Pembayaran tagihan listrik bulan November" 
                class="metronic-input-orange"
              />
            </el-form-item>

            <div class="separator separator-dashed my-5"></div>

            <div class="row g-3 mb-4">
              <!-- Nominal (Amount) -->
              <div class="col-md-12">
                <el-form-item prop="amount" label="Nominal (Rp)" class="mb-0">
                  <el-input 
                    v-model="formData.amount" 
                    type="number" 
                    placeholder="0" 
                    class="metronic-input-orange input-number-lg"
                  >
                    <template #prefix>
                      <span class="text-gray-600 fw-bold ms-2 mt-1 fs-5">Rp</span>
                    </template>
                  </el-input>
                </el-form-item>
              </div>
            </div>

            <div class="row g-3">
              <!-- Metode Pembayaran -->
              <div class="col-md-6">
                <el-form-item prop="payment_method" label="Metode Pembayaran" class="mb-0">
                  <el-select 
                    v-model="formData.payment_method" 
                    placeholder="Pilih metode" 
                    class="w-100 metronic-select-orange"
                    allow-create
                    filterable
                  >
                    <el-option label="Cash (Tunai)" value="Cash" />
                    <el-option label="Transfer Bank" value="Transfer Bank" />
                    <el-option label="Midtrans / QRIS" value="Midtrans" />
                    <el-option label="Kartu Kredit/Debit" value="Kartu Kredit/Debit" />
                  </el-select>
                </el-form-item>
              </div>

              <!-- Referensi ID -->
              <div class="col-md-6">
                <el-form-item prop="reference_id" label="No. Referensi (Opsional)" class="mb-0">
                  <el-input 
                    v-model="formData.reference_id" 
                    placeholder="Contoh: INV-001" 
                    class="metronic-input-orange"
                  >
                    <template #prefix>
                      <i class="ki-duotone ki-tag fs-3 text-gray-500">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                      </i>
                    </template>
                  </el-input>
                </el-form-item>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-end gap-2 mt-8 pt-3 border-top">
              <button type="button" class="btn btn-light btn-sm fw-bold" data-bs-dismiss="modal">
                <i class="ki-duotone ki-cross fs-3 me-1">
                  <span class="path1"></span>
                  <span class="path2"></span>
                </i>
                Batal
              </button>
              <button type="button" class="btn btn-orange btn-sm fw-bold" @click="submit" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="ki-duotone ki-check fs-3 me-1">
                  <span class="path1"></span>
                  <span class="path2"></span>
                </i>
                Simpan Transaksi
              </button>
            </div>

          </el-form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { Modal } from 'bootstrap';
import ApiService from "@/core/services/ApiService";
import Swal from 'sweetalert2';

// Emits
const emit = defineEmits(['saved']);

// Refs
const modalRef = ref<HTMLElement | null>(null);
const formRef = ref<any>(null);
const modalInstance = ref<any>(null);
const loading = ref(false);

// Form Data
const formData = reactive({
  transaction_date: '',
  type: '',
  category: '',
  description: '',
  amount: '',
  payment_method: 'Cash',
  reference_id: ''
});

// Validation Rules
const rules = reactive({
  transaction_date: [{ required: true, message: 'Tanggal wajib diisi', trigger: 'change' }],
  type: [{ required: true, message: 'Tipe transaksi wajib dipilih', trigger: 'change' }],
  category: [{ required: true, message: 'Kategori wajib dipilih', trigger: 'change' }],
  description: [{ required: true, message: 'Deskripsi wajib diisi', trigger: 'blur' }],
  amount: [
    { required: true, message: 'Nominal wajib diisi', trigger: 'blur' },
    { validator: (rule: any, value: any, callback: any) => {
        if (value <= 0) {
          callback(new Error('Nominal harus lebih dari 0'));
        } else {
          callback();
        }
      }, trigger: 'blur'
    }
  ],
  payment_method: [{ required: true, message: 'Metode pembayaran wajib diisi', trigger: 'change' }]
});

onMounted(() => {
  if (modalRef.value) {
    modalInstance.value = new Modal(modalRef.value);
  }
});

// Open Modal
const open = () => {
  resetForm();
  
  // Set default datetime to now
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');
  formData.transaction_date = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

  if (modalInstance.value) {
    modalInstance.value.show();
  }
};

const resetForm = () => {
  if (formRef.value) {
    formRef.value.resetFields();
  }
  Object.assign(formData, {
    transaction_date: '',
    type: '',
    category: '',
    description: '',
    amount: '',
    payment_method: 'Cash',
    reference_id: ''
  });
};

const submit = () => {
  if (!formRef.value) return;

  formRef.value.validate(async (valid: boolean) => {
    if (valid) {
      loading.value = true;
      try {
        await ApiService.post("cash-flow", formData);

        Swal.fire({
          text: "Transaksi manual berhasil dicatat!",
          icon: "success",
          buttonsStyling: false,
          confirmButtonText: "Ok",
          customClass: { confirmButton: "btn btn-orange" }
        });

        if (modalInstance.value) {
          modalInstance.value.hide();
        }
        emit('saved');
      } catch (error: any) {
        Swal.fire({
          text: error.response?.data?.message || "Terjadi kesalahan sistem.",
          icon: "error",
          buttonsStyling: false,
          confirmButtonText: "Ok",
          customClass: { confirmButton: "btn btn-danger" }
        });
      } finally {
        loading.value = false;
      }
    }
  });
};

defineExpose({ open });
</script>

<style scoped>
/* ========================
   ORANGE THEME
   ======================== */
.text-orange { color: #F68B1E !important; }
.bg-orange { background-color: #F68B1E !important; }
.btn-orange {
  background-color: #F68B1E;
  border-color: #F68B1E;
  color: #fff;
}
.btn-orange:hover:not(:disabled) {
  background-color: #e57b0e;
  border-color: #e57b0e;
  color: #fff;
}
.btn-orange:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ========================
   INPUT STYLING
   ======================== */
.metronic-input-orange :deep(.el-input__wrapper),
.metronic-input-orange :deep(.el-textarea__inner),
.metronic-select-orange :deep(.el-input__wrapper),
.metronic-datepicker-orange :deep(.el-input__wrapper) {
  background-color: var(--bs-body-bg);
  border: 1px solid #e4e6ef;
  box-shadow: none;
  transition: all 0.3s;
}

.metronic-input-orange :deep(.el-input__wrapper:hover),
.metronic-input-orange :deep(.el-textarea__inner:hover),
.metronic-select-orange :deep(.el-input__wrapper:hover),
.metronic-datepicker-orange :deep(.el-input__wrapper:hover) {
  border-color: #F68B1E;
}

.metronic-input-orange :deep(.el-input__wrapper.is-focus),
.metronic-input-orange :deep(.el-textarea__inner:focus),
.metronic-select-orange :deep(.el-input__wrapper.is-focus),
.metronic-datepicker-orange :deep(.el-input__wrapper.is-focus) {
  border-color: #F68B1E;
  box-shadow: 0 0 0 0.15rem rgba(246, 139, 30, 0.15);
}

.input-number-lg :deep(.el-input__inner) {
  font-size: 1.25rem;
  font-weight: 700;
  color: #F68B1E;
}

/* ========================
   FORM LABEL
   ======================== */
:deep(.el-form-item__label) {
  font-weight: 600;
  color: var(--bs-gray-700);
  font-size: 0.925rem;
  margin-bottom: 0.5rem;
}

/* ========================
   MODAL SCROLL & THEME
   ======================== */
.modal-content { border-radius: 0.75rem; }
.scroll-y { max-height: calc(100vh - 200px); overflow-y: auto; }
.scroll-y::-webkit-scrollbar { width: 6px; }
.scroll-y::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.scroll-y::-webkit-scrollbar-thumb { background: #F68B1E; border-radius: 10px; }
.scroll-y::-webkit-scrollbar-thumb:hover { background: #e57b0e; }

[data-bs-theme="dark"] .scroll-y::-webkit-scrollbar-track { background: #1b1b29; }
[data-bs-theme="dark"] :deep(.el-form-item__label) { color: var(--bs-gray-400); }
[data-bs-theme="dark"] .metronic-input-orange :deep(.el-input__wrapper),
[data-bs-theme="dark"] .metronic-input-orange :deep(.el-textarea__inner),
[data-bs-theme="dark"] .metronic-select-orange :deep(.el-input__wrapper),
[data-bs-theme="dark"] .metronic-datepicker-orange :deep(.el-input__wrapper) {
  background-color: #1b1b29;
  border-color: #323248;
}
</style>