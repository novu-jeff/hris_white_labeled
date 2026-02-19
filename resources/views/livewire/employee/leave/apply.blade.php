<form wire:submit.prevent="save" wire:target="save">
    <div class="row">
        <div class="col-12">
            <div class="card shadow p-4">
                <div class="card-header bg-transparent border-0">
                    <p class="text-muted mb-0 text-uppercase fst-italic">All <span class="text-danger">*</span> is required</p>
                    <ul class="calendar-legend mt-5 text-uppercase fw-bold">
                        <li><span class="legend-color available"></span>Available</li>
                        <li><span class="legend-color selected"></span>Selected</li>
                        <li><span class="legend-color pending"></span>Pending</li>
                        <li><span class="legend-color approved"></span>Approved</li>
                        <li><span class="legend-color holiday"></span>Holiday</li>
                        <li><span class="legend-color unavailable"></span>Unavailable</li>
                    </ul>
                </div>
                <hr class="mx-3">
                <div class="card-body">
                    <div class="row">
                        @if(!is_null($this->type))
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-end">
                                    <h6 class="text-uppercase fw-bold">Remaining Leave Credits: {{$this->remaining_credits}}</h6>
                                </div>
                            </div>
                        @endif
                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="type">Type <span class="text-danger">*</span></label>
                            <select wire:model.live="type" wire:change="handleLeaveCredits" id="type" class="form-select text-uppercase">
                                <option value=""> - CHOOSE - </option>
                                @foreach($leaveTypes as $leave)
                                    <option value="{{$leave->id}}">{{$leave->code . ' - ' . $leave->name}}</option>
                                @endforeach
                            </select>
                            <div class="error-field">
                                @error('type') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        @if(in_array($type, [1,2]))
                            <div class="col-12 col-md-12 mb-4">
                                <label class="mb-2" for="duration">Leave Duration <span class="text-danger">*</span></label>
                                <select wire:model.live="duration"  id="duration" class="form-select text-uppercase">
                                    <option value=""> - CHOOSE - </option>
                                    <option value="wholeday">Whole Day</option>
                                    <option value="halfday_morning">Half Day - Morning</option>
                                    <option value="halfday_afternoon">Half Day - Afternoon</option>
                                </select>
                                <div class="error-field">
                                    @error('duration') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif

                        <div class="col-12 col-md-12 mb-4">
                            <div id="calendar-container" wire:ignore></div>
                            <div class="error-field">
                                @error('selectedDates') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        @if($type == 1)
                            <div class="col-12 col-md-6 mb-4">
                                <label class="mb-2" for="location">Location <span class="text-danger">*</span></label>
                                <select wire:model.live="location" id="location" class="form-select text-uppercase">
                                    <option value=""> - CHOOSE -</option>
                                    <option value="ph">Within Philippines</option>
                                    <option value="abroad">Abroad</option>
                                </select>
                                <div class="error-field">
                                    @error('location') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-4">
                                <label class="mb-2" for="location_specific">Specific Location</label>
                                <input type="text" wire:model="location_specific" id="location_specific" class="form-control">
                                <div class="error-field">
                                    @error('location_specific') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>

                        @elseif($type == 2)
                            <div class="col-12 col-md-6 mb-4">
                                <label class="mb-2" for="confinement">Patient Type <span class="text-danger">*</span></label>
                                <select wire:model="confinement" id="confinement" class="form-select text-uppercase">
                                    <option value=""> - CHOOSE -</option>
                                    <option value="hospital">In Hospital</option>
                                    <option value="out-patient">Out Patient</option>
                                </select>
                                <div class="error-field">
                                    @error('confinement') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-4">
                                <label class="mb-2" for="illness">Illness (Specify)</label>
                                <input type="text" wire:model="illness" id="illness" class="form-control">
                                <div class="error-field">
                                    @error('illness') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @elseif($type == 8)
                            <div class="col-12 {{ $study == '' || $study != 'others' ? 'col-md-12' : 'col-md-4' }} mb-4">
                                <label class="mb-2" for="study">Purpose <span class="text-danger">*</span></label>
                                <select wire:model.live="study" id="study" class="form-select text-uppercase">
                                    <option value=""> - CHOOSE -</option>
                                    <option value="completion_masters">Completion of Master's Degree</option>
                                    <option value="examination">Bar/Board Examination Review</option>
                                    <option value="others">Other Purpose</option>
                                </select>
                                <div class="error-field">
                                    @error('study') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            @if($study == 'others')
                                <div class="col-12 col-md-8 mb-4">
                                    <label class="mb-2" for="study_other_purpose">Other Purpose (Specify) <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="study_other_purpose" id="study_other_purpose" class="form-control">
                                    <div class="error-field">
                                        @error('study_other_purpose') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
                <hr class="mx-3">
                <div class="card-footer bg-transparent border-0 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                        <span wire:loading.remove wire:target="save">Proceed <i class="fa-solid fa-arrow-right ms-2"></i></span>
                        <span wire:loading wire:target="save">Proceeding <i class="fa-solid fa-spinner ms-2 fa-spin"></i></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
