<script src="{{ asset('admin/assets/vendors/js/vendor.bundle.base.js') }}"></script>
    <!-- endinject -->
    
    <!-- Plugin js for this page -->
    <script src="{{ asset('admin/assets/vendors/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('admin/assets/vendors/progressbar.js/progressbar.min.js') }}"></script>
    <script src="{{ asset('admin/assets/vendors/jvectormap/jquery-jvectormap.min.js') }}"></script>
    <script src="{{ asset('admin/assets/vendors/jvectormap/jquery-jvectormap-world-mill-en.js') }}"></script>
    <script src="{{ asset('admin/assets/vendors/owl-carousel-2/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('admin/assets/js/jquery.cookie.js') }}" type="text/javascript"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="{{ asset('admin/assets/js/off-canvas.js') }}"></script>
    <script src="{{ asset('admin/assets/js/hoverable-collapse.js') }}"></script>
    <script src="{{ asset('admin/assets/js/misc.js') }}"></script>
    <script src="{{ asset('admin/assets/js/settings.js') }}"></script>
    <script src="{{ asset('admin/assets/js/todolist.js') }}"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="{{ asset('admin/assets/js/dashboard.js') }}"></script>
    
    <!-- Bootstrap 5 JS Bundle (includes Popper) - Load LAST to override old Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Simple and reliable dropdown functionality -->
    <script>
    // Simple dropdown toggle function
    function toggleUserDropdown() {
        console.log('Toggle function called');
        
        const menu = document.getElementById('userDropdownMenu');
        const toggle = document.getElementById('userDropdown');
        
        if (menu && toggle) {
            console.log('Found elements');
            
            // Check current display state
            const currentDisplay = window.getComputedStyle(menu).display;
            console.log('Current display:', currentDisplay);
            
            if (currentDisplay === 'none') {
                // Force the dropdown to be visible with multiple CSS properties
                menu.style.display = 'block';
                menu.style.visibility = 'visible';
                menu.style.opacity = '1';
                menu.style.transform = 'translateY(0)';
                menu.style.pointerEvents = 'auto';
                menu.classList.add('show');
                
                toggle.setAttribute('aria-expanded', 'true');
                toggle.classList.add('active');
                
                console.log('Dropdown opened with force');
                
                // Debug: Log the actual computed styles
                const computedStyle = window.getComputedStyle(menu);
                console.log('After opening - display:', computedStyle.display);
                console.log('After opening - visibility:', computedStyle.visibility);
                console.log('After opening - position:', computedStyle.position);
                console.log('After opening - z-index:', computedStyle.zIndex);
                console.log('After opening - top:', computedStyle.top);
                console.log('After opening - right:', computedStyle.right);
                
            } else {
                menu.style.display = 'none';
                menu.style.visibility = 'hidden';
                menu.style.opacity = '0';
                menu.classList.remove('show');
                
                toggle.setAttribute('aria-expanded', 'false');
                toggle.classList.remove('active');
                
                console.log('Dropdown closed');
            }
        } else {
            console.log('Elements not found - menu:', menu, 'toggle:', toggle);
        }
        
        return false;
    }
    
    // Document ready handler
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM ready, setting up dropdown...');
        
        const userDropdown = document.getElementById('userDropdown');
        const dropdownMenu = document.getElementById('userDropdownMenu');
        
        if (userDropdown && dropdownMenu) {
            console.log('Elements found in DOM ready');
            
            // Force initial styles
            dropdownMenu.style.display = 'none';
            dropdownMenu.style.visibility = 'hidden';
            dropdownMenu.style.opacity = '0';
            
            // Remove any existing event listeners first
            userDropdown.onclick = null;
            
            // Add click event listener
            userDropdown.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Click event triggered');
                toggleUserDropdown();
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userDropdown.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.style.display = 'none';
                    dropdownMenu.style.visibility = 'hidden';
                    dropdownMenu.style.opacity = '0';
                    dropdownMenu.classList.remove('show');
                    userDropdown.setAttribute('aria-expanded', 'false');
                    userDropdown.classList.remove('active');
                    console.log('Dropdown closed by outside click');
                }
            });
            
            // Prevent dropdown from closing when clicking inside
            dropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            console.log('Dropdown setup complete');
        } else {
            console.log('Elements not found in DOM ready');
        }
    });
    
    // Emergency debug function - call this from console
    window.debugDropdown = function() {
        const menu = document.getElementById('userDropdownMenu');
        if (menu) {
            console.log('=== DROPDOWN DEBUG ===');
            const computed = window.getComputedStyle(menu);
            console.log('Element:', menu);
            console.log('Display:', computed.display);
            console.log('Visibility:', computed.visibility);
            console.log('Opacity:', computed.opacity);
            console.log('Position:', computed.position);
            console.log('Top:', computed.top);
            console.log('Right:', computed.right);
            console.log('Z-index:', computed.zIndex);
            console.log('Width:', computed.width);
            console.log('Height:', computed.height);
            console.log('Overflow:', computed.overflow);
            console.log('Transform:', computed.transform);
            
            // Force show for 5 seconds
            menu.style.display = 'block';
            menu.style.visibility = 'visible';
            menu.style.opacity = '1';
            menu.style.backgroundColor = 'red';
            menu.style.border = '5px solid blue';
            
            console.log('Forced visible with red background and blue border for 5 seconds');
            
            setTimeout(() => {
                menu.style.backgroundColor = 'white';
                menu.style.border = '2px solid #007bff';
            }, 5000);
        }
    };
    </script>