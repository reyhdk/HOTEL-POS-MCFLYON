<template>
    <div
        class="modal fade"
        id="kt_modal_check_in"
        ref="modalRef"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
    >
        <div class="modal-dialog modal-dialog-centered mw-950px">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden theme-transition">
                <!-- Header -->
                <div class="modal-header border-0 py-4 px-5 bg-orange-gradient d-flex align-items-center">
                    <div class="d-flex align-items-center gap-3 w-100">
                        <div class="symbol symbol-45px symbol-circle bg-white bg-opacity-20 d-flex align-items-center justify-content-center animate-bounce">
                            <i class="ki-duotone ki-key fs-2 text-white">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </div>
                        <div class="d-flex flex-column" style="color: #ffffff !important">
                            <h3 class="fw-bolder mb-0 fs-3 text-shadow text-white">
                                Kamar {{ roomData?.room_number }}
                            </h3>
                            <div class="d-flex align-items-center gap-2 opacity-100">
                                <span class="fs-9 fw-bold text-uppercase ls-1 text-white">Check-In</span>
                                <span class="bullet bullet-dot bg-white h-4px w-4px"></span>
                                <span class="fs-9 fw-bold text-white">
                                    {{ formatRupiah(roomData?.price_per_night) }}/wengi
                                </span>
                            </div>
                        </div>
                    </div>
                    <button 
                        type="button" 
                        class="btn btn-icon btn-sm btn-active-light-primary rounded-circle hover-scale" 
                        data-bs-dismiss="modal"
                        style="background: rgba(255,255,255,0.2);">
                        <i class="ki-duotone ki-cross fs-1 text-white">
                        <span class="path1"></span><span class="path2"></span>
                        </i>
                    </button>
                </div>

                <div class="modal-body p-0 bg-body-adaptive">
                    <div class="row g-0">
                        <!-- SISI KIWA: Form Utama -->
                        <div class="col-lg-7 border-end border-gray-200 bg-white">
                            <!-- Alert Booking Dinten Niki -->
                            <transition name="slide-down">
                                <div v-if="existingBookingToday" class="alert-booking p-3 px-5 d-flex align-items-center gap-3 border-bottom border-warning border-opacity-25 bg-light-warning bg-opacity-50">
                                    <i class="ki-duotone ki-calendar-tick fs-2 text-warning"><span class="path1"></span><span class="path2"></span></i>
                                    <div class="flex-grow-1 lh-1">
                                        <div class="fs-8 fw-bold text-gray-800">Tamu niki gadhah booking dinten niki</div>
                                    </div>
                                    <button class="btn btn-xs btn-warning fw-bold text-dark shadow-sm" @click="pilihBooking(existingBookingToday)">Proses</button>
                                </div>
                            </transition>

                            <div class="p-6 d-flex flex-column gap-6">
                                <!-- Mode Switcher -->
                                <div class="nav-switcher rounded-pill p-1 d-flex bg-light position-relative border border-gray-200">
                                    <button v-for="mode in ['walk_in', 'booking_existing']" :key="mode" type="button" class="btn btn-sm flex-grow-1 rounded-pill fw-bold transition-all z-index-1 py-2"
                                        :class="checkInMode === mode ? 'btn-active-mode shadow-sm text-orange-600' : 'text-muted hover-text-adaptive'" @click="gantiMode(mode)">
                                        <i class="ki-duotone fs-4 me-2" :class="mode === 'walk_in' ? 'ki-profile-user' : 'ki-calendar-search'"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                        {{ mode === "walk_in" ? "Walk-In" : "Cari Booking" }}
                                    </button>
                                </div>

                                <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef" hide-required-asterisk class="form-adaptive">
                                    <transition name="fade-mode" mode="out-in">
                                        <!-- AREA FORM WALK-IN -->
                                        <div v-if="checkInMode === 'walk_in'" key="walkin" class="d-flex flex-column gap-4">
                                            <div class="row g-3">
                                                <div class="col-6" v-for="type in ['existing', 'new']" :key="type">
                                                    <div class="card-option cursor-pointer p-3 rounded-3 border d-flex align-items-center justify-content-center gap-2 transition-all"
                                                        :class="guestType === type ? 'active-selection' : 'border-dashed border-gray-300'" @click="guestType = type; resetFormGuestSelection();">
                                                        <i class="ki-duotone fs-2" :class="[type === 'existing' ? 'ki-profile-user' : 'ki-user-edit', guestType === type ? 'text-orange-600' : 'text-gray-400']">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                        <span class="fs-8 fw-bold" :class="guestType === type ? 'text-orange-600' : 'text-gray-500'">{{ type === "existing" ? "Tamu Lawas" : "Tamu Anyar" }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="min-h-60px">
                                                <div v-if="guestType === 'existing'" class="animate-fade-in">
                                                    <el-form-item prop="guest_id" class="mb-0">
                                                        <el-select v-model="formData.guest_id" filterable remote reserve-keyword placeholder="Pados Nama / No HP..." :remote-method="searchGuests" :loading="loadingGuests" class="w-100" size="large" @change="onExistingGuestSelected">
                                                            <template #prefix><i class="ki-duotone ki-magnifier fs-3 text-gray-500"><span class="path1"></span><span class="path2"></span></i></template>
                                                            <el-option v-for="item in guestOptions" :key="item.id" :label="item.name" :value="item.id">
                                                                <div class="d-flex justify-content-between"><span class="fw-bold">{{ item.name }}</span><span class="text-muted fs-9">{{ item.phone_number }}</span></div>
                                                            </el-option>
                                                        </el-select>
                                                    </el-form-item>
                                                </div>
                                                <div v-else class="new-guest-box p-4 rounded-3 border border-orange-dashed animate-fade-in bg-light-orange bg-opacity-10">
                                                    <el-form-item prop="new_name" class="mb-3"><el-input v-model="formData.new_name" placeholder="Nama Jangkep" class="input-adaptive" /></el-form-item>
                                                    <div class="d-flex gap-3">
                                                        <el-form-item prop="new_phone" class="mb-0 w-50"><el-input v-model="formData.new_phone" placeholder="No WhatsApp" class="input-adaptive" /></el-form-item>
                                                        <el-form-item class="mb-0 w-50"><el-input v-model="formData.new_email" placeholder="Email" class="input-adaptive" /></el-form-item>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-5">
                                                    <div class="upload-box h-100 w-100 rounded-3 border border-dashed d-flex flex-column align-items-center justify-content-center cursor-pointer position-relative overflow-hidden transition-all bg-light"
                                                        :class="[ktpFile ? 'border-success' : (isKtpRequired ? 'border-danger' : 'border-gray-400 hover-border-orange')]" style="min-height: 90px" @click="triggerUpload">
                                                        <div v-if="!ktpPreview" class="text-center p-2">
                                                            <i class="ki-duotone ki-camera fs-1" :class="isKtpRequired ? 'text-danger' : 'text-gray-400'"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                            <div class="fs-10 fw-bold" :class="isKtpRequired ? 'text-danger' : 'text-muted'">{{ isKtpRequired ? 'Wajib Upload KTP' : 'Upload KTP' }}</div>
                                                        </div>
                                                        <img v-else :src="ktpPreview" class="w-100 h-100 object-fit-cover" />
                                                        <input type="file" ref="ktpRef" class="d-none" accept="image/*" @change="handleUpload" />
                                                    </div>
                                                </div>
                                                <div class="col-7">
                                                    <div class="date-box p-3 rounded-3 border border-dashed border-gray-400 bg-light h-100 d-flex flex-column justify-content-center">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <label class="form-label fs-9 fw-bold text-gray-600 mb-0">RENCANA CHECK-OUT</label>
                                                            <span class="badge badge-light-orange text-orange-600 fw-bold fs-10 px-2 py-1">{{ durasi }} Sewengi</span>
                                                        </div>
                                                        <el-form-item prop="check_out_date" class="mb-0">
                                                            <el-date-picker v-model="formData.check_out_date" type="date" class="w-100" format="DD MMM YYYY" value-format="YYYY-MM-DD" :disabled-date="disabledDate" />
                                                        </el-form-item>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-2">
                                                <label class="form-label fs-9 fw-bold text-gray-500 mb-2">METODE PEMBAYARAN KAMAR</label>
                                                <div class="d-flex gap-2">
                                                    <div v-for="method in ['cash', 'midtrans']" :key="method" class="card-option flex-grow-1 p-3 rounded-3 border cursor-pointer transition-all d-flex align-items-center justify-content-center gap-2"
                                                        :class="formData.payment_method === method ? 'active-selection' : 'border-dashed border-gray-300'" @click="formData.payment_method = method">
                                                        <i class="ki-duotone fs-2" :class="[method === 'cash' ? 'ki-bill' : 'ki-scan-barcode', formData.payment_method === method ? 'text-orange-600' : 'text-gray-500']"><span class="path1"></span><span class="path2"></span></i>
                                                        <span class="fs-8 fw-bold" :class="formData.payment_method === method ? 'text-orange-600' : 'text-gray-600'">{{ method === "cash" ? "Tunai / Kontan" : "QRIS / Transfer" }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- AREA FORM DARI BOOKING -->
                                        <div v-else key="booking" class="d-flex flex-column gap-3">
                                            <div class="text-center py-2">
                                                <i class="ki-duotone ki-calendar-search fs-3x text-orange-600 animate-bounce mb-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                                <h6 class="fw-bold text-gray-800 mb-1">Cari Data Booking</h6>
                                                <p class="text-muted fs-9">Namung booking ingkang statusipun lunas</p>
                                            </div>
                                            <el-form-item prop="booking_id" class="mb-2">
                                                <el-select v-model="formData.booking_id" filterable placeholder="Ketik nama tamu booking..." class="w-100" size="large" @change="onBookingSelected">
                                                    <el-option v-for="book in availableBookings" :key="book.id" :label="book.guest.name" :value="book.id">
                                                        <div class="d-flex justify-content-between align-items-center py-1">
                                                            <div>
                                                                <span class="fw-bold d-block lh-1 mb-1">{{ book.guest.name }}</span>
                                                                <span class="text-muted fs-10">{{ formatDate(book.check_in_date) }} - {{ formatDate(book.check_out_date) }}</span>
                                                            </div>
                                                            <span class="badge badge-light-success fs-10">Lunas</span>
                                                        </div>
                                                    </el-option>
                                                </el-select>
                                            </el-form-item>

                                            <div v-if="selectedBooking" class="booking-info-box p-4 rounded-3 border-start border-4 border-primary bg-light-primary mb-2 shadow-sm animate-fade-in">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <div class="fs-7 fw-bolder text-primary mb-1">{{ selectedBooking.guest.name }}</div>
                                                        <div class="fs-9 text-muted fw-bold">{{ selectedBooking.guest.phone_number }}</div>
                                                    </div>
                                                    <div class="d-flex flex-column align-items-end gap-1">
                                                        <span class="badge badge-primary fw-bolder text-uppercase fs-10">Confirmed</span>
                                                        <!-- STATUS VERIFIKASI -->
                                                        <span v-if="selectedBooking.guest?.is_verified" class="badge badge-light-success fs-10 border border-success border-opacity-25">
                                                            <i class="ki-duotone ki-shield-tick fs-9 me-1"><span class="path1"></span><span class="path2"></span></i> Terverifikasi
                                                        </span>
                                                        <span v-else class="badge badge-light-danger fs-10 border border-danger border-opacity-25">
                                                            <i class="ki-duotone ki-shield-cross fs-9 me-1"><span class="path1"></span><span class="path2"></span></i> Perlu KTP Ulang
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="separator separator-dashed my-2 border-primary opacity-25"></div>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <div class="fs-10 text-muted fw-bold mb-1">CHECK-IN</div>
                                                        <div class="fs-9 fw-bolder text-gray-800">{{ formatDate(selectedBooking.check_in_date) }}</div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="fs-10 text-muted fw-bold mb-1">CHECK-OUT</div>
                                                        <div class="fs-9 fw-bolder text-gray-800">{{ formatDate(selectedBooking.check_out_date) }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- UPLOAD KTP RE-VERIFY (Mung muncul yen tamu dereng terverifikasi) -->
                                            <div v-if="selectedBooking && !selectedBooking.guest?.is_verified" class="p-4 rounded-3 border border-dashed border-danger bg-light-danger bg-opacity-10 animate-fade-in mb-2">
                                                <div class="d-flex align-items-center gap-2 mb-3">
                                                    <i class="ki-duotone ki-information-5 fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                    <span class="fs-9 fw-bold text-danger">KTP ditolak sadurunge. Admin wajib upload KTP ingkang cetha dinten niki.</span>
                                                </div>
                                                <div class="upload-box-mini rounded-3 border border-dashed border-gray-400 bg-white p-3 text-center cursor-pointer hover-border-orange transition-all" @click="triggerUpload">
                                                    <div v-if="!ktpPreview" class="text-muted fs-10 fw-bold">Klik kagem Scan/Upload KTP</div>
                                                    <img v-else :src="ktpPreview" class="img-fluid rounded shadow-sm" style="max-height: 120px" />
                                                    <input type="file" ref="ktpRef" class="d-none" accept="image/*" @change="handleUpload" />
                                                </div>
                                            </div>
                                            
                                            <div v-if="isEarlyCheckIn && selectedBooking" class="payment-method-box mt-1">
                                                <div class="alert alert-warning border border-warning border-opacity-20 d-flex align-items-center gap-3 p-3 rounded-3 mb-3">
                                                    <i class="ki-duotone ki-information-5 fs-2 text-warning"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                    <span class="fs-9 fw-bold text-gray-800 lh-1">Wonten biaya Early Check-In. Mangga pilih metode pembayaran tambahan.</span>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <div v-for="method in ['cash', 'midtrans']" :key="method" class="card-option flex-grow-1 p-3 rounded-3 border cursor-pointer transition-all d-flex align-items-center justify-content-center gap-2"
                                                        :class="formData.early_payment_method === method ? 'active-selection' : 'border-dashed border-gray-300'" @click="formData.early_payment_method = method">
                                                        <i class="ki-duotone fs-2" :class="[method === 'cash' ? 'ki-bill' : 'ki-scan-barcode', formData.early_payment_method === method ? 'text-orange-600' : 'text-gray-500']"><span class="path1"></span><span class="path2"></span></i>
                                                        <span class="fs-8 fw-bold" :class="formData.early_payment_method === method ? 'text-orange-600' : 'text-gray-600'">{{ method === 'cash' ? 'Bayar Tunai' : 'QRIS / Online' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </transition>

                                    <!-- AREA ACTION: TOTAL & SUBMIT -->
                                    <div class="mt-auto pt-6 border-top border-dashed">
                                        <div class="d-flex align-items-center justify-content-between mb-4">
                                            <div class="privacy-card d-flex align-items-center gap-3 px-4 py-2 rounded-3 border bg-light-subtle transition-all cursor-pointer"
                                                :class="formData.is_incognito ? 'border-dark bg-dark' : 'border-gray-300'"
                                                @click="formData.is_incognito = !formData.is_incognito">
                                                <div class="symbol symbol-30px">
                                                    <div class="symbol-label rounded-circle bg-white shadow-xs">
                                                        <i class="ki-duotone fs-4" :class="formData.is_incognito ? 'ki-eye-slash text-dark' : 'ki-eye text-gray-400'"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fs-10 fw-bold text-uppercase ls-1 mb-0" :class="formData.is_incognito ? 'text-white' : 'text-gray-500'">Privasi Tamu</span>
                                                    <span class="fs-9 fw-bolder" :class="formData.is_incognito ? 'text-white' : 'text-gray-900'">{{ formData.is_incognito ? 'Sembunyi' : 'Tampil' }}</span>
                                                </div>
                                                <div class="form-check form-switch form-check-custom form-check-solid form-check-sm ms-2">
                                                    <input class="form-check-input h-15px w-25px" type="checkbox" v-model="formData.is_incognito" @click.stop />
                                                </div>
                                            </div>

                                            <div class="text-end">
                                                <div v-if="checkInMode === 'booking_existing'" class="fs-10 fw-bold text-success text-uppercase ls-1 mb-1">
                                                    <i class="ki-duotone ki-check-circle fs-8 text-success me-1"><span class="path1"></span><span class="path2"></span></i>Kamar Sampun Lunas
                                                </div>
                                                <div class="fs-9 fw-bold text-gray-500 text-uppercase ls-1">{{ currentPaymentLabel }}</div>
                                                <div class="text-orange-600 d-flex align-items-baseline justify-content-end">
                                                    <span class="fs-8 fw-bold me-1">Rp</span>
                                                    <span class="fs-1 fw-bolder ls-n1">{{ formatRupiahSimple(computedTotalBayar) }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-orange w-100 fw-bold hover-scale shadow-sm py-4 fs-6" :disabled="loading">
                                            <span v-if="!loading" class="d-flex align-items-center justify-content-center gap-2">
                                                <i class="ki-duotone ki-entrance-right fs-3"><span class="path1"></span><span class="path2"></span></i>
                                                {{ checkInMode === 'walk_in' ? 'Check-In Saiki' : (computedTotalBayar > 0 ? 'Bayar Tambahan & Check-In' : 'Verifikasi & Check-In') }}
                                            </span>
                                            <span v-else>Memproses... <span class="spinner-border spinner-border-sm ms-1"></span></span>
                                        </button>
                                    </div>
                                </el-form>
                            </div>
                        </div>

                        <!-- SISI TENGEN: Jadwal & Kalender -->
                        <div class="col-lg-5 bg-light-subtle d-flex flex-column border-start border-gray-200">
                            <div class="p-5 flex-grow-1 overflow-auto">
                                <div class="d-flex align-items-center justify-content-between mb-4 px-1">
                                    <span class="fw-bolder fs-8 text-gray-800">Cek Kasedhiyan</span>
                                    <span class="badge badge-light-primary fw-bold fs-10 px-3 py-1 rounded-pill">{{ currentMonthName }}</span>
                                </div>
                                
                                <div class="mini-calendar shadow-sm mb-5">
                                    <div class="calendar-grid-header">
                                        <span v-for="day in ['M','S','S','R','K','J','S']" :key="day" class="text-gray-400 fw-bolder">{{day}}</span>
                                    </div>
                                    <div class="calendar-grid-body">
                                        <div v-for="n in paddingDays" :key="'pad-'+n" class="day empty"></div>
                                        <div v-for="date in daysInMonth" :key="date" class="day clickable" 
                                            :class="[getDayStatusClass(date), { 'is-today': isToday(date), 'is-clicked': clickedDate === date }]"
                                            @click="handleDateClick(date)">
                                            {{ date }}
                                        </div>
                                    </div>
                                </div>

                                <div class="transition-all" style="min-height: 80px">
                                    <transition name="fade-mode" mode="out-in">
                                        <div v-if="selectedDayBooking" :key="clickedDate" class="p-3 rounded-3 bg-white border border-gray-200 shadow-sm animate-fade-in">
                                            <div class="fs-10 fw-bold text-muted text-uppercase mb-2 lh-1">Informasi Tgl {{ clickedDate }}:</div>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="symbol symbol-35px symbol-circle bg-light-orange border border-orange border-opacity-20 shadow-xs">
                                                    <span class="symbol-label fs-8 fw-bold text-orange-600">{{ selectedDayBooking.guest?.name?.charAt(0) }}</span>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden lh-1">
                                                    <div class="fs-8 fw-bolder text-gray-900 text-truncate mb-1">{{ selectedDayBooking.guest?.name }}</div>
                                                    <span class="badge p-0 fs-10 fw-bold text-gray-500">{{ selectedDayBooking.status === 'checked_in' ? 'Stayed' : 'Reserved' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else-if="clickedDate" :key="'empty-'+clickedDate" class="p-4 rounded-3 bg-white border border-dashed border-gray-300 text-center animate-fade-in d-flex flex-column align-items-center justify-content-center">
                                            <i class="ki-duotone ki-shield-check fs-2x text-gray-400 mb-1"><span class="path1"></span><span class="path2"></span></i>
                                            <span class="fs-10 fw-bold text-gray-400 fst-italic ls-1">KOSONG</span>
                                        </div>
                                        <div v-else class="h-100 d-flex align-items-center justify-content-center py-8">
                                            <div class="text-center opacity-50">
                                                <i class="ki-duotone ki-hand fs-2x mb-2 text-gray-400"><span class="path1"></span><span class="path2"></span></i>
                                                <div class="fs-10 fw-bold text-gray-400 text-center lh-sm">Ketuk tanggal kalender<br>kangge cek tamu</div>
                                            </div>
                                        </div>
                                    </transition>
                                </div>

                                <div class="mt-auto pt-5 border-top border-gray-200 border-opacity-50">
                                    <div class="fs-10 fw-bold text-gray-500 mb-3 text-uppercase ls-1">Keterangan:</div>
                                    <div class="d-flex flex-wrap gap-x-4 gap-y-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="bullet bullet-dot bg-success h-8px w-8px"></span>
                                            <span class="fs-10 fw-bold text-gray-700">Terisi</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="bullet bullet-dot bg-warning h-8px w-8px"></span>
                                            <span class="fs-10 fw-bold text-gray-700">Booking</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="bullet bullet-dot bg-gray-400 h-8px w-8px"></span>
                                            <span class="fs-10 fw-bold text-gray-700">Kosong</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="bullet bullet-dot border border-success h-8px w-8px bg-white"></span>
                                            <span class="fs-10 fw-bold text-gray-700">Dinten Niki</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
import axios from "axios";

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
            bookings: [],
            selectedBooking: null,
            existingBookingToday: null,
            ktpFile: null,
            ktpPreview: null,
            isKtpInDb: false,
            settings: null,
            currentDate: new Date(),
            clickedDate: null,
            selectedDayBooking: null,

            formData: {
                room_id: null,
                guest_id: null,
                booking_id: null,
                check_in_date: moment().format("YYYY-MM-DD"),
                check_out_date: moment().add(1, "days").format("YYYY-MM-DD"),
                payment_method: "cash",
                early_payment_method: "cash",
                is_incognito: false,
                new_name: "",
                new_phone: "",
                new_email: "",
                notes: "",
            },
        };
    },
    computed: {
        isEarlyCheckIn() {
            if (!this.settings || !this.settings.check_in_time) return false;
            const now = moment();
            const standardCheckIn = moment(this.settings.check_in_time.substring(0, 5), "HH:mm");
            return now.isBefore(standardCheckIn);
        },
        isKtpRequired() {
            // Logika Verifikasi KTP (Permintaan User):
            // 1. Yen walk-in tamu anyar, wajib.
            if (this.checkInMode === 'walk_in' && this.guestType === 'new') return true;
            // 2. Yen walk-in tamu lawas nanging dereng wonten KTP ing DB, wajib.
            if (this.checkInMode === 'walk_in' && this.guestType === 'existing' && !this.isKtpInDb && !this.ktpFile) return true;
            // 3. Yen booking online nanging tamu dereng verified (reject-an admin), wajib upload dinten niki.
            if (this.checkInMode === 'booking_existing' && this.selectedBooking && !this.selectedBooking.guest?.is_verified && !this.ktpFile) return true;
            
            return false;
        },
        currentMonthName() { return moment(this.currentDate).format('MMMM YYYY'); },
        daysInMonth() { return new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0).getDate(); },
        paddingDays() { return new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1).getDay(); },
        durasi() {
            const start = moment(this.formData.check_in_date);
            const end = moment(this.formData.check_out_date);
            const diff = end.diff(start, "days");
            return diff > 0 ? diff : 1;
        },
        totalHarga() { return this.durasi * (this.roomData?.price_per_night || 0); },
        earlyCheckInFee() {
            if (!this.settings || !this.settings.check_in_time) return 0;
            const feePerHour = parseInt(this.settings.early_check_in_fee) || 0;
            if (feePerHour === 0) return 0;
            const now = moment();
            const standardCheckIn = moment(this.settings.check_in_time.substring(0, 5), "HH:mm");
            if (now.isSameOrAfter(standardCheckIn)) return 0;
            const hoursEarly = standardCheckIn.diff(now, "hours");
            const minutesResidue = standardCheckIn.diff(now, "minutes") % 60;
            const totalHours = minutesResidue > 0 ? hoursEarly + 1 : hoursEarly;
            return totalHours * feePerHour;
        },
        computedTotalBayar() {
            if (this.checkInMode === 'booking_existing') return this.earlyCheckInFee;
            return this.totalHarga + this.earlyCheckInFee;
        },
        currentPaymentLabel() {
            if (this.checkInMode === 'booking_existing') return this.earlyCheckInFee > 0 ? 'BIAYA EARLY CHECK-IN' : 'TOTAL PEMBAYARAN';
            return 'TOTAL PEMBAYARAN BARU';
        },
        rules() {
            if (this.checkInMode === "walk_in") {
                const r = { check_out_date: [{ required: true, message: "Pilih tanggal", trigger: "change" }] };
                if (this.guestType === "existing") {
                    r.guest_id = [{ required: true, message: "Pilih tamu", trigger: "change" }];
                } else {
                    r.new_name = [{ required: true, message: "Nama wajib", trigger: "blur" }];
                    r.new_phone = [{ required: true, message: "No HP wajib", trigger: "blur" }];
                }
                return r;
            }
            return { booking_id: [{ required: true, message: "Pilih booking", trigger: "change" }] };
        }
    },
    methods: {
        formatRupiah(v) { return v ? new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(v) : "Rp 0"; },
        formatRupiahSimple(v) { return v ? new Intl.NumberFormat("id-ID").format(v) : "0"; },
        formatDate(d) { return d ? moment(d).format("DD MMM YYYY") : "-"; },
        disabledDate(t) { return t.getTime() < Date.now() - 8.64e7; },
        isToday(date) { return date === moment().date() && this.currentDate.getMonth() === moment().month(); },

        openModal(room) {
            this.roomData = room;
            this.resetForm();
            this.clickedDate = null;
            this.selectedDayBooking = null;
            this.formData.room_id = room.id;
            this.fetchSettings();
            this.fetchBookings(room.id);
            this.fetchFullSchedule(room.id);
            if (!this.modal) this.modal = new Modal(this.$refs.modalRef);
            this.modal.show();
        },
        resetForm() {
            this.checkInMode = "walk_in";
            this.guestType = "existing";
            this.ktpFile = null;
            this.ktpPreview = null;
            this.isKtpInDb = false;
            this.formData.guest_id = null;
            this.formData.booking_id = null;
            this.formData.check_out_date = moment().add(1, "days").format("YYYY-MM-DD");
            this.formData.payment_method = "cash";
            this.formData.early_payment_method = "cash";
            this.formData.is_incognito = false;
        },
        resetFormGuestSelection() {
            this.formData.guest_id = null;
            this.formData.new_name = "";
            this.formData.new_phone = "";
            this.ktpFile = null;
            this.ktpPreview = null;
            this.isKtpInDb = false;
            this.resetValidasi();
        },
        async fetchSettings() {
            try {
                const res = await axios.get("settings");
                const data = res.data.data || res.data;
                this.settings = Array.isArray(data) ? data[0] : data;
            } catch (e) { console.error(e); }
        },
        async fetchBookings(roomId) {
            try {
                const res = await axios.get("bookings", { params: { room_id: roomId, status_in: "paid,confirmed,verified", date_gte: moment().format("YYYY-MM-DD") } });
                this.availableBookings = res.data.data || [];
                const today = moment().startOf('day');
                this.existingBookingToday = this.availableBookings.find(b => moment(b.check_in_date).isSame(today, 'day'));
            } catch (e) { console.error(e); }
        },
        async fetchFullSchedule(roomId) {
            try {
                const { data } = await ApiService.query('/bookings', { room_id: roomId, status_in: 'paid,confirmed,verified,checked_in' });
                this.bookings = data.data || [];
            } catch (e) { console.error(e); }
        },
        getDayStatusClass(day) {
            const d = moment(new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), day)).startOf('day');
            const bookAtDate = this.bookings.find(b => {
                const start = moment(b.check_in_date).startOf('day');
                const end = moment(b.check_out_date).startOf('day');
                return d.isSameOrAfter(start) && d.isBefore(end);
            });
            if (!bookAtDate) return 'is-empty';
            return bookAtDate.status === 'checked_in' ? 'is-occupied' : 'is-reserved';
        },
        handleDateClick(day) {
            this.clickedDate = day;
            const d = moment(new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), day)).startOf('day');
            this.selectedDayBooking = this.bookings.find(b => {
                const start = moment(b.check_in_date).startOf('day');
                const end = moment(b.check_out_date).startOf('day');
                return d.isSameOrAfter(start) && d.isBefore(end);
            });
        },
        gantiMode(m) { 
            this.checkInMode = m; 
            this.ktpFile = null;
            this.ktpPreview = null;
            this.isKtpInDb = false;
            this.resetValidasi(); 
            if (m === 'booking_existing' && this.formData.booking_id) this.onBookingSelected();
        },
        resetValidasi() { this.$nextTick(() => { if (this.$refs.formRef) this.$refs.formRef.clearValidate(); }); },
        searchGuests: debounce(function (query) {
            if (query?.length >= 2) {
                this.loadingGuests = true;
                axios.get("guests", { params: { search: query } }).then(res => this.guestOptions = res.data.data).finally(() => this.loadingGuests = false);
            }
        }, 500),
        onExistingGuestSelected(val) {
            const guest = this.guestOptions.find(g => g.id === val);
            if (guest && guest.ktp_image) {
                // Yen tamu sampun verified, tampilaken preview saking DB.
                if (guest.is_verified) {
                    this.ktpPreview = guest.ktp_image.startsWith('http') ? guest.ktp_image : `/storage/${guest.ktp_image}`;
                    this.isKtpInDb = true;
                } else {
                    this.ktpPreview = null;
                    this.isKtpInDb = false;
                }
            } else {
                this.ktpPreview = null;
                this.isKtpInDb = false;
            }
        },
        triggerUpload() { this.$refs.ktpRef.click(); },
        handleUpload(e) {
            const file = e.target.files[0];
            if (file) { 
                this.ktpFile = file; 
                this.ktpPreview = URL.createObjectURL(file); 
            }
        },
        pilihBooking(book) {
            this.checkInMode = "booking_existing";
            this.$nextTick(() => { 
                this.formData.booking_id = book.id; 
                this.onBookingSelected(); 
            });
        },
        onBookingSelected() { 
            this.selectedBooking = this.availableBookings.find(b => b.id === this.formData.booking_id);
            if (this.selectedBooking && this.selectedBooking.guest) {
                const g = this.selectedBooking.guest;
                // Logika: Yen sampun verified, tampilaken. Yen dereng, biarkan admin upload.
                if (g.is_verified) {
                    this.ktpPreview = g.ktp_image ? (g.ktp_image.startsWith('http') ? g.ktp_image : `/storage/${g.ktp_image}`) : null;
                    this.isKtpInDb = !!g.ktp_image;
                } else {
                    this.ktpPreview = null;
                    this.isKtpInDb = false;
                    this.ktpFile = null;
                }
            }
        },
        async submit() {
            this.$refs.formRef.validate(async (valid) => {
                if (!valid) return;
                
                // Cegah check-in awal
                if (this.checkInMode === 'booking_existing' && this.selectedBooking) {
                    const today = moment().startOf('day');
                    const bookDate = moment(this.selectedBooking.check_in_date).startOf('day');
                    if (bookDate.isAfter(today)) {
                        Swal.fire({ title: "Terlalu Cepat", text: `Tamu punika dijadwalaken Check-in tgl ${bookDate.format('DD MMM YYYY')}.`, icon: "warning" });
                        return;
                    }
                }

                if (this.isKtpRequired) {
                    Swal.fire("Info", "Wajib lampiraken foto KTP kagem tamu punika.", "warning");
                    return;
                }

                this.loading = true;
                try {
                    const payload = new FormData();
                    payload.append("room_id", this.roomData.id);
                    payload.append("is_incognito", this.formData.is_incognito ? 1 : 0);
                    if (this.ktpFile) payload.append("ktp_image", this.ktpFile);

                    if (this.checkInMode === "walk_in") {
                        if (this.guestType === "existing") payload.append("guest_id", this.formData.guest_id);
                        else {
                            payload.append("guest_name", this.formData.new_name);
                            payload.append("guest_phone", this.formData.new_phone);
                            payload.append("guest_email", this.formData.new_email);
                        }
                        payload.append("check_out_date", this.formData.check_out_date + " 12:00:00");
                        payload.append("payment_method", this.formData.payment_method);
                    } else {
                        payload.append("booking_id", this.formData.booking_id);
                        payload.append("payment_method", this.formData.early_payment_method);
                    }

                    const { data } = await axios.post("admin/check-ins/store-direct", payload, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });

                    if (data.snap_token) {
                        window.snap.pay(data.snap_token, {
                            onSuccess: () => { 
                                Swal.fire("Berhasil", "Pembayaran sukses lan check-in diproses.", "success");
                                this.$emit("success"); this.modal.hide(); 
                            },
                            onPending: () => {
                                Swal.fire("Menunggu Pembayaran", "Mangga rampungaken pembayaran QRIS panjenengan.", "info");
                                this.$emit("success"); this.modal.hide();
                            },
                            onClose: () => { Swal.fire("Batal", "Pembayaran dibatalaken.", "warning"); }
                        });
                    } else {
                        Swal.fire("Berhasil", data.message, "success");
                        this.$emit("success"); this.modal.hide();
                    }
                } catch (e) { 
                    Swal.fire("Gagal", e.response?.data?.message || "Error saat memproses check-in.", "error"); 
                } finally { this.loading = false; }
            });
        }
    }
};
</script>

