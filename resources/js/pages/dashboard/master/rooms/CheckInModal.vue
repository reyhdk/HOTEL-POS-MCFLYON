<template>
    <div
        class="modal fade"
        id="kt_modal_check_in"
        ref="modalRef"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
    >
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div
                class="modal-content rounded-4 border-0 shadow-lg overflow-hidden theme-transition"
            >
                <div
                    class="modal-header border-0 py-4 px-5 bg-orange-gradient d-flex align-items-center"
                >
                    <div class="d-flex align-items-center gap-3 w-100">
                        <div
                            class="symbol symbol-45px symbol-circle bg-white bg-opacity-20 d-flex align-items-center justify-content-center animate-bounce"
                        >
                            <i class="ki-duotone ki-key fs-2 text-white"
                                ><span class="path1"></span
                                ><span class="path2"></span
                            ></i>
                        </div>
                        <div
                            class="d-flex flex-column"
                            style="color: #ffffff !important"
                        >
                            <h3
                                class="fw-bolder mb-0 fs-3 text-shadow text-white"
                            >
                                Kamar {{ roomData?.room_number }}
                            </h3>
                            <div
                                class="d-flex align-items-center gap-2 opacity-100"
                            >
                                <span
                                    class="fs-9 fw-bold text-uppercase ls-1 text-white"
                                    >Check-In</span
                                >
                                <span
                                    class="bullet bullet-dot bg-white h-4px w-4px"
                                ></span>
                                <span class="fs-9 fw-bold text-white"
                                    >{{
                                        formatRupiah(roomData?.price_per_night)
                                    }}/malam</span
                                >
                            </div>
                        </div>
                    </div>
                    <div
                        class="btn btn-icon btn-sm btn-white btn-active-light-orange ms-2 rounded-circle shadow-sm"
                        data-bs-dismiss="modal"
                    >
                        <i class="ki-duotone ki-cross fs-2 text-orange-600"
                            ><span class="path1"></span
                            ><span class="path2"></span
                        ></i>
                    </div>
                </div>

                <div class="modal-body p-0 bg-body-adaptive">
                    <transition name="slide-down">
                        <div
                            v-if="existingBookingToday"
                            class="alert-booking p-3 px-5 d-flex align-items-center gap-3 border-bottom border-warning border-opacity-25"
                        >
                            <i
                                class="ki-duotone ki-calendar-tick fs-2 text-warning"
                                ><span class="path1"></span
                                ><span class="path2"></span
                            ></i>
                            <div class="flex-grow-1 lh-1">
                                <div class="fs-8 fw-bold text-adaptive">
                                    Booking Hari Ini:
                                    {{ existingBookingToday.guest.name }}
                                </div>
                            </div>
                            <button
                                class="btn btn-xs btn-warning fw-bold text-dark"
                                @click="pilihBooking(existingBookingToday)"
                            >
                                Proses
                            </button>
                        </div>
                    </transition>

                    <div class="p-5">
                        <div
                            class="nav-switcher rounded-pill p-1 d-flex mb-5 position-relative"
                        >
                            <button
                                v-for="mode in ['walk_in', 'booking_existing']"
                                :key="mode"
                                type="button"
                                class="btn btn-sm flex-grow-1 rounded-pill fw-bold transition-all z-index-1"
                                :class="
                                    checkInMode === mode
                                        ? 'btn-active-mode shadow-sm'
                                        : 'text-muted hover-text-adaptive'
                                "
                                @click="gantiMode(mode)"
                            >
                                {{
                                    mode === "walk_in"
                                        ? "Langsung (Walk-In)"
                                        : "Dari Booking"
                                }}
                            </button>
                        </div>

                        <el-form
                            @submit.prevent="submit"
                            :model="formData"
                            :rules="rules"
                            ref="formRef"
                            hide-required-asterisk
                            class="form-adaptive"
                        >
                            <transition name="fade-mode" mode="out-in">
                                <div
                                    v-if="checkInMode === 'walk_in'"
                                    key="walkin"
                                    class="d-flex flex-column gap-3"
                                >
                                    <div class="row g-3">
                                        <div
                                            class="col-6"
                                            v-for="type in ['existing', 'new']"
                                            :key="type"
                                        >
                                            <div
                                                class="card-option cursor-pointer p-3 rounded-3 border d-flex align-items-center justify-content-center gap-2 transition-all"
                                                :class="
                                                    guestType === type
                                                        ? 'active border-orange'
                                                        : 'border-dashed'
                                                "
                                                @click="
                                                    guestType = type;
                                                    resetValidasi();
                                                "
                                            >
                                                <i
                                                    class="ki-duotone fs-2"
                                                    :class="[
                                                        type === 'existing'
                                                            ? 'ki-profile-user'
                                                            : 'ki-user-edit',
                                                        guestType === type
                                                            ? 'text-orange-600'
                                                            : 'text-gray-400',
                                                    ]"
                                                    ><span class="path1"></span
                                                    ><span class="path2"></span
                                                ></i>
                                                <span
                                                    class="fs-8 fw-bold"
                                                    :class="
                                                        guestType === type
                                                            ? 'text-orange-600'
                                                            : 'text-gray-500'
                                                    "
                                                    >{{
                                                        type === "existing"
                                                            ? "Tamu Lama"
                                                            : "Tamu Baru"
                                                    }}</span
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <div class="min-h-60px mt-1">
                                        <div
                                            v-if="guestType === 'existing'"
                                            class="animate-fade-in"
                                        >
                                            <el-form-item
                                                prop="guest_id"
                                                class="mb-4"
                                            >
                                                <el-select
                                                    v-model="formData.guest_id"
                                                    filterable
                                                    remote
                                                    reserve-keyword
                                                    placeholder="Cari Nama / No HP..."
                                                    :remote-method="
                                                        searchGuests
                                                    "
                                                    :loading="loadingGuests"
                                                    class="w-100"
                                                    size="large"
                                                    popper-class="adaptive-popper"
                                                >
                                                    <template #prefix
                                                        ><i
                                                            class="ki-duotone ki-magnifier fs-3 text-gray-500"
                                                            ><span
                                                                class="path1"
                                                            ></span
                                                            ><span
                                                                class="path2"
                                                            ></span></i
                                                    ></template>
                                                    <el-option
                                                        v-for="item in guestOptions"
                                                        :key="item.id"
                                                        :label="item.name"
                                                        :value="item.id"
                                                    >
                                                        <div
                                                            class="d-flex justify-content-between"
                                                        >
                                                            <span
                                                                class="fw-bold"
                                                                >{{
                                                                    item.name
                                                                }}</span
                                                            >
                                                            <span
                                                                class="text-muted fs-9"
                                                                >{{
                                                                    item.phone_number
                                                                }}</span
                                                            >
                                                        </div>
                                                    </el-option>
                                                </el-select>
                                            </el-form-item>
                                        </div>

                                        <div
                                            v-else
                                            class="new-guest-box p-3 rounded-3 border border-orange-dashed animate-fade-in mb-3"
                                        >
                                            <el-form-item
                                                prop="new_name"
                                                class="mb-4"
                                            >
                                                <el-input
                                                    v-model="formData.new_name"
                                                    placeholder="Nama Lengkap"
                                                    class="input-adaptive"
                                                />
                                            </el-form-item>
                                            <div class="d-flex gap-3">
                                                <el-form-item
                                                    prop="new_phone"
                                                    class="mb-0 w-50"
                                                >
                                                    <el-input
                                                        v-model="
                                                            formData.new_phone
                                                        "
                                                        placeholder="No HP/WA"
                                                        class="input-adaptive"
                                                    />
                                                </el-form-item>
                                                <el-form-item class="mb-0 w-50">
                                                    <el-input
                                                        v-model="
                                                            formData.new_email
                                                        "
                                                        placeholder="Email (Opsional)"
                                                        class="input-adaptive"
                                                    />
                                                </el-form-item>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-5">
                                            <div class="mb-4 h-100">
                                                <div
                                                    class="upload-box h-100 w-100 rounded-3 border border-dashed d-flex flex-column align-items-center justify-content-center cursor-pointer position-relative overflow-hidden transition-all"
                                                    :class="
                                                        ktpFile
                                                            ? 'border-success'
                                                            : 'border-gray-400 hover-border-orange'
                                                    "
                                                    style="min-height: 80px"
                                                    @click="triggerUpload"
                                                >
                                                    <div
                                                        v-if="!ktpPreview"
                                                        class="text-center p-2"
                                                    >
                                                        <div
                                                            class="symbol symbol-30px bg-light-orange mb-1"
                                                        >
                                                            <i
                                                                class="ki-duotone ki-camera fs-3 text-orange-600"
                                                                ><span
                                                                    class="path1"
                                                                ></span
                                                                ><span
                                                                    class="path2"
                                                                ></span
                                                            ></i>
                                                        </div>
                                                        <div
                                                            class="fs-9 fw-bold text-muted"
                                                        >
                                                            Foto KTP
                                                        </div>
                                                    </div>
                                                    <img
                                                        v-else
                                                        :src="ktpPreview"
                                                        class="w-100 h-100 object-fit-cover"
                                                    />

                                                    <div
                                                        v-if="ktpPreview"
                                                        class="position-absolute top-0 end-0 p-1"
                                                    >
                                                        <button
                                                            class="btn btn-icon btn-circle btn-xs btn-danger shadow-sm"
                                                            @click.stop="
                                                                hapusKtp
                                                            "
                                                        >
                                                            <i
                                                                class="ki-duotone ki-trash fs-5"
                                                                ><span
                                                                    class="path1"
                                                                ></span
                                                                ><span
                                                                    class="path2"
                                                                ></span
                                                            ></i>
                                                        </button>
                                                    </div>
                                                    <input
                                                        type="file"
                                                        ref="ktpRef"
                                                        class="d-none"
                                                        accept="image/*"
                                                        @change="handleUpload"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-7">
                                            <div
                                                class="date-box p-3 rounded-3 border border-dashed h-100 d-flex flex-column justify-content-center"
                                            >
                                                <div
                                                    class="d-flex justify-content-between align-items-center mb-1"
                                                >
                                                    <label
                                                        class="form-label fs-9 fw-bold text-muted mb-0"
                                                        >Check-Out</label
                                                    >
                                                    <span
                                                        class="badge badge-light-orange text-orange-600 fw-bold"
                                                        >{{
                                                            durasi
                                                        }}
                                                        Malam</span
                                                    >
                                                </div>
                                                <el-form-item
                                                    prop="check_out_date"
                                                    class="mb-0 pb-1"
                                                >
                                                    <el-date-picker
                                                        v-model="
                                                            formData.check_out_date
                                                        "
                                                        type="date"
                                                        class="w-100 input-adaptive"
                                                        format="DD MMM YYYY"
                                                        value-format="YYYY-MM-DD"
                                                        :disabled-date="
                                                            disabledDate
                                                        "
                                                        popper-class="adaptive-popper"
                                                        :teleported="true"
                                                    />
                                                </el-form-item>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-1 mb-2">
                                        <div
                                            v-for="method in [
                                                'cash',
                                                'midtrans',
                                            ]"
                                            :key="method"
                                            class="card-option flex-grow-1 p-2 rounded-3 border cursor-pointer transition-all d-flex align-items-center justify-content-center gap-2"
                                            :class="
                                                formData.payment_method ===
                                                method
                                                    ? 'active border-orange'
                                                    : 'border-dashed'
                                            "
                                            @click="
                                                formData.payment_method = method
                                            "
                                        >
                                            <i
                                                class="ki-duotone fs-2"
                                                :class="
                                                    method === 'cash'
                                                        ? 'ki-bill'
                                                        : 'ki-scan-barcode'
                                                "
                                                ><span class="path1"></span
                                                ><span class="path2"></span
                                            ></i>
                                            <span
                                                class="fs-8 fw-bold text-adaptive-inverse"
                                                >{{
                                                    method === "cash"
                                                        ? "Tunai"
                                                        : "QRIS"
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    key="booking"
                                    class="py-5 text-center"
                                >
                                    <div
                                        class="symbol symbol-60px symbol-circle bg-light-orange mb-4 animate-bounce"
                                    >
                                        <i
                                            class="ki-duotone ki-calendar-search fs-2x text-orange-600"
                                            ><span class="path1"></span
                                            ><span class="path2"></span
                                        ></i>
                                        h
                                    </div>
                                    <h6 class="fw-bold text-adaptive mb-3">
                                        Pilih Booking Terkonfirmasi
                                    </h6>
                                    <el-form-item
                                        prop="booking_id"
                                        class="mb-4"
                                    >
                                        <el-select
                                            v-model="formData.booking_id"
                                            filterable
                                            placeholder="Cari Tamu..."
                                            class="w-100"
                                            size="large"
                                            popper-class="adaptive-popper"
                                        >
                                            <el-option
                                                v-for="book in availableBookings"
                                                :key="book.id"
                                                :label="book.guest.name"
                                                :value="book.id"
                                            >
                                                <div
                                                    class="d-flex justify-content-between"
                                                >
                                                    <span class="fw-bold">{{
                                                        book.guest.name
                                                    }}</span>
                                                    <span
                                                        class="badge badge-light-success"
                                                        >{{ book.status }}</span
                                                    >
                                                </div>
                                            </el-option>
                                        </el-select>
                                    </el-form-item>
                                    <div
                                        v-if="!availableBookings.length"
                                        class="text-muted fs-8 fst-italic"
                                    >
                                        Belum ada booking siap check-in.
                                    </div>
                                </div>
                            </transition>
                        </el-form>
                    </div>
                </div>

                <div class="modal-footer p-0 bg-footer-adaptive border-top-0">
                    <div
                        v-if="checkInMode === 'walk_in'"
                        class="w-100 border-top border-dashed p-4 shadow-top"
                    >
                        <div
                            class="d-flex justify-content-between align-items-center mb-4"
                        >
                            <span class="text-muted fs-8 fw-bold text-uppercase"
                                >Total Tagihan</span
                            >
                            <div
                                class="d-flex align-items-baseline text-orange-600"
                            >
                                <span class="fs-8 fw-bold me-1">Rp</span>
                                <span class="fs-2 fw-bolder ls-n1">{{
                                    formatRupiahSimple(totalHarga)
                                }}</span>
                            </div>
                        </div>

                        <div class="d-flex gap-3 align-items-stretch">
                            <div
                                class="incognito-box rounded-3 px-3 d-flex align-items-center justify-content-center"
                                style="min-width: 130px"
                            >
                                <div
                                    class="form-check form-switch form-check-custom form-check-solid form-check-sm"
                                >
                                    <input
                                        class="form-check-input h-20px w-30px"
                                        type="checkbox"
                                        v-model="formData.is_incognito"
                                        id="incognito_toggle"
                                    />
                                    <label
                                        class="form-check-label fs-8 fw-bold text-muted ms-2 cursor-pointer"
                                        for="incognito_toggle"
                                        >Incognito</label
                                    >
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="submit"
                                class="btn btn-orange flex-grow-1 fw-bold hover-scale"
                                :disabled="loading"
                            >
                                <span v-if="!loading">Check-In</span>
                                <span v-else
                                    >Proses...
                                    <span
                                        class="spinner-border spinner-border-sm ms-1"
                                    ></span
                                ></span>
                            </button>
                        </div>
                    </div>
                    <div v-else class="w-100 p-4 border-top border-dashed">
                        <button
                            type="button"
                            @click="submit"
                            class="btn btn-orange w-100 fw-bold hover-scale"
                            :disabled="loading"
                        >
                            Verifikasi & Masuk
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ApiService from "@/core/services/ApiService";
import { Modal } from "bootstrap";
import moment from "moment";
import { ElMessage } from "element-plus";
import debounce from "lodash/debounce";
import Swal from "sweetalert2";

export default {
    name: "CheckInModalUltimate",
    emits: ["success"],
    data() {
        return {
            modal: null,
            roomData: null,
            loading: false,
            loadingGuests: false,
            checkInMode: "walk_in",
            guestType: "existing",
            guestOptions: [],
            availableBookings: [],
            existingBookingToday: null,
            ktpFile: null,
            ktpPreview: null,

            formData: {
                room_id: null,
                guest_id: null,
                booking_id: null,
                check_in_date: moment().format("YYYY-MM-DD"),
                check_out_date: moment().add(1, "days").format("YYYY-MM-DD"),
                payment_method: "cash",
                is_incognito: false,
                // Field Tamu Baru
                new_name: "",
                new_phone: "",
                new_email: "",
            },
        };
    },
    computed: {
        isNewGuest() {
            return this.guestType === "new";
        },
        durasi() {
            const start = moment(this.formData.check_in_date);
            const end = moment(this.formData.check_out_date);
            const diff = end.diff(start, "days");
            return diff > 0 ? diff : 1;
        },
        totalHarga() {
            return this.durasi * (this.roomData?.price_per_night || 0);
        },
        rules() {
            if (this.checkInMode === "walk_in") {
                const r = {
                    check_out_date: [
                        {
                            required: true,
                            message: "Wajib diisi",
                            trigger: "change",
                        },
                    ],
                };
                if (this.guestType === "existing") {
                    r.guest_id = [
                        {
                            required: true,
                            message: "Pilih tamu",
                            trigger: "change",
                        },
                    ];
                } else {
                    r.new_name = [
                        {
                            required: true,
                            message: "Nama wajib",
                            trigger: "blur",
                        },
                    ];
                    r.new_phone = [
                        {
                            required: true,
                            message: "No HP wajib",
                            trigger: "blur",
                        },
                    ];
                }
                return r;
            }
            return {
                booking_id: [
                    {
                        required: true,
                        message: "Pilih booking",
                        trigger: "change",
                    },
                ],
            };
        },
    },
    methods: {
        // --- 1. FUNGSI HELPER (YANG HILANG SEBELUMNYA) ---
        formatRupiah(value) {
            if (!value) return "Rp 0";
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
                minimumFractionDigits: 0,
            }).format(value);
        },
        formatRupiahSimple(value) {
            if (!value) return "0";
            return new Intl.NumberFormat("id-ID").format(value);
        },
        disabledDate(time) {
            // Disable tanggal sebelum hari ini
            return time.getTime() < Date.now() - 8.64e7;
        },

        // --- 2. LOGIKA UTAMA ---
        openModal(room) {
            this.roomData = room;
            this.resetForm();
            this.formData.room_id = room.id;
            this.fetchBookings(room.id);
            if (!this.modal) this.modal = new Modal(this.$refs.modalRef);
            this.modal.show();
        },
        closeModal() {
            this.modal?.hide();
        },
        resetForm() {
            this.checkInMode = "walk_in";
            this.guestType = "existing";
            this.ktpFile = null;
            this.ktpPreview = null;
            this.guestOptions = [];
            this.existingBookingToday = null;

            // Reset formData Values
            this.formData.guest_id = null;
            this.formData.booking_id = null;
            this.formData.check_in_date = moment().format("YYYY-MM-DD");
            this.formData.check_out_date = moment()
                .add(1, "days")
                .format("YYYY-MM-DD");
            this.formData.payment_method = "cash";
            this.formData.is_incognito = false;

            // Reset New Guest Fields
            this.formData.new_name = "";
            this.formData.new_phone = "";
            this.formData.new_email = "";

            this.resetValidasi();
        },
        gantiMode(mode) {
            this.checkInMode = mode;
            this.resetValidasi();
        },
        resetValidasi() {
            this.$nextTick(() => {
                if (this.$refs.formRef) this.$refs.formRef.clearValidate();
            });
        },

        searchGuests: debounce(function (query) {
            if (query?.length >= 3) {
                this.loadingGuests = true;
                axios
                    .get("guests", { params: { search: query } })
                    .then((res) => (this.guestOptions = res.data.data))
                    .finally(() => (this.loadingGuests = false));
            }
        }, 500),

        triggerUpload() {
            this.$refs.ktpRef.click();
        },
        handleUpload(e) {
            const file = e.target.files[0];
            if (file) {
                this.ktpFile = file;
                this.ktpPreview = URL.createObjectURL(file);
            }
        },
        hapusKtp() {
            this.ktpFile = null;
            this.ktpPreview = null;
            this.$refs.ktpRef.value = "";
        },

        async fetchBookings(roomId) {
            try {
                // Mengambil booking yang PAID/CONFIRMED untuk hari ini ke depan
                const res = await axios.get("bookings", {
                    params: {
                        room_id: roomId,
                        status_in: "paid,confirmed,verified",
                        date_gte: moment().format("YYYY-MM-DD"),
                    },
                });
                this.availableBookings = res.data.data || [];
                // Cek apakah ada booking hari ini
                this.existingBookingToday = this.availableBookings.find((b) =>
                    b.check_in_date.startsWith(moment().format("YYYY-MM-DD"))
                );
            } catch (e) {
                console.error(e);
            }
        },

        pilihBooking(book) {
            this.checkInMode = "booking_existing";
            this.$nextTick(() => {
                this.formData.booking_id = book.id;
                // Opsional: Set durasi sesuai booking asli
                this.formData.check_in_date = book.check_in_date.split("T")[0];
                this.formData.check_out_date =
                    book.check_out_date.split("T")[0];
            });
        },

        async submit() {
            // 1. Validasi Form (Gunakan this.$refs)
            if (!this.$refs.formRef) return;

            await this.$refs.formRef.validate(async (valid) => {
                if (valid) {
                    this.loading = true; // Gunakan this.loading
                    try {
                        // 2. Siapkan Payload Dasar
                        const payload = {
                            room_id: this.roomData.id, // Gunakan this.roomData
                            notes: this.formData.notes || "-", // Gunakan this.formData
                            is_incognito: this.formData.is_incognito ? 1 : 0,
                        };

                        // 3. Logika Berdasarkan Mode (Walk-In vs Booking Existing)
                        if (this.checkInMode === "walk_in") {
                            // A. Handle Data Tamu (Baru vs Lama)
                            if (this.guestType === "existing") {
                                if (!this.formData.guest_id) {
                                    this.loading = false;
                                    return; // Validasi sudah dihandle rules
                                }
                                payload.guest_id = this.formData.guest_id;
                            } else {
                                // Kirim data tamu baru ke backend
                                // Pastikan backend mendukung penerimaan data tamu baru via endpoint ini
                                payload.guest_name = this.formData.new_name;
                                payload.guest_phone = this.formData.new_phone;
                                payload.guest_email = this.formData.new_email;
                                payload.is_new_guest = true;
                            }

                            // B. Handle Tanggal Check-Out
                            // Karena di template Anda menggunakan Date Picker, kita kirim check_out_date
                            if (!this.formData.check_out_date) {
                                Swal.fire(
                                    "Error",
                                    "Tanggal check-out wajib dipilih.",
                                    "warning"
                                );
                                this.loading = false;
                                return;
                            }

                            // Format Tanggal: Tambahkan jam 12:00:00 agar backend menerimanya
                            payload.check_out_date =
                                this.formData.check_out_date + " 12:00:00";
                            payload.duration = null; // Set null agar backend membaca tanggal

                            // C. Handle Pembayaran
                            payload.payment_method =
                                this.formData.payment_method;
                        } else {
                            // MODE: DARI BOOKING EXISTING
                            if (!this.formData.booking_id) {
                                Swal.fire(
                                    "Error",
                                    "Pilih data booking terlebih dahulu.",
                                    "warning"
                                );
                                this.loading = false;
                                return;
                            }
                            // Jika check-in dari booking, biasanya kita panggil endpoint berbeda
                            // Tapi jika pakai endpoint store-direct, kirim booking_id
                            payload.booking_id = this.formData.booking_id;
                        }

                        // Debugging di Console
                        console.log("Payload CheckIn:", payload);

                        // 4. Kirim ke Backend
                        // Pastikan path API sesuai dengan route Anda
                        await axios.post(
                            "admin/check-ins/store-direct",
                            payload
                        );

                        Swal.fire({
                            text: "Check-in berhasil!",
                            icon: "success",
                            buttonsStyling: false,
                            confirmButtonText: "Ok",
                            customClass: { confirmButton: "btn btn-primary" },
                        }).then(() => {
                            this.$emit("success"); // Gunakan this.$emit
                            this.closeModal(); // Gunakan this.closeModal
                        });
                    } catch (error) {
                        // HAPUS ': any' karena ini Javascript biasa
                        console.error("Check-in error:", error);
                        Swal.fire({
                            text:
                                error.response?.data?.message ||
                                "Terjadi kesalahan saat check-in.",
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Ok",
                            customClass: { confirmButton: "btn btn-danger" },
                        });
                    } finally {
                        this.loading = false;
                    }
                } else {
                    Swal.fire({
                        text: "Mohon lengkapi formulir dengan benar.",
                        icon: "warning",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: { confirmButton: "btn btn-primary" },
                    });
                    return false;
                }
            });
        },
    },
};
</script>

