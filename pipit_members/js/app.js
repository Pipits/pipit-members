window.addEventListener('load', (event) => {
    const pageUrl = window.location.pathname.replace(/\/+$/, "");


    console.log(Perch.UI.Helpers)
    console.log(`${Perch.path}/addons/apps/perch_members`)
    
    if (pageUrl == `${Perch.path}/addons/apps/perch_members`) {
        let smartbar = document.querySelector('.smartbar ul');

        smartbar.insertAdjacentHTML('beforeend', `
    <li class="smartbar-end smartbar-util">
        <a href="${Perch.path}/addons/apps/pipit_members/export/" title="Download CSV">
            ${Perch.UI.Helpers.icon('ext/o-cloud-download', 14)}
            <span>Download CSV</span>
        </a>
    </li>
    `);
    }
});