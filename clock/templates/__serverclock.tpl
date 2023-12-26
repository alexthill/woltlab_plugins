<script data-relocate="true">
window.addEventListener('DOMContentLoaded', function () {
    const lang = document.documentElement.lang;
    const el = document.createElement('span');
    el.className = 'styleChanger';
    const footer = document.querySelector('footer .boxContainer');
    if (footer) {
        footer.parentNode.insertBefore(el, footer);
        updateTime();
    }
    function updateTime() {
        const date = new Date(TIME_NOW * 1000 + performance.now());
        el.textContent = date.toLocaleDateString(lang) + ' ' + date.toLocaleTimeString(lang);
        window.setTimeout(updateTime, 1000 - (performance.now() % 1000));
    }
});
</script>