<style scoped>
/* --- THEME VARIABLES --- */
.theme-transition {
    transition: background-color 0.3s, color 0.3s;
}
:root {
    --bg-modal: #ffffff;
    --bg-input: #ffffff;
    --bg-secondary: #f9f9f9;
    --border-color: #e4e6ef;
    --text-primary: #3f4254;
    --text-muted: #a1a5b7;
    --shadow-color: rgba(0, 0, 0, 0.05);
}
[data-bs-theme="dark"] .theme-transition,
.dark-mode .theme-transition {
    --bg-modal: #1e1e2d;
    --bg-input: #151521;
    --bg-secondary: #2b2b40;
    --border-color: #323248;
    --text-primary: #ffffff;
    --text-muted: #6d6d80;
    --shadow-color: rgba(0, 0, 0, 0.2);
}

/* --- ORANGE ACCENT --- */
.bg-orange-gradient {
    background: linear-gradient(135deg, #ff9900 0%, #ff5e00 100%);
}
.text-orange-600 {
    color: #ff7700 !important;
}
.bg-light-orange {
    background-color: rgba(255, 153, 0, 0.1) !important;
}
.border-orange {
    border-color: #ff9900 !important;
}
.border-orange-dashed {
    border: 1px dashed #ff9900 !important;
}
.badge-light-orange {
    background-color: rgba(255, 153, 0, 0.1);
    color: #ff7700;
}
.text-shadow {
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.text-white {
    color: #ffffff !important;
}

.btn-orange {
    background: linear-gradient(to right, #ff9900, #ff7700);
    color: white;
    border: none;
    box-shadow: 0 4px 10px rgba(255, 119, 0, 0.3);
    transition: all 0.3s;
}
.btn-orange:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(255, 119, 0, 0.4);
}

/* --- ADAPTIVE CLASSES --- */
.bg-body-adaptive {
    background-color: var(--bg-modal);
    color: var(--text-primary);
}
.bg-footer-adaptive {
    background-color: var(--bg-modal);
}
.text-adaptive {
    color: var(--text-primary);
}
.text-muted {
    color: var(--text-muted) !important;
}
.nav-switcher {
    background-color: var(--bg-secondary);
}
.btn-active-mode {
    background-color: var(--bg-modal);
    color: #ff7700;
    box-shadow: 0 2px 6px var(--shadow-color);
}
.hover-text-adaptive:hover {
    color: var(--text-primary);
}
.card-option,
.upload-box,
.date-box,
.incognito-box,
.new-guest-box {
    background-color: var(--bg-modal);
    border-color: var(--border-color);
}
.alert-booking {
    background-color: rgba(255, 193, 7, 0.15);
}
.hover-border-orange:hover {
    border-color: #ff9900 !important;
}

/* ELEMENT PLUS OVERRIDES */
:deep(.el-input__wrapper),
:deep(.el-select__wrapper) {
    background-color: var(--bg-input) !important;
    box-shadow: none !important;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 8px 12px;
}
:deep(.el-input__inner) {
    color: var(--text-primary);
}
:deep(.el-input__wrapper.is-focus),
:deep(.el-select__wrapper.is-focused) {
    border-color: #ff9900 !important;
}
:deep(.el-date-editor) {
    --el-input-bg-color: var(--bg-input);
    --el-text-color-regular: var(--text-primary);
}

/* Validasi Error Styling */
:deep(.el-form-item__error) {
    position: relative; /* Supaya mengambil ruang, bukan melayang */
    top: 0;
    margin-top: 4px;
    font-size: 11px;
    line-height: 1.2;
}
/* Tambahan untuk memastikan layout tidak hancur */
:deep(.el-form-item) {
    margin-bottom: 0; /* Kita atur manual margin di template */
}

/* ANIMATIONS */
.animate-bounce {
    animation: softBounce 2s infinite;
}
@keyframes softBounce {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}
.fade-mode-enter-active,
.fade-mode-leave-active {
    transition: opacity 0.2s ease;
}
.fade-mode-enter-from,
.fade-mode-leave-to {
    opacity: 0;
}
.hover-scale:hover {
    transform: translateY(-2px);
}
.slide-down-enter-active {
    animation: slideDown 0.3s ease-out;
}
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<style>
/* GLOBAL */
.adaptive-popper {
    z-index: 9999 !important;
}
[data-bs-theme="dark"] .adaptive-popper .el-select-dropdown,
[data-bs-theme="dark"] .adaptive-popper .el-picker-panel,
.dark-mode .adaptive-popper .el-select-dropdown,
.dark-mode .adaptive-popper .el-picker-panel {
    background-color: #1e1e2d;
    border-color: #323248;
}
[data-bs-theme="dark"] .adaptive-popper .el-select-dropdown__item,
.dark-mode .adaptive-popper .el-select-dropdown__item {
    color: #fff;
}
[data-bs-theme="dark"] .adaptive-popper .el-select-dropdown__item.hover,
.dark-mode .adaptive-popper .el-select-dropdown__item.hover {
    background-color: #2b2b40;
}
</style>
