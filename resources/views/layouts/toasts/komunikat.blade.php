<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div ref="komunikatToastowy" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fas fa-info-circle"></i>
            <strong class="me-auto ms-2">Komunikat</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            @{{ tekstKomunikatu }}
        </div>
    </div>
</div>
