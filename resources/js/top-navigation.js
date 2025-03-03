document.addEventListener('DOMContentLoaded', () => {
    handleTopbarAndSidebarHover();
    handleScroll();
});

const handleTopbarAndSidebarHover = () => {
    const topbarNav = document.querySelector('.fi-topbar > nav');
    const sidebarHeader = document.querySelector('.fi-sidebar-header');

    const addHoveredClass = () => {
        topbarNav.classList.add('topbar-hovered');
        if (sidebarHeader) {
            sidebarHeader.classList.add('topbar-hovered');
        }
    };

    const removeHoveredClass = () => {
        topbarNav.classList.remove('topbar-hovered');
        if (sidebarHeader) {
            sidebarHeader.classList.remove('topbar-hovered');
        }
    };

    topbarNav.addEventListener('mouseenter', addHoveredClass);
    topbarNav.addEventListener('mouseleave', removeHoveredClass);

    if (sidebarHeader) {
        sidebarHeader.addEventListener('mouseenter', addHoveredClass);
        sidebarHeader.addEventListener('mouseleave', removeHoveredClass);
    }
};

const handleScroll = () => {
    const topbarNav = document.querySelector('.fi-topbar > nav');
    const sidebarHeader = document.querySelector('.fi-sidebar-header');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 0) {
            topbarNav.classList.add('topbar-scrolled');
            if (sidebarHeader) {
                sidebarHeader.classList.add('topbar-scrolled');
            }
        } else {
            topbarNav.classList.remove('topbar-scrolled');
            if (sidebarHeader) {
                sidebarHeader.classList.remove('topbar-scrolled');
            }
        }
    });
};


