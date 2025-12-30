<template>
  <div class="modal fade" id="kt_modal_room_schedule" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
      <div class="modal-content rounded-4 border-0 shadow-lg theme-modal overflow-hidden">
        
        <div class="modal-header border-0 pb-0 bg-gradient-orange position-relative overflow-hidden">
          <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10">
            <div class="pattern-dots"></div>
          </div>
          
          <div class="w-100 position-relative z-index-1 py-5 px-2">
            <div class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-4">
                <div class="symbol symbol-50px symbol-circle bg-white bg-opacity-20 shadow-sm animate-pulse-slow">
                  <i class="ki-duotone ki-calendar-tick fs-2x text-white">
                    <span class="path1"></span><span class="path2"></span>
                    <span class="path3"></span><span class="path4"></span>
                  </i>
                </div>
                <div>
                  <h2 class="text-white fw-bolder mb-1 fs-2">
                    Jadwal Kamar <span class="text-warning">#{{ roomData?.room_number }}</span>
                  </h2>
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-light-warning fs-8 fw-bold px-3 py-1">
                      {{ getRoomType }}
                    </span>
                    <span class="text-white text-opacity-75 fs-8">•</span>
                    <span class="text-white text-opacity-75 fs-8 fw-semibold">Cek Ketersediaan</span>
                  </div>
                </div>
              </div>
              
              <button 
                type="button" 
                class="btn btn-icon btn-sm btn-active-light-primary rounded-circle position-relative z-index-2 hover-scale" 
                data-bs-dismiss="modal"
                style="background: rgba(255,255,255,0.2);">
                <i class="ki-duotone ki-cross fs-2 text-white">
                  <span class="path1"></span><span class="path2"></span>
                </i>
              </button>
            </div>
          </div>
        </div>

        <div class="modal-body p-0">
          <div class="row g-0">
            
            <div class="col-lg-7 p-7 border-end border-gray-200">
              <div class="d-flex justify-content-between align-items-center mb-5">
                <button 
                  class="btn btn-sm btn-icon btn-light-orange rounded-circle hover-scale shadow-sm" 
                  @click="changeMonth(-1)" 
                  :disabled="loading">
                  <i class="ki-duotone ki-left fs-3 text-orange">
                    <span class="path1"></span><span class="path2"></span>
                  </i>
                </button>
                
                <div class="text-center">
                  <h4 class="fw-bolder m-0 text-gray-900 fs-3">{{ currentMonthName }} {{ currentYear }}</h4>
                  <span class="text-muted fs-8 fw-semibold">Klik tanggal untuk detail</span>
                </div>
                
                <button 
                  class="btn btn-sm btn-icon btn-light-orange rounded-circle hover-scale shadow-sm" 
                  @click="changeMonth(1)" 
                  :disabled="loading">
                  <i class="ki-duotone ki-right fs-3 text-orange">
                    <span class="path1"></span><span class="path2"></span>
                  </i>
                </button>
              </div>

              <div class="calendar-wrapper position-relative">
                <transition name="fade">
                  <div 
                    v-if="loading" 
                    class="position-absolute w-100 h-100 top-0 start-0 z-index-2 bg-white bg-opacity-90 d-flex align-items-center justify-content-center rounded-3">
                    <div class="text-center">
                      <span class="spinner-border spinner-border-lg text-orange mb-2"></span>
                      <div class="text-muted fs-8 fw-bold">Memuat jadwal...</div>
                    </div>
                  </div>
                </transition>

                <div class="calendar-grid">
                  <div 
                    class="day-name text-gray-600 fw-bold" 
                    v-for="day in daysOfWeek" 
                    :key="day">
                    {{ day }}
                  </div>
                  
                  <div v-for="n in paddingDays" :key="'pad-'+n" class="calendar-day empty"></div>

                  <div 
                    v-for="date in daysInMonth" 
                    :key="date" 
                    class="calendar-day"
                    :class="getDayStatusClass(date)"
                    @click="showDateInfo(date)">
                    <span class="day-number">{{ date }}</span>
                    <div v-if="isDateBooked(date)" class="booked-indicator">
                      <div class="booked-dot"></div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="d-flex align-items-center gap-5 mt-6 px-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="legend-dot bg-success"></span>
                  <span class="text-gray-700 fs-8 fw-semibold">Hari Ini</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="legend-dot bg-orange"></span>
                  <span class="text-gray-700 fs-8 fw-semibold">Ter-booking</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="legend-dot bg-gray-300"></span>
                  <span class="text-gray-700 fs-8 fw-semibold">Tersedia</span>
                </div>
              </div>
            </div>

            <div class="col-lg-5 p-7 bg-light-subtle">
              <div class="d-flex align-items-center justify-content-between mb-5">
                <h4 class="fw-bolder text-gray-900 m-0 fs-4">
                  <i class="ki-duotone ki-calendar-8 fs-3 text-orange me-2">
                    <span class="path1"></span><span class="path2"></span>
                    <span class="path3"></span><span class="path4"></span>
                    <span class="path5"></span><span class="path6"></span>
                  </i>
                  Booking Mendatang
                </h4>
                <span class="badge badge-light-orange fs-8 fw-bold px-3 py-2">
                  {{ upcomingBookings.length }} Booking
                </span>
              </div>
              
              <div v-if="loading && upcomingBookings.length === 0" class="text-center py-10">
                <span class="spinner-border text-orange spinner-border-sm"></span>
                <div class="text-muted fs-8 fw-semibold mt-2">Memuat data...</div>
              </div>

              <div v-else-if="upcomingBookings.length === 0" class="text-center py-10">
                <div class="symbol symbol-100px symbol-circle bg-light-success mx-auto mb-4">
                  <i class="ki-duotone ki-check-circle fs-3x text-success">
                    <span class="path1"></span><span class="path2"></span>
                  </i>
                </div>
                <h5 class="fw-bold text-gray-900 mb-2">Kamar Tersedia</h5>
                <p class="text-muted fs-7 mb-0">Tidak ada booking aktif untuk<br>periode mendatang</p>
              </div>

              <div v-else class="scroll-y mh-450px pe-3">
                <transition-group name="list-fade" tag="div" class="d-flex flex-column gap-3">
                  <div 
                    v-for="book in upcomingBookings" 
                    :key="book.id" 
                    class="booking-card card border-0 shadow-sm hover-elevate-up">
                    <div class="card-body p-4">
                      <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2">
                          <div class="symbol symbol-35px symbol-circle bg-light-orange">
                            <i class="ki-duotone ki-profile-circle fs-2 text-orange">
                              <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                          </div>
                          <div>
                            <div class="d-flex align-items-center gap-2">
                              <span class="fw-bold text-gray-900 fs-6">{{ book.guest.name }}</span>
                              <span 
                                v-if="book.is_incognito" 
                                class="badge badge-sm badge-light-danger" 
                                title="Mode Incognito">
                                <i class="ki-duotone ki-eye-slash fs-8">
                                  <span class="path1"></span><span class="path2"></span>
                                  <span class="path3"></span><span class="path4"></span>
                                </i>
                              </span>
                            </div>
                            <span class="text-muted fs-9 fw-semibold">{{ book.midtrans_order_id }}</span>
                          </div>
                        </div>
                        
                        <span class="badge badge-success fs-9 fw-bold px-2 py-1">
                          {{ book.status.toUpperCase() }}
                        </span>
                      </div>

                      <div class="separator separator-dashed mb-3"></div>
                      
                      <div class="d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                          <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="ki-duotone ki-entrance-right fs-5 text-success">
                              <span class="path1"></span><span class="path2"></span>
                            </i>
                            <span class="text-gray-700 fs-8 fw-semibold">Check-in</span>
                          </div>
                          <div class="text-gray-900 fs-7 fw-bold ms-7">{{ formatDate(book.check_in_date) }}</div>
                        </div>
                        
                        <div class="separator separator-vertical h-40px"></div>
                        
                        <div class="flex-grow-1">
                          <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="ki-duotone ki-entrance-left fs-5 text-danger">
                              <span class="path1"></span><span class="path2"></span>
                            </i>
                            <span class="text-gray-700 fs-8 fw-semibold">Check-out</span>
                          </div>
                          <div class="text-gray-900 fs-7 fw-bold ms-7">{{ formatDate(book.check_out_date) }}</div>
                        </div>
                      </div>

                      <div class="mt-3 pt-3 border-top border-gray-200 border-dashed">
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="text-muted fs-8 fw-semibold">Durasi Menginap</span>
                          <span class="badge badge-light-primary fs-8 fw-bold px-3 py-1">
                            {{ calculateDuration(book.check_in_date, book.check_out_date) }} Malam
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </transition-group>
              </div>

            </div>
          </div>
        </div>
        
        <div class="modal-footer border-0 bg-light-subtle py-4">
          <button 
            type="button" 
            class="btn btn-light-orange fw-bold hover-scale px-6" 
            data-bs-dismiss="modal">
            <i class="ki-duotone ki-cross-circle fs-3 me-2">
              <span class="path1"></span><span class="path2"></span>
            </i>
            Tutup
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from "bootstrap";
import ApiService from "@/core/services/ApiService"; 
import moment from "moment";
import 'moment/locale/id';

