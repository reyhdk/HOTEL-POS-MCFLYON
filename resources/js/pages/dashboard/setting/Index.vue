<template>
    <VForm class="card mb-10" @submit="submit" :validation-schema="formSchema" ref="formRef">
        
        <div class="card-header align-items-center">
            <h2 class="mb-0">Konfigurasi Website</h2>
        </div>

        <div class="card-body">
            
            <div class="row mb-8">
                <div class="col-md-6">
                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold fs-6 required">Nama Aplikasi</label>
                        <Field class="form-control form-control-lg form-control-solid" 
                            type="text" name="app" autocomplete="off" 
                            v-model="formData.app" placeholder="Masukkan nama aplikasi" />
                        <div class="fv-plugins-message-container">
                            <ErrorMessage name="app" class="text-danger" />
                        </div>
                    </div>

                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold fs-6 required">Deskripsi</label>
                        <Field as="textarea" rows="4" class="form-control form-control-lg form-control-solid" 
                            name="description" v-model="formData.description" 
                            placeholder="Deskripsi singkat aplikasi" />
                        <div class="fv-plugins-message-container">
                            <ErrorMessage name="description" class="text-danger" />
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="bg-light-primary rounded border-primary border border-dashed p-6">
                        <h4 class="text-primary fw-bold mb-4">
                            <i class="ki-duotone ki-time fs-2 me-2 text-primary">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            Kebijakan Waktu Hotel
                        </h4>
                        
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label fw-bold fs-6 required">Jam Check-In</label>
                                <Field name="check_in_time" v-model="formData.check_in_time" 
                                    class="form-control form-control-solid" type="time" />
                                <div class="text-muted fs-7 mt-2">Waktu standar tamu masuk.</div>
                                <ErrorMessage name="check_in_time" class="text-danger" />
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold fs-6 required">Jam Check-Out</label>
                                <Field name="check_out_time" v-model="formData.check_out_time" 
                                    class="form-control form-control-solid" type="time" />
                                <div class="text-muted fs-7 mt-2">Batas akhir tamu keluar.</div>
                                <ErrorMessage name="check_out_time" class="text-danger" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="separator my-10"></div>

            <div class="row">
                <div class="col-md-4 mb-8">
                    <label class="form-label fw-bold fs-6">Logo Aplikasi</label>
                    <file-upload 
                        :files="files.logo" 
                        :accepted-file-types="['image/*']"
                        :required="false"
                        v-on:updatefiles="(file) => (formData.logo = file)"
                    ></file-upload>
                    <div class="text-muted fs-7 mt-2">Format: png, jpg, jpeg.</div>
                </div>

                <div class="col-md-4 mb-8">
                    <label class="form-label fw-bold fs-6">Background Login</label>
                    <file-upload 
                        :files="files.bgAuth" 
                        :accepted-file-types="['image/*']"
                        :required="false"
                        v-on:updatefiles="(file) => (formData.bg_auth = file)"
                    ></file-upload>
                </div>

                <div class="col-md-4 mb-8">
                    <label class="form-label fw-bold fs-6">Background Landing</label>
                    <file-upload 
                        :files="files.bgLanding" 
                        :accepted-file-types="['image/*']"
                        :required="false"
                        v-on:updatefiles="(file) => (formData.bg_landing = file)"
                    ></file-upload>
                </div>
            </div>

        </div>

        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="submit" class="btn btn-primary" :data-kt-indicator="loading ? 'on' : 'off'" :disabled="loading">
                <span class="indicator-label">Simpan Perubahan</span>
                <span class="indicator-progress">
                    Loading... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>
    </VForm>
</template>

<script>
import { defineComponent } from "vue";
import * as Yup from "yup";
import ApiService from "@/core/services/ApiService"; 
import FileUpload from "@/components/FileUpload.vue"; 
import Swal from "sweetalert2";

