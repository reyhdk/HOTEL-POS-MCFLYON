<template>
  <VForm class="form w-100" @submit="onSubmitLogin" :validation-schema="loginSchema">
    <div class="fv-row mb-10">
      <label class="form-label fs-6 fw-bold">Email</label>
      <Field
        tabindex="1"
        class="form-control form-control-lg form-control-solid"
        type="text"
        name="email"
        autocomplete="off"
      />
      <div class="fv-plugins-message-container">
        <ErrorMessage name="email" class="fv-help-block" />
      </div>
    </div>
    <div class="fv-row mb-5">
      <div class="d-flex flex-stack mb-2">
        <label class="form-label fw-bold fs-6 mb-0">Password</label>
      </div>
      <div class="position-relative mb-3">
        <Field
          tabindex="2"
          class="form-control form-control-lg form-control-solid"
          :type="passwordFieldType"
          name="password"
          autocomplete="off"
        />
        <span
          class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2"
          @click="togglePasswordVisibility"
        >
          <i
            :class="
              passwordFieldType === 'password'
                ? 'bi-eye-slash'
                : 'bi-eye'
            "
            class="bi fs-2"
          ></i>
        </span>
      </div>
      <div class="fv-plugins-message-container">
        <ErrorMessage name="password" class="fv-help-block" />
      </div>
    </div>
    <div class="text-center">
      <button
        tabindex="3"
        type="submit"
        ref="submitButton"
        class="btn btn-lg btn-primary w-100 mb-5"
      >
        <span class="indicator-label">Login</span>
        <span class="indicator-progress">
          Please wait...
          <span
            class="spinner-border spinner-border-sm align-middle ms-2"
          ></span>
        </span>
      </button>
    </div>
    </VForm>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import { useAuthStore } from "@/stores/auth";
// import { useRouter } from "vue-router"; // Tidak lagi diperlukan
import * as Yup from "yup";
import Swal from "sweetalert2";

const store = useAuthStore();
// const router = useRouter(); // Tidak lagi diperlukan, redirect diurus oleh auth.ts
const submitButton = ref<HTMLButtonElement | null>(null);

const passwordFieldType = ref("password");

// Skema validasi (sudah benar)
const loginSchema = Yup.object().shape({
  email: Yup.string().email("Email tidak valid").required("Email wajib diisi").label("Email"),
  password: Yup.string().min(8, "Password minimal 8 karakter").required("Password wajib diisi").label("Password"),
});

// Fungsi untuk toggle visibilitas password (sudah benar)
const togglePasswordVisibility = () => {
  passwordFieldType.value = passwordFieldType.value === "password" ? "text" : "password";
};

// ▼▼▼ [DIBENARKAN] FUNGSI SUBMIT DENGAN PENANGANAN ERROR YANG BENAR ▼▼▼
const onSubmitLogin = async (values: any) => {
  if (submitButton.value) {
    submitButton.value.disabled = true;
    submitButton.value.setAttribute("data-kt-indicator", "on");
  }

  try {
    // 1. Mencoba untuk login
    await store.login(values);

    // 2. Jika berhasil (tidak ada error), tampilkan notifikasi sukses
    Swal.fire({
      text: "Login berhasil!",
      icon: "success",
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 1500,
    });
    // Pengalihan halaman akan diurus secara otomatis oleh auth.ts

  } catch (error) {
    // 3. Jika store.login() gagal, blok ini akan dijalankan
    Swal.fire({
      text: store.errors as string || "Email atau password salah.",
      icon: "error",
      buttonsStyling: false,
      confirmButtonText: "Coba Lagi",
      customClass: { confirmButton: "btn btn-danger" },
    });

  } finally {
    // 4. Blok ini akan SELALU berjalan, baik login sukses maupun gagal
    // Pastikan tombol submit selalu aktif kembali
    if (submitButton.value) {
      submitButton.value.disabled = false;
      submitButton.value.removeAttribute("data-kt-indicator");
    }
  }
};
</script>