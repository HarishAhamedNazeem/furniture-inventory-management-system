     </div>
    </div>
    <!-- jQuery - use local first to avoid CORB issues -->
    <script src="libs/js/jquery.min.js"></script>
    <script>
        // Fallback for jQuery if local fails
        if (typeof jQuery === 'undefined') {
            document.write('<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"><\/script>');
        }
    </script>
    
    <!-- Bootstrap JS - use local first -->
    <script src="libs/js/bootstrap.min.js"></script>
    <script>
        // Bootstrap availability check with better timing
        $(document).ready(function() {
            // Wait a bit for Bootstrap to load
            setTimeout(function() {
                if (typeof $.fn.modal === 'undefined') {
                    console.log('Bootstrap modal not found, loading CDN fallback...');
                    var script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/3.3.4/js/bootstrap.min.js';
                    script.onload = function() {
                        console.log('CDN Bootstrap loaded successfully');
                        // Trigger custom event to notify other scripts
                        $(document).trigger('bootstrapLoaded');
                    };
                    script.onerror = function() {
                        console.error('Failed to load CDN Bootstrap');
                        // Still trigger event even if failed
                        $(document).trigger('bootstrapLoaded');
                    };
                    document.head.appendChild(script);
                } else {
                    console.log('Bootstrap modal is available');
                    $(document).trigger('bootstrapLoaded');
                }
            }, 200);
        });
    </script>
    
    <!-- Bootstrap Datepicker - use local first -->
    <script src="libs/js/bootstrap-datepicker.min.js"></script>
    <script>
        // Fallback for Datepicker if local fails
        $(document).ready(function() {
            setTimeout(function() {
                if (typeof $.fn.datepicker === 'undefined') {
                    console.log('Datepicker not found, loading CDN fallback...');
                    var script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.min.js';
                    script.onload = function() {
                        console.log('CDN Datepicker loaded successfully');
                    };
                    script.onerror = function() {
                        console.error('Failed to load CDN Datepicker');
                    };
                    document.head.appendChild(script);
                }
            }, 300);
        });
    </script>
    
    <!-- Custom functions -->
    <script type="text/javascript" src="libs/js/functions.js"></script>
    
    <!-- Force login form styling after all scripts load -->
    <script>
        // Force login form field styling to match customer theme
        function forceLoginFormStyling() {
            if (document.body.classList.contains('login-body')) {
                const formControls = document.querySelectorAll('.login-page .form-control, .login-page input[type="text"], .login-page input[type="password"], #username, #password');
                
                // Specifically target username and password fields by ID
                const usernameField = document.getElementById('username');
                const passwordField = document.getElementById('password');
                
                formControls.forEach(function(element) {
                    // Remove any existing white/gray styling first
                    element.style.removeProperty('border-color');
                    element.style.removeProperty('background');
                    element.style.removeProperty('background-color');
                    element.style.removeProperty('box-shadow');
                    element.style.removeProperty('-webkit-box-shadow');
                    element.style.removeProperty('-moz-box-shadow');
                    
                    // Apply brown border and background - MORE BROWN
                    element.style.setProperty('border', '2px solid rgba(101, 67, 33, 0.7)', 'important');
                    element.style.setProperty('background', 'rgba(139, 115, 85, 0.3)', 'important');
                    element.style.setProperty('background-color', 'rgba(139, 115, 85, 0.3)', 'important');
                    element.style.setProperty('box-shadow', '0 2px 8px rgba(0, 0, 0, 0.05)', 'important');
                    element.style.setProperty('-webkit-box-shadow', '0 2px 8px rgba(0, 0, 0, 0.05)', 'important');
                    element.style.setProperty('-moz-box-shadow', '0 2px 8px rgba(0, 0, 0, 0.05)', 'important');
                    element.style.setProperty('color', '#2c1810', 'important');
                    element.style.setProperty('border-radius', '8px', 'important');
                    element.style.setProperty('height', '45px', 'important');
                    element.style.setProperty('padding', '12px 15px', 'important');
                    
                    // Add focus event listener
                    element.addEventListener('focus', function() {
                        this.style.setProperty('border-color', 'rgba(139, 69, 19, 0.7)', 'important');
                        this.style.setProperty('box-shadow', '0 0 0 4px rgba(139, 69, 19, 0.2)', 'important');
                        this.style.setProperty('-webkit-box-shadow', '0 0 0 4px rgba(139, 69, 19, 0.2)', 'important');
                        this.style.setProperty('-moz-box-shadow', '0 0 0 4px rgba(139, 69, 19, 0.2)', 'important');
                        this.style.setProperty('background', 'rgba(139, 115, 85, 0.4)', 'important');
                        this.style.setProperty('background-color', 'rgba(139, 115, 85, 0.4)', 'important');
                        this.style.setProperty('outline', 'none', 'important');
                    });
                    
                    // Add blur event listener
                    element.addEventListener('blur', function() {
                        this.style.setProperty('border-color', 'rgba(101, 67, 33, 0.7)', 'important');
                        this.style.setProperty('box-shadow', '0 2px 8px rgba(0, 0, 0, 0.05)', 'important');
                        this.style.setProperty('-webkit-box-shadow', '0 2px 8px rgba(0, 0, 0, 0.05)', 'important');
                        this.style.setProperty('-moz-box-shadow', '0 2px 8px rgba(0, 0, 0, 0.05)', 'important');
                        this.style.setProperty('background', 'rgba(139, 115, 85, 0.3)', 'important');
                        this.style.setProperty('background-color', 'rgba(139, 115, 85, 0.3)', 'important');
                    });
                });
                
                // Specifically style username and password fields with stronger brown
                if (usernameField) {
                    usernameField.style.setProperty('background', 'rgba(139, 115, 85, 0.4)', 'important');
                    usernameField.style.setProperty('background-color', 'rgba(139, 115, 85, 0.4)', 'important');
                    usernameField.style.setProperty('border', '2px solid rgba(101, 67, 33, 0.8)', 'important');
                    console.log('Username field styled with brown background');
                }
                
                if (passwordField) {
                    passwordField.style.setProperty('background', 'rgba(139, 115, 85, 0.4)', 'important');
                    passwordField.style.setProperty('background-color', 'rgba(139, 115, 85, 0.4)', 'important');
                    passwordField.style.setProperty('border', '2px solid rgba(101, 67, 33, 0.8)', 'important');
                    console.log('Password field styled with brown background');
                }
                
                console.log('Login form styling forced - applied to', formControls.length, 'elements');
            }
        }
        
        // Apply styling immediately and after a delay to ensure it overrides everything
        document.addEventListener('DOMContentLoaded', function() {
            forceLoginFormStyling();
        });
        
        // Also apply after a short delay to override any dynamic styles
        setTimeout(forceLoginFormStyling, 100);
        setTimeout(forceLoginFormStyling, 500);
        setTimeout(forceLoginFormStyling, 1000);
    </script>
  </body>
</html>

<?php if(isset($db)) { $db->db_disconnect(); } ?>