export default {
    name: "RoomScheduleModal",
    data() {
        return {
            modalInstance: null,
            roomData: null,
            loading: false,
            bookings: [], 
            currentDate: new Date(),
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            refreshInterval: null, // ✅ TAMBAHAN: Auto refresh
        };
    },
    computed: {
        currentYear() { return this.currentDate.getFullYear(); },
        currentMonth() { return this.currentDate.getMonth(); },
        currentMonthName() { return moment(this.currentDate).format('MMMM'); },
        daysInMonth() { return new Date(this.currentYear, this.currentMonth + 1, 0).getDate(); },
        paddingDays() { return new Date(this.currentYear, this.currentMonth, 1).getDay(); },
        upcomingBookings() {
            const today = moment().startOf('day');
            return this.bookings
                .filter(b => moment(b.check_out_date).isAfter(today))
                .sort((a, b) => new Date(a.check_in_date) - new Date(b.check_in_date));
        },
        getRoomType() {
            if (!this.roomData) return 'Tipe Kamar';
            
            if (this.roomData.type && typeof this.roomData.type === 'object' && this.roomData.type.name) {
                return this.roomData.type.name;
            }
            
            if (this.roomData.type && typeof this.roomData.type === 'string') {
                return this.roomData.type;
            }
            
            return 'Tipe Kamar';
        }
    },
    methods: {
        openModal(room) {
            this.roomData = room;
            this.currentDate = new Date();
            
            this.$nextTick(() => {
                if (!this.modalInstance) {
                    const el = this.$refs.modalRef;
                    if(el) {
                        this.modalInstance = new Modal(el);
                        
                        // ✅ TAMBAHAN: Setup event listener untuk auto refresh saat modal ditutup
                        el.addEventListener('hidden.bs.modal', () => {
                            this.stopAutoRefresh();
                        });
                    }
                }
                if(this.modalInstance) {
                    this.modalInstance.show();
                    this.fetchSchedule();
                    this.startAutoRefresh(); // ✅ Mulai auto refresh
                }
            });
        },
        
        // ✅ TAMBAHAN: Auto refresh setiap 5 detik
        startAutoRefresh() {
            this.stopAutoRefresh(); // Clear existing interval
            this.refreshInterval = setInterval(() => {
                console.log('🔄 Auto refresh kalender...');
                this.fetchSchedule();
            }, 5000); // Refresh setiap 5 detik
        },
        
        // ✅ TAMBAHAN: Stop auto refresh
        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        },
        
        // ✅ FIX UTAMA: Hanya tampilkan booking yang VERIFIED & AKTIF
        async fetchSchedule() {
            if(!this.roomData) return;
            
            this.loading = true;
            
            try {
                const startOfMonth = moment(this.currentDate).startOf('month').subtract(7, 'days').format('YYYY-MM-DD');
                const endOfMonth = moment(this.currentDate).endOf('month').add(7, 'days').format('YYYY-MM-DD');

                const { data } = await ApiService.query('/bookings', {
                    room_id: this.roomData.id,
                    date_from: startOfMonth,
                    date_to: endOfMonth
                });
                
                // 🔥 FILTER UTAMA: Hanya tampilkan booking yang TIDAK ditolak & TIDAK dibatalkan
                this.bookings = (data.data || []).filter(booking => {
                    // Exclude booking yang ditolak atau dibatalkan
                    if (booking.verification_status === 'rejected') return false;
                    if (booking.status === 'cancelled') return false;
                    
                    // Hanya tampilkan booking yang sudah lunas atau sedang check-in
                    const validStatuses = ['paid', 'confirmed', 'verified', 'checked_in', 'settlement'];
                    return validStatuses.includes(booking.status);
                });
                
                console.log(`📅 Room ${this.roomData.room_number} Schedule:`, this.bookings.length, 'active bookings');
                console.log('🔍 Raw data:', data.data?.length || 0, 'Filtered:', this.bookings.length);
                
            } catch (error) {   
                console.error("Gagal memuat jadwal:", error);
                this.bookings = [];
            } finally {
                this.loading = false;
            }
        },

        changeMonth(step) {
            this.currentDate = new Date(this.currentYear, this.currentMonth + step, 1);
            this.fetchSchedule();
        },

        isDateBooked(day) {
            const currentDate = moment([this.currentYear, this.currentMonth, day]);
            return this.bookings.some(b => {
                const checkIn = moment(b.check_in_date);
                const checkOut = moment(b.check_out_date);
                return currentDate.isSameOrAfter(checkIn, 'day') && currentDate.isBefore(checkOut, 'day');
            });
        },

        getDayStatusClass(date) {
            const dateObj = new Date(this.currentYear, this.currentMonth, date);
            const dateStr = moment(dateObj).format('YYYY-MM-DD');
            const todayStr = moment().format('YYYY-MM-DD');
            const isPast = moment(dateObj).isBefore(moment(), 'day');

            let classes = [];
            if (dateStr === todayStr) classes.push('today');
            if (this.isDateBooked(date)) classes.push('booked');
            if (isPast && !classes.includes('today')) classes.push('past');

            return classes.join(' ');
        },

        showDateInfo(day) {
            const currentDate = moment([this.currentYear, this.currentMonth, day]);
            const booking = this.bookings.find(b => {
                const checkIn = moment(b.check_in_date);
                const checkOut = moment(b.check_out_date);
                return currentDate.isSameOrAfter(checkIn, 'day') && currentDate.isBefore(checkOut, 'day');
            });

            if (booking) {
                const duration = this.calculateDuration(booking.check_in_date, booking.check_out_date);
                const message = `
                    <div class="text-start">
                        <div class="fw-bold fs-5 mb-2">${booking.guest.name}</div>
                        <div class="text-muted fs-7 mb-3">${booking.midtrans_order_id}</div>
                        <div class="separator separator-dashed mb-3"></div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-gray-600">Check-in:</span>
                            <span class="fw-bold">${this.formatDate(booking.check_in_date)}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-gray-600">Check-out:</span>
                            <span class="fw-bold">${this.formatDate(booking.check_out_date)}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-gray-600">Durasi:</span>
                            <span class="badge badge-light-primary">${duration} Malam</span>
                        </div>
                    </div>
                `;
                
                this.$swal({
                    html: message,
                    icon: 'info',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#F68B1E',
                    customClass: {
                        popup: 'rounded-3',
                        confirmButton: 'btn fw-bold'
                    }
                });
            }
        },

        calculateDuration(checkIn, checkOut) {
            return Math.max(1, moment(checkOut).diff(moment(checkIn), 'days'));
        },

        formatDate(date) {
            return moment(date).format('DD MMM YYYY');
        }
    },
    
    // ✅ TAMBAHAN: Cleanup saat component destroyed
    beforeUnmount() {
        this.stopAutoRefresh();
    }
};
</script>

