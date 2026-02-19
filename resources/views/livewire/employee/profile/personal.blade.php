<form wire:submit.prevent="save" wire:target="save" enctype="multipart/form-data">
    <div class="accordion" id="accordionTabPersonal">
        <div class="accordion-item mb-4">
            <h2 class="accordion-header">
                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-personal" aria-expanded="false" aria-controls="flush-personal">
                    Personal Information
                </button>
            </h2>
            <div id="flush-personal" class="accordion-collapse collapse show">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="profile">Profile Photo</label>
                            <input type="file" wire:model="records.profile" id="profile" class="form-control">
                             <!-- Note for max upload size -->
                            <small class="text-muted d-block mt-1">Maximum file size: 1MB</small>
                            <div class="error-field">
                                @error('records.profile') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Show preview if file selected -->
                            @if(isset($records['profile']) && is_string($records['profile']))
                                <img src="{{ asset('storage/' . $records['profile']) }}" alt="Profile Photo" class="mt-2" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;">
                            @elseif($records['profile'] instanceof \Livewire\TemporaryUploadedFile)
                                <img src="{{ $records['profile']->temporaryUrl() }}" alt="Profile Preview" class="mt-2" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;">
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        
                        <div class="col-12 col-md-3 mb-3">
                            <label class="mb-2" for="lastname">Surname</label>
                            <input type="text" wire:model="records.lastname" id="lastname" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('records.lastname') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="firstname">First Name</label>
                            <input type="text" wire:model="records.firstname" id="firstname" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('records.firstname') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 col-md-3 mb-3">
                            <label class="mb-2" for="middlename">Middle Name</label>
                            <input type="text" wire:model="records.middlename" id="middlename" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('records.middlename') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 col-md-2 mb-3">
                            <label class="mb-2" for="suffix">Suffix</label>
                            <select wire:model="records.suffix" id="suffix" class="form-select text-uppercase">
                                <option value=""> - CHOOSE - </option>
                                <option value="jr">Jr</option>
                                <option value="sr">Sr</option>
                                <option value="I">I</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                                <option value="V">V</option>
                            </select>
                            <div class="error-field">
                                @error('records.suffix') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="birthday">Date of Birth</label>
                            <input type="date" wire:model="records.birthday" id="birthday" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('records.birthday') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="civil_status">Civil Status</label>
                            <select wire:model="records.civil_status" id="civil_status" class="form-select text-uppercase">
                                <option value=""> - CHOOSE - </option>
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="divorced">Divorced</option>
                                <option value="separated">Separated</option>
                                <option value="widowed">Widowed</option>
                                <option value="annulled">Annulled</option>
                            </select>
                            <div class="error-field">
                                @error('records.civil_status') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="sex">Sex</label>
                            <select wire:model="records.sex" id="sex" class="form-select text-uppercase">
                                <option value=""> - CHOOSE - </option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            <div class="error-field">
                                @error('records.sex') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 mb-3">
                            <hr>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="citizenship">Citizenship</label>
                            <select wire:model="records.citizenship" wire:change="select_change('citizenship')"  id="citizenship" class="form-select text-uppercase">
                                <option value=""> - CHOOSE - </option>
                                <option value="filipino">Filipino</option>
                                <option value="dual_citizenship">Dual Citizenship</option>
                            </select>
                            <div class="error-field">
                                @error('records.citizenship') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @if ($isDualCitizenship)
                            <div class="col-12 col-md-4 mb-3">    
                                <label class="mb-2" for="country">Country (Dual Citizenship)</label>
                                    <select wire:model="records.country" id="citizenship_type" class="form-select text-uppercase">
                                        <option value=""> - CHOOSE - </option>
                                        @foreach ($countries as $country)
                                            <option value="{{$country['name']['common']}}">{{$country['name']['common']}}</option>
                                        @endforeach
                                    </select>
                                    <div class="error-field">
                                        @error('records.country') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endif
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="citizenship_type">Citizenship Type</label>
                            <select wire:model="records.citizenship_type" id="citizenship_type" class="form-select text-uppercase">
                                <option value=""> - CHOOSE - </option>
                                <option value="by_birth">By Birth</option>
                                <option value="by_naturalization">By Naturalization</option>
                            </select>
                            <div class="error-field">
                                @error('records.citizenship_type') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="birth_certificate">Birth Certificate - (img/pdf)</label>
                            <input type="file" name="birth_certificate" id="birth_certificate" class="form-control">
                            <div class="error-field">
                                @error('birth_certificate') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @if($isMarried)
                            <div class="col-12 col-md-4 mb-3">
                                <label class="mb-2" for="marriage_certificate">Marriage Certificate - (img/pdf)</label>
                                <input type="file" name="marriage_certificate" id="marriage_certificate" class="form-control">
                                <div class="error-field">
                                    @error('records.marriage_certificate') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="accordion-item mb-4">
            <h2 class="accordion-header">
                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-address" aria-expanded="false" aria-controls="flush-address">
                    Address
                </button>
            </h2>
            <div id="flush-address" class="accordion-collapse collapse show">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="present_address">Residential Address</label>
                            <input type="text" wire:model="records.present_address" id="present_address" class="form-control text-uppercase" placeholder="House / Block / Lot / Street / Subdivision / Village / Barangay">
                            <div class="error-field">
                                @error('records.present_address') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="present_province">State / Province</label>
                            <input type="text" wire:model="records.present_province" id="present_province" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('records.present_province') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="present_city">City / Municipality</label>
                            <input type="text" wire:model="records.present_city" id="present_city" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('records.present_city') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div> 
                        <div class="col-12 mb-3">
                            <hr>
                        </div> 
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="permanent_address">Permanent Address</label>
                            <input type="text" wire:model="records.permanent_address" id="permanent_address" class="form-control text-uppercase" placeholder="House / Block / Lot / Street / Subdivision / Village / Barangay">
                            <div class="error-field">
                                @error('records.permanent_address') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="permanent_province">State / Province</label>
                            <input type="text" wire:model="records.permanent_province" id="permanent_province" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('records.permanent_province') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="permanent_city">City / Municipality</label>
                            <input type="text" wire:model="records.permanent_city" id="permanent_city" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('records.permanent_city') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>    
                    </div>
                </div>
            </div>
        </div>
        <div class="accordion-item mb-4">
            <h2 class="accordion-header">
                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-contact" aria-expanded="false" aria-controls="flush-contact">
                    Contact Information
                </button>
            </h2>
            <div id="flush-contact" class="accordion-collapse collapse show">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="mobile_number">Mobile No.</label>
                            <input type="text" wire:model="records.mobile_number" id="mobile_number" class="form-control text-uppercase" data-mask="mobile">
                            <div class="error-field">
                                @error('records.mobile_number') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="tel_no">Telephone No.</label>
                            <input type="text" wire:model="records.tel_no" id="tel_no" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('records.tel_no') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="email">Personal Email</label>
                            <input type="email" wire:model="records.email" id="email" class="form-control" placeholder="Your personal email address">
                            <div class="error-field">
                                @error('records.email') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>  
                    </div>
                </div>
            </div>
        </div>
        <div class="accordion-item mb-4">
            <h2 class="accordion-header">
                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-appearance" aria-expanded="false" aria-controls="flush-appearance">
                    Appearance
                </button>
            </h2>
            <div id="flush-appearance" class="accordion-collapse collapse show">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="height">Height (cm)</label>
                            <input type="text" wire:model="records.height" id="height" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('records.height') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="weight">Weight (kg)</label>
                            <input type="text" wire:model="records.weight" id="weight" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('records.weight') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="blood_type">Blood Type</label>
                            <input type="text" wire:model="records.blood_type" id="blood_type" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('records.blood_type') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer mt-5 pb-3 d-flex justify-content-end bg-transparent border-0">
            <div class="text-end">
                <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                    <span wire:loading.remove wire:target="save">Save <i class="fa-solid fa-arrow-right ms-2"></i></span>
                    <span wire:loading wire:target="save">Saving <i class="fa-solid fa-spinner ms-2 fa-spin"></i></span>
                </button>
            </div>
        </div>
    </div>
</form>

