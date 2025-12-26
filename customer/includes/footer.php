    </div>
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Swisswood Works</h5>
                    <p class="mb-3">Premium products and furniture designed for modern living and timeless elegance.</p>
                    <div class="d-flex gap-3">
                        <a href="https://www.facebook.com/share/14NGWUa5oYC/" target="_blank" class="text-decoration-none"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/swiss_woodworks?igsh=YXFmNnhrMGZ1azNy" target="_blank" class="text-decoration-none"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.tiktok.com/@swiss.woodworks?_t=ZS-90akuvM6Qhb&_r=1" target="_blank" class="text-decoration-none"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="shop.php" class="text-decoration-none">Shop</a></li>
                        <li><a href="orders.php" class="text-decoration-none">Orders</a></li>
                        <li><a href="profile.php" class="text-decoration-none">Profile</a></li>
                        <li><a href="support.php" class="text-decoration-none">Support</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6>Customer Service</h6>
                    <ul class="list-unstyled">
                        <li><a href="support.php" class="text-decoration-none">Contact Us</a></li>
                        <li><a href="support.php#faq" class="text-decoration-none">FAQs</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6>Contact Info</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i>300/A
                        <br>Dehipagoda, Muruthagahamula</li>
                        <li><i class="fas fa-phone me-2"></i>+94 76 132 1604<br>+94 75 361 4324</li>
                        <li><i class="fas fa-envelope me-2"></i>swisswoodworks@gmail.com</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4" style="border-color: #374151;">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 Swisswood Works. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl, {
                    autoClose: true,
                    boundary: 'viewport'
                });
            });
            
            console.log('Bootstrap dropdowns initialized:', dropdownList.length);
            
            document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                        menu.classList.remove('show');
                    });
                    
                    
                    var dropdownMenu = this.nextElementSibling;
                    if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                        dropdownMenu.classList.toggle('show');
                    }
                });
            });
            
            
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                        menu.classList.remove('show');
                    });
                }
            });
        });
    </script>
    <script>
        
        function addToCart(productId) {
            $.ajax({
                url: 'ajax/add_to_cart.php',
                method: 'POST',
                data: { product_id: productId },
                success: function(response) {
                    const data = JSON.parse(response);
                    if(data.success) {
                        // Update cart count
                        const cartCount = $('.cart-count');
                        if(cartCount.length) {
                            cartCount.text(data.cart_count);
                        } else {
                            $('.fa-shopping-cart').parent().append('<span class="cart-count">' + data.cart_count + '</span>');
                        }
                        alert('Product added to cart!');
                    } else {
                        alert(data.message);
                    }
                },
                error: function() {
                    alert('Error adding product to cart');
                }
            });
        }

        
        function updateCartQuantity(cartId, quantity) {
            $.ajax({
                url: 'ajax/update_cart.php',
                method: 'POST',
                data: { 
                    cart_id: cartId,
                    quantity: quantity
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if(data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                },
                error: function() {
                    alert('Error updating cart');
                }
            });
        }

        function removeFromCart(cartId) {
            if(confirm('Are you sure you want to remove this item from your cart?')) {
                $.ajax({
                    url: 'ajax/remove_from_cart.php',
                    method: 'POST',
                    data: { cart_id: cartId },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if(data.success) {
                            location.reload();
                        } else {
                            alert(data.message);
                        }
                    },
                    error: function() {
                        alert('Error removing item from cart');
                    }
                });
            }
        }
    </script>
</body>
</html> 