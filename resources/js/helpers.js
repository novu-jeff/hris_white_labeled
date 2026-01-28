export function copy_link() {
    $('.copy-link').on('click', function(e) {
        e.preventDefault();
        const link = $(this).data('target');

        const tempInput = document.createElement('input');
        tempInput.value = link;
        document.body.appendChild(tempInput);

        tempInput.select();
        tempInput.setSelectionRange(0, 99999); 
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        Swal.fire({
            icon: "success",
            title: 'Job Link Copied',
            html: 'You can now share the job link you copied!',  
            confirmButtonText: 'GOT IT',   
            confirmButtonColor: '#143953', 
            cancelButtonColor: '#d33',      
            reverseButtons: true,  
        });  
    });
}

export function reinitializeDataTable() {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('.data-tables')) {
        $('.data-tables').DataTable().destroy();
    }
    if ($.fn.DataTable) {
        $('.data-tables').DataTable({
            scrollX: true,
            pageLength: 10 
        });
    } else {
        console.error('DataTable plugin is not loaded.');
    }
}

export function resetErrorsAndFields() {
    $('input[type="text"], input[type="number"], textarea').val('');
    $('select').prop('selectedIndex', 0);
    $('.error-field span').text('');
    $('.ck-content').empty();
}

export function ckeditor(isReadOnly = false) {

    let editEditor;

    // Check if an existing instance of CKEditor exists and destroy it
    const ckeditorElement = document.querySelector('#ckeditor');
    if (ckeditorElement && ckeditorElement.ckeditorInstance) {
        ckeditorElement.ckeditorInstance.destroy()
        .then(() => {
            console.log('Existing CKEditor instance destroyed.');
        })
        .catch(error => {
            console.error('Error destroying the existing editor:', error);
        });
    }

    // Create a new instance of CKEditor
    ClassicEditor
    .create(ckeditorElement, {
        toolbar: ['heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', 'blockQuote'],
        height: '500px'
    })
    .then(editor => {
        editEditor = editor;

        // Store the editor instance on the DOM element for future reference
        ckeditorElement.ckeditorInstance = editor;

        editor.model.document.on('change:data', () => {
            Livewire.dispatch('ckeditor', [editor.getData()]);
        });
        if (isReadOnly) {
            editor.isReadOnly = true;
        }
    })
    .catch(error => {
        console.error('Error creating the CKEditor instance:', error);
    });
}


export function removeRowDT(id) {
    var row = $('tr[data-id="' + id + '"]');
    
    if (row.length) {
        row.remove();
        
        if ($('tr').length === 1) {
            location.reload(); 
        }
    } else {
        console.log('Row not found!');
    }
}


export function reloadDT() {
    location.reload();
}

export function hideModal() {
    // $('body').css('overflow-y', 'scroll');
    $('.modal').removeClass('show').css('display', 'none');
    $('.modal-backdrop').remove(); 
    $('.modal-backdrop').css({
        'position': 'relative',
        'height': '100%'
    });
}

export function formatTime(date) {
    if (!date) return '';  // If no date is provided, return an empty string

    // Check if the date is already in a string format (e.g., "06:58 AM")
    if (typeof date === 'string' && /\d{2}:\d{2} (AM|PM)/.test(date)) {
        return date;  // Return the string as is if it's already in the correct format
    }

    // If it's a Date object, format it
    if (date instanceof Date) {
        return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    // In case the date is neither a string nor a Date object, return an empty string
    return '';
}

export function convertToHoursAndMinutes(mins) {
    const hours = Math.floor(mins / 60);
    const minutes = mins % 60;
    return `${hours} hr ${minutes} min`;
}

export function getGPSCoordinates() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject('Geolocation is not supported by your browser.');
        } else {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const coords = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    resolve(coords);
                },
                (error) => {
                    reject(`Geolocation error: ${error.message}`);
                }
            );
        }
    });
}

export function setupMap(token, center, width = 120, height = 100) {
    const mapContainer = document.getElementById("map");

    if (!mapContainer) {
        console.error("Error: #map container not found in the DOM.");
        return;
    }

    mapContainer.style.width = `${width}px`;
    mapContainer.style.height = `${height}px`;

    mapboxgl.accessToken = token;

    try {
        // Re-initialization safety: remove previous map instance if any.
        if (mapContainer.__mapboxInstance) {
            try { mapContainer.__mapboxInstance.remove(); } catch (_) {}
            mapContainer.__mapboxInstance = null;
        }
        mapContainer.innerHTML = '';

        const map = new mapboxgl.Map({
            container: "map",
            style: "mapbox://styles/mapbox/streets-v12",
            center: center,
            zoom: 14,
            interactive: false,
            preserveDrawingBuffer: true,
        });

        new mapboxgl.Marker().setLngLat(center).addTo(map);

        map.on('load', () => map.resize());
        mapContainer.__mapboxInstance = map;
    } catch (e) {
        console.error("Mapbox failed to initialize:", e);
    }
}