export default defineComponent({
    name: "admin-setting",
    components: {
        FileUpload
    },
    setup() {
        // Skema Validasi
        const formSchema = Yup.object().shape({
            app: Yup.string().required("Nama aplikasi harus diisi"),
            description: Yup.string().required("Deskripsi harus diisi"),
            check_in_time: Yup.string().required("Jam Check-in wajib diisi"),
            check_out_time: Yup.string().required("Jam Check-out wajib diisi"),
        });

        return {
            formSchema,
        };
    },
    data() {
        return {
            loading: false, // Loading state untuk tombol simpan
            formData: {
                app: "",
                description: "",
                check_in_time: "14:00",
                check_out_time: "12:00",
                logo: [],
                bg_auth: [],
                bg_landing: []
            },
            files: {
                logo: [],
                bgAuth: [],
                bgLanding: []
            }
        };
    },
    methods: {
        // Mengambil data dari Server saat halaman dibuka
        getSetting() {
            ApiService.get("settings")
                .then(({ data }) => {
                    // Data dari API langsung dimasukkan ke formData
                    const val = data;

                    this.formData.app = val.app || "";
                    this.formData.description = val.description || "";
                    this.formData.check_in_time = val.check_in_time || "14:00";
                    this.formData.check_out_time = val.check_out_time || "12:00";

                    // Handle Preview Gambar
                    const timestamp = new Date().getTime();
                    if (val.logo) this.files.logo = [val.logo + '?v=' + timestamp];
                    if (val.bg_auth) this.files.bgAuth = [val.bg_auth + '?v=' + timestamp];
                    if (val.bg_landing) this.files.bgLanding = [val.bg_landing + '?v=' + timestamp];
                })
                .catch((error) => {
                    console.error("Gagal mengambil setting:", error);
                    Swal.fire({
                        text: "Gagal memuat data pengaturan.",
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: { confirmButton: "btn btn-danger" }
                    });
                });
        },

submit() {
    this.loading = true;
    
    const formData = new FormData();
    formData.append("app", this.formData.app);
    formData.append("description", this.formData.description);
    formData.append("check_in_time", this.formData.check_in_time);
    formData.append("check_out_time", this.formData.check_out_time);

    // --- FUNGSI BANTUAN UNTUK EKSTRAK FILEPOND ---
    const appendFile = (key, fileArray) => {
        if (fileArray && fileArray.length > 0) {
            const item = fileArray[0];
            
            // Logika Deteksi: Apakah ini FilePond Wrapper?
            // FilePond object biasanya punya properti '.file' yang berisi native File
            if (item.file instanceof File) {
                console.log(`[${key}] FilePond Wrapper dideteksi, mengambil .file asli...`);
                formData.append(key, item.file);
            } 
            // Atau apakah ini sudah native File (jarang terjadi di FilePond tapi mungkin)?
            else if (item instanceof File) {
                console.log(`[${key}] Native File dideteksi...`);
                formData.append(key, item);
            }
            // Jika tidak, abaikan (mungkin URL string gambar lama)
            else {
                console.log(`[${key}] Bukan file baru (String/URL), diabaikan.`);
            }
        }
    };

    // Terapkan ke 3 file gambar
    appendFile("logo", this.formData.logo);
    appendFile("bg_auth", this.formData.bg_auth);
    appendFile("bg_landing", this.formData.bg_landing);

    // Method Spoofing
    formData.append("_method", "POST");

    // Kirim Request
    ApiService.post("/settings", formData, {
        headers: {
            "Content-Type": "multipart/form-data" 
        }
    })
    .then(({ data }) => {
        // Hapus debug log jika sukses
        // console.log("Response Sukses:", data); 
        Swal.fire({
            text: "Pengaturan berhasil diperbarui!",
            icon: "success",
            buttonsStyling: false,
            confirmButtonText: "Ok, mengerti!",
            customClass: { confirmButton: "btn btn-primary" }
        });
        this.getSetting();
    })
    .catch((err) => {
        console.error("Error Response:", err);
        // Tampilkan pesan error debug dari controller jika ada
        const pesan = err.response?.data?.message || "Terjadi kesalahan.";
        
        // Cek jika ini error debug controller
        if (err.response?.data?.status === "DEBUG_MODE") {
             console.log("DEBUG BACKEND:", err.response.data);
        }

        Swal.fire({
            text: pesan, 
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Ok",
            customClass: { confirmButton: "btn btn-danger" }
        });
    })
    .finally(() => {
        this.loading = false;
    });
}   
    },
    mounted() {
        // Panggil fungsi getSetting saat komponen selesai dimuat
        this.getSetting();
    }
});
</script>