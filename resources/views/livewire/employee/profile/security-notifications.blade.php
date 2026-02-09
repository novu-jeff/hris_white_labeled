<div>
    <div class="accordion" id="accordionSecurityNotifications">
        <div class="accordion-item mb-4">
            <h2 class="accordion-header">
                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-security" aria-expanded="true" aria-controls="flush-security">
                    Security
                </button>
            </h2>
            <div id="flush-security" class="accordion-collapse collapse show">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2 d-block">Password</label>
                            <button
                                type="button"
                                class="btn btn-outline-primary px-4 py-3 text-uppercase fw-bold"
                                data-bs-toggle="modal"
                                data-bs-target="#employee-change-password-modal"
                            >
                                Change Password
                            </button>
                            <p class="text-muted mt-2 mb-0" style="font-size: 12px;">
                                Use a strong password (min. 8 characters).
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-item mb-4">
            <h2 class="accordion-header">
                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-notifications" aria-expanded="true" aria-controls="flush-notifications">
                    Notifications
                </button>
            </h2>
            <div id="flush-notifications" class="accordion-collapse collapse show">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-12 col-md-8 mb-3">
                            <label class="mb-2 d-block">Email notifications</label>
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="email_notifications_enabled"
                                    wire:model="email_notifications_enabled"
                                >
                                <label class="form-check-label" for="email_notifications_enabled">
                                    Send me an email when I receive in-app notifications
                                </label>
                            </div>
                            <p class="text-muted mt-2 mb-0" style="font-size: 12px;">
                                You can disable this anytime.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button
                            type="button"
                            class="btn btn-primary px-5 py-3 text-uppercase fw-bold"
                            wire:click="saveNotificationSettings"
                            wire:loading.attr="disabled"
                            wire:target="saveNotificationSettings"
                        >
                            <span wire:loading.remove wire:target="saveNotificationSettings">Save <i class="fa-solid fa-arrow-right ms-2"></i></span>
                            <span wire:loading wire:target="saveNotificationSettings">Saving <i class="fa-solid fa-spinner ms-2 fa-spin"></i></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

