<footer class="footer border-top py-2 bg-light">
    <div class="container-xxl d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-medium text-primary">⚡ Advanced Admin Panel</span>
            <span class="vr"></span>
            <span id="liveDateTime" class="text-muted small"></span>
        </div>
        <div>
            <a href="https://t.me/zayro_o" target="_blank" class="text-decoration-none hover-lift">
                <span class="badge bg-primary bg-gradient d-flex align-items-center gap-2 px-3 py-2">
                    <i class="ri-code-s-slash-line"></i>
                    <span>Presented By <strong>Shree Win Pro Admin</strong></span>
                    <i class="ri-external-link-line small"></i>
                </span>
            </a>
        </div>
    </div>
</footer>

<script>
    function updateDateTime() {
        const now = new Date();
        const date = now.toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
        const time = now.toLocaleTimeString('en-IN', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });
        document.getElementById('liveDateTime').textContent = `${date} | ${time}`;
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>

<style>
    .hover-lift:hover {
        transform: translateY(-2px);
        transition: transform 0.2s ease;
    }

    .badge {
        border-radius: 8px !important;
        box-shadow: 0 2px 8px rgba(105, 108, 255, 0.3);
    }
</style>
