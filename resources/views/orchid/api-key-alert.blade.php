<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <div class="d-flex align-items-center">
        <div class="me-3">
            <i class="bs bs-key fs-3"></i>
        </div>
        <div class="flex-grow-1">
            <h5 class="alert-heading mb-1">
                <i class="bs bs-check-circle"></i> API Key Generated Successfully!
            </h5>
            <p class="mb-2">
                <strong>Important:</strong> Copy your API key now. For security reasons, it won't be displayed again.
            </p>
            <div class="input-group">
                <input type="text"
                    class="form-control font-monospace bg-light"
                    value="{{ $generatedKey }}"
                    id="generated-api-key"
                    readonly>
                <button class="btn btn-outline-success"
                    type="button"
                    onclick="copyApiKey()"
                    id="copy-btn">
                    <i class="bs bs-clipboard"></i> Copy
                </button>
            </div>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<script>
    function copyApiKey() {
        const keyInput = document.getElementById('generated-api-key');
        const copyBtn = document.getElementById('copy-btn');

        navigator.clipboard.writeText(keyInput.value).then(() => {
            copyBtn.innerHTML = '<i class="bs bs-check"></i> Copied!';
            copyBtn.classList.remove('btn-outline-success');
            copyBtn.classList.add('btn-success');

            setTimeout(() => {
                copyBtn.innerHTML = '<i class="bs bs-clipboard"></i> Copy';
                copyBtn.classList.remove('btn-success');
                copyBtn.classList.add('btn-outline-success');
            }, 2000);
        });
    }
</script>