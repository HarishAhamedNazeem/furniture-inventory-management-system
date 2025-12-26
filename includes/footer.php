    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5 class="text-gradient">SWISSWOOD WORKS</h5>
                    <p class="text-muted">Quality wooden furniture for your home and office.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="/InventorySystem_PHP/" class="text-decoration-none">Home</a></li>
                        <li><a href="/InventorySystem_PHP/customer/shop.php" class="text-decoration-none">Shop</a></li>
                        <li><a href="/InventorySystem_PHP/customer/about.php" class="text-decoration-none">About Us</a></li>
                        <li><a href="/InventorySystem_PHP/customer/contact.php" class="text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i> +94 76 132 1604<br>+94 75 361 4324</li>
                        <li><i class="fas fa-envelope me-2"></i> swisswoodworks@gmail.com</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> 300/A
                        <br>Dehipagoda, Muruthagahamula</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 Swisswood Works. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="https://www.facebook.com/share/14NGWUa5oYC/" target="_blank" class="text-decoration-none me-3"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/swiss_woodworks?igsh=YXFmNnhrMGZ1azNy" target="_blank" class="text-decoration-none me-3"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.tiktok.com/@swiss.woodworks?_t=ZS-90akuvM6Qhb&_r=1" target="_blank" class="text-decoration-none me-3"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Custom JavaScript -->
    <script>
        // Enable Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Enable Bootstrap popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl)
        });

        // Add active class to current page in navigation
        $(document).ready(function() {
            var currentPage = window.location.pathname;
            $('.nav-link').each(function() {
                if ($(this).attr('href') === currentPage) {
                    $(this).addClass('active');
                }
            });
        });
    </script>
</body>
</html> 