<style scoped>
.text-orange { color: #F68B1E !important; }
.bg-orange { background-color: #F68B1E !important; }
.bg-light-orange { background-color: #FFF4E6 !important; }
.btn-light-orange { background-color: #FFF4E6; color: #F68B1E; border: none; }
.btn-light-orange:hover { background-color: #FFE5C7; color: #F68B1E; }
.badge-light-orange { background-color: #FFF4E6; color: #F68B1E; }
.bg-gradient-orange { background: linear-gradient(135deg, #F68B1E 0%, #FF6B35 100%); }
.pattern-dots { background-image: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 20px 20px; width: 100%; height: 100%; }

.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; text-align: center; }
.day-name { font-size: 0.75rem; padding: 8px 0; text-transform: uppercase; letter-spacing: 0.5px; }
.calendar-day { aspect-ratio: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; background-color: #F9F9F9; color: #5E6278; position: relative; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 2px solid transparent; }
.calendar-day:not(.empty):hover { background-color: #F1F1F4; transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); }
.calendar-day.empty { background: transparent; cursor: default; }
.calendar-day.past:not(.today) { opacity: 0.4; cursor: not-allowed; }
.calendar-day.today { border-color: #17C653; background-color: rgba(23, 198, 83, 0.1); color: #17C653; font-weight: 700; box-shadow: 0 0 0 3px rgba(23, 198, 83, 0.1); }
.calendar-day.booked { background: linear-gradient(135deg, #FFF4E6 0%, #FFE5C7 100%); color: #F68B1E; font-weight: 700; border-color: #F68B1E; box-shadow: 0 0 0 3px rgba(246, 139, 30, 0.1); }
.calendar-day.booked:hover { background: linear-gradient(135deg, #FFE5C7 0%, #FFD6A8 100%); transform: translateY(-3px) scale(1.05); box-shadow: 0 6px 16px rgba(246, 139, 30, 0.25); }

.day-number { font-size: 0.95rem; margin-bottom: 2px; }
.booked-indicator { position: absolute; bottom: 4px; }
.booked-dot { width: 6px; height: 6px; background-color: #F68B1E; border-radius: 50%; animation: pulse-dot 2s infinite; }
@keyframes pulse-dot { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.3); opacity: 0.7; } }

.legend-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
.booking-card { transition: all 0.3s ease; border-left: 4px solid #F68B1E !important; }
.booking-card:hover { transform: translateX(4px); box-shadow: 0 4px 12px rgba(246, 139, 30, 0.15) !important; }

.hover-scale { transition: transform 0.3s ease; }
.hover-scale:hover { transform: scale(1.05); }
.hover-elevate-up { transition: all 0.3s ease; }
.hover-elevate-up:hover { transform: translateY(-3px); }
.animate-pulse-slow { animation: pulse-slow 3s infinite; }
@keyframes pulse-slow { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.list-fade-enter-active { transition: all 0.4s ease; }
.list-fade-leave-active { transition: all 0.3s ease; position: absolute; }
.list-fade-enter-from { opacity: 0; transform: translateX(-20px); }
.list-fade-leave-to { opacity: 0; transform: translateX(20px); }
.list-fade-move { transition: transform 0.4s ease; }

.scroll-y::-webkit-scrollbar { width: 6px; }
.scroll-y::-webkit-scrollbar-track { background: #F5F8FA; border-radius: 10px; }
.scroll-y::-webkit-scrollbar-thumb { background: linear-gradient(180deg, #F68B1E 0%, #FF6B35 100%); border-radius: 10px; }
.scroll-y::-webkit-scrollbar-thumb:hover { background: #F68B1E; }
</style>