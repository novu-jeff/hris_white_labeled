<div class="container">
    <div class="search-jobs">
        <div>
            <div class="content shadow {{$isEmptySearch ? 'error'  : '' }}">
                <div class="search-box">
                    <input type="text" name="search" id="search" class="form-control" wire:model.defer='search_query' placeholder="Job, Title, Keyword" value="{{ is_string($search_query ?? null) ? e($search_query) : '' }}">
                </div>
                <div class="search-submit" class="d-flex">
                    <button class="btn btn-primary px-4 py-2 text-light text-uppercase fw-bold" wire:click='find'>Search 
                        <span class="ms-1">
                            <i class="fa-solid fa-magnifying-glass fa-shake"></i>
                        </span>
                    </button>
                </div>
            </div>
            @if ($isEmptySearch)
                <div class="error-field" style="color: red; text-transform: uppercase; font-size: 11px; font-weight: 600; margin-top: 8px;">Try searching something...</div>
            @endif
        </div>
    </div>
    <div>
        @if ($search_result && $search_term && !$isEmptySearch)
            <div class="searched-query text-muted mt-5" wire:ignore>
                <p class="m-0">You're searching for: <span>{{$search_result['parameter']}}</span></p>
                <p class="m-0">Returned <span>{{$search_result['total']}} result/s</span></p>
            </div>
            <hr class="mt-4">
        @endif
    </div>
    <div class="row mb-3 mt-5">
        <div class="col-md-6 d-flex align-items-center gap-2">
            <label for="entries" class="form-label mb-0">Show entries:</label>
            <select id="entries" wire:model.change="entries" class="form-select w-auto">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="30">30</option>
                <option value="40">40</option>
                <option value="50">50</option>
                <option value="60">60</option>
                <option value="70">70</option>
                <option value="80">80</option>
                <option value="90">90</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>
    
    <div wire:loading class="w-100">
        @include('loading.jobs')
    </div>
    <div wire:loading.remove>
        <div class="jobs-lists">
            <div class="row">
                <div class="col-12 col-md-12 {{$records->count() > 0 ? 'col-lg-5 col-xl-5' : ''}} mb-4">
                    <div class="row">
                        @forelse ($records as $record)
                            @php $recordId = data_get($record, 'id'); $recordSlug = data_get($record, 'slug'); @endphp
                            <div class="col-12 mb-4">
                                <div class="card shadow px-2 {{ $record_info != null && data_get($record_info, 'id') === $recordId ? 'active' : '' }}" wire:click="show_more({{ (int) $recordId }})">
                                    <div class="card-header border-0 bg-transparent">
                                        <div class="position-title">
                                            <h4 class="m-0 text-uppercase">{{ e(data_get($record, 'position')) }}</h4>
                                        </div>
                                        <div class="company-info">
                                            <p class="m-0 text-uppercase">{{ e(data_get($record, 'company_name')) }}</p>
                                            <p class="m-0 text-uppercase">{{ e(data_get($record, 'location')) }}</p>
                                        </div>
                                        <div class="date-posted">
                                            <p class="m-0">
                                                Posted {{ relative_time(data_get($record, 'created_at'), 'hours ago') }}
                                            </p>
                                        </div>
                                        <div class="actions" wire:ignore>
                                            <div class="dropdown">
                                                <button class="btn btn-transparent btn-dropdown d-flex align-items-start justify-content-center" type="button" id="menu-{{ $recordId }}" data-bs-toggle="dropdown" aria-expanded="true">
                                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2" wire:navigate href="{{ route('home.view-job', ['slug' => $recordSlug]) }}">
                                                            <i class="fa-solid fa-eye"></i>
                                                            <span>
                                                                View Info 
                                                            </span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2 copy-link" href="javascript:void(0)" data-target="{{ route('home.view-job', ['slug' => $recordSlug]) }}">
                                                            <i class="fa-solid fa-link"></i>
                                                            <span>
                                                                Copy Link 
                                                            </span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="mx-3">
                                    <div class="card-body pt-1 pb-5">
                                        <div class="perks">
                                            <div>{{ money_format(data_get($record, 'min_salary')) . ' - ' . money_format(data_get($record, 'max_salary')) }} per month</div>
                                            @if(!is_null(data_get($record, 'employment_type_id')))
                                                <div>{{ e(data_get($record, 'employment_type.name')) }}</div>
                                            @endif
                                            <div>{{ e(data_get($record, 'setup')) }}</div>
                                            <div>{{ e(data_get($record, 'slots')) . ' slots' }}</div>
                                        </div>
                                        <div class="description">
                                            <small class="text-muted fst-italic fw-bold text-uppercase text-decoration-underline" style="text-underline-offset: 4px">Description</small>
                                            <div class="description-content mt-2">
                                                {!! see_more(strip_tags(data_get($record, 'description') ?? ''), 400) !!}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="job-info">
                                <div class="choose-first px-5">
                                    <div class="d-flex gap-3">
                                        <div>
                                            <i class="fa-solid fa-arrow-left-long"></i>
                                        </div>
                                        <div>
                                            @if(empty($search_term))
                                                <h4 class="fw-bold">Oops! No jobs were found.</h4>
                                                <p class="mb-0 fs-5">Unfortunately, we couldn't retrieve any data this time. We’re sorry for the inconvenience caused.</p>
                                                <p class="mb-0 fs-5">
                                                    If the issue persists, kindly contact our administrator at <a href="mailto:{{ e(is_string(data_get($provider ?? [], 'email')) ? data_get($provider ?? [], 'email') : '') }}">{{ e(is_string(data_get($provider ?? [], 'email')) ? data_get($provider ?? [], 'email') : '') }}</a>.
                                                    Thank you for letting us know, and we’ll work on resolving the issue as quickly as possible.
                                                </p>
                                            @else
                                                <h4 class="fw-bold">Oops! No jobs were found.</h4>
                                                <p class="mt-3 mb-0 fs-5">What's Happening?</p>
                                                <ul class="mt-3" style="font-size: 15px">
                                                    <li class="mb-2 text-uppercase"><span class="text-uppercase fw-bold">Invalid Keywords:</span> The search terms entered may not match any records in our database.</li>
                                                    <li class="mb-2 text-uppercase"><span class="text-uppercase fw-bold">No Matching Records:</span> The criteria used in your search may not correspond to any available data.</li>
                                                    <li class="mb-2 text-uppercase"><span class="text-uppercase fw-bold">Misspelled Words:</span> Typographical errors in the search query can lead to no results.</li>
                                                    <li class="mb-2 text-uppercase"><span class="text-uppercase fw-bold">Filters Applied:</span> Active filters might narrow down results too much, leaving no matching data.</li>
                                                    <li class="mb-2 text-uppercase"><span class="text-uppercase fw-bold">Outdated Data:</span> The information you’re searching for may no longer be available or relevant.</li>
                                                    <li class="mb-2 text-uppercase"><span class="text-uppercase fw-bold">System Updates:</span> Temporary system updates or data maintenance might impact search results.</li>
                                                </ul>                                        
                                            @endif
                                        </div>
                                    </div>
                                    <div class="banner">
                                        <img src="{{asset('img/404.svg')}}" alt="banner" class="w-100">
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
                @if($records->count() > 0)
                    <div class="col-12 col-md-12 col-lg-7 col-xl-7 mb-4 d-none d-lg-block">
                        <div class="job-info">
                            @if ($record_info)
                                @php $recordInfoId = data_get($record_info, 'id'); @endphp
                                <div class="card px-2" wire:click="show_more({{ (int) $recordInfoId }})">
                                    <div class="card-header border-0 bg-transparent">
                                        <div class="position-title">
                                            <h4 class="m-0 text-uppercase">{{ e(data_get($record_info, 'position')) }}</h4>
                                        </div>
                                        <div class="company-info">
                                            <p class="m-0 text-uppercase">{{ e(data_get($record_info, 'company_name')) }}</p>
                                            <p class="m-0 text-uppercase">{{ e(data_get($record_info, 'location') . ' • ' . str_replace('-', ' ', data_get($record_info, 'setup') ?? '') . ' • ' . str_replace('-', ' ', data_get($record_info, 'type') ?? '')) }}</p>
                                        </div>
                                        <div class="salary">
                                            <p class="m-0 text-uppercase">{{ money_format(data_get($record_info, 'min_salary')) . ' - ' . money_format(data_get($record_info, 'max_salary')) }} per month</p>
                                        </div>
                                        <div class="date-posted">
                                            <p class="m-0">
                                                Posted {{ relative_time(data_get($record_info, 'created_at'), 'hours ago') }}
                                            </p>
                                        </div>
                                        <div class="actions d-flex gap-3 justify-content-start">
                                            @if (!in_array($recordInfoId, $applied_job_ids ?? []))
                                                @if (!in_array($recordInfoId, $saved_job_ids ?? []))
                                                    <button class="btn text-light btn-primary d-flex align-items-center gap-2" wire:click='save_job({{ (int) $recordInfoId }})' href="javascript:void(0)">
                                                        <i class="fa-solid fa-thumbtack"></i>
                                                        <span>
                                                            Save Job
                                                        </span>
                                                    </button>
                                                @else
                                                    <button class="btn text-light btn-primary d-flex align-items-center gap-2" wire:click='save_job({{ (int) $recordInfoId }})' href="javascript:void(0)">
                                                        <i class="fa-solid fa-xmark"></i>
                                                        <span>
                                                            Unsave Job
                                                        </span>
                                                    </button>
                                                @endif
                                                <button class="btn btn-outline-primary" wire:click='apply({{ (int) $recordInfoId }})'>Apply Now</button>
                                            @else
                                                <button class="btn btn-primary">Applied Already</button>
                                            @endif
                                        </div>
                                    </div>
                                    <hr class="mx-3">
                                    <div class="card-body pt-1 pb-5">
                                        <div class="description">
                                            <small class="text-muted fst-italic fw-bold text-uppercase text-decoration-underline" style="text-underline-offset: 4px">Description</small>
                                            <div class="description-content mt-2">
                                                {!! see_more(data_get($record_info, 'description') ?? '', 400) !!}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="choose-first px-5">
                                    <div class="d-flex gap-3">
                                        <div>
                                            <i class="fa-solid fa-arrow-left-long"></i>
                                        </div>
                                        <div>
                                            <h4>Choose a job first</h4>
                                            <p>Displays all informations here</p>
                                        </div>
                                    </div>
                                    <div class="banner">
                                        <img src="{{asset('img/choice.svg')}}" alt="banner">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="mt-4">
            {{ $records->links(data: ['scrollTo' => false]) }}
        </div>
    </div>
</div>

@script
    <script>
        
        $(function() {

            copy_link();

            $wire.on('navigateToSearch', function (event) {
                if (event) {
                    const data = JSON.parse(JSON.stringify(event))[0];
                    history.pushState(null, '', '?search=' + data);
                } 

                $wire.search(true);
            });

        })

    </script>
@endscript