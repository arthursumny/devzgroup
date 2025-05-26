document.addEventListener('DOMContentLoaded', function() {
    // Scroll class for header
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            document.querySelector('.main-header').classList.add('scrolled');
        } else {
            document.querySelector('.main-header').classList.remove('scrolled');
        }
    });

    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.main-nav ul'); // Target the <ul> directly

    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            // Toggle aria-expanded attribute for accessibility
            const isExpanded = navMenu.classList.contains('active');
            menuToggle.setAttribute('aria-expanded', isExpanded);
        });
    }

    // Mobile dropdown toggle (for "Produtos" and any other dropdowns)
    const dropdowns = document.querySelectorAll('.main-nav .dropdown');

    dropdowns.forEach(dropdown => {
        const dropbtn = dropdown.querySelector('.dropbtn'); // The clickable element for the dropdown
        const dropdownContent = dropdown.querySelector('.dropdown-content');

        if (dropbtn && dropdownContent) {
            dropbtn.addEventListener('click', function(event) {
                // Only prevent default and toggle for mobile viewports
                if (window.innerWidth <= 768) {
                    event.preventDefault(); // Prevent link navigation for '#'

                    // Close other open dropdowns in the mobile menu
                    // This is optional, but good UX if you only want one open at a time
                    dropdowns.forEach(otherDropdown => {
                        if (otherDropdown !== dropdown && otherDropdown.classList.contains('open')) {
                            otherDropdown.classList.remove('open');
                            // const otherContent = otherDropdown.querySelector('.dropdown-content');
                            // if (otherContent) otherContent.style.display = 'none'; // CSS handles this via .open
                        }
                    });
                    
                    // Toggle current dropdown's 'open' class
                    dropdown.classList.toggle('open');
                }
            });
        }
    });
});