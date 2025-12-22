<template>
    <div
        class="modal fade"
        id="kt_modal_check_in"
        ref="modalRef"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered mw-550px">
            <div
                class="modal-content rounded-3 border-0 shadow-lg animate__animated animate__zoomIn animate__faster"
            >
                <div
                    class="modal-header border-0 bg-gradient-orange position-relative"
                >
                    <div class="w-100 position-relative z-index-1 py-4">
                        <div
                            class="d-flex align-items-center justify-content-between"
                        >
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="symbol symbol-45px symbol-circle bg-white bg-opacity-20 animate__animated animate__bounceIn"
                                >
                                    <i
                                        class="ki-duotone ki-home fs-2 text-white"
                                        ><span class="path1"></span
                                        ><span class="path2"></span
                                    ></i>
                                </div>
                                <div>
                                    <h2 class="text-white fw-bold mb-0 fs-3">
                                        Kamar #{{ roomData?.room_number }}
                                    </h2>
                                    <div
                                        class="text-white text-opacity-75 fs-8"
                                    >
                                        {{
                                            formatCurrency(
                                                roomData?.price_per_night
                                            )
                                        }}/malam
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="btn btn-icon btn-sm btn-light position-absolute top-0 end-0 m-3"
                                data-bs-dismiss="modal"
                            >
                                <i class="ki-duotone ki-cross fs-2"
                                    ><span class="path1"></span
                                    ><span class="path2"></span
                                ></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-body px-5 pb-5 pt-4">
                    <div
                        v-if="existingBookingToday"
                        class="alert bg-light-warning border border-warning rounded-3 p-3 mb-4 animate__animated animate__fadeInDown"
                    >
                        <div
                            class="d-flex align-items-center justify-content-between"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <i
                                    class="ki-duotone ki-calendar-tick fs-2 text-warning animate__animated animate__pulse animate__infinite"
                                    ><span class="path1"></span
                                    ><span class="path2"></span
                                ></i>
                                <div>
                                    <div class="fw-bold text-gray-900 fs-7">
                                        Booking Hari Ini!
                                    </div>
                                    <div class="text-gray-700 fs-8">
                                        {{ existingBookingToday.guest.name }}
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="setModeToBooking(existingBookingToday)"
                                class="btn btn-warning btn-sm"
                            >
                                Proses
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="btn-group w-100" role="group">
                            <button
                                type="button"
                                class="btn btn-sm fw-bold py-3 transition-mode"
                                :class="
                                    checkInMode === 'walk_in'
                                        ? 'btn-orange'
                                        : 'btn-light-orange'
                                "
                                @click="checkInMode = 'walk_in'"
                            >
                                <i
                                    class="ki-duotone ki-entrance-right fs-4 me-1"
                                    ><span class="path1"></span
                                    ><span class="path2"></span
                                ></i>
                                Walk-In
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm fw-bold py-3 transition-mode"
                                :class="
                                    checkInMode === 'booking_existing'
                                        ? 'btn-orange'
                                        : 'btn-light-orange'
                                "
                                @click="checkInMode = 'booking_existing'"
                            >
                                <i class="ki-duotone ki-calendar-tick fs-4 me-1"
                                    ><span class="path1"></span
                                    ><span class="path2"></span
                                ></i>
                                Booking
                            </button>
                        </div>
                    </div>

                    <el-form
                        @submit.prevent="submit"
                        :model="formData"
                        :rules="currentRules"
                        ref="formRef"
                        label-position="top"
                        class="metronic-form"
                    >
                        <transition name="slide-fade" mode="out-in">
                            <div
                                v-if="checkInMode === 'walk_in'"
                                key="walk-in"
                                class="content-transition"
                            >
                                <div class="mb-4">
                                    <label
                                        class="form-label fw-bold text-gray-700 fs-7 mb-2"
                                        >Pilih Tamu</label
                                    >
                                    <div class="btn-group w-100" role="group">
                                        <input
                                            type="radio"
                                            class="btn-check"
                                            id="guest_existing"
                                            v-model="guestType"
                                            value="existing"
                                        />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-outline-orange btn-active-light-orange fs-8 py-2"
                                            for="guest_existing"
                                        >
                                            <i
                                                class="ki-duotone ki-profile-circle fs-3 me-1"
                                                ><span class="path1"></span
                                                ><span class="path2"></span
                                            ></i>
                                            Tamu Lama
                                        </label>
                                        <input
                                            type="radio"
                                            class="btn-check"
                                            id="guest_new"
                                            v-model="guestType"
                                            value="new"
                                        />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-outline-orange btn-active-light-orange fs-8 py-2"
                                            for="guest_new"
                                        >
                                            <i
                                                class="ki-duotone ki-user-tick fs-3 me-1"
                                                ><span class="path1"></span
                                                ><span class="path2"></span
                                            ></i>
                                            Tamu Baru
                                        </label>
                                    </div>
                                </div>

                                <div
                                    v-if="guestType === 'existing'"
                                    class="mb-4"
                                >
                                    <el-form-item prop="guest_id">
                                        <el-select
                                            v-model="formData.guest_id"
                                            filterable
                                            remote
                                            reserve-keyword
                                            placeholder="Cari nama atau No HP..."
                                            :remote-method="searchGuests"
                                            :loading="loadingGuests"
                                            class="w-100"
                                            size="large"
                                        >
                                            <template #prefix>
                                                <i
                                                    class="ki-duotone ki-magnifier fs-4 text-gray-500"
                                                    ><span class="path1"></span
                                                    ><span class="path2"></span
                                                ></i>
                                            </template>
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
                                                        class="fw-bold fs-8"
                                                        >{{ item.name }}</span
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

                                <div v-else class="mb-4">
                                    <div class="mb-3">
                                        <label
                                            class="form-label required fw-bold fs-8"
                                            >Nama Lengkap</label
                                        >
                                        <el-input
                                            v-model="newGuestData.name"
                                            placeholder="Nama tamu"
                                            size="large"
                                        />
                                    </div>
                                    <div class="row">
                                        <div class="col-7">
                                            <label
                                                class="form-label required fw-bold fs-8"
                                                >No. WhatsApp</label
                                            >
                                            <el-input
                                                v-model="
                                                    newGuestData.phone_number
                                                "
                                                placeholder="08xx"
                                                size="large"
                                            />
                                        </div>
                                        <div class="col-5">
                                            <label
                                                class="form-label fw-bold fs-8"
                                                >Email</label
                                            >
                                            <el-input
                                                v-model="newGuestData.email"
                                                placeholder="Opsional"
                                                size="large"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 mb-4">
                                    <label
                                        class="form-label required fw-bold fs-8"
                                        >Foto KTP / Identitas</label
                                    >

                                    <div class="d-flex align-items-start gap-3">
                                        <div
                                            class="position-relative bg-light border border-dashed border-gray-300 rounded overflow-hidden flex-shrink-0"
                                            style="width: 150px; height: 100px"
                                        >
                                            <div
                                                v-if="!ktpPreview"
                                                class="d-flex flex-column align-items-center justify-content-center w-100 h-100 text-gray-400"
                                            >
                                                <i
                                                    class="ki-duotone ki-picture fs-2x mb-1"
                                                    ><span class="path1"></span
                                                    ><span class="path2"></span
                                                ></i>
                                                <span class="fs-9"
                                                    >Preview</span
                                                >
                                            </div>

                                            <img
                                                v-else
                                                :src="ktpPreview"
                                                class="w-100 h-100"
                                                style="object-fit: cover"
                                                alt="KTP Preview"
                                            />

                                            <div
                                                v-if="ktpPreview"
                                                class="position-absolute top-0 end-0 m-1 bg-white rounded-circle cursor-pointer shadow-sm p-1 d-flex align-items-center justify-content-center"
                                                style="
                                                    width: 20px;
                                                    height: 20px;
                                                "
                                                @click.stop="removeKtpFile"
                                            >
                                                <i
                                                    class="ki-duotone ki-cross fs-9 text-danger"
                                                    ><span class="path1"></span
                                                    ><span class="path2"></span
                                                ></i>
                                            </div>
                                        </div>

                                        <div class="flex-grow-1 pt-2">
                                            <input
                                                type="file"
                                                ref="ktpInputRef"
                                                class="d-none"
                                                accept="image/*"
                                                @change="handleKtpUpload"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline btn-outline-dashed btn-outline-orange w-100 text-start ps-3"
                                                @click="
                                                    $refs.ktpInputRef.click()
                                                "
                                            >
                                                <i
                                                    class="ki-duotone ki-file-up fs-3 me-2 text-orange"
                                                    ><span class="path1"></span
                                                    ><span class="path2"></span
                                                ></i>
                                                <span
                                                    class="text-gray-600 fs-8"
                                                    v-if="!ktpFile"
                                                    >Klik upload foto
                                                    identitas...</span
                                                >
                                                <span
                                                    class="text-orange fs-8 fw-bold text-truncate"
                                                    v-else
                                                    >{{ ktpFile.name }}</span
                                                >
                                            </button>
                                            <div
                                                class="text-muted fs-9 mt-2 ms-1 lh-sm"
                                            >
                                                Format: .jpg, .png (Max 2MB).<br />Wajib
                                                untuk verifikasi tamu saat
                                                check-in.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label
                                        class="form-label fw-bold text-gray-700 fs-7 mb-2"
                                        >Periode Menginap</label
                                    >
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <el-date-picker
                                                v-model="formData.check_in_date"
                                                type="date"
                                                class="w-100"
                                                size="large"
                                                disabled
                                                placeholder="Check-In"
                                                format="DD/MM/YYYY"
                                                value-format="YYYY-MM-DD"
                                            />
                                        </div>
                                        <div class="col-6">
                                            <el-form-item prop="check_out_date">
                                                <el-date-picker
                                                    v-model="
                                                        formData.check_out_date
                                                    "
                                                    type="date"
                                                    class="w-100"
                                                    size="large"
                                                    :disabled-date="
                                                        disabledDate
                                                    "
                                                    format="DD/MM/YYYY"
                                                    value-format="YYYY-MM-DD"
                                                    placeholder="Check-Out"
                                                />
                                            </el-form-item>
                                        </div>
                                    </div>
                                    <div
                                        v-if="calculatedDuration > 0"
                                        class="bg-light-orange rounded-2 p-2 mt-2"
                                    >
                                        <div
                                            class="d-flex justify-content-between align-items-center"
                                        >
                                            <span
                                                class="text-orange fs-8 fw-bold"
                                                >{{
                                                    calculatedDuration
                                                }}
                                                Malam</span
                                            >
                                            <span
                                                class="text-orange fs-6 fw-bold"
                                                >{{
                                                    formatCurrency(
                                                        calculatedTotalPrice
                                                    )
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label
                                        class="form-label fw-bold text-gray-700 fs-7 mb-2"
                                        >Pembayaran</label
                                    >
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div
                                                class="payment-card"
                                                :class="{
                                                    active:
                                                        formData.payment_method ===
                                                        'cash',
                                                }"
                                                @click="
                                                    formData.payment_method =
                                                        'cash'
                                                "
                                            >
                                                <i
                                                    class="ki-duotone ki-dollar fs-2 text-success"
                                                    ><span class="path1"></span
                                                    ><span class="path2"></span
                                                ></i>
                                                <span class="fw-bold fs-8"
                                                    >Tunai</span
                                                >
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div
                                                class="payment-card"
                                                :class="{
                                                    active:
                                                        formData.payment_method ===
                                                        'midtrans',
                                                }"
                                                @click="
                                                    formData.payment_method =
                                                        'midtrans'
                                                "
                                            >
                                                <i
                                                    class="ki-duotone ki-scan-barcode fs-2 text-orange"
                                                    ><span class="path1"></span
                                                    ><span class="path2"></span
                                                ></i>
                                                <span class="fw-bold fs-8"
                                                    >QRIS</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                key="booking"
                                class="content-transition"
                            >
                                <div class="text-center mb-4">
                                    <div
                                        class="symbol symbol-60px symbol-circle mb-3 bg-light-orange mx-auto"
                                    >
                                        <i
                                            class="ki-duotone ki-calendar-search fs-2x text-orange"
                                            ><span class="path1"></span
                                            ><span class="path2"></span
                                        ></i>
                                    </div>
                                    <h5 class="fw-bold text-gray-900 mb-1 fs-6">
                                        Pilih Booking
                                    </h5>
                                    <p class="text-muted fs-8 mb-0">
                                        Status harus
                                        <span
                                            class="badge badge-success badge-sm"
                                            >Confirmed</span
                                        >
                                    </p>
                                </div>

                                <el-form-item prop="booking_id">
                                    <el-select
                                        v-model="formData.booking_id"
                                        placeholder="Cari booking..."
                                        class="w-100"
                                        size="large"
                                        filterable
                                    >
                                        <el-option
                                            v-for="book in availableBookings"
                                            :key="book.id"
                                            :label="book.guest.name"
                                            :value="book.id"
                                        >
                                            <div
                                                class="d-flex justify-content-between align-items-center py-1"
                                            >
                                                <div class="flex-grow-1 me-3">
                                                    <div
                                                        class="fw-bold fs-8 text-gray-900"
                                                    >
                                                        {{ book.guest.name }}
                                                    </div>
                                                    <div
                                                        class="text-muted fs-9"
                                                    >
                                                        {{
                                                            formatDate(
                                                                book.check_in_date
                                                            )
                                                        }}
                                                        -
                                                        {{
                                                            formatDate(
                                                                book.check_out_date
                                                            )
                                                        }}
                                                    </div>
                                                </div>
                                                <span
                                                    class="badge badge-success badge-sm flex-shrink-0"
                                                    >{{ book.status.toUpperCase() }}</span
                                                >
                                            </div>
                                        </el-option>
                                    </el-select>
                                </el-form-item>

                                <div
                                    v-if="availableBookings.length === 0"
                                    class="alert alert-warning p-3"
                                >
                                    <div class="d-flex align-items-center">
                                        <i
                                            class="ki-duotone ki-information-4 fs-2 text-warning me-2"
                                            ><span class="path1"></span
                                            ><span class="path2"></span
                                        ></i>
                                        <div class="fs-8">
                                            Tidak ada booking confirmed untuk hari ini
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>

                        <div
                            class="d-flex justify-content-between align-items-center mt-5 pt-4 border-top"
                        >
                            <div class="form-check form-check-sm">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    v-model="formData.is_incognito"
                                    id="incognito"
                                />
                                <label
                                    class="form-check-label fs-8 fw-bold"
                                    for="incognito"
                                >
                                    <i class="ki-duotone ki-eye-slash fs-5 me-1"
                                        ><span class="path1"></span
                                        ><span class="path2"></span
                                    ></i>
                                    Incognito
                                </label>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-orange px-5 hover-scale"
                                :disabled="loading"
                            >
                                <span
                                    v-if="loading"
                                    class="spinner-border spinner-border-sm me-2"
                                ></span>
                                <span class="fw-bold fs-7">
                                    {{
                                        checkInMode === "walk_in"
                                            ? "Check-In Sekarang"
                                            : "Verifikasi & Check-In"
                                    }}
                                </span>
                                <i
                                    v-if="!loading"
                                    class="ki-duotone ki-arrow-right fs-4 ms-1"
                                    ><span class="path1"></span
                                    ><span class="path2"></span
                                ></i>
                            </button>
                        </div>
                    </el-form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import { Modal } from "bootstrap";
