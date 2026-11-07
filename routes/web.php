<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| HRMS Modules
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    // Module 1: Citizen Registration & Master Citizen Index
    Route::livewire('citizens', 'pages::citizens.index')->name('citizens.index');
    Route::livewire('citizens/create', 'pages::citizens.form')->name('citizens.create');
    Route::livewire('citizens/{citizen}/edit', 'pages::citizens.form')->name('citizens.edit');

    // Module 2: Triage Assessment
    Route::livewire('triage', 'pages::triage.index')->name('triage.index');
    Route::livewire('triage/create', 'pages::triage.form')->name('triage.create');
    Route::livewire('triage/{triage}/edit', 'pages::triage.form')->name('triage.edit');

    // Module 3: Physician (CPOE)
    Route::livewire('physician', 'pages::physician.index')->name('physician.index');
    Route::livewire('physician/create', 'pages::physician.form')->name('physician.create');
    Route::livewire('physician/{consultation}/edit', 'pages::physician.form')->name('physician.edit');

    // Module 4: Nursing / Ward Management
    Route::livewire('nursing', 'pages::nursing.index')->name('nursing.index');
    Route::livewire('nursing/create', 'pages::nursing.form')->name('nursing.create');
    Route::livewire('nursing/{admission}/edit', 'pages::nursing.form')->name('nursing.edit');

    // Module 5: Laboratory
    Route::livewire('laboratory', 'pages::laboratory.index')->name('laboratory.index');
    Route::livewire('laboratory/create', 'pages::laboratory.form')->name('laboratory.create');
    Route::livewire('laboratory/{order}/edit', 'pages::laboratory.form')->name('laboratory.edit');

    // Module 6: Radiology
    Route::livewire('radiology', 'pages::radiology.index')->name('radiology.index');
    Route::livewire('radiology/create', 'pages::radiology.form')->name('radiology.create');
    Route::livewire('radiology/{order}/edit', 'pages::radiology.form')->name('radiology.edit');

    // Module 7: Medicine Inventory & Dispensing
    Route::livewire('pharmacy', 'pages::pharmacy.index')->name('pharmacy.index');
    Route::livewire('pharmacy/medicine/create', 'pages::pharmacy.medicine')->name('pharmacy.medicine.create');
    Route::livewire('pharmacy/medicine/{medicine}/edit', 'pages::pharmacy.medicine')->name('pharmacy.medicine.edit');
    Route::livewire('pharmacy/receive', 'pages::pharmacy.receive')->name('pharmacy.receive');
    Route::livewire('pharmacy/dispenses', 'pages::pharmacy.dispenses')->name('pharmacy.dispenses');
    Route::livewire('pharmacy/dispenses/create', 'pages::pharmacy.dispense')->name('pharmacy.dispense.create');
    Route::livewire('pharmacy/dispenses/{dispense}', 'pages::pharmacy.dispense')->name('pharmacy.dispense.edit');

    // Module 8: MSWD / Social Services
    Route::livewire('mswd', 'pages::mswd.index')->name('mswd.index');
    Route::livewire('mswd/create', 'pages::mswd.form')->name('mswd.create');
    Route::livewire('mswd/{assistance}/edit', 'pages::mswd.form')->name('mswd.edit');

    // Module 9 (staff side): Appointments from the Citizen Portal
    Route::livewire('appointments', 'pages::appointments.index')->name('appointments.index');
    Route::livewire('appointments/{appointment}/edit', 'pages::appointments.form')->name('appointments.edit');

    // Module 10: System Administration
    Route::livewire('users', 'pages::users.index')->name('users.index');
    Route::livewire('users/create', 'pages::users.form')->name('users.create');
    Route::livewire('users/{user}/edit', 'pages::users.form')->name('users.edit');
    Route::livewire('reference', 'pages::reference.index')->name('reference.index');

    // Module 11: Household Survey (community profiling)
    Route::livewire('household-survey', 'pages::household-survey.index')->name('household-survey.index');
    Route::livewire('household-survey/create', 'pages::household-survey.form')->name('household-survey.create');
    Route::livewire('household-survey/{household}/edit', 'pages::household-survey.form')->name('household-survey.edit');

    // Module 12: BHW Portal (web + mobile)
    Route::livewire('bhw', 'pages::bhw.dashboard')->name('bhw-portal.index');
    Route::livewire('bhw/residents', 'pages::bhw.residents')->name('bhw-portal.residents');
    Route::livewire('bhw/visits', 'pages::bhw.visits')->name('bhw-portal.visits');
    Route::livewire('bhw/visits/create', 'pages::bhw.visit-form')->name('bhw-portal.visit.create');
    Route::livewire('bhw/visits/{visit}/edit', 'pages::bhw.visit-form')->name('bhw-portal.visit.edit');
    Route::livewire('bhw/surveys', 'pages::bhw.surveys')->name('bhw-portal.surveys');
    Route::livewire('bhw/surveys/{survey}/fill', 'pages::bhw.survey-fill')->name('bhw-portal.survey.fill');
    Route::livewire('bhw/verifications', 'pages::bhw.verifications')->name('bhw-portal.verifications');
    Route::livewire('bhw/verify/{citizen}', 'pages::bhw.verify')->name('bhw-portal.verify');

    // Module 13: Executive Dashboards (MHO + Mayor)
    Route::livewire('dashboards', 'pages::dashboards.index')->name('dashboards.index');
    Route::livewire('health-intelligence', 'pages::health-intel.index')->name('health-intel.index');

    // Module 14: Data Import / Export Tools
    Route::livewire('import-export', 'pages::import-export.index')->name('import-export.index');

    // Enhancement: Survey system (staff builder)
    Route::livewire('surveys', 'pages::surveys.index')->name('surveys.index');
    Route::livewire('surveys/create', 'pages::surveys.form')->name('surveys.create');
    Route::livewire('surveys/{survey}/edit', 'pages::surveys.form')->name('surveys.edit');
    Route::livewire('surveys/{survey}/share', 'pages::surveys.share')->name('surveys.share');
    Route::livewire('surveys/{survey}/responses', 'pages::surveys.responses')->name('surveys.responses');
    Route::livewire('surveys/responses/{response}', 'pages::surveys.response-detail')->name('surveys.response.show');
});

/*
|--------------------------------------------------------------------------
| Module 9: Citizen Portal (public)
|--------------------------------------------------------------------------
*/
// Public survey (link / QR)
Route::livewire('s/{slug}', 'pages::survey-public.fill')->name('survey.public');

Route::prefix('portal')->group(function () {
    Route::redirect('/', 'portal/login');
    Route::livewire('login', 'pages::portal.login')->name('portal.login');
    Route::livewire('enroll', 'pages::portal.enroll')->name('portal.enroll');
    Route::livewire('dashboard', 'pages::portal.dashboard')->name('portal.dashboard');
    Route::livewire('appointments/book', 'pages::portal.book')->name('portal.book');
    Route::post('logout', function () {
        \Illuminate\Support\Facades\Auth::guard('citizen')->logout();
        request()->session()->regenerateToken();
        return redirect()->route('portal.login');
    })->name('portal.logout');
});

require __DIR__.'/settings.php';
