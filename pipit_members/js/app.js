window.addEventListener('load', (event) => {
    const pageUrl = window.location.pathname.replace(/\/+$/, "");
    let icon = '';
    try {
        icon = Perch.UI.Helpers.icon('ext/o-cloud-download', 14)
    } catch (e) {
        // could not use Perch.UI.Helpers.icon()
    }

    if (pageUrl == `${Perch.path}/addons/apps/perch_members`) {

        document.querySelector('.smartbar ul').insertAdjacentHTML('beforeend', `
    <li class="smartbar-end smartbar-util">
        <a href="${Perch.path}/addons/apps/pipit_members/export/" title="Download CSV">
            ${icon}<span>Download CSV</span>
        </a>
    </li>
    `);

    }
});