import moment from "moment";
import { ElMessage } from "element-plus";

export default {
    name: "CheckInModal",
    emits: ["success"],
    data() {
        return {
            modalInstance: null,
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
            },

            newGuestData: { name: "", email: "", phone_number: "" },
        };
    },
    computed: {
        isNewGuest() {
            return this.guestType === "new";
        },
        currentRules() {
            if (this.checkInMode === "walk_in") {
                return {
                    guest_id: [
                        {
                            required: !this.isNewGuest,
                            message: "Silakan pilih tamu",
                            trigger: "change",
                        },
                    ],
                    check_out_date: [
                        {
                            required: true,
                            message: "Tanggal Check-out wajib diisi",
                            trigger: "change",
                        },
                    ],
                };
            } else {
                return {
                    booking_id: [
                        {
                            required: true,
                            message: "Silakan pilih data booking",
                            trigger: "change",
                        },
                    ],
                };
            }
        },
        calculatedDuration() {
            if (!this.formData.check_out_date || !this.formData.check_in_date)
                return 0;
            const checkIn = moment(this.formData.check_in_date);
            const checkOut = moment(this.formData.check_out_date);
            return Math.max(1, checkOut.diff(checkIn, "days"));
        },
        calculatedTotalPrice() {
            return (
                this.calculatedDuration * (this.roomData?.price_per_night || 0)
            );
        },
    },
    watch: {
        checkInMode() {
            if (this.$refs.formRef) this.$refs.formRef.clearValidate();
        },
        guestType() {
            if (this.$refs.formRef) this.$refs.formRef.clearValidate();
        },
    },
    methods: {
        openModal(room) {
            this.roomData = room;
            this.resetState();
            this.formData.room_id = room.id;
            this.fetchRoomBookings(room.id);

            if (!this.modalInstance) {
                const modalEl = this.$refs.modalRef;
                if (modalEl) this.modalInstance = new Modal(modalEl);
            }
            this.modalInstance?.show();
        },

        resetState() {
            this.checkInMode = "walk_in";
            this.guestType = "existing";
            this.existingBookingToday = null;
            this.availableBookings = [];
            this.guestOptions = [];
            this.formData.guest_id = null;
            this.formData.booking_id = null;
            this.formData.check_in_date = moment().format("YYYY-MM-DD");
            this.formData.check_out_date = moment()
                .add(1, "days")
                .format("YYYY-MM-DD");
            this.formData.payment_method = "cash";
            this.formData.is_incognito = false;
            this.newGuestData = { name: "", email: "", phone_number: "" };

            this.removeKtpFile();

            if (this.$refs.formRef) this.$refs.formRef.resetFields();
        },

        closeModal() {
            if (this.modalInstance) this.modalInstance.hide();
        },

        handleKtpUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                ElMessage.warning("Ukuran file terlalu besar (Max 2MB)");
                return;
            }

            this.ktpFile = file;
            this.ktpPreview = URL.createObjectURL(file);
        },

        removeKtpFile() {
            this.ktpFile = null;
            this.ktpPreview = null;
            if (this.$refs.ktpInputRef) {
                this.$refs.ktpInputRef.value = "";
            }
        },

        // ✅ FIXED: Tambahkan 'confirmed' status
        async fetchRoomBookings(roomId) {
            try {
                const response = await axios.get("bookings", {
                    params: {
                        room_id: roomId,
                        status_in: "paid,confirmed,settlement", // 🔥 Tambahkan 'confirmed'
                        date_gte: moment().format("YYYY-MM-DD"),
                    },
                });

                this.availableBookings = response.data.data || [];
                const today = moment().format("YYYY-MM-DD");
                this.existingBookingToday = this.availableBookings.find(
                    (b) => b.check_in_date === today
                );
                
                console.log("📋 Available Bookings for Check-in:", this.availableBookings.length);
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingGuests = false;
            }
        },

        async submit() {
            this.$refs.formRef.validate(async (valid) => {
                if (!valid) return;

                if (this.checkInMode === "walk_in" && !this.ktpFile) {
                    ElMessage.warning("Wajib upload foto KTP/Identitas tamu.");
                    return;
                }

                this.loading = true;

                try {
                    if (this.checkInMode === "walk_in") {
                        let finalGuestId = this.formData.guest_id;

                        if (this.isNewGuest) {
                            if (
                                !this.newGuestData.name ||
                                !this.newGuestData.phone_number
                            ) {
                                throw new Error(
                                    "Nama dan No HP Tamu wajib diisi"
                                );
                            }
                            const guestRes = await axios.post(
                                "guests",
                                this.newGuestData
                            );
                            finalGuestId = guestRes.data.data
                                ? guestRes.data.data.id
                                : guestRes.data.id;
                        }

                        const payload = new FormData();
                        payload.append("room_id", this.formData.room_id);
                        payload.append("guest_id", finalGuestId);
                        payload.append(
                            "check_in_date",
                            this.formData.check_in_date
                        );
                        payload.append(
                            "check_out_date",
                            this.formData.check_out_date
                        );
                        payload.append(
                            "payment_method",
                            this.formData.payment_method
                        );
                        payload.append(
                            "is_incognito",
                            this.formData.is_incognito ? "1" : "0"
                        );

                        if (this.ktpFile) {
                            payload.append("ktp_image", this.ktpFile);
                        }

                        const response = await axios.post(
                            "check-in/walk-in",
                            payload,
                            {
                                headers: {
                                    "Content-Type": "multipart/form-data",
                                },
                            }
                        );

                        if (response.data.snap_token) {
                            this.handleMidtransPopup(response.data.snap_token);
                            return;
                        }
                    } else {
                        await axios.post("check-in/process-booking", {
                            booking_id: this.formData.booking_id,
                            is_incognito: this.formData.is_incognito,
                        });
                    }

                    ElMessage.success("Check-in Berhasil!");
                    this.$emit("success");
                    this.closeModal();
                } catch (error) {
                    console.error(error);

                    if (error.response && error.response.status === 422) {
                        const errors = error.response.data.errors;
                        const firstError = Object.values(errors)[0][0];
                        ElMessage.error(
                            firstError ||
                                "Data tidak valid. Cek kembali inputan Anda."
                        );
                    } else {
                        const msg =
                            error.response?.data?.message ||
                            error.message ||
                            "Terjadi kesalahan sistem.";
                        ElMessage.error(msg);
                    }
                } finally {
                    this.loading = false;
                }
            });
        },

        handleMidtransPopup(snapToken) {
            if (typeof window.snap === "undefined") {
                ElMessage.error(
                    "Library Midtrans belum dimuat. Refresh halaman."
                );
                this.loading = false;
                return;
            }

            window.snap.pay(snapToken, {
                onSuccess: (result) => {
                    ElMessage.success(
                        "Pembayaran Lunas! Check-in sedang diproses..."
                    );
                    this.$emit("success");
                    this.closeModal();
                },
                onPending: (result) => {
                    ElMessage.warning("Menunggu Pembayaran...");
                    this.$emit("success");
                    this.closeModal();
                },
                onError: (result) => {
                    ElMessage.error("Pembayaran Gagal.");
                    this.loading = false;
                },
                onClose: () => {
                    ElMessage.info("Jendela pembayaran ditutup.");
                    this.loading = false;
                },
            });
        },

        formatCurrency(val) {
            if (!val) return "Rp 0";
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
                minimumFractionDigits: 0,
            }).format(val);
        },
        formatDate(date) {
            return moment(date).format("DD MMM YYYY");
        },
        disabledDate(time) {
            return time.getTime() < new Date().setHours(0, 0, 0, 0);
        },
    },
};
</script>

