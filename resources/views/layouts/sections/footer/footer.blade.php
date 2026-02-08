@php
    $containerFooter =
        isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact'
            ? 'container-xxl'
            : 'container-fluid';
@endphp

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
    <div class="{{ $containerFooter }}">
        <div class="footer-container d-flex align-items-center justify-content-center py-4 flex-md-row flex-column">
            <div class="text-body text-center">
                &#169;
                <script>
                    document.write(new Date().getFullYear());
                </script>
                , made with ❤️ by
                <a href="https://primesafepathsolutions.com/" target="_blank" class="footer-link">PrimeSafepath
                    Solutions</a>
            </div>
        </div>
    </div>
</footer>
<!-- / Footer -->