<style scoped>
.bg-orange-gradient { background: linear-gradient(135deg, #ff9900 0%, #ff5e00 100%); }
.text-orange-600 { color: #ff7700 !important; }
.bg-light-orange { background-color: rgba(255, 153, 0, 0.1) !important; }
.btn-orange { background: linear-gradient(to right, #ff9900, #ff7700); color: white; border: none; }
.btn-active-mode { background-color: #fff; color: #ff7700; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }

/* Mini Calendar */
.mini-calendar { background: #fff; padding: 12px; border-radius: 12px; border: 1px solid #f1f1f1; }
.calendar-grid-header { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-size: 8px; font-weight: 800; color: #a1a5b7; margin-bottom: 8px; }
.calendar-grid-body { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.day { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 600; border-radius: 6px; transition: all 0.2s; }
.day.clickable { cursor: pointer; }
.day.empty { opacity: 0; pointer-events: none; }
.day:hover:not(.empty) { transform: scale(1.15); background: #f8f9fa; z-index: 2; }
.day.is-clicked { border: 2px solid #ff9900 !important; transform: scale(1.15); font-weight: 800; z-index: 2; box-shadow: 0 3px 10px rgba(255, 153, 0, 0.2); }

.day.is-empty { background: #f1f1f2; color: #7e8299; }
.day.is-occupied { background: #e8fff3; color: #17c653; font-weight: 700; }
.day.is-reserved { background: #fff4e6; color: #ff9900; font-weight: 700; }
.day.is-today { outline: 2px dashed #17c653; outline-offset: -2px; }

.active-selection { 
    border: 2px solid #ff9900 !important; 
    background-color: rgba(255, 153, 0, 0.05) !important;
    box-shadow: 0 4px 12px rgba(255, 153, 0, 0.15) !important;
}

.privacy-card { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); user-select: none; }
.privacy-card:active { transform: scale(0.98); }

.upload-box-mini { border: 2px dashed #e4e6ef; background-color: #f9f9f9; min-height: 100px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.hover-border-orange:hover { border-color: #ff9900; background-color: rgba(255, 153, 0, 0.02); }

:deep(.el-input__wrapper), :deep(.el-select__wrapper) { box-shadow: none !important; border: 1px solid #e4e6ef; border-radius: 8px; padding: 8px 12px; }

.animate-bounce { animation: softBounce 2s infinite; }
@keyframes softBounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease-out; }
.slide-down-enter-from, .slide-down-leave-to { transform: translateY(-20px); opacity: 0; }

.fade-mode-enter-active, .fade-mode-leave-active { transition: opacity 0.2s ease; }
.fade-mode-enter-from, .fade-mode-leave-to { opacity: 0; }

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>