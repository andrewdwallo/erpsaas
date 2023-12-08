document.addEventListener('DOMContentLoaded', () => {
    for (const dropdown of document.querySelectorAll('.fi-topbar-dropdown, .fi-topbar-dropdown .fi-dropdown-trigger')) {
        const observer = new MutationObserver((mutations) => {
            for (const { attributeName } of mutations) {
                if (attributeName === 'aria-expanded') {
                    updateBorder(dropdown);
                }
            }
        });

        observer.observe(dropdown, { attributes: true });
    }

    document.querySelectorAll('.fi-topbar-item > a').forEach(item => {
        item.addEventListener('mouseenter', () => updateBorderOnHover(true, item));
        item.addEventListener('mouseleave', () => updateBorderOnHover(false, item));
    });

    insertBgDiv();

    handleTopbarAndSidebarHover();

    handleScroll();
});

const updateBorder = (dropdown) => {
    const activeItem = document.querySelector('.fi-topbar-item-active > *');
    const hoveredItem = dropdown.querySelector('.fi-topbar-item > *');

    if (activeItem && hoveredItem !== activeItem) {
        activeItem.style.borderBottomColor = dropdown.getAttribute('aria-expanded') === 'true' ? 'transparent' : '';
    }
};

const updateBorderOnHover = (isHovered, hoveredItem) => {
    const activeItem = document.querySelector('.fi-topbar-item-active > *');
    if (activeItem && hoveredItem !== activeItem) {
        activeItem.style.borderBottomColor = isHovered ? 'transparent' : '';
    }
};

const insertBgDiv = () => {
    document.querySelectorAll('a.fi-topbar-dropdown-list-item').forEach(anchor => {
        anchor.insertAdjacentHTML('beforeend', '<div class="bg"></div>');
    });
};

const handleTopbarAndSidebarHover = () => {
    const topbarNav = document.querySelector('.fi-topbar > nav');
    const sidebarHeader = document.querySelector('.fi-sidebar-header');

    const addHoveredClass = () => {
        topbarNav.classList.add('topbar-hovered');
        sidebarHeader.classList.add('topbar-hovered');
    };

    const removeHoveredClass = () => {
        topbarNav.classList.remove('topbar-hovered');
        sidebarHeader.classList.remove('topbar-hovered');
    };

    topbarNav.addEventListener('mouseenter', addHoveredClass);
    sidebarHeader.addEventListener('mouseenter', addHoveredClass);
    topbarNav.addEventListener('mouseleave', removeHoveredClass);
    sidebarHeader.addEventListener('mouseleave', removeHoveredClass);
};

const handleScroll = () => {
    const topbarNav = document.querySelector('.fi-topbar > nav');
    const sidebarHeader = document.querySelector('.fi-sidebar-header');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 0) {
            topbarNav.classList.add('topbar-scrolled');
            sidebarHeader.classList.add('topbar-scrolled');
        } else {
            topbarNav.classList.remove('topbar-scrolled');
            sidebarHeader.classList.remove('topbar-scrolled');
        }
    });
}

