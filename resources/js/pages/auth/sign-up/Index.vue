<template>
  <div class="w-lg-500px p-10">
    <VForm class="form w-100" @submit="onSubmitRegister" :validation-schema="registrationSchema">
      <div class="mb-10 text-center">
        <h1 class="text-dark mb-3">Buat Akun Baru</h1>
        <div class="text-gray-400 fw-semobold fs-4">
          Sudah punya akun?
          <router-link to="/" class="link-primary fw-bold">Login di sini</router-link>
        </div>
      </div>
      <div class="fv-row mb-7">
        <label class="form-label fw-bold text-dark fs-6">Nama Lengkap</label>
        <Field class="form-control form-control-lg form-control-solid" type="text" name="name" autocomplete="off"/>
        <ErrorMessage name="name" class="fv-help-block" />
      </div>
      <div class="fv-row mb-7">
        <label class="form-label fw-bold text-dark fs-6">Email</label>
        <Field class="form-control form-control-lg form-control-solid" type="email" name="email" autocomplete="off"/>
        <ErrorMessage name="email" class="fv-help-block" />
      </div>
      <div class="fv-row mb-7">
        <label class="form-label fw-bold text-dark fs-6">Password</label>
        <Field class="form-control form-control-lg form-control-solid" type="password" name="password" autocomplete="off"/>
        <ErrorMessage name="password" class="fv-help-block" />
      </div>
      <div class="fv-row mb-10">
        <label class="form-label fw-bold text-dark fs-6">Konfirmasi Password</label>
        <Field class="form-control form-control-lg form-control-solid" type="password" name="password_confirmation" autocomplete="off"/>
        <ErrorMessage name="password_confirmation" class="fv-help-block" />
      </div>
      <div class="text-center">
        <button ref="submitButton" type="submit" class="btn btn-lg btn-primary w-100">
          <span class="indicator-label">Daftar</span>
          <span class="indicator-progress">
            Memproses...
            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
          </span>
        </button>
      </div>
    </VForm>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import * as Yup from "yup";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import Swal from "sweetalert2";

const submitButton = ref<HTMLButtonElement | null>(null);
const store = useAuthStore();
const router = useRouter();

const registrationSchema = Yup.object().shape({
  name: Yup.string().required().label("Nama Lengkap"),
  email: Yup.string().min(4).required().email().label("Email"),
  password: Yup.string().required().min(8).label("Password"),
  password_confirmation: Yup.string().required().oneOf([Yup.ref("password")], "Password harus sama").label("Konfirmasi password"),
});

const onSubmitRegister = async (values: any) => {
  if (submitButton.value) {
    submitButton.value.disabled = true;
    submitButton.value.setAttribute("data-kt-indicator", "on");
  }

  // Panggil aksi register dari store
  await store.register(values);

  // PERBAIKAN: Cek status login sebagai indikator sukses
  if (store.isAuthenticated) {
    Swal.fire({
      text: "Registrasi berhasil! Anda akan dialihkan ke dasbor.",
      icon: "success",
      buttonsStyling: false,
      confirmButtonText: "Ok, Lanjutkan!",
      customClass: { confirmButton: "btn btn-primary" },
    }).then(() => {
      router.push({ name: "dashboard" });
    });
  } else {
    // Jika tidak sukses, tampilkan pesan error dari store
    Swal.fire({
      text: store.errors as string, // Tampilkan pesan error
      icon: "error",
      buttonsStyling: false,
      confirmButtonText: "Coba Lagi",
      customClass: { confirmButton: "btn btn-primary" },
    });
  }

  // Aktifkan kembali tombol submit
  submitButton.value?.removeAttribute("data-kt-indicator");
  if (submitButton.value) {
    submitButton.value.disabled = false;
  }
};
</script>