<style scoped>
.text-orange { color: #f68b1e !important; }
.bg-orange { background-color: #f68b1e !important; }
.bg-light-orange { background-color: #fff4e6 !important; }
.border-orange { border-color: #f68b1e !important; }

.btn-orange { background-color: #f68b1e; color: white; border: none; transition: all 0.3s ease; }
.btn-orange:hover { background-color: #d67616; color: white; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(246, 139, 30, 0.4); }

.btn-light-orange { background-color: #fff4e6; color: #f68b1e; border: 1px solid #ffe5c7; }
.btn-light-orange:hover, .btn-light-orange.active { background-color: #ffe5c7; color: #f68b1e; }

.bg-gradient-orange { background: linear-gradient(135deg, #f68b1e 0%, #ff6b35 100%); }

.hover-scale { transition: transform 0.3s ease; }
.hover-scale:hover { transform: scale(1.02); }

.transition-mode { transition: all 0.3s ease; }

.slide-fade-enter-active { transition: all 0.4s ease; }
.slide-fade-leave-active { transition: all 0.2s ease; position: absolute; width: 100%; opacity: 0; }
.slide-fade-enter-from { transform: translateX(20px); opacity: 0; }
.slide-fade-leave-to { transform: translateX(-20px); opacity: 0; }

.content-transition { transition: all 0.4s ease; }

.payment-card { border: 2px solid #e4e6ef; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s ease; background: #fff; display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center; }
.payment-card:hover { border-color: #f68b1e; transform: translateY(-2px); }
.payment-card.active { border-color: #f68b1e; background: #fff4e6; box-shadow: 0 4px 12px rgba(246, 139, 30, 0.25); }

:deep(.el-input__wrapper) { box-shadow: none !important; background-color: #f9f9f9; border: 1px solid #e1e3ea; border-radius: 6px; padding: 6px 12px; }
:deep(.el-input__wrapper.is-focus) { border-color: #f68b1e !important; background-color: #ffffff; box-shadow: 0 0 0 3px rgba(246, 139, 30, 0.1) !important; }
:deep(.el-date-editor.el-input) { width: 100%; }
:deep(.el-select-dropdown__item.is-selected) { background-color: #fff4e6; color: #f68b1e; }
:deep(.btn-check:checked + .btn-outline-orange) { background-color: #fff4e6 !important; border-color: #f68b1e !important; color: #f68b1e !important; }

@keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
.animate__animated.animate__fadeInDown { animation: fadeInDown 0.5s ease-out; }
.animate__faster { animation-duration: 0.3s; }
.scroll-y::-webkit-scrollbar { width: 5px; }
.scroll-y::-webkit-scrollbar-thumb { background: #f68b1e; border-radius: 3px; }
.badge-sm { padding: 3px 8px; font-size: 0.7rem; }
</style>