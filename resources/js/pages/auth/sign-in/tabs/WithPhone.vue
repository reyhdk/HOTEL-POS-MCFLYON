<template>
  <VForm class="form w-100" @submit="handleLogin" :validation-schema="loginSchema">
    <div class="fv-row mb-10">
      <label class="form-label fs-6 fw-bold text-dark">No. Telepon</label>
      <Field
        tabindex="1"
        class="form-control form-control-lg form-control-solid"
        type="text"
        name="identifier"
        autocomplete="off"
        v-model="identifier"
      />
      <div class="fv-plugins-message-container">
        <div class="fv-help-block">
          <ErrorMessage name="identifier" />
        </div>
      </div>
    </div>
    <div v-if="/^08[0-9]\d{8,11}$/.test(identifier)" class="fv-row mb-5">
      <label class="form-label fw-bold text-dark fs-6 d-flex align-items-center justify-content-between">
        Kode OTP
      </label>
      <v-otp-input
        input-classes="form-control form-control-lg form-control-solid text-center"
        separator="-"
        :num-inputs="6"
        input-type="numeric"
        v-model:value="otpValue"
      />
      <Field type="hidden" name="otp" v-model="otpValue" />
      <div class="fv-plugins-message-container">
        <div class="fv-help-block">
          <ErrorMessage name="otp" />
        </div>
      </div>
      <div v-if="otpInterval == 0" class="text-gray-400 fw-semobold fs-4 text-end mt-4">
        Tidak menerima kode?
        <button type="button" class="btn p-0 link-primary fw-bold" @click="sendOtp">
          Kirim ulang
        </button>
      </div>
      <div v-else class="text-gray-400 fw-semobold fs-4 text-end mt-4">
        Kirim ulang dalam <span class="fw-bold">{{ otpInterval }}</span> detik
      </div>
    </div>
    <div class="text-center">
      <button
        tabindex="3"
        type="submit"
        ref="submitButton"
        class="btn btn-lg btn-primary w-100 mb-5"
        :disabled="isSubmitting"
      >
        <span class="indicator-label">Login</span>
        <span class="indicator-progress">
          Memproses...
          <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
        </span>
      </button>
      </div>
    </VForm>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { Field, Form as VForm, ErrorMessage } from "vee-validate";
import { useAuthStore } from "@/stores/auth";
import * as Yup from "yup";
import { toast } from "vue3-toastify";
import ApiService from "@/core/services/ApiService"; // Impor ApiService untuk OTP

// --- Setup & Inisialisasi ---
const store = useAuthStore();
const submitButton = ref<HTMLButtonElement | null>(null);
const isSubmitting = ref(false);

// State untuk input form
const identifier = ref(""); // Nomor telepon
const otpValue = ref("");   // Kode OTP

// --- Skema Validasi dengan Yup ---
const loginSchema = Yup.object().shape({
  identifier: Yup.string()
    .matches(/^08[0-9]\d{8,11}$/, "No. Telepon tidak valid")
    .required("Harap masukkan No. Telepon")
    .label("No. Telepon"),
  otp: Yup.string()
    .min(6, "Kode OTP harus 6 digit")
    .required('Harap masukkan Kode OTP')
    .label("Kode OTP"),
});

// --- Logika Login ---
const handleLogin = async (values: any) => {
  isSubmitting.value = true;
  if (submitButton.value) {
    submitButton.value.setAttribute("data-kt-indicator", "on");
  }

  try {
    // Memanggil aksi login dari store, sama seperti di WithEmail.vue
    // Tambahkan 'type: "phone"' agar backend tahu ini login via telepon
    await store.login({ ...values, type: "phone" });

    // Jika berhasil, store dan router akan menangani sisanya
    toast.success("Login berhasil!");

  } catch (error) {
    // Jika gagal, store akan mengisi `store.errors`
    // Tampilkan pesan error dari store
    toast.error(store.errors as string || "Login gagal, silakan coba lagi.");
  } finally {
    isSubmitting.value = false;
    if (submitButton.value) {
      submitButton.value.removeAttribute("data-kt-indicator");
    }
  }
};

// --- Logika OTP ---
const otpInterval = ref(0);
let timeIntv: any = null;

const sendOtp = async () => {
  if (!identifier.value) {
    toast.error("Silakan masukkan nomor telepon terlebih dahulu.");
    return;
  }

  try {
    // 1. Kirim request untuk mendapatkan OTP
    await ApiService.post('/auth/getOtp', { identifier: identifier.value });
    toast.success('Kode OTP berhasil dikirim ke No. Telepon Anda');

    // 2. Mulai timer hitung mundur
    otpInterval.value = 30;
    handleOtpInterval();
  } catch (err: any) {
    toast.error(err.response?.data?.message || "Gagal mengirim OTP.");
  }
};

const handleOtpInterval = () => {
  clearInterval(timeIntv);
  timeIntv = setInterval(() => {
    otpInterval.value--;
    if (otpInterval.value === 0) {
      clearInterval(timeIntv);
    }
  }, 1000);
};
</script>