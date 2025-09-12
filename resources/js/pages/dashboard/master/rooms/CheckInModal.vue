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
                <el-select
                  v-model="formData.guest_id"
                  filterable
                  placeholder="Cari atau pilih nama tamu..."
                  class="w-100"
                  :loading="loadingGuests"
                >
                  <el-option
                    v-for="guest in guests"
                    :key="guest.id"
                    :label="guest.name"
                    :value="guest.id"
                  />
                </el-select>
              </el-form-item>
            </div>
          </div>
          <div class="modal-footer flex-center">
            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
            <button :data-kt-indicator="loading ? 'on' : null" class="btn btn-lg btn-primary" type="submit">
              <span v-if="!loading" class="indicator-label">Konfirmasi Check-in</span>
            </button>
          </div>
        </el-form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, defineProps, defineEmits, watch } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";

// Menerima data kamar dari parent
const props = defineProps<{ roomData: any }>();
const emit = defineEmits(['checkin-success']);

const formRef = ref<any>(null);
const modalRef = ref<null | HTMLElement>(null);
const loading = ref(false);
const loadingGuests = ref(true);

const guests = ref<{ id: number; name: string }[]>([]);
const formData = ref({
  room_id: null as number | null,
  guest_id: null as number | null,
});

// Aturan validasi
const rules = ref({
  guest_id: [{ required: true, message: "Tamu wajib dipilih.", trigger: "change" }],
});

// Ambil daftar tamu dari server
const getGuests = async () => {
  try {
    loadingGuests.value = true;
    const response = await axios.get("/guests");
    guests.value = response.data;
  } catch (error) {
    console.error("Gagal mengambil data tamu:", error);
  } finally {
    loadingGuests.value = false;
  }
};

// Saat komponen dimuat, ambil daftar tamu
onMounted(() => {
  getGuests();
});

// Setiap kali `roomData` berubah (saat modal dibuka), update `room_id` di form
watch(() => props.roomData, (newVal) => {
    if (newVal) {
        formData.value.room_id = newVal.id;
        formData.value.guest_id = null; // Kosongkan pilihan tamu
    }
});

const submit = () => {
  if (!formRef.value) return;
  formRef.value.validate(async (valid: boolean) => {
    if (valid) {
      loading.value = true;
      try {
        await axios.post('/check-in', formData.value);
        Swal.fire("Berhasil!", "Tamu telah berhasil check-in.", "success");
        emit('checkin-success');
        if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
      } catch (error: any) {
        let errorMsg = "Gagal melakukan check-in.";
        if (error.response?.data?.message) {
          errorMsg = error.response.data.message;
        }
        Swal.fire("Error!", errorMsg, "error");
      } finally {
        loading.value = false;
      }
    }
  });
};
</script>