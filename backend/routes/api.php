<?php
declare(strict_types=1);

use App\Helpers\Router;
use App\Controllers\AuthController;
use App\Controllers\DoctorController;
use App\Controllers\PatientController;
use App\Controllers\AppointmentController;
use App\Controllers\QueueController;
use App\Controllers\ConsultationController;
use App\Controllers\PrescriptionController;
use App\Controllers\PaymentController;
use App\Controllers\AdminController;
use App\Controllers\TaxonomyController;
use App\Controllers\PostController;

// --------------------------------------------------------------------------
// Auth Routes
// --------------------------------------------------------------------------
Router::post('/api/v1/auth/register', [AuthController::class, 'register']);
Router::post('/api/v1/auth/send-registration-otp', [AuthController::class, 'sendRegistrationOtp']);
Router::post('/api/v1/auth/verify-registration-otp', [AuthController::class, 'verifyRegistrationOtp']);
Router::post('/api/v1/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Router::post('/api/v1/auth/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
Router::post('/api/v1/auth/reset-password', [AuthController::class, 'resetPassword']);
Router::post('/api/v1/auth/login', [AuthController::class, 'login']);
Router::get('/api/v1/auth/me', [AuthController::class, 'me']);
Router::post('/api/v1/auth/logout', [AuthController::class, 'logout']);
Router::post('/api/v1/users/avatar', [AuthController::class, 'uploadAvatar']);
Router::post('/api/v1/patients/avatar', [AuthController::class, 'uploadAvatar']);
Router::post('/api/v1/doctor/avatar', [AuthController::class, 'uploadAvatar']);
Router::post('/api/v1/users/device-token', [AuthController::class, 'saveDeviceToken']);




// --------------------------------------------------------------------------
// Taxonomy & Public Locations & Posts
// --------------------------------------------------------------------------
Router::get('/api/v1/specialties', [TaxonomyController::class, 'getSpecialties']);
Router::get('/api/v1/banners', [TaxonomyController::class, 'getMarketingBanners']);
Router::get('/api/v1/locations/cities', [TaxonomyController::class, 'getCities']);
Router::get('/api/v1/locations/cities/{id}/areas', [TaxonomyController::class, 'getAreas']);
Router::get('/api/v1/posts', [PostController::class, 'getPosts']);
Router::get('/api/v1/posts/{id}', [PostController::class, 'getPost']);

// --------------------------------------------------------------------------
// Doctor Public Discovery
// --------------------------------------------------------------------------
Router::get('/api/v1/doctors', [DoctorController::class, 'search']);
Router::get('/api/v1/doctors/{id}', [DoctorController::class, 'profile']);
Router::get('/api/v1/doctors/{id}/chambers', [DoctorController::class, 'chambers']);
Router::get('/api/v1/doctors/{id}/availability', [DoctorController::class, 'availability']);

// --------------------------------------------------------------------------
// Patient Self-Service & Family
// --------------------------------------------------------------------------
Router::get('/api/v1/patients/profile', [PatientController::class, 'getProfile']);
Router::put('/api/v1/patients/profile', [PatientController::class, 'updateProfile']);
Router::get('/api/v1/patients/family', [PatientController::class, 'getFamily']);
Router::post('/api/v1/patients/family', [PatientController::class, 'addFamily']);
Router::delete('/api/v1/patients/family/{id}', [PatientController::class, 'deleteFamily']);
Router::get('/api/v1/patients/favorites', [PatientController::class, 'getFavorites']);
Router::post('/api/v1/patients/favorites/{id}', [PatientController::class, 'addFavorite']);
Router::delete('/api/v1/patients/favorites/{id}', [PatientController::class, 'removeFavorite']);

// --------------------------------------------------------------------------
// Appointments & Booking
// --------------------------------------------------------------------------
Router::post('/api/v1/appointments/validate', [AppointmentController::class, 'validateBooking']);
Router::post('/api/v1/appointments/book', [AppointmentController::class, 'book']);
Router::get('/api/v1/appointments', [AppointmentController::class, 'list']);
Router::get('/api/v1/appointments/{id}', [AppointmentController::class, 'show']);
Router::post('/api/v1/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);

// --------------------------------------------------------------------------
// Live Queue Engine
// --------------------------------------------------------------------------
Router::get('/api/v1/queue/live/{appointment_id}', [QueueController::class, 'patientLiveQueue']);
Router::post('/api/v1/queue/checkin', [QueueController::class, 'checkIn']);
Router::get('/api/v1/queue/doctor/today', [QueueController::class, 'doctorTodayQueue']);
Router::post('/api/v1/queue/doctor/call-next', [QueueController::class, 'callNext']);
Router::post('/api/v1/queue/doctor/start', [QueueController::class, 'startConsultation']);
Router::post('/api/v1/queue/doctor/complete', [QueueController::class, 'completeConsultation']);
Router::post('/api/v1/queue/doctor/skip', [QueueController::class, 'skipPatient']);
Router::post('/api/v1/queue/doctor/recall-skipped', [QueueController::class, 'recallSkipped']);
Router::post('/api/v1/queue/doctor/swap', [QueueController::class, 'swapQueue']);
Router::post('/api/v1/queue/doctor/no-show', [QueueController::class, 'markNoShow']);
Router::post('/api/v1/queue/doctor/delay', [QueueController::class, 'broadcastDelay']);

// --------------------------------------------------------------------------
// Doctor Chamber & Availability Management
// --------------------------------------------------------------------------
Router::get('/api/v1/doctor/dashboard', [DoctorController::class, 'dashboard']);
Router::get('/api/v1/doctor/profile', [DoctorController::class, 'getProfile']);
Router::put('/api/v1/doctor/profile', [DoctorController::class, 'updateProfile']);
Router::post('/api/v1/doctor/presence', [DoctorController::class, 'updatePresence']);
Router::post('/api/v1/doctor/booking/toggle', [DoctorController::class, 'toggleBooking']);
Router::post('/api/v1/doctor/availability/toggle', [DoctorController::class, 'toggleAvailability']);
Router::post('/api/v1/doctor/capacity/update', [DoctorController::class, 'updateCapacity']);
Router::get('/api/v1/doctor/chambers', [DoctorController::class, 'myChambers']);
Router::post('/api/v1/doctor/chambers', [DoctorController::class, 'createChamber']);
Router::put('/api/v1/doctor/chambers/{id}', [DoctorController::class, 'updateChamber']);
Router::delete('/api/v1/doctor/chambers/{id}', [DoctorController::class, 'deleteChamber']);
Router::get('/api/v1/doctor/schedules', [DoctorController::class, 'mySchedules']);
Router::post('/api/v1/doctor/schedules', [DoctorController::class, 'saveSchedule']);
Router::put('/api/v1/doctor/schedules/{id}', [DoctorController::class, 'updateSchedule']);
Router::delete('/api/v1/doctor/schedules/{id}', [DoctorController::class, 'deleteSchedule']);

// --------------------------------------------------------------------------
// Consultations & Digital Prescriptions
// --------------------------------------------------------------------------
Router::post('/api/v1/consultations', [ConsultationController::class, 'save']);
Router::get('/api/v1/consultations/{appointment_id}', [ConsultationController::class, 'showByAppointment']);
Router::get('/api/v1/prescriptions/{id}/pdf', [PrescriptionController::class, 'streamPdf']);
Router::get('/api/v1/prescriptions/verify/{token}', [PrescriptionController::class, 'verifyPublicQr']);
Router::get('/api/v1/patients/medical-records', [PatientController::class, 'getMedicalRecords']);

// --------------------------------------------------------------------------
// Payments & Promotions
// --------------------------------------------------------------------------
Router::post('/api/v1/payments/initiate', [PaymentController::class, 'initiate']);
Router::post('/api/v1/payments/webhook/{gateway}', [PaymentController::class, 'webhook']);
Router::post('/api/v1/promotions/apply', [PaymentController::class, 'applyPromo']);

// --------------------------------------------------------------------------
// Platform Administration
// --------------------------------------------------------------------------
Router::get('/api/v1/admin/dashboard', [AdminController::class, 'dashboard']);
Router::get('/api/v1/admin/doctors', [AdminController::class, 'listDoctors']);
Router::post('/api/v1/admin/doctors', [AdminController::class, 'createDoctor']);
Router::get('/api/v1/admin/doctors/{id}/details', [AdminController::class, 'getDoctorDetails']);
Router::put('/api/v1/admin/doctors/{id}', [AdminController::class, 'updateDoctorProfile']);
Router::post('/api/v1/admin/doctors/{id}/capacity', [AdminController::class, 'adminUpdateCapacity']);
Router::post('/api/v1/admin/doctors/{id}/delay', [AdminController::class, 'adminBroadcastDelay']);
Router::post('/api/v1/admin/doctors/{id}/chambers', [AdminController::class, 'adminCreateChamber']);
Router::post('/api/v1/admin/doctors/{id}/schedules', [AdminController::class, 'adminSaveSchedule']);
Router::post('/api/v1/admin/doctors/{id}/verify', [AdminController::class, 'verifyDoctor']);
Router::post('/api/v1/admin/doctors/{id}/reject', [AdminController::class, 'rejectDoctor']);
Router::post('/api/v1/admin/doctors/{id}/suspend', [AdminController::class, 'suspendDoctor']);
Router::post('/api/v1/admin/availability/override', [AdminController::class, 'overrideAvailability']);
Router::get('/api/v1/admin/revenue', [AdminController::class, 'revenueReport']);
Router::get('/api/v1/admin/audit-logs', [AdminController::class, 'auditLogs']);
Router::get('/api/v1/admin/settings', [AdminController::class, 'settings']);
Router::put('/api/v1/admin/settings', [AdminController::class, 'updateSetting']);
Router::get('/api/v1/admin/promotions', [AdminController::class, 'listPromotions']);
Router::post('/api/v1/admin/promotions', [AdminController::class, 'createPromotion']);
Router::get('/api/v1/admin/administrators', [AdminController::class, 'listAdministrators']);
Router::post('/api/v1/admin/administrators', [AdminController::class, 'createAdministrator']);
Router::put('/api/v1/admin/administrators/{id}/status', [AdminController::class, 'updateAdministratorStatus']);
Router::get('/api/v1/admin/patients', [AdminController::class, 'listPatients']);
Router::get('/api/v1/admin/patients/{id}', [AdminController::class, 'getPatientDetails']);
Router::put('/api/v1/admin/patients/{id}/status', [AdminController::class, 'updatePatientStatus']);
Router::post('/api/v1/admin/patients/quick-register', [AdminController::class, 'quickRegisterPatient']);
Router::post('/api/v1/admin/appointments/book-for-patient', [AdminController::class, 'adminBookForPatient']);
Router::get('/api/v1/admin/specialties', [TaxonomyController::class, 'getAllSpecialtiesAdmin']);
Router::post('/api/v1/admin/specialties', [TaxonomyController::class, 'createSpecialty']);
Router::put('/api/v1/admin/specialties/{id}', [TaxonomyController::class, 'updateSpecialty']);
Router::delete('/api/v1/admin/specialties/{id}', [TaxonomyController::class, 'deleteSpecialty']);
Router::get('/api/v1/admin/marketing/banners', [TaxonomyController::class, 'getAllBannersAdmin']);
Router::post('/api/v1/admin/marketing/banners', [TaxonomyController::class, 'createBanner']);
Router::put('/api/v1/admin/marketing/banners/{id}', [TaxonomyController::class, 'updateBanner']);
Router::delete('/api/v1/admin/marketing/banners/{id}', [TaxonomyController::class, 'deleteBanner']);

// Admin Posts, Offers & Announcements
Router::get('/api/v1/admin/posts', [PostController::class, 'getAllPostsAdmin']);
Router::post('/api/v1/admin/posts', [PostController::class, 'createPost']);
Router::put('/api/v1/admin/posts/{id}', [PostController::class, 'updatePost']);
Router::delete('/api/v1/admin/posts/{id}', [PostController::class, 'deletePost']);

