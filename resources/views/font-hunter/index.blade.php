@props([
    'url' => null,
    'messages' => null,
    'is_downloadable' => false,
])

<style>
.lw-font-hunter-container{max-width:640px;margin:32px auto;font-family:Arial,sans-serif}
.lw-font-hunter-title{font-size:24px;font-weight:700;text-align:center;margin-bottom:10px;color:#333}
.lw-font-hunter-description{font-size:13px;margin-bottom:24px;color:#ccc;font-style:italic;text-align:center}
.lw-font-hunter-form{display:flex;align-items:center;gap:8px}
.lw-font-hunter-input-wrapper{position:relative;flex:1}
.lw-font-hunter-search-icon{position:absolute;top:50%;left:12px;transform:translateY(-50%);width:20px;height:20px;color:#6b7280}
.lw-font-hunter-input{width:100%;padding:8px 12px 8px 40px;border:1px solid #d1d5db;border-radius:4px;font-size:16px;transition:border-color 0.2s,box-shadow .2s}
.lw-font-hunter-input:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 2px #3b82f680}
.lw-font-hunter-button{padding:8px 24px;border:none;border-radius:4px;font-size:16px;cursor:pointer;transition:background-color .2s}
.lw-font-hunter-button.generate{background-color:#22c55e;color:#fff}
.lw-font-hunter-button.generate:hover{background-color:#16a34a}
.lw-font-hunter-button.download{background-color:#eab308;color:#fff}
.lw-font-hunter-button.download:hover{background-color:#ca8a04}
.lw-font-hunter-messages{margin-top:16px}
.lw-font-hunter-messages .alert{padding:16px;border-left:4px solid;margin-bottom:16px;border-radius:4px}
.lw-font-hunter-messages .alert-success{background-color:#dcfce7;border-color:#22c55e;color:#166534}
.lw-font-hunter-messages .alert-danger{background-color:#fee2e2;border-color:#ef4444;color:#991b1b}
.lw-font-hunter-messages ul{margin:0;padding-left:20px}
.lw-font-hunter-footer{margin-top:24px;text-align:center;color:#6b7280;font-size:14px}
.lw-font-hunter-footer a{color:#3b82f6;text-decoration:none}
.lw-font-hunter-footer a:hover{text-decoration:underline}
@media (max-width: 640px) {
.lw-font-hunter-container{margin:16px}
.lw-font-hunter-form{flex-direction:column;gap:12px}
.lw-font-hunter-button{width:100%}
}
</style>

<div class="lw-font-hunter-container" {{ $attributes }}>
    <h1 class="lw-font-hunter-title">{{ $title ?? "Laravel Widget :: Font Hunter" }}</h1>
    <p class="lw-font-hunter-description">
        {{ $description ?? "This tool helps you to find the fonts used in a website. Just enter the URL of the website and click on 'Generate'." }} 
    </p>

    <form action="{{ route('laravel-widget.font-hunter.generate') }}" method="POST" class="lw-font-hunter-form">
        @csrf
        <div class="lw-font-hunter-input-wrapper">
            <svg class="lw-font-hunter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input 
                type="search" 
                name="url" 
                value="{{ old('url', $url) }}" 
                placeholder="e.g., https://fonts.googleapis.com/css?family=Lato:300,400,400i,700" 
                class="lw-font-hunter-input"
            >
        </div>
        <div class="lw-font-hunter-buttons">
            <button type="submit" class="lw-font-hunter-button generate">
                Generate
            </button>
            <button 
                type="button" 
                class="lw-font-hunter-button download" 
                style="{{ $is_downloadable ? '' : 'display: none;' }}"
                data-route={{ route('laravel-widget.font-hunter.download') }}
                data-zip-path=""
            >
                Download
            </button>
        </div>
    </form>

    <div class="lw-font-hunter-messages">
        @if($messages)
            {!! $messages !!}
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.lw-font-hunter-form');
    const messagesContainer = document.querySelector('.lw-font-hunter-messages');
    const generateButton = form.querySelector('.lw-font-hunter-button.generate');
    const downloadButton = form.querySelector('.lw-font-hunter-button.download');
    
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const url = form.getAttribute('action');
            
            generateButton.disabled = true;
            generateButton.textContent = 'Processing...';
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                // Clear existing messages
                messagesContainer.innerHTML = '';
                
                // Handle response
                if (response.ok && data.status) {
                    // Display success messages
                    if (data.success && data.success.length > 0) {
                        const messageList = data.success.map(msg => `<li>${msg}</li>`).join('');
                        displayMessage(`<h4>${data.message}</h4><ul>${messageList}</ul>`, 'success');
                    } else if (data.message) {
                        displayMessage(data.message, 'success');
                    }
                    
                    // Handle downloadable content
                    if (data.downloadable && downloadButton) {
                        downloadButton.style.display = 'inline-block';
                        downloadButton.setAttribute('data-zip-path', data.download_url);
                    } else if (downloadButton) {
                        downloadButton.style.display = 'none';
                    }
                } else {
                    // Display error messages
                    if (data.errors && data.errors.length > 0) {
                        const errorList = data.errors.map(err => `<li>${err}</li>`).join('');
                        displayMessage(`<h4>${data.message}</h4><ul>${errorList}</ul>`, 'danger');
                    } else if (data.message) {
                        displayMessage(data.message, 'danger');
                    }
                }
            } catch (error) {
                displayMessage('An error occurred while processing your request.', 'danger');
                console.error('Error:', error);
            } finally {
                generateButton.disabled = false;
                generateButton.textContent = 'Generate';
            }
        });
    }

    if (downloadButton) {
        downloadButton.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const zipUrl = `${downloadButton.getAttribute("data-route")}?path=${encodeURIComponent(downloadButton.getAttribute('data-zip-path'))}`;
            downloadButton.disabled = true;
            downloadButton.classList.add('loading');
            downloadButton.textContent = 'Downloading...';
            
            try {
                // Set timeout for the download request
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000); // 30s timeout
                
                const response = await fetch(zipUrl, {
                    method: 'GET',
                    signal: controller.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                clearTimeout(timeoutId);
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    triggerDownload(url);
                    window.URL.revokeObjectURL(url);
                    
                    // Hide the download button
                    downloadButton.style.display = 'none';
                    
                    // Clear messages and show success
                    messagesContainer.innerHTML = '';
                    displayMessage('File downloaded successfully!', 'success');
                    
                    // Notify server to reset state
                    await fetch(zipUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                } else {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to download the file.');
                }
            } catch (error) {
                messagesContainer.innerHTML = '';
                const errorMessage = error.name === 'AbortError' 
                    ? 'Download timed out. Please try again.'
                    : error.message || 'An error occurred while downloading.';
                displayMessage(errorMessage, 'danger');
                console.error('Download Error:', error);
            } finally {
                downloadButton.disabled = false;
                downloadButton.classList.remove('loading');
                downloadButton.textContent = 'Download';
            }
        });
    }
    
    function displayMessage(message, type) {
        if (!messagesContainer) return;
        
        messagesContainer.innerHTML = '';
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = message;
        messagesContainer.appendChild(alertDiv);
    }
    
    function triggerDownload(url) {
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
});
</script>
