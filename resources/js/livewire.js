import { 
    resetErrorsAndFields,
    removeRowDT,
    reloadDT,
    hideModal ,
    ckeditor,
    reinitializeDataTable
} from "./helpers";

Livewire.on('redirect_to', (event) => {
    const url = event[0].url;
    const delay = event[0].delay;
    setTimeout(() => {
        window.location.href = url;
    }, delay ?? 3000);   
});

Livewire.on('notice', (event) =>  {
    const notice = JSON.parse(JSON.stringify(event))[0];
    Swal.fire({
        icon: "warning",
        title: notice.title,
        html: notice.message,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showCancelButton: true,        
        cancelButtonText: 'Cancel',   
        confirmButtonText: 'Proceed',   
        confirmButtonColor: '#143953', 
        cancelButtonColor: '#d33',      
        reverseButtons: true,  
    }).then((result) => {
        if(result.isConfirmed) {
            Livewire.dispatch('withdraw', [notice.id, true]);
        }
    });  
}) 

Livewire.on('alert', (event) => {

    const alert = JSON.parse(JSON.stringify(event))[0]; 

    if (alert.status === 'success') {


        if (alert.resetFields === true) {
            resetErrorsAndFields();
        }

        $('.modal.show').each(function() {  
            var modalInstance = bootstrap.Modal.getInstance(this);  
            if (modalInstance) {
                modalInstance.hide(); 
            }
        });

        Swal.fire({
            icon: "success",
            title: alert.title,
            html: alert.message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            confirmButtonText: 'GOT IT',
            confirmButtonColor: '#143953',
        }).then((result) => {

            if (result.isConfirmed && alert.isRemoveRowDT) {
                removeRowDT(alert.id);
            }

            if (result.isConfirmed && alert.isReloadDT) {
                reloadDT();
            }

            if (alert.hasOwnProperty('redirect') && alert.redirect == '_reload') {
                return location.reload();
            }

            if (alert.hasOwnProperty('redirect') && alert.redirect == '_stay') {
                return;
            }

            if (alert.hasOwnProperty('redirect') && alert.redirect !== '') {
                location.href = alert.redirect;
            }
        });
        
    } else if(alert.status === 'processing') {
        Swal.fire({
            title: alert.title,
            text: alert.message,
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    } else {
        
        if (alert.showAlert === true) {
            Swal.fire({
                icon: alert.status,
                title: alert.title,
                html: alert.message,
                allowOutsideClick: false,
                allowEscapeKey: false,
                confirmButtonText: 'GOT IT',
                confirmButtonColor: '#143953',
            });

        }     
        
    }

});

Livewire.on("showConfirmation", function (data) {
    let fileInputHtml = "";
    let formData = {};
    let isFileUploadPresent = false;

    if (data[0].plugin && data[0].plugin[0] === "file") {
        const pluginTitle = data[0].plugin["title"] || "";
        const hrElement = pluginTitle.trim() ? '<hr class="my-4">' : "";

        fileInputHtml = `
            ${hrElement}
            <div class="form-group">
                <label for="accomplishment-file">${pluginTitle}</label>
                <input type="file" id="accomplishment-file" class="form-control mt-2 mb-3">
                <a style="font-size: 12px;" href="/templates/forms/HRMS-PD Form 07.docx" class="text-primary text-uppercase mb-3" download>Download Template</a>
                <div id="error-message"></div> <!-- Placeholder for error message -->
            </div>
        `;

        isFileUploadPresent = true;
    }

    function showSwal(errorMessage = "") {
        Swal.fire({
            icon: "info",
            title: data[0].title,
            html: data[0].message + fileInputHtml,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showCancelButton: true,
            cancelButtonText: "Cancel",
            confirmButtonText: "Proceed",
            confirmButtonColor: "#143953",
            cancelButtonColor: "#d33",
            reverseButtons: true,
            didOpen: () => {
                if (errorMessage) {
                    document.getElementById("error-message").innerHTML =
                        `<p style="font-size:11px" class="text-danger mt-2 text-uppercase fw-bold">${errorMessage}</p>`;
                }
            },
        }).then(function (result) {
            const fileInput = document.getElementById("accomplishment-file");
            const selectedFile = fileInput ? fileInput.files[0] : null;

            if (result.isConfirmed) {
                if (isFileUploadPresent && !selectedFile) {
                    showSwal("The accomplishment report file is required."); // Reopen modal with error message
                    return; // Prevent dispatching if validation fails
                }

                if (selectedFile) {
                    // Convert file to Base64 and send it to Livewire
                    const reader = new FileReader();
                    reader.readAsDataURL(selectedFile);
                    reader.onload = () => {
                        formData["report"] = reader.result;
                        formData["filename"] = selectedFile.name;
                        formData["mimeType"] = selectedFile.type;

                        Livewire.dispatch(data[0].action, [formData, data[0]["time"]]);
                    };
                } else {
                    Livewire.dispatch(data[0].action, [false]);
                }
            }
        });
    }

    showSwal(); // Initial call to show the modal
});


Livewire.on('showModal', function(data) {
    var modal = new bootstrap.Modal($('#'+data[0].modal));
    modal.show();

    if (data[0].plugins && data[0].plugins.includes('ckeditor')) {
        ckeditor();
    }

    // Ensure password toggle is applied for modal inputs
    setTimeout(() => window.initPasswordToggles?.(), 0);

});

Livewire.on('hideModal', function(data) {
    var modalElement = document.getElementById(data[0].modal);
    if (modalElement) {
        var modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
    } else {
        console.error('Modal with ID ' + data[0].modal + ' not found.');
    }
});

Livewire.on('showLatest', function() {
    setTimeout(() => {
        $('.modal-body').animate({
            scrollTop: $('.modal-body')[0].scrollHeight * 100
        }, 1); 
    }, 100);
});


Livewire.on('reinitializeSelect', function() {
    $(function() {
        $('.select-2').select2();
        $('.select-2').on('change', function() {
            var field = $(this).attr('id');
            var value = $(this).val(); 
            Livewire.dispatch('populateField', [field, value]);
        });
    });
});

Livewire.on('reinitializeDataTable', function() {
    $(function() {
        reinitializeDataTable();
    });
});

Livewire.on('select2:init', () => {
    $('.select-2').select2();
});

Livewire.on('scrollToError', function(errors) {
    if (errors.length) {
        let errorField = Array.isArray(errors[0]) ? errors[0][0] : errors[0]; 

        let $inputElement = $(`[wire\\:model="${errorField}"]`); 
        if (!$inputElement.length) {
            $inputElement = $(`[wire\\:model\\.live="${errorField}"]`);
        }

        if ($inputElement.length) {
            $('html, body').animate({
                scrollTop: $inputElement.offset().top - 500 
            }, 100);
        }
    }
});

/**
 * Global password visibility toggle
 * - Wraps every <input type="password"> in an input-group with an eye button
 * - Works with dynamically rendered Livewire DOM (MutationObserver)
 */
function initPasswordToggles(root = document) {
    if (!root) return;

    // Inject minimal CSS once
    if (!document.getElementById('pw-toggle-style')) {
        const style = document.createElement('style');
        style.id = 'pw-toggle-style';
        style.textContent = `
            .pw-toggle-btn {
                cursor: pointer;
                user-select: none;
            }
            .pw-toggle-btn:focus {
                box-shadow: none !important;
            }
        `;
        document.head.appendChild(style);
    }

    const inputs = root.querySelectorAll
        ? root.querySelectorAll('input[type="password"]:not([data-pw-toggle-applied])')
        : [];

    inputs.forEach((input) => {
        // Skip if input is inside an input-group already (we still can append button)
        // but only if we applied before.
        input.setAttribute('data-pw-toggle-applied', '1');

        // Ensure an input-group wrapper
        const parent = input.parentElement;
        const isInputGroup = parent && parent.classList.contains('input-group');

        let groupEl = parent;
        if (!isInputGroup) {
            groupEl = document.createElement('div');
            groupEl.className = 'input-group';
            input.parentNode.insertBefore(groupEl, input);
            groupEl.appendChild(input);
        }

        // Add toggle button
        const btnWrap = document.createElement('span');
        btnWrap.className = 'input-group-text pw-toggle-btn';
        btnWrap.setAttribute('role', 'button');
        btnWrap.setAttribute('title', 'Show/Hide password');
        btnWrap.innerHTML = '<i class="fa-solid fa-eye"></i>';

        const toggle = () => {
            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            btnWrap.innerHTML = isPassword
                ? '<i class="fa-solid fa-eye-slash"></i>'
                : '<i class="fa-solid fa-eye"></i>';
        };

        btnWrap.addEventListener('click', toggle);

        // If a toggle already exists (e.g., duplicated Livewire render), don't append again
        const alreadyHasToggle = groupEl.querySelector('.pw-toggle-btn');
        if (!alreadyHasToggle) {
            groupEl.appendChild(btnWrap);
        }
    });
}

window.initPasswordToggles = initPasswordToggles;

// Initial run
document.addEventListener('DOMContentLoaded', () => initPasswordToggles(document));

// Re-run after Livewire navigations
document.addEventListener('livewire:navigated', () => initPasswordToggles(document));

// Observe DOM changes to catch dynamically injected password fields (Livewire, modals, etc.)
if (!window.__pwToggleObserver) {
    window.__pwToggleObserver = new MutationObserver((mutations) => {
        for (const m of mutations) {
            if (m.type !== 'childList' || !m.addedNodes?.length) continue;
            m.addedNodes.forEach((node) => {
                if (node.nodeType !== 1) return; // ELEMENT_NODE
                initPasswordToggles(node);
            });
        }
    });

    window.__pwToggleObserver.observe(document.documentElement, {
        childList: true,
        subtree: true,
    });
}