$(function () {
    const scheduledDates = @json($scheduledDates);
    const isEdit = @json($isEdit);
    const presetSelectedDates = @json($selectedDates ?? []);
    const currentYear = parseInt('{{$currentYear}}');

    function syncSelectedDatesToLivewire(selectedDates) {
        const calendarEl = document.getElementById('calendar-container');
        const root = calendarEl && calendarEl.closest('[wire\\:id]');
        const wireId = root && root.getAttribute('wire:id');
        if (wireId && typeof Livewire !== 'undefined' && Livewire.find(wireId)) {
            Livewire.find(wireId).call('setSelectedDates', selectedDates);
        } else {
            Livewire.dispatch('setSelectedDates', { dates: selectedDates });
        }
    }

    setTimeout(() => {
        const calendarEl = document.getElementById('calendar-container');
        if (!calendarEl || $(calendarEl).data('calendar-initialized')) return;

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        let selectedDates = isEdit && Array.isArray(presetSelectedDates) ? presetSelectedDates.map(d => d.date || d) : [];

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            selectable: true,
            weekends: false,
            headerToolbar: {
                center: 'title',
            },
            dateClick(info) {
                const ymd = formatToYMD(info.date);
                if (isToggleable(ymd)) {
                    toggleDate(ymd);
                }
            },
            eventClick(info) {
                info.jsEvent.preventDefault();
                const ymd = formatToYMD(info.event.start);
                if (isToggleable(ymd)) {
                    toggleDate(ymd);
                }
            },
            datesSet() {
                renderEvents();
            }
        });

        function formatToYMD(date) {
            const d = new Date(date);
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        }

        function formatToMMDD(date) {
            const d = new Date(date);
            return `${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        }

        function isBlocked(ymd, mmdd) {
            return scheduledDates.some(item =>
                (item.type === 'holiday' && item.date === mmdd) ||
                (item.type === 'leave' && item.date === ymd)
            );
        }

        function isSickLeaveType() {
            // Leave type id 2 = Sick Leave
            const leaveTypeEl = document.getElementById('type');
            return leaveTypeEl && String(leaveTypeEl.value) === '2';
        }

        function isToggleable(ymd) {
            const date = new Date(ymd + 'T12:00:00');
            date.setHours(0, 0, 0, 0);
            const mmdd = formatToMMDD(date);

            const futureOrToday = date.getTime() >= today.getTime();
            const yearLimit = date.getFullYear() <= currentYear;
            const blocked = isBlocked(ymd, mmdd);
            const sickLeave = isSickLeaveType();

            if (!yearLimit) return false;
            // Sick Leave: only today and past dates (not future)
            if (sickLeave && date.getTime() > today.getTime()) return false;
            // Other leave types: only today and future dates
            if (!sickLeave && !futureOrToday) return false;

            if (isEdit) return true;

            return !blocked;
        }

        function toggleDate(ymd) {
            const index = selectedDates.indexOf(ymd);
            if (index !== -1) {
                selectedDates.splice(index, 1);
            } else {
                selectedDates.push(ymd);
            }
            renderEvents();
        }

        function renderEvents() {
            const start = calendar.view.activeStart;
            const end = calendar.view.activeEnd;
            const events = [];

            for (let d = new Date(start); d < end; d.setDate(d.getDate() + 1)) {
                const date = new Date(d);
                const ymd = formatToYMD(date);
                const mmdd = formatToMMDD(date);
                date.setHours(0, 0, 0, 0);
                const sickLeave = isSickLeaveType();

                // Non-sick leave: hide past dates
                if (!sickLeave && date.getTime() < today.getTime()) {
                    continue;
                }

                // Sick leave: hide future dates
                if (sickLeave && date.getTime() > today.getTime()) {
                    continue;
                }

                if (date.getFullYear() > currentYear) {
                    if (date.getFullYear() > currentYear) {
                        events.push({
                            title: 'Unavailable',
                            start: ymd,
                            backgroundColor: '#777',
                            borderColor: '#777',
                            textColor: '#fff',
                            classNames: ['fc-sticky', 'fc-event-title'],
                        });
                    }
                    continue;
                }

                const holiday = scheduledDates.find(e => e.type === 'holiday' && e.date === mmdd);
                const leave = scheduledDates.find(e => e.type === 'leave' && e.date === ymd);
                const isSelected = selectedDates.includes(ymd);
                const isBlockedDate = isBlocked(ymd, mmdd);
                const isPreset = presetSelectedDates.some(d => (d.date || d) === ymd);

                if (holiday) {
                    events.push({
                        title: holiday.name,
                        start: ymd,
                        backgroundColor: '#8A0303',
                        borderColor: '#8A0303',
                        textColor: '#fff',
                        classNames: ['fc-sticky', 'fc-event-title'],
                    });
                } else if (leave && !(isEdit && isPreset)) {
                    const color = leave.status === 'pending' ? '#e67e22' : '#6dbfb8';
                    events.push({
                        title: leave.name,
                        start: ymd,
                        backgroundColor: color,
                        borderColor: color,
                        textColor: '#fff',
                        classNames: ['fc-sticky', 'fc-event-title'],
                    });
                } else {
                    const bg = isSelected ? '#225f8b' : '#175850';
                    events.push({
                        title: isSelected ? 'Selected' : 'Available',
                        start: ymd,
                        backgroundColor: bg,
                        borderColor: bg,
                        textColor: '#fff',
                        classNames: ['fc-sticky', 'fc-event-title'],
                    });
                }
            }

            calendar.removeAllEvents();
            calendar.addEventSource(events);

            syncSelectedDatesToLivewire(selectedDates);
        }

        calendar.render();
        renderEvents();

        // Re-render immediately when leave type changes (no day click needed)
        const leaveTypeEl = document.getElementById('type');
        if (leaveTypeEl) {
            leaveTypeEl.addEventListener('change', () => {
                // Drop selected dates that are no longer valid for the new leave type
                selectedDates = selectedDates.filter(d => isToggleable(d));
                renderEvents();
            });
        }

        $(calendarEl).data('calendar-initialized', true);
    }, 300);
});
</script>

