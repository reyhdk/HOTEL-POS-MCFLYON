<template>
  <div class="modal fade" id="kt_modal_check_in" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="fw-bold">Check-in Tamu ke Kamar {{ roomData?.room_number }}</h2>
          <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
            <i class="ki-duotone ki-cross fs-1"></i>
          </div>
        </div>
        <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef">
          <div class="modal-body py-10 px-lg-17">

            <div class="fv-row mb-7">
              <label class="required fs-6 fw-semibold mb-2">Pilih Tamu</label>
              <el-form-item prop="guest_id">
                <el-select v-model="formData.guest_id" filterable placeholder="Cari atau pilih nama tamu..." class="w-100" :loading="loadingGuests">
                  <el-option v-for="guest in guests" :key="guest.id" :label="guest.name" :value="guest.id"/>
                </el-select>
              </el-form-item>
            </div>

            <div class="row mb-7">
              <div class="col-md-6">
                <label class="required fs-6 fw-semibold mb-2">Tanggal Check-in</label>
                <el-form-item prop="check_in_date">
                  <el-date-picker v-model="formData.check_in_date" type="date" placeholder="Pilih tanggal" class="w-100" format="DD-MM-YYYY" value-format="YYYY-MM-DD"/>
                </el-form-item>
              </div>
              <div class="col-md-6">
                <label class="required fs-6 fw-semibold mb-2">Tanggal Check-out</label>
                <el-form-item prop="check_out_date">
                  <el-date-picker v-model="formData.check_out_date" type="date" placeholder="Pilih tanggal" class="w-100" format="DD-MM-YYYY" value-format="YYYY-MM-DD"/>
                </el-form-item>
              </div>
            </div>

            <div class="fv-row mb-10">
              <label class="required fs-6 fw-semibold mb-2">Metode Pembayaran</label>
              <div class="d-flex flex-wrap gap-5">
                <div
                  v-for="method in paymentMethods"
                  :key="method.value"
                  @click="formData.payment_method = method.value"
                  class="card card-flush h-100 flex-fill border border-dashed"
                  :class="{'border-primary shadow-sm border-2': formData.payment_method === method.value }"
                  style="cursor: pointer;">
                    <div class="card-body text-center d-flex flex-column justify-content-center p-5">
                        <i :class="method.icon" class="fs-3x mb-3"></i>
                        <span class="fw-semibold">{{ method.label }}</span>
                    </div>
                </div>
              </div>
            </div>

            <div v-if="totalCost > 0" class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6">
                <i class="ki-duotone ki-bill fs-2tx text-primary me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                <div class="d-flex flex-stack flex-grow-1">
                    <div class="fw-semibold">
                        <h4 class="text-gray-900 fw-bold">Total Tagihan ({{ durationInNights }} Malam)</h4>
                        <div class="fs-6 text-gray-700">{{ formatCurrency(totalCost) }}</div>
                    </div>
                </div>
            </div>

          </div>
          <div class="modal-footer flex-center">
            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
            <button :data-kt-indicator="loading ? 'on' : null" class="btn btn-lg btn-primary" type="submit" :disabled="loading">
              <span v-if="!loading" class="indicator-label">
                {{ formData.payment_method === 'cash' ? 'Konfirmasi Check-in' : 'Lanjutkan ke Pembayaran' }}
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
</template>

<script setup lang="ts">
import { ref, onMounted, defineProps, defineEmits, watch, computed } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import type { FormInstance, FormRules } from 'element-plus';

declare const snap: any;

const props = defineProps<{ roomData: any }>();
const emit = defineEmits(['checkin-success']);

const formRef = ref<FormInstance>();
const modalRef = ref<null | HTMLElement>(null);
const loading = ref(false);
const loadingGuests = ref(true);

const guests = ref<{ id: number; name: string; phone_number: string }[]>([]);
const paymentMethods = ref([
    { label: 'Tunai (Cash)', value: 'cash', icon: 'ki-duotone ki-dollar text-success' },
    { label: 'Online (Midtrans)', value: 'midtrans', icon: 'ki-duotone ki-credit-cart text-primary' }
]);
const formData = ref({
  room_id: null as number | null,
  guest_id: null as number | null,
  check_in_date: '',
  check_out_date: '',
  payment_method: 'cash',
});

const rules = ref<FormRules>({
  guest_id: [{ required: true, message: "Tamu wajib dipilih.", trigger: "change" }],
  check_in_date: [{ required: true, message: "Tanggal check-in wajib diisi.", trigger: "change" }],
  check_out_date: [{ required: true, message: "Tanggal check-out wajib diisi.", trigger: "change" }],
});

const durationInNights = computed(() => {
    if (!formData.value.check_in_date || !formData.value.check_out_date) return 0;
    const start = new Date(formData.value.check_in_date);
    const end = new Date(formData.value.check_out_date);
    const diffTime = end.getTime() - start.getTime();
    if (diffTime <= 0) return 0;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

const totalCost = computed(() => {
    if (!props.roomData || durationInNights.value <= 0) return 0;
    return props.roomData.price_per_night * durationInNights.value;
});

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

const formatCurrency = (value: number) => {
    if (!value || isNaN(value)) return "Rp 0";
    return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

onMounted(getGuests);

watch(() => props.roomData, (newVal) => {
    if (newVal) {
        const today = new Date();
        const tomorrow = new Date();
        tomorrow.setDate(today.getDate() + 1);

        formData.value = {
            room_id: newVal.id,
            guest_id: null,
            check_in_date: today.toISOString().split('T')[0],
            check_out_date: tomorrow.toISOString().split('T')[0],
            payment_method: 'cash',
        };
    }
});

const submit = () => {
  if (!formRef.value) return;
  formRef.value.validate(async (valid: boolean) => {
    if (valid) {
      if (durationInNights.value <= 0) {
        Swal.fire("Error!", "Tanggal check-out harus setelah tanggal check-in.", "error");
        return;
      }
      loading.value = true;
      let response: any = null;
      try {
        response = await ApiService.post('/check-in', formData.value);

        if (response.data.snap_token) {
            loading.value = false;
            snap.pay(response.data.snap_token, {
                onSuccess: () => {
                    Swal.fire("Berhasil!", "Pembayaran berhasil dan tamu telah check-in.", "success");
                    emit('checkin-success');
                    if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
                },
                onPending: () => {
                    Swal.fire("Tertunda", "Pembayaran tertunda. Check-in akan aktif setelah pembayaran lunas.", "info");
                    emit('checkin-success');
                    if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
                },
                onClose: () => {
                    Swal.fire("Dibatalkan", "Pembayaran dibatalkan. Data check-in telah dibuat dengan status pending.", "warning");
                    emit('checkin-success');
                    if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
                }
            });
        } else {
            Swal.fire("Berhasil!", "Tamu telah berhasil check-in.", "success");
            emit('checkin-success');
            if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
        }
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || "Gagal melakukan check-in.";
        Swal.fire("Error!", errorMsg, "error");
      } finally {
        if (!response?.data?.snap_token) {
            loading.value = false;
        }
      }
    }
  });
};
</script>
