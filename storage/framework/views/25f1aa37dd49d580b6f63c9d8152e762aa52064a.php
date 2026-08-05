<?php $__env->startSection('title', 'Import a Form'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h4>Import from Word or Excel</h4>
            <p class="text-muted">
                Upload a <code>.docx</code> or <code>.xlsx</code> file. We parse it deterministically first —
                headings become sections, questions become fields, bullet/choice lists become options — and only
                fall back to AI to guess the type of an ambiguous field. You'll get a preview and mapping screen
                to fix anything before it's saved.
            </p>

            <?php if(session('info')): ?>
                <div class="alert alert-info"><?php echo e(session('info')); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div id="import-dropzone" class="border border-dashed rounded p-4 text-center" style="border-style: dashed !important; cursor: pointer;">
                        <input type="file" id="import-file-input" accept=".docx,.xlsx" class="d-none">
                        <p class="mb-1">Click to choose a file, or drag one here</p>
                        <p class="text-muted small mb-0">.docx or .xlsx, up to 10MB</p>
                    </div>

                    <div id="import-progress" class="mt-3 d-none">
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                        </div>
                        <p class="text-muted small mt-2 mb-0" id="import-progress-label">Uploading…</p>
                    </div>

                    <div id="import-error" class="alert alert-danger mt-3 d-none"></div>
                </div>
            </div>

            <div class="mt-4">
                <h6>Sample files to try</h6>
                <p class="text-muted small">
                    Two Excel layouts are supported: a plain header-row sheet, and a structured
                    <code>Label | Type | Required | Options | Help Text</code> sheet. Sample files used to build
                    and test this feature are committed under <code>/samples</code> in the repo.
                </p>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropzone = document.getElementById('import-dropzone');
    const input = document.getElementById('import-file-input');
    const progress = document.getElementById('import-progress');
    const progressLabel = document.getElementById('import-progress-label');
    const errorBox = document.getElementById('import-error');

    dropzone.addEventListener('click', () => input.click());
    dropzone.addEventListener('dragover', (e) => e.preventDefault());
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        if (e.dataTransfer.files.length) {
            handleFile(e.dataTransfer.files[0]);
        }
    });
    input.addEventListener('change', () => {
        if (input.files.length) handleFile(input.files[0]);
    });

    function handleFile(file) {
        errorBox.classList.add('d-none');
        progress.classList.remove('d-none');
        progressLabel.textContent = 'Uploading…';

        const formData = new FormData();
        formData.append('file', file);

        fetch("<?php echo e(route('imports.upload')); ?>", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': "<?php echo e(csrf_token()); ?>",
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(r => r.text().then(text => {
            let body;
            try {
                body = JSON.parse(text);
            } catch (e) {
                // Server returned HTML (a 500 error page, CSRF 419, etc.) instead
                // of JSON — surface something readable instead of a raw parse error.
                throw new Error(
                    r.status === 419
                        ? 'Your session expired — please refresh the page and try again.'
                        : `Server error (${r.status}). Check the Laravel log for details.`
                );
            }
            return { ok: r.ok, body };
        }))
        .then(({ ok, body }) => {
            if (!ok) {
                const message = body.errors
                    ? Object.values(body.errors).flat().join(' ')
                    : (body.message || 'Upload failed.');
                throw new Error(message);
            }
            progressLabel.textContent = 'Parsing your file…';
            poll(body.status_url, body.review_url);
        })
        .catch(err => {
            progress.classList.add('d-none');
            errorBox.textContent = err.message;
            errorBox.classList.remove('d-none');
        });
    }

    function poll(statusUrl, reviewUrl) {
        fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
            .then(r => r.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Server error while checking import status.');
                }

                if (data.status === 'needs_review') {
                    window.location.href = reviewUrl;
                } else if (data.status === 'failed') {
                    progress.classList.add('d-none');
                    errorBox.textContent = data.error || 'Could not parse this file.';
                    errorBox.classList.remove('d-none');
                } else {
                    setTimeout(() => poll(statusUrl, reviewUrl), 1500);
                }
            })
            .catch(err => {
                progress.classList.add('d-none');
                errorBox.textContent = err.message;
                errorBox.classList.remove('d-none');
            });
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.builder', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/anushka/Desktop/Form-Forge/resources/views/forms/import.blade.php ENDPATH**